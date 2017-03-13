<?php

/**
 * Created by PhpStorm.
 * User: wcorc
 * Date: 12/03/2017
 * Time: 1:50 PM
 */
class CsvExporter
{
    //make sure we place the file somewhere it would be deleted in case of failure
    //so it doesnt lurk on the drive eating space
    private $tempPath = '/tmp';

    private $fp;

    public function __construct($jobID)
    {
        $file_name = $this->tempPath . DIRECTORY_SEPARATOR . date('Y-m-d') . '-' . $jobID . '.csv';
        $this->tempPath = $file_name;
        $this->fp = fopen($file_name, "w");
    }

    /**
     * Saves an array of records into the temporary file
     */
    public function storeRecordsInTempFile($records)
    {
        foreach ($records as $record) {
            $this->storeRecordInTempFile($record);
        }
    }

    /**
     * Saves a single record into the files
     * @param $record records in arrray format
     */
    public function storeRecordInTempFile($record){
        fputcsv($this->fp, $record);
    }

    //close the handler if it hasnt been close yet
    function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
    }

    /**
     * preserver the information on the temporary file
     * @param $savePath
     */
    public function preserveTempFile($savePath)
    {
        fclose($this->fp);
        $this->fp = null;
        //should we move rather than copy?
        //copy allows us to recover some data...
        $success = copy($this->tempPath, $savePath);
        if ($success) {
            //Delete temp file if all goes well
            unlink($this->tempPath);
            return true;
        }
        return false;
    }


}