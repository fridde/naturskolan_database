<?php


use Carbon\Carbon;

use AcceptanceTester as A;

// codecept run acceptance AdminCest --steps -f
class AdminCest
{
    public function _before(A $I)
    {
        // if run manually, don't forget to first run
        // codecept run acceptance HelperTestsCest:resetDatabase
        $I->amOnPage('/');
        $I->setCookie('AuthKey', $I->get('natu', 'AuthKey'));
        $I->setCookie('XDEBUG_SESSION', 'PHPSTORM');
    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance AdminCest:setWorkSchedule --steps -f
    public function setWorkSchedule(A $I)
    {

        // TODO: Continue the methods of this class
    }




}
