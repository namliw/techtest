<?php

/**
 * Created by PhpStorm.
 * User: wcorc
 * Date: 13/03/2017
 * Time: 11:14 AM
 */
class RecordInfoTest extends SapphireTest
{
    /**
     * @dataProvider recordProvider
     * @param $record
     */
    public function testIsRecordValid($record, $expected)
    {
        $final = TaxCalculations::isValidRecord($record);
        $this->assertEquals($expected, $final);
    }

    public function recordProvider()
    {
        return [
            'Valid_record' => [
                ['David', 'Rudd', '60050', '9%', '01 March – 31 March'],
                true
            ],
            'Invalid_record' => [
                ['David', 'Rudd', '60050', '%', '01 March – 31 March'],
                false
            ],
            'Invalid_record_2' => [
                ['David', 'Rudd', '60050', '', '01 March – 31 March'],
                false
            ],
            'Invalid_record_3' => [
                ['David', 'Rudd', '60050', '0%', '01 March – 31 March'],
                false
            ],
            'Invalid_record_4' => [
                ['David', 'Rudd', '60050', 'words%', '01 March – 31 March'],
                false
            ],
        ];
    }

    /**
     * @dataProvider retreivalProvider
     * @param $info
     * @param $expected
     * @param $record
     */
    public function testInformationRetreival($record, $info, $expected)
    {
        $final = TaxCalculations::getInfoFromRecord($record, $info);
        $this->assertEquals($expected, $final);
    }

    public function retreivalProvider()
    {
        return [
            'Valid_record' => [
                ['David', 'Rudd', '60050', '9%', '01 March – 31 March'],
                'Name', 'David'
            ],
            'Valid_record_1' => [
                ['David', 'Rudd', '60050', '9%', '01 March – 31 March'],
                'LastName', 'Rudd'
            ],
            'Valid_record_2' => [
                ['David', 'Rudd', '60050', '9%', '01 March – 31 March'],
                'PaymentStartDate', '01 March – 31 March'
            ],
            'Valid_record_3' => [
                ['David', 'Rudd', '60050', '9%', '01 March – 31 March'],
                'SuperRate', 0.09
            ],
            'Valid_record_4' => [
                ['David', 'Rudd', '60050', '9%', '01 March – 31 March'],
                'AnnualSalary', 60050
            ],
            'Invalid_Record' => [
                ['David', 'Rudd', '60050', '9%', '01 March – 31 March'],
                'NONE', false
            ],
        ];
    }

    /**
     * @dataProvider calculationsProvider
     * @param $record
     * @param $expected
     */
    public function testCalculations($record, $expected)
    {
        $brs = $this->getBrackets();
        $final = TaxCalculations::applyCalculations($record, $brs);
        $this->assertEquals($expected, $final);
    }

    public function getBrackets()
    {
        $brackets = array(
            array(
                'From' => 0, 'To' => 18200, 'CentsPerDollar' => 0, 'Threshold' => 0, 'BaseTax' => 0
            ),
            array(
                'From' => 18201, 'To' => 37000, 'CentsPerDollar' => 19, 'Threshold' => 18200, 'BaseTax' => 0
            ),
            array(
                'From' => 37001, 'To' => 80000, 'CentsPerDollar' => 32.5, 'Threshold' => 37000, 'BaseTax' => 3572
            ),
            array(
                'From' => 80001, 'To' => 180000, 'CentsPerDollar' => 37, 'Threshold' => 80000, 'BaseTax' => 17547
            ),
            array(
                'From' => 180001, 'To' => 0, 'CentsPerDollar' => 45, 'Threshold' => 180000, 'BaseTax' => 54547
            )
        );

        $final = array();
        foreach ($brackets as $b) {
            $br = new TaxBracket($b);
            $final[] = $br;
        }

        return $final;
    }

    public function calculationsProvider()
    {
        return array(
            'Row_1' => array(
                ['David', 'Rudd', '60050', '9%', '01 March – 31 March'],
                [5004.0, 922.0, 4082.0, 450.0]
            ),
            'Row_2' => array(
                ['Ryan', 'Chen', '120000', '10%', '01 March – 31 March'],
                [10000.0, 2696.0, 7304.0, 1000.0]
            ),

            'Row_3' => array(
                ['Martine', 'Streich', '11597', '14.94%', '01 January - 31 Jan']
                //gross income, income tax, net income, super
            , [966.0, 0.0, 966.0, 144.0]),
            'Row_4' => array(
                ['Kattie', 'Anderson', '54822', '34.5%', '01 November - 30 Nov']
            , [4569.0, 780.0, 3789.0, 1576.0]),
            'Row_5' => array(
                ['Kaela', 'Von', '211737', '19.54%', '01 June - 30 Jun']
            , [17645.0, 5736.0, 11909.0, 3448.0]),
            'Row_6' => array(
                ['Emilio', 'Stark', '150302', '11.64%', '01 September - 30 Sep']
            , [12525.0, 3630.0, 8895.0, 1458.0]),
            'Row_7' => array(
                ['Clementine', 'Lubowitz', '181616', '32.45%', '01 May - 31 May']
            , [15135.0, 4606.0, 10529.0, 4911.0]),
            'Row_8' => array(
                ['Jose', 'Kessler', '127940', '21.49%', '01 December - 31 Dec']
            , [10662.0, 2940.0, 7722.0, 2291.0]),
            'Row_9' => array(
                ['Cheyenne', 'Homenick', '130813', '27.41%', '01 June - 30 Jun']
            , [10901.0, 3029.0, 7872.0, 2988.0]),
            'Row_10' => array(
                ['Concepcion', 'Medhurst', '160562', '19.94%', '01 June - 30 Jun']
            , [13380.0, 3946.0, 9434.0, 2668.0]),
            'Row_11' => array(
                ['Carmela', 'Shanahan', '163740', '40.46%', '01 January - 31 Jan']
            , [13645.0, 4044.0, 9601.0, 5521.0]),
            'Row_12' => array(
                ['Eusebio', 'Roob', '54035', '17.69%', '01 October - 31 Oct']
            , [4503.0, 759.0, 3744.0, 797.0]),
            'Row_13' => array(
                ['Geoffrey', 'Ortiz', '31596', '16.35%', '01 October - 31 Oct']
            , [2633.0, 212.0, 2421.0, 430.0]),
            'Row_14' => array(
                ['Maxie', 'Stark', '170995', '31.85%', '01 September - 30 Sep']
            , [14250.0, 4268.0, 9982.0, 4539.0]),
            'Row_15' => array(
                ['Aditya', 'Lubowitz', '245985', '46.19%', '01 June - 30 Jun']
            , [20499.0, 7020.0, 13479.0, 9468.0]),
            'Row_16' => array(
                ['Gail', 'McDermott', '223704', '46.23%', '01 September - 30 Sep']
            , [18642.0, 6184.0, 12458.0, 8618.0]),
            'Row_17' => array(
                ['Elvera', 'Marks', '202008', '43.33%', '01 March - 31 Mar']
            , [16834.0, 5371.0, 11463.0, 7294.0]),
            'Row_18' => array(
                ['Clare', 'Hane', '59795', '47.95%', '01 April - 30 Apr']
            , [4983.0, 915.0, 4068.0, 2389.0]),
            'Row_19' => array(
                ['Waldo', 'White', '236773', '47.75%', '01 April - 30 Apr']
            , [19731.0, 6675.0, 13056.0, 9422.0]),
        );
    }

}