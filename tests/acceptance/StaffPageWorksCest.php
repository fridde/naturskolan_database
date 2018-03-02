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



    /*
    public function frontpageWorksForVisitor(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->wait(2);
        $I->makeScreenshot('frontpage_visitor');
        foreach (self::$schools_on_frontpage as $index => $school_name) {
            $I->see($school_name, '.flexbox');
            $I->canSeeInSource($index, '.flexbox');
        }
        foreach (self::$schools_not_on_frontpage as $index => $school_name) {
            $I->cantSee($school_name, '.flexbox');
        }
        $I->seeInTitle('Sigtuna Naturskolans databas');
        $I->cantSee('Logga ut', '.nav');
    }

    public function frontpageWorksForAdmin(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->makeScreenshot('frontpage_admin');
        $I->checkMultiple('cantSee', self::$admin_nav_items, ['.nav']);

        $I->setCookie('Hash', self::$natu_cookie);
        $I->reloadPage();
        foreach (self::$admin_nav_items as $item) {
            $I->see($item, '.nav');
        }
        $I->see('Logga ut', '.nav');
    }

    // codecept run acceptance IndexCest:userCanLogin
    public function userCantLoginWithBadPW(AcceptanceTester $I)
    {
        $I->wantTo('Be rejected using a bad password');
        $I->amOnPage('/');
        $I->resetCookie('Hash');
        $link = Locator::find('a', ['href' => self::BASE . '/skola/pers']);
        $I->seeElement($link);
        $I->click($link);
        $I->wait(3);
        $I->see('Ange skolans lösenord');
        $I->makeScreenshot('login_modal');
        $hide_pw_cb = Locator::find('input', ['name' => 'hide-password', 'type' => 'checkbox']);
        $hidden_pw_field = Locator::find('input', ['name' => 'password', 'type' => 'password']);
        $visible_pw_field = Locator::find('input', ['name' => 'password', 'type' => 'text']);
        $I->seeElement($hidden_pw_field);
        $I->cantSeeElement($visible_pw_field);
        $I->seeElement($hide_pw_cb);
        $bad_pw = 'Bad password';
        $I->fillField($hidden_pw_field, $bad_pw);
        $I->makeScreenshot('password_hidden');
        $I->uncheckOption($hide_pw_cb);
        $I->wait(3);
        $I->cantSeeElement($hidden_pw_field);
        $I->seeElement($visible_pw_field);
        $I->makeScreenshot('password_visible');
        $I->seeInField($visible_pw_field, $bad_pw);

        $login_button = Locator::find('button', ['id' => 'login_modal_submit']);
        $I->click($login_button);
        $exclusive_items = array_diff(self::$user_nav_items, self::$visitor_nav_items);

        //TODO: Change to a better test as soon as there is a better response
        $I->checkMultiple('cantSee', $exclusive_items, ['.navbar']);

        $I->wantTo('Enter a valid pw, but for wrong school');
        $I->fillField($visible_pw_field, self::$galax_pw);
        $I->click($login_button);
        $I->checkMultiple('cantSee', $exclusive_items, ['.navbar']);
        $I->checkMultiple('canSee', self::$visitor_nav_items, ['.navbar']);


    }

    public function userCanLogin(AcceptanceTester $I)
    {
        $I->wantTo('Enter with a valid password');
        $I->amOnPage('/');
        $I->resetCookie('Hash');
        $link = Locator::find('a', ['href' => self::BASE . '/skola/pers']);
        $I->click($link);
        $I->wait(3);
        $hidden_pw_field = Locator::find('input', ['name' => 'password', 'type' => 'password']);
        $I->fillField($hidden_pw_field, self::$st_pers_pw);
        $login_button = Locator::find('button', ['id' => 'login_modal_submit']);
        $I->click($login_button);
        $I->wait(3);

        $I->checkMultiple('canSee', self::$user_nav_items, ['.nav']);
        $hash = $I->grabCookie('Hash');
        $I->assertNotEmpty($hash);
        $I->wait(10);
        $I->seeInDatabase('cookies', ['Name' => 'Hash', 'School_id' => 'pers', 'Value' => $hash]);
    }

    */


}
