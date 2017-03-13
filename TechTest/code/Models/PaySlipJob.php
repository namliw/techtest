<?php

/**
 * Created by PhpStorm.
 * User: wcorc
 * Date: 11/03/2017
 * Time: 1:47 PM
 */
class PaySlipJob extends DataObject
{
    private static $better_buttons_actions = array(
        'doQueueJob', 'doCreateSourceFile'
    );

    const STATUS_NEW = 'New';
    const STATUS_QUEUED = 'Queued';
    const STATUS_WORKING = 'Processing';
    const STATUS_DONE = 'Done';
    const STATUS_FAILED = 'Error';
    public static $db = array(
        'Status' => 'Enum("New,Queued,Processing,Done,Error")'
    );

    public static $has_one = array(
        'FinancialYear' => 'FinancialYear',
        'SourceFile' => 'File',
        'OutputFile' => 'File'
    );

    private static $summary_fields = array(
        "ID" => 'Job ID',
        'Status' => 'Status',
        'SourceFile.Link' => 'Input File',
        'OutputFile.Link' => 'Invoice File',
    );

    public function getCMSValidator()
    {
        return new RequiredFields(
            array(
                'FinancialYear'
            )
        );
    }

    public function canDelete($member = null)
    {
        if ($this->isValidEditingStatus()) {
            return parent::canDelete($member);
        }
        //job should not be deleted after it has been processed
        return false;
    }

    public function canEdit($member = null)
    {
        //IF it has been scheduled for processing or is in process editing should be disabled
        if ($this->isValidEditingStatus()) {
            //If it has a valid status handle permissions normally
            return parent::canEdit($member);
        }
        return false;
    }

    /**
     * Determines if the current job is in a state where editing is a safe thing to do
     * @return bool
     */
    public function isValidEditingStatus()
    {
        return ($this->ID == 0 || $this->Status == self::STATUS_NEW || $this->Status == self::STATUS_FAILED);
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->replaceField('Status', $fields->fieldByName('Root.Main.Status')->performReadonlyTransformation());

        if (!$this->isValidEditingStatus()) {
            $inputFile = SiteTreeURLSegmentField::create("SourceFile", $this->fieldLabel('SourceFile'))
                ->setURLPrefix($this->SourceFile()->Link())
                ->setURLSuffix('');
            $fields->replaceField('SourceFile', $inputFile);
        }

        $inputFile = $fields->fieldByName('Root.Main.OutputFile')->performReadonlyTransformation();
        $inputFile->setDescription('Once the job is completed a link will appear to download the invoice file.');
        if ($this->OutputFile()->ID != 0) {
            $inputFile = SiteTreeURLSegmentField::create("OutputFile", 'Invoice File Link')
                ->setURLPrefix($this->OutputFile()->Link())
                ->setURLSuffix('');
        }

        $fields->replaceField('OutputFile', $inputFile);
        return $fields;
    }

    public function getBetterButtonsActions()
    {
        $fields = parent::getBetterButtonsActions();
        if ($this->ID && $this->Status == self::STATUS_NEW) {
            if ($this->SourceFileID == 0) {
                $fields->push(BetterButtonCustomAction::create('doCreateSourceFile', 'Generate Test Source File')
                    ->setRedirectType(BetterButtonCustomAction::REFRESH));
            } else {

                $fields->push(BetterButtonCustomAction::create('doQueueJob', 'Generate Invoice File')
                    ->setRedirectType(BetterButtonCustomAction::REFRESH));

            }
        }
        //TODO: add cancel processing

        return $fields;
    }

    /**
     * creates a sample file with 500 records for procesing
     */
    public function doCreateSourceFile()
    {
        $exporter = new CsvExporter($this->ID);
        //Write Header in temp file
        $exporter->storeRecordInTempFile(array(
            'first name',
            'last name',
            'annual salary',
            'super rate (%)',
            'payment start date'
        ));

        $faker = Faker\Factory::create();

        for ($i = 0; $i < 500; $i++) {
            $temp = array();
            $temp[] = $faker->firstName;
            $temp[] = $faker->lastName;
            //TODO: make this configurable
            $temp[] = $faker->randomFloat(2, 10000, 250000);//generate salaries between 10k and 250k a year
            $temp[] = $faker->randomFloat(2, 9, 50) . '%';//Create a super rate between 9-50%
            $m = $faker->monthName;
            $l = strtotime('last day of ' . $m);
            $temp[] = '01 ' . $m . ' - ' . date('d M', $l);

            $exporter->storeRecordInTempFile($temp);
            unset($temp);
        }

        $name = 'dummy-source-' . date('Y-m-d') . '-Job-' . $this->ID . '.csv';
        $filename = '/assets/' . $name;
        $savePath = Director::baseFolder() . $filename;
        $newFile = $exporter->preserveTempFile($savePath);
        if ($newFile) {
            $file = new File();
            $file->Filename = $filename;
            $file->Name = $name;
            $file->Title = $name;
            $file->write();
            $this->SourceFileID = $file->ID;
            $this->write();
            return 'File generated Successfully';
        }

        return 'Failed to generate test file';
    }

    /**
     * Adds the current job to the work queue
     * @return string
     */
    public function doQueueJob()
    {
        try {
            //TODO: ideally we would use transactions here.
            ProcessInvoiceGeneration::queueInvoiceProcessingJob($this->ID);
            $this->Status = self::STATUS_QUEUED;
            $this->write();
            return 'Job has been queued for processing';
        } catch (Exception $e) {
            error_log($e->getTraceAsString());
            return 'An error has occurred while queueing this job.';
        }
    }

    public function markAsDone($newFileID)
    {
        $this->OutputFileID = $newFileID;
        $this->markStatus(self::STATUS_DONE);
    }

    private function markStatus($status)
    {
        $this->Status = $status;
        $this->write();
    }

    public function markAsFailed()
    {
        $this->markStatus(self::STATUS_FAILED);
        //TODO: add notification for admins
    }

    public function markAsInProgress()
    {
        $this->markStatus(self::STATUS_WORKING);
    }

}