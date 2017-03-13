<?php

/**
 * Created by PhpStorm.
 * User: wcorc
 * Date: 11/03/2017
 * Time: 3:07 PM
 */
class ProcessInvoiceGeneration extends AbstractQueuedJob
{
    //This handles how many times we should retry this job before we mark it as failed
    const JOB_RETRIES = 3;

    protected $jobObject = null;
    protected $taxBrackets = null;
    protected $csvExporter = null;
    protected $saveLocation = '/assets';

    //Restrict the values that can be set for this job
    private static $allowedValues = array(
        'jobID'
    );

    public function __construct($params = array())
    {
        if (!empty($params) && is_array($params)) {
            $allParams = self::$allowedValues;
            foreach ($allParams as $par) {
                if (isset($params[$par])) {
                    $value = $params[$par];
                    $this->$par = $value;
                }
            }
        }
    }

    /**
     * Return a signature for this queued job
     * The signature has to be unique so 2 jobs handling the same data can't be created
     *
     * @return string
     */
    public function getSignature()
    {
        return md5(get_class($this) . serialize($this->jobData) . $this->getTriesCounter());
    }

    public function getTitle()
    {
        return 'Process Invoice Jobs';
    }

    public function failJob($message)
    {
        error_log($message);
        $this->addMessage($message, 'ERROR');
        throw new Exception($message);
    }

    public function process()
    {
        if ($this->getTriesCounter() > self::JOB_RETRIES) {
            $this->failJob('Max retries for this job reached.');
        }

        if (!$this->hasValidJobData()) {
            $this->failJob('Invalid job data. ' . $this->jobID);
        }

        try {
            $jobObj = $this->getJobObj();
            $jobObj->markAsInProgress();
            $filePath = $jobObj->SourceFile()->getFullPath();
            $importer = new CsvImporter($filePath, false, ',');

            $this->writeOutputHeaders();
            //lets be memory friendly
            //import only 200 records at the time
            while ($data = $importer->get(200)) {
                $invoicedRecords = $this->handleRecords($data);
                $this->writeRecordsToFile($invoicedRecords);
                //make sure we free some memory
                unset($invoicedRecords);
                unset($data);
            }

            $fileID = $this->consolidateInformation();
            if ($fileID === false) {
                $this->failJob('Error while preserving invoice file for job: ' . $this->jobID);
            }
            $jobObj->markAsDone($fileID);

        } catch (Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            $this->addMessage('Error while processing job ' . $e->getMessage());
            return false;
        }
        $this->isComplete = true;
        return true;
    }

    /**
     * Writes the headers for the invoice file
     */
    private function writeOutputHeaders(){
        $this->writeRecordsToFile(array(array(
            'name',
            'pay period',
            'gross income',
            'income tax',
            'net income',
            'super'
        )));
    }

    /**
     * Temporarily preserves in a file the records processed
     * @param $records
     */
    private function writeRecordsToFile($records)
    {
        if (!isset($this->csvExporter)) {
            $this->csvExporter = new CsvExporter($this->jobID);
        }
        $this->csvExporter->storeRecordsInTempFile($records);
    }

    /*
     * Work row by row calculating the required amounts
     * skips any row with invalid data
     */
    private function handleRecords($records)
    {
        $processed = array();
        $taxBrackets = $this->getTaxBrackets();
        foreach ($records as $record) {
            if (!$this->isValidRecord($record)) {
                $record = implode(',', $record);
                error_log('Invalid Record ' . $record);
                $this->addMessage('Skipping invalid record' . $record);
                continue;
            }
            $invoice = $this->applyCalculations($record, $taxBrackets);
            $processed[] = $invoice;
        }
        return $processed;
    }

    /**
     * validates the information for a single row on the CSV
     * @param $record
     * @return bool
     */
    private function isValidRecord($record)
    {
        return TaxCalculations::isValidRecord($record);
    }

    /**
     * generates the record to write on the invoice file
     * @param $record
     * @param $grossincome
     * @param $incometax
     * @param $netincome
     * @param $super
     * @return array
     */
    private function createOuputRecord($record, $grossincome, $incometax, $netincome, $super)
    {
        return TaxCalculations::createOuputRecord($record, $grossincome, $incometax, $netincome, $super);
    }

    /**
     * Calculate the gross income, income tax, net income and super contributions
     * @param $record
     * @param $taxBrackets
     * @return array
     */
    private function applyCalculations($record, $taxBrackets)
    {
        $results = TaxCalculations::applyCalculations($record, $taxBrackets);
        list($grossincome, $incometax, $netincome, $super) = $results;
        return $this->createOuputRecord($record, $grossincome, $incometax, $netincome, $super);
    }

    //I though modeling each row as a model was overkill
    private function getInfoFromRecord($record, $property)
    {
        return TaxCalculations::getInfoFromRecord($record, $property);
    }

    private function getJobObj()
    {
        return $this->jobObject;
    }

    private function getTaxBrackets()
    {
        return $this->taxBrackets;
    }

    /**
     * Validate that the information on the Job Object is valid
     * @return bool
     */
    public function hasValidJobData()
    {
        try {
            if (!is_numeric($this->jobID) || $this->jobID < 0) {
                return false;
            }
            $jobObj = PaySlipJob::get_by_id('PaySlipJob', $this->jobID);
            if (!$jobObj) {
                return false;
            }

            $this->jobObject = $jobObj;
            $this->taxBrackets = $jobObj->FinancialYear()->TaxBrackets();

            return true;
        } catch (Exception $e) {
            error_log($e->getTraceAsString());
        }

        return false;
    }

    /**
     * Retries job
     */
    public function retryJob()
    {
        if ($this->getTriesCounter() > self::JOB_RETRIES) {
            $this->addMessage('Max retries for this job reached.', 'ERROR');
            $this->failJob('Max retries for this job reached.');
        }

        $this->increaseTries();
        $job = new ProcessInvoiceGeneration((array)$this->jobData);
        //Retry this job at a later time in hopes the issue has being fixed
        singleton('QueuedJobService')->queueJob($job, JOB_DELAY);
    }

    public function getTriesCounter()
    {
        if (!isset($this->Try)) {
            $this->Try = 1;
        }
        return $this->Try;
    }

    public function increaseTries()
    {
        $tries = $this->getTriesCounter();
        $this->Try = $tries + 1;
    }

    public static function queueInvoiceProcessingJob($jobID)
    {
        $j = new ProcessInvoiceGeneration(array('jobID' => $jobID));
        singleton('QueuedJobService')->queueJob($j);//process right away
    }

    /**
     * All information is stored temporarily on a file until processing is finished
     * this functions saves the information permanently on the correct folder and updates the
     * job record as finished
     */
    public function consolidateInformation()
    {
        $name = 'invoice-' . date('Y-m-d') . '-Job-' . $this->jobID.'.csv';
        $filename = $this->saveLocation . DIRECTORY_SEPARATOR .
            $name;
        $savePath = Director::baseFolder() . $filename;
        $newFile = $this->csvExporter->preserveTempFile($savePath);
        if ($newFile) {
            $file = new File();
            $file->Filename = $filename;
            $file->Name = $name;
            $file->Title = $name;
            $file->OwnerID = $this->getJobObj()->SourceFile()->OwnerID;
            $file->write();
            return $file->ID;
        }
        return false;
    }

}