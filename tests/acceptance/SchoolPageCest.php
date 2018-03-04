<?php


use Carbon\Carbon;
use Codeception\Util\Locator;

class SchoolPageCest
{
    public const BASE = '/testing/naturskolan_database';

    public static $st_pers_pw = 'ev2Hae';
    public static $st_pers_hash = 'IhKWKA9lROo3oDwR3tV/2.fwu9yzPyKDRf3swEg1sHh5BKYV2bQ9K';

    public static $items_visible_on_group_page = [
        '2A',
        '2B',
        '2C',
        'Ansvarig lärare',
        'Specialkost',
        'Information om gruppen'
    ];

    public static $teachers_st_per = [
        'Tomas Samuelsson',
        'Anna Svensson',
        'Pär Hedin',
        'Anna Rågwall'
    ];


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
        $staff_link = Locator::find('a', ['href' => self::BASE.'/skola/pers/staff']);
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
        foreach ($places as $place) {
            if (!empty($place)) {
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
        $num_users_after = $I->grabNumRecords('users');
        $I->assertSame($num_users_after, $num_users_before + 1);
    }

    // codecept run acceptance SchoolPageCest:groupPageWorks --steps
    public function groupPageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/skola/pers/groups');


        $I->checkMultiple('see', self::$items_visible_on_group_page);

        $I->seeInDatabase('groups', ['id' => 44, 'Name' => '2A', 'User_id' => 53]);
        $teacher_for_2a_field_path = '//div[@data-entity-id="44"]//select[@name="User"]';
        $I->seeElement($teacher_for_2a_field_path);
        $I->seeOptionIsSelected($teacher_for_2a_field_path, 'Tomas Samuelsson');
        $I->checkMultiple('seeInSource', self::$teachers_st_per);
        $I->dontSeeInSource('Stefan Eriksson');
    }

}
