<?php

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

    // codecept run acceptance AdminCest:updateGroupCountsForSchools --steps -f
    public function updateGroupCountsForSchools(A $I)
    {
        $I->amOnPage('admin/batch/set_group_count');
        $I->pause(0.5);

        $group_numbers = $I->getGroupNumbersForSchool('pers');
        $I->assertEquals($group_numbers['2018']['2'], 3);
        $I->assertEquals($group_numbers['2017']['2'], 0);
        $I->assertEquals($group_numbers['2017']['5'], 3);
        $I->assertEquals($group_numbers['2019']['2'], 0);
        $I->assertEquals($group_numbers['2019']['5'], 0);
        $I->assertArrayNotHasKey('fri', $group_numbers['2019']);

        $year_selector = '//select[@name="start-year"]';
        $I->seeElement($year_selector);
        $I->seeOptionIsSelected($year_selector, '2018');

        $I->selectOption($year_selector, '2019');

        $textarea = '//textarea[@id="group-count-lines"]';
        $I->seeElement($textarea);

        $text = $I->get('strings', 'group_count_text');
        $I->fillField($textarea, $text);

        $I->click('//button[@id="update"]');
        $I->pause(1.5);

        $group_numbers = $I->getGroupNumbersForSchool('pers');
        $I->assertEquals($group_numbers['2018']['2'], 3);
        $I->assertEquals($group_numbers['2017']['2'], 0);
        $I->assertEquals($group_numbers['2017']['5'], 3);
        $I->assertEquals($group_numbers['2019']['2'], 8);
        $I->assertEquals($group_numbers['2019']['5'], 0);
        $I->assertEquals($group_numbers['2019']['fri'], 5);

        $old_text = $I->grabValueFrom($textarea);
        $I->assertGreaterThan(0, strlen($old_text));

        $I->click('//button[@id="clean"]');
        $I->pause(0.5);

        $new_text = $I->grabTextFrom($textarea);
        $I->assertEquals(0, strlen($new_text));
    }

    // codecept run acceptance AdminCest:setWorkSchedule --steps -f
    public function setWorkSchedule(A $I)
    {


    }


}
