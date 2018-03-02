<?php


use Codeception\Util\Locator;

class IndexCest
{
    public const BASE = '/testing/naturskolan_database';

    public static $schools_on_frontpage = [
        'berg' => 'Bergius',
        'cent' => 'Centralskolan',
        'edda' => 'Eddaskolan',
        'ekil' => 'Ekillaskolan',
        'gala' => 'Galaxskolan',
        'gert' => 'S:ta Gertruds skola',
        'gsar' => 'Grundsärskolan',
        'jose' => 'Josefinaskolan',
        'norr' => 'Norrbackaskolan',
        'oden' => 'Odensala skola',
        'olof' => 'S:t Olofs skola',
        'pers' => 'S:t Pers skola',
        'rabg' => 'Råbergsskolan',
        'saga' => 'Sagaskolan',
        'satu' => 'Sätunaskolan',
        'shoj' => 'Steningehöjdens skola',
        'skep' => 'Skepptuna skola',
        'sshl' => 'Sigtunaskolan Humanistiska Läroverket',
        'ting' => 'Tingvallaskolan',
        'vals' => 'Valstaskolan',
        'vari' => 'Väringaskolan',
    ];

    public static $schools_not_on_frontpage = [
        'anna' => 'Annan skola',
        'natu' => 'Naturskolan',
    ];

    public static $natu_cookie = 'xGvueTHZ9FRoqyceP25WIO1xQU8rpDl9b4kl02pM4nqrLnQEOUXiK';

    public static $admin_nav_items = [
        'skolor',
        'verktyg',
        'tabeller',
    ];

    public static $user_nav_items = [
        'grupper',
        'personal',
        'hjälp',
        'kontakt',
        'logga ut',
    ];

    public static $visitor_nav_items = [
        'hjälp',
        'kontakt',
    ];

    public static $st_pers_pw = 'ev2Hae';

    public static $galax_pw = '4oEUWA';

    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }


    public function UserIsThere(AcceptanceTester $I)
    {
        $I->seeInDatabase('users', ['FirstName' => 'Heinz', 'LastName' => 'Krumbichel']);
    }

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




}
