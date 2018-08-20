<?php


use AcceptanceTester as A;

// codecept run acceptance PermissionCest --steps -f
class PermissionCest
{
    public function _before(A $I)
    {
        $I->setCookie('XDEBUG_SESSION', 'PHPSTORM');
    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance PermissionCest:canGetCalendar --steps -f
    public function canGetCalendar(A $I)
    {
        $I->runCronTask('rebuild_calendar');
        $I->pause();



    }


}
