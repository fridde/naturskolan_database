<?php


use AcceptanceTester as A;

// codecept run acceptance PermissionCest --steps -f
class PermissionCest
{
    public function _before(A $I)
    {
        $I->amOnPage('/');
        $I->setCookie('AuthKey', $I->get('natu', 'AuthKey'));
        $I->setCookie('XDEBUG_SESSION', 'PHPSTORM');
    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance PermissionCest:canGetCalendar --steps -f
    public function canGetCalendar(A $I)
    {
        $I->runCronTask('rebuild_calendar');
        $I->resetCookie('AuthKey');
        $I->delay();

        $I->amOnPage('calendar');
        $I->delay();
        $I->makeScreenshot('calendar_downloaded');

    }


}
