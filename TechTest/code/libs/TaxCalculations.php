<?php

/**
 * Created by PhpStorm.
 * User: wcorc
 * Date: 12/03/2017
 * Time: 2:02 PM
 */
class TaxCalculations
{
    //I though modeling each row as a model was overkill
    //This is a simple function to access the information on a single row in a readable manner
    public static function getInfoFromRecord($record, $property)
    {
        $info = false;
        switch ($property) {
            case 'Name':
                $info = $record[0];
                break;
            case 'LastName':
                $info = $record[1];
                break;
            case 'AnnualSalary':
                $info = (float)$record[2];
                break;
            case 'SuperRate':
                $info = (float)str_replace('%', '', $record[3]);//Remove percentage if present
                $info = $info / 100;
                break;
            case 'PaymentStartDate':
                $info = $record[4];
                break;
        }

        return $info;
    }

    /**
     * Calculate the gross income, income tax, net income and super contributions
     * @param $record
     * @param $taxBrackets
     * @return array
     */
    public static function applyCalculations($record, $taxBrackets)
    {
        $annSalary = self::getInfoFromRecord($record, 'AnnualSalary');
        $grossincome = $annSalary / 12;
        $incometax = 0;
        //Assume brackets come in order
        foreach ($taxBrackets as $bracket) {
            //Stop processing if we find the correct bracket
            if ($bracket->isSalaryInBracketRange($annSalary)) {
                $incometax = $bracket->getTaxCharge($annSalary);
                break;
            }
        }

        $grossincome = round($grossincome, 0);//make sure we dont get decimals
        $incometax = round($incometax, 0);//make sure we dont get decimals

        $netincome = $grossincome - $incometax;
        $super = $grossincome * self::getInfoFromRecord($record, 'SuperRate');

        $netincome = round($netincome, 0);
        $super = round($super, 0);


        return array($grossincome, $incometax, $netincome, $super);

    }

    /**
     * Converst the input record into a valid invoiced record for the ouput CSV
     * @param $record
     * @param $grossincome
     * @param $incometax
     * @param $netincome
     * @param $super
     * @return array
     */
    public static function createOuputRecord($record, $grossincome, $incometax, $netincome, $super)
    {
        $outout = array();
        $outout[0] = self::getInfoFromRecord($record, 'Name') . ' ' . self::getInfoFromRecord($record, 'LastName');
        $outout[1] = self::getInfoFromRecord($record, 'PaymentStartDate');
        $outout[2] = $grossincome;
        $outout[3] = $incometax;
        $outout[4] = $netincome;
        $outout[5] = $super;
        return $outout;
    }

    /**
     * attempts to validate that the information for a single row is valid
     * @param $record
     * @return bool
     */
    public static function isValidRecord($record)
    {
        //TODO: add more validations
        $data = self::getInfoFromRecord($record, 'SuperRate');
        return is_float($data) && $data != 0;
    }

}