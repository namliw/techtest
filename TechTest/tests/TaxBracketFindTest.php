<?php

/**
 * Created by PhpStorm.
 * User: wcorc
 * Date: 13/03/2017
 * Time: 10:19 AM
 */
class TaxBracketFindTest extends SapphireTest
{
    /**
     * @dataProvider salaryProvider
     * @param $from
     * @param $to
     * @param $salary
     * @param $expected
     */
    public function testBelongsToTaxBracket($from, $to, $salary, $expected)
    {
        $taxB = new TaxBracket();
        $taxB->From = $from;
        $taxB->To = $to;
        $this->assertEquals($expected, $taxB->isSalaryInBracketRange($salary));
    }

    public function salaryProvider()
    {
        return [
            'Belongs_1' => [0, 18000, 0, true],
            'Belongs_2' => [0, 18000, 18000, true],
            'Belongs_3' => [0, 18000, 17999, true],
            'Outside_3' => [0, 18000, 50000, false],
            'Outside_4' => [0, 18000, 18001, false],
        ];
    }

    /**
     * @dataProvider salaryTaxProvider
     * @param $centsPerDollar
     * @param $baseTax
     * @param $salary
     * @param $expected
     */
    public function testIsValidCharge($centsPerDollar, $Threshold, $baseTax, $salary, $expected)
    {

        $taxB = new TaxBracket();
        $taxB->CentsPerDollar = $centsPerDollar;
        $taxB->BaseTax = $baseTax;
        $taxB->Threshold = $Threshold;
        $final = round($taxB->getTaxCharge($salary),0);
        $expected = (float)$expected;
        $this->assertEquals($expected, $final);

    }


    public function salaryTaxProvider()
    {
        return [
            'SecondTaxB' => [37, 80000, 17547, 120000, 2696],
            'FirstTaxB' => [32.5, 37000, 3572, 60050, 922],
        ];
    }
}