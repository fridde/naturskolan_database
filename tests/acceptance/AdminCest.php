<?php

use AcceptanceTester as A;

// codecept run acceptance AdminCest --steps -f
class AdminCest
{
    public function _before(A $I)
    {
        // if run manually, don't forget to first run
        // codecept run acceptance HelperTestsCest:resetDatabase
        $I->setTestDate();
        $I->amOnPage('/');
        $I->setCookie('AuthKey', $I->get('natu', 'AuthKey'));
        $I->setCookie('XDEBUG_SESSION', 'PHPSTORM');
    }

    public function _after(A $I)
    {
    }



    // codecept run acceptance AdminCest:setColleagues --steps -f
    public function setColleagues(A $I)
    {
        $I->amOnPage('admin/batch/set_colleagues');
        $I->pause(0.7);

        $I->seeInPageSource('2018-06-19');
        $I->dontSeeInPageSource('2018-05-08');  // test date is 2018-06-01 and no past events should be visible

        $first_row = '//tr[@data-id="102"]';
        $I->seeElement($first_row);

        $I->assertEquals(0, $I->grabNumRecords('colleagues_visits'));

        $I->click($first_row . '//td[@data-colleague-id="1"]');
        $I->pause(1);

        $criteria_1 = ['visit_id' => 102, 'user_id' => 1];
        $I->seeInDatabase('colleagues_visits', $criteria_1);

        $second_row = '//tr[@data-id="152"]';
        $I->seeElement($second_row);

        $I->click($second_row . '//td[@data-colleague-id="6"]');
        $I->pause(1);

        $criteria_2 = ['visit_id' => 152, 'user_id' => 6];
        $I->seeInDatabase('colleagues_visits', $criteria_2);

        $I->assertEquals(2, $I->grabNumRecords('colleagues_visits'));

        $I->runCronTask('rebuild_calendar');

        $I->seeFileFound('kalender.ics', codecept_root_dir());

        $strings = [
            '[Jo] Vårvandring',
            '[F] Universum'
        ];

        $I->seeStringsInThisFile($strings);
    }

    




}
