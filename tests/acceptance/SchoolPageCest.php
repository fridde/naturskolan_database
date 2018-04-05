<?php


use Carbon\Carbon;
use Codeception\Util\Locator;

use AcceptanceTester as A;

// codecept run acceptance SchoolPageCest --steps -f
class SchoolPageCest
{
    public function _before(A $I)
    {
        $I->amOnPage('/');
        $I->setCookie('Hash', $I->get('st_per', 'hash'));
        $I->amOnPage('/skola/pers');
        $I->setTestDate();
        $I->wait(2);
    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance SchoolPageCest:canChangeFields --steps -f
    public function canChangeFields(A $I)
    {
        $staff_link = Locator::find('a', ['href' => $I->get('BASE').'/skola/pers/staff']);
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

        $last_change = $I->grabFromDatabase('users', 'LastChange', ['id' => 102]);
        $I->wantToTest('Has LastChange been updated?');
        $I->assertTrue(Carbon::parse($last_change)->gt(Carbon::now()->subMinute()));
    }

    // codecept run acceptance SchoolPageCest:passwordIsRevealed --steps -f
    public function passwordIsRevealed(A $I)
    {
        $places = [null, '/skola/pers/staff', '/skola/pers/groups'];
        foreach ($places as $place) {
            if (!empty($place)) {
                $I->amOnPage($place);
            }
            $pw_reveal_btn = Locator::find('button', ['data-school' => 'pers']);
            $I->seeElement($pw_reveal_btn);
            $I->click($pw_reveal_btn);
            $I->waitForText($I->get('st_per','pw'), 10);
            $I->see($I->get('st_per','pw'));
        }
    }
    // codecept run acceptance SchoolPageCest:rowIsAdded --steps -f
    public function rowIsAdded(A $I)
    {
        $I->amOnPage('/skola/pers/staff');
        $row_btn = Locator::find('button', ['id' => 'add-row-btn']);
        $I->canSeeElement($row_btn);
        $I->click($row_btn);
        $I->wait(3);
        $row_path = '//table[@data-entity="User"]//tbody//tr';
        $rows = $I->grabMultiple($row_path);
        $I->assertCount(9, $rows);
        $num_users_before = $I->grabNumRecords('users');
        $I->fillField($I->get('paths', 'last_staff_row'), 'Ronald');
        $I->clickWithLeftButton(null, 0, -50);
        $I->wait(3);
        $I->seeInDatabase('users', ['FirstName' => 'Ronald']);
        $num_users_after = $I->grabNumRecords('users');
        $I->assertSame($num_users_after, $num_users_before + 1);
    }

    // codecept run acceptance SchoolPageCest:groupPageWorks --steps -f
    public function groupPageWorks(A $I)
    {
        $I->amOnPage('/skola/pers/groups');


        $I->checkMultiple('see', $I->get('st_per', 'items_visible_on_group_page'));

        $I->seeInDatabase('groups', ['id' => 44, 'Name' => '2A', 'User_id' => 53]);
        $teacher_for_2a_field_path = $I->get('paths','teacher_for_2a');
        $I->seeElement($teacher_for_2a_field_path);
        $I->seeOptionIsSelected($teacher_for_2a_field_path, 'Björn Rosenström'); // teacher with id 53
        $I->checkMultiple('seeInSource', $I->get('st_per', 'teachers'));
        $I->dontSeeInPageSource('Stefan Eriksson'); // different school
        $I->selectOption($teacher_for_2a_field_path, 'Anna Svensson'); // teacher with id 24
        $I->clickWithLeftButton(null,-50,0);
        $I->wait(2);
        $I->seeInDatabase('groups', ['id' => 44, 'Name' => '2A', 'User_id' => 24]);
        $I->seeInDatabase('changes', [
            'EntityClass' => 'Group',
            'EntityId' => 44,
            'Property' => 'User',
            'OldValue' => 53,
            'Processed' => null
            ]);

        $visits_for_2a = ['2018-02-16', 'Universum', '2018-04-13', 'Vårvandring', '2018-06-07', 'Forntidsdag'];
        //
        $visit_locator_for_2a = $I->get('paths', 'visits_for_2a');
        $I->checkMultiple('see', $visits_for_2a, [$visit_locator_for_2a]);
        
        $I->cantSee('5A');
        $I->click('//a[@href="#tab_5"]');
        $I->wait(2);
        $I->canSee('5A');
    }

}
