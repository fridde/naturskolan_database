<?php


use Carbon\Carbon;

use AcceptanceTester as A;
use Codeception\Util\Locator;

// codecept run acceptance AdminCest --steps -f
class TableCest
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

    // codecept run acceptance TableCest:editEventTable --steps -f
    public function editEventTable(A $I)
    {
        $initial_event_count = 2;

        $I->amOnPage('/table/Event');
        $I->assertCount($initial_event_count, $I->getTableRows('Event'));
        $I->assertSame($initial_event_count, $I->grabNumRecords('events'));

        $button = $I->getAddRowButton();
        $I->seeElement($button);
        $I->click($button);
        $I->assertCount($initial_event_count + 1, $I->getTableRows('Event'));



        $I->fillField($I->getFieldFromLastRow('Event', 'Title'), 'Åka till tandläkaren');
        $I->clickWithLeftButton(null, 0, -50);
        $I->wait(2);
        // as we have only entered the title and not a start date yet
        $I->assertSame($initial_event_count, $I->grabNumRecords('events'));

        $I->fillField($I->getFieldFromLastRow('Event', 'StartDate'), '2018-08-05');
        $I->clickWithLeftButton(null, 0, -50);
        $I->wait(2);
        $I->assertSame($initial_event_count + 1, $I->grabNumRecords('events'));

        $I->runCronTask('rebuild_calendar');
        $I->seeFileFound('kalender.ics', codecept_root_dir());

        $I->seeInThisFile('tandläkaren');

    }






}
