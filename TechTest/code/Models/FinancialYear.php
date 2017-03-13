<?php

/**
 * Created by PhpStorm.
 * User: wcorc
 * Date: 11/03/2017
 * Time: 1:15 PM
 */
class FinancialYear extends DataObject
{
    public static $db = array(
        'Year' => 'Int(4)',
        'Current' => 'Boolean'
    );

    public static $has_many = array(
        'TaxBrackets' => 'TaxBracket',
        'PaySlipJobs' => 'PaySlipJob'
    );

    public function getTitle()
    {
        return $this->Year;
    }

    public function getName()
    {
        return $this->getTitle();
    }

    public function getCMSValidator()
    {
        return new RequiredFields(
            array(
                'Year',
            )
        );
    }

    private static $summary_fields = array(
        "Year" => 'Financial Year',
        'Current' => 'Current finalcial year',
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->fieldByName('Root.Main.Year')->setDescription('Runs from July 1 of the entered value to June 30 of the following year.');

        $configForms = GridFieldConfig_RelationEditor::create();
        $configForms->addComponent(new GridFieldSortableRows('SortOrder'));

        $formField = new GridField('TaxBrackets', 'Tax Brackets', $this->TaxBrackets(), $configForms);
        $fields->replaceField('TaxBrackets', $formField);

        return $fields;
    }

    protected function validate()
    {
        $result = parent::validate();
        //Make sure negative values are not entered.
        $nonNegativeFields = array('Year');
        foreach ($nonNegativeFields as $field) {
            if ($this->$field < 0) {
                $result->error(
                    'The field ' . $this->fieldLabel($field) . ' must be greater or equal to 0.' //Normally this would be a translation function
                );
            }
        }
        return $result;
    }

}