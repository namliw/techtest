<?php

/**
 * Created by PhpStorm.
 * User: wcorc
 * Date: 11/03/2017
 * Time: 1:16 PM
 */
class TaxBracket extends DataObject
{
    public static $db = array(
        'From' => 'Int',//Arguably this could be a decimal, in case the gov decides to get really specific
        //but assuming we'd be handling large amounts of data should we design on assumptions or facts?
        'To' => 'Int',
        'BaseTax' => 'Int',
        'CentsPerDollar' => 'Decimal(7,5)',//At this stage they're just cents so we only need room up to 99
        //with a presicion of 5
        'Threshold' => 'Int',
        'SortOrder' => 'Int'
    );

    private static $summary_fields = array(
        "From" => 'From',
        'To' => 'To',
        'CentsPerDollar' => 'Taxed cents per dollar',
        'Threshold' => 'Initial Tax'
    );

    //It is very tempting to make brackets reusable but for the sake of keeping human error minimized
    //they shall not
    public static $has_one = array(
        'FinancialYear' => 'FinancialYear'
    );

    public function getCMSValidator()
    {
        return new RequiredFields(
            array(
                'From', 'To', 'CentsPerDollar', 'Threshold',
            )
        );
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('SortOrder');
        return $fields;
    }

    protected function validate()
    {
        $result = parent::validate();

        //Make sure negative values are not entered.
        $nonNegativeFields = array('From', 'To', 'CentsPerDollar', 'Threshold');
        foreach ($nonNegativeFields as $field) {
            if ($this->$field < 0) {
                $result->error(
                    'The field ' . $this->fieldLabel($field) . ' must be greater or equal to 0.' //Normally this would be a translation function
                );
            }
        }

        return $result;
    }

    /**
     * Returns true if the salary is whithin the bracket range false otherwise
     * @param $salary
     * @return bool
     */
    public function isSalaryInBracketRange($salary)
    {

        if (($this->To != 0 && $salary >= $this->from && $salary <= $this->To)
            || ($this->To == 0 && $salary >= $this->from)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Calculates the income tax for a given salary
     * @param $annualSalary
     * @return float
     */
    public function getTaxCharge($annualSalary)
    {
        //TODO: should this validate if the salary is applicable to this bracket?
        //It wont make much sense otherwise
        $cents = (float)($this->CentsPerDollar / 100);
        $annualSalary = (float)$annualSalary;
        $bt = (float)$this->BaseTax;
        $over = (float)$this->Threshold;
        return ($bt + ($annualSalary - $over) * $cents) / 12;
    }


}