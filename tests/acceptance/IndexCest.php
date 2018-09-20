<?php


use Codeception\Util\Locator;
use AcceptanceTester as A;

// codecept run acceptance IndexCest --steps -f
class IndexCest
{
    public function _before(A $I)
    {
        $I->setTestDate();
        $I->setCookie('XDEBUG_SESSION', 'PHPSTORM');
    }

    public function _after(A $I)
    {
    }

    // codecept run acceptance IndexCest:userIsThere --steps -f
    public function userIsThere(A $I)
    {
        $I->seeInDatabase('users', ['FirstName' => 'Heinz', 'LastName' => 'Krumbichel']);
    }
    // codecept run acceptance IndexCest:frontpageWorksForVisitor --steps -f
    public function frontpageWorksForVisitor(A $I)
    {
        $I->amOnPage('/');
        $I->pause(0.7);
        $I->makeScreenshot('frontpage_visitor');
        $schools_on_fp = $I->get('schools','frontpage') ?? [];
        foreach ($schools_on_fp as $index => $school_name) {
            $I->see($school_name, '.flexbox');
            $I->canSeeInSource($index, '.flexbox');
        }
        $schools_not_on_frontpage = $I->get('schools','not_on_frontpage') ?? [];
        foreach ($schools_not_on_frontpage as $index => $school_name) {
            $I->cantSee($school_name, '.flexbox');
        }
        $I->seeInTitle('Sigtuna Naturskolans databas');
        $I->cantSee('Logga ut', '.nav');
    }

    // codecept run acceptance IndexCest:frontpageWorksForAdmin --steps -f
    public function frontpageWorksForAdmin(A $I)
    {
        $I->amOnPage('/');
        $I->makeScreenshot('frontpage_admin');
        $admin_nav_items = $I->get('nav_items','admin');
        $I->checkMultiple('cantSee', $admin_nav_items, '.nav');

        $I->setCookie('AuthKey', $I->get('natu','AuthKey'));
        $I->reloadPage();
        foreach ($admin_nav_items as $item) {
            $I->see($item, '.nav');
        }
        $I->see('Logga ut', '.nav');
    }

    // codecept run acceptance IndexCest:userCantLoginWithBadPW --steps -f
    public function userCantLoginWithBadPW(A $I)
    {
        $I->wantTo('Be rejected using a bad password');
        $I->amOnPage('/');
        $I->resetCookie('AuthKey');
        $link = Locator::find('a', ['href' => $I->get('BASE') . '/skola/pers']);
        $I->seeElement($link);
        $I->click($link);
        $I->pause();
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
        $I->pause();
        $I->cantSeeElement($hidden_pw_field);
        $I->seeElement($visible_pw_field);
        $I->makeScreenshot('password_visible');
        $I->seeInField($visible_pw_field, $bad_pw);

        $login_button = Locator::find('button', ['id' => 'login_modal_submit']);
        $I->click($login_button);
        $user_nav_items = $I->get('nav_items','user');
        $visitor_nav_items = $I->get('nav_items','visitor');

        $exclusive_items = array_diff($user_nav_items, $visitor_nav_items);

        //TODO: Change to a better test as soon as there is a better response
        $I->checkMultiple('cantSee', $exclusive_items, '.navbar');

        $I->wantTo('Enter a valid pw, but for wrong school');
        $I->fillField($visible_pw_field, $I->get('gala', 'pw'));
        $I->click($login_button);
        $I->checkMultiple('cantSee', $exclusive_items, '.navbar');
        $I->checkMultiple('canSee', $I->get('nav_items','visitor'), '.navbar');


    }

    // codecept run acceptance IndexCest:userCanLogin --steps -f
    public function userCanLogin(A $I)
    {
        $I->wantTo('Enter with a valid password');
        $I->amOnPage('/');
        $I->resetCookie('AuthKey');
        $link = Locator::find('a', ['href' => $I->get('BASE') . '/skola/pers']);
        $I->click($link);
        $I->pause();
        $hidden_pw_field = Locator::find('input', ['name' => 'password', 'type' => 'password']);
        $I->fillField($hidden_pw_field, $I->get('st_per', 'pw'));
        $login_button = Locator::find('button', ['id' => 'login_modal_submit']);
        $I->click($login_button);
        $I->pause(1.5);
        $user_nav_items = $I->get('nav_items', 'user');
        //$I->pauseExecution();
        $I->checkMultiple('canSee', $user_nav_items, '.nav');
        $AuthKey = $I->grabCookie('AuthKey');
        $I->assertNotEmpty($AuthKey);
        $I->pause();
        $I->seeInDatabase('hashes', ['Category' => 2, 'Owner_id' => 'pers']);
    }

    // codecept run acceptance IndexCest:userCanLogout --steps -f
    public function userCanLogout(A $I)
    {
        $I->wantTo('Logout and not be able to enter');
        $I->amOnPage('/');
        $I->setCookie('AuthKey', $I->get('st_per', 'AuthKey'));
        $I->amOnPage('/skola/pers');

        $I->click('//a[@href="logout"]');
        $I->pause(0.7);
        $I->dontSeeCookie('AuthKey');
        $I->dontSeeInCurrentUrl('skola/pers');
    }

    // codecept run acceptance IndexCest:userCantGetPasswordMail --steps -f
    public function userCantGetPasswordMail(A $I)
    {

    }




}
