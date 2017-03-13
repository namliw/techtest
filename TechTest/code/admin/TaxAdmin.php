<?php

/**
 * Created by PhpStorm.
 * User: wcorc
 * Date: 11/03/2017
 * Time: 1:40 PM
 */
class TaxAdmin extends ModelAdmin
{
    public static $managed_models = array(
        'PaySlipJob','FinancialYear','TaxBracket'
    );
    public static $url_segment = 'payslip';
    public static $menu_title = 'Payslip Admin';
    private static $menu_icon = 'TechTest/icon/icon_16.png';

}