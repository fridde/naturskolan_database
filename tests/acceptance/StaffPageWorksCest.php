<?php


use Carbon\Carbon;
use Codeception\Util\Locator;

class StaffPageWorksCest
{
    public const BASE = '/testing/naturskolan_database';

    public static $st_pers_pw = 'ev2Hae';
    public static $st_pers_hash = 'IhKWKA9lROo3oDwR3tV/2.fwu9yzPyKDRf3swEg1sHh5BKYV2bQ9K';



    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->setCookie('Hash', self::$st_pers_hash);
        $I->amOnPage('/skola/pers');
    }

    public function _after(AcceptanceTester $I)
    {
    }


    public function canChangeFields(AcceptanceTester $I)
    {
        $staff_link = Locator::find('a', ['href' => self::BASE . '/skola/pers/staff']);
        $I->seeElement($staff_link);
        $I->click($staff_link);
        $I->waitForText('Lägg till person');

        $heinz_field = Locator::find('input', ['name' => 'FirstName', 'value' => 'Heinz']);
        $krumbichel_field = Locator::find('input', ['name' => 'LastName', 'value' => 'Krumbichel']);
        $I->seeInField($heinz_field, 'Heinz');
        $I->fillField($heinz_field, 'Albus');
        $I->click($krumbichel_field);
        $I->wait(3);
        $I->seeInDatabase('users', ['FirstName' => 'Albus']);

        $last_change = $I->grabFromDatabase('users', 'LastChange', ['id' => 58]);
        $I->wantToTest('Has LastChange been updated?');
        $I->assertTrue(Carbon::parse($last_change)->gt(Carbon::now()->subMinute()));
    }

    public function passwordIsRevealed(AcceptanceTester $I)
    {
        $places = [null, '/skola/pers/staff', '/skola/pers/groups'];
        foreach($places as $place){
            if(!empty($place)){
                $I->amOnPage($place);
            }
            $pw_reveal_btn = Locator::find('button', ['data-school' => 'pers']);
            $I->seeElement($pw_reveal_btn);
            $I->click($pw_reveal_btn);
            $I->waitForText(self::$st_pers_pw, 10);
            $I->see(self::$st_pers_pw);
        }
    }

    public function rowIsAdded(AcceptanceTester $I)
    {
        $I->amOnPage('/skola/pers/staff');
        $row_btn = Locator::find('button', ['id' => 'add-row-btn']);
        $I->canSeeElement($row_btn);
        $I->click($row_btn);
        $I->wait(3);
        $row_path = 'table[data-entity="User"] tbody tr';
        $rows = $I->grabMultiple($row_path);
        $I->assertCount(5, $rows);
        $num_users_before = $I->grabNumRecords('users');
        $I->fillField('//table[@data-entity="User"]//tbody//tr[last()]//input[@name="FirstName"]', 'Ronald');
        $I->clickWithLeftButton(null, 0, -50);
        $I->wait(3);
        $I->seeInDatabase('users', ['FirstName' => 'Ronald']);
    }

    public function groupPageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/skola/pers/groups');
    }

}
