<?php


use Carbon\Carbon;
use Codeception\Util\Locator;

use AcceptanceTester as A;

// codecept run acceptance SchoolPageCest --steps -f
class SchoolPageCest
{
    public function _before(A $I)
    {
        $I->setTestDate();
        $I->amOnPage('/');
        $I->setCookie('AuthKey', $I->get('st_per', 'AuthKey'));
        $I->amOnPage('/skola/pers');
        $I->delay(0.7);
    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance SchoolPageCest:canChangeFields --steps -f
    public function canChangeFields(A $I)
    {
        $I->waitForText('Lägg till person');

        $heinz_field = Locator::find('input', ['name' => 'FirstName', 'value' => 'Heinz']);
        $krumbichel_field = Locator::find('input', ['name' => 'LastName', 'value' => 'Krumbichel']);
        $I->seeInField($heinz_field, 'Heinz');
        $last_change_before = $I->grabFromDatabase('users', 'LastChange', ['id' => 102]);
        $I->fillField($heinz_field, 'Albus');
        $I->click($krumbichel_field);
        $I->delay();

        $I->seeInDatabase('users', ['FirstName' => 'Albus']);

        $last_change_after = $I->grabFromDatabase('users', 'LastChange', ['id' => 102]);
        $I->wantToTest('Has LastChange been updated?');
        $I->assertTrue(Carbon::parse($last_change_after)->gt(Carbon::parse($last_change_before)));
    }

    // codecept run acceptance SchoolPageCest:passwordIsRevealed --steps -f
    public function passwordIsRevealed(A $I)
    {
        $pw_reveal_btn = Locator::find('button', ['data-school' => 'pers']);
        $I->seeElement($pw_reveal_btn);
        $I->click($pw_reveal_btn);
        $I->waitForText($I->get('st_per', 'pw'), 10);
        $I->see($I->get('st_per', 'pw'));

    }

    // codecept run acceptance SchoolPageCest:rowIsAdded --steps -f
    public function rowIsAdded(A $I)
    {
        $row_btn = $I->getAddRowButton();
        $I->canSeeElement($row_btn);
        $I->scrollTo($row_btn);
        $I->click($row_btn);
        $I->delay();
        $rows = $I->getTableRows('User');
        $I->assertCount(10, $rows);
        $num_users_before = $I->grabNumRecords('users');
        $I->fillField($I->getFieldFromLastRow('User', 'FirstName'), 'Ronald');
        $I->pause();
        $I->clickAway();
        $I->delay();
        $I->seeInDatabase('users', ['FirstName' => 'Ronald']);
        $num_users_after = $I->grabNumRecords('users');
        $I->assertEquals($num_users_after, $num_users_before + 1);
    }

    // codecept run acceptance SchoolPageCest:groupPageWorks --steps -f
    public function groupPageWorks(A $I)
    {
        $segment_header = '//span[text()=\'Grupper åk 2/3\']';

        $I->canSeeElement($segment_header);
        $I->scrollTo($segment_header);
        $I->click($segment_header);
        $I->delay();
        $I->checkMultiple('see', $I->get('st_per', 'items_visible_on_group_page'));

        $I->seeInDatabase('groups', ['id' => 44, 'Name' => '2A', 'User_id' => 53]);
        $teacher_for_2a_field_path = $I->get('paths', 'teacher_for_2a');
        $I->seeElement($teacher_for_2a_field_path);
        $I->seeOptionIsSelected($teacher_for_2a_field_path, 'Björn Rosenström'); // teacher with id 53
        $I->checkMultiple('seeInSource', $I->get('st_per', 'teachers'));
        $I->dontSeeInPageSource('Stefan Eriksson'); // different school
        $I->selectOption($teacher_for_2a_field_path, 'Anna Svensson'); // teacher with id 24
        $I->clickAway();
        $I->delay(0.7);
        $I->seeInDatabase('groups', ['id' => 44, 'Name' => '2A', 'User_id' => 24]);
        $I->seeInDatabase(
            'changes',
            [
                'EntityClass' => 'Group',
                'EntityId' => 44,
                'Property' => 'User',
                'OldValue' => 53,
                'Processed' => null,
            ]
        );

        $visits_for_2a = ['2018-06-04', 'Universum', '2018-11-13', 'Vårvandring', '2019-02-07', 'Forntidsdag'];
        //
        $visit_locator_for_2a = $I->get('paths', 'visits_for_2a');
        $I->checkMultiple('see', $visits_for_2a, $visit_locator_for_2a);

        $I->cantSee('5A');
        //
        $segment5_btn = '//button[@data-target="#segment_5"]';
        $I->scrollTo($segment5_btn);
        $I->click($segment5_btn);
        $I->delay(0.7);
        $I->canSee('5A');

        // TODO: Test group name editor

    }

}
