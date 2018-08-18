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

    // codecept run acceptance AdminCest:addMissingGroups --steps -f
    public function addMissingGroups(A $I)
    {
        $I->amOnPage('admin');
        $I->pause(0.5);

        $missing_group_btn = '//div[@id="missingGroups"]//button';
        $I->seeElement($missing_group_btn);

        $segment_selector = '//div[@id="missingGroups"]//select[@name="Segment"]';
        $I->seeElement($segment_selector);
        $I->seeOptionIsSelected($segment_selector, 'åk 2/3');

        $expected_vals_groups = $I->getGroupNumbersForSchool('vals');
        $criteria = [
            'fri_18' => ['School_id' => 'vals', 'Segment' => 'fri', 'StartYear' => 2018],
            '2_19' => ['School_id' => 'vals', 'Segment' => '2', 'StartYear' => 2019],
        ];


        $actual_before = [
            'fri_18' => $I->grabNumRecords('groups', $criteria['fri_18']),
            '2_19' => $I->grabNumRecords('groups', $criteria['2_19']),
        ];

        $I->assertNotSame($expected_vals_groups['2018']['fri'], $actual_before['fri_18']);
        $I->assertNotSame($expected_vals_groups['2019']['2'], $actual_before['2_19']);

        $I->click($missing_group_btn);
        $I->pause();

        $result_box = '//div[@id="missingGroups"]//div[@class="result-box"]';
        $result_strings = [
            'Tillagda grupper:',
            'Grupp',
            'åk 2/3',
            'Valstaskolan',
        ];
        $I->checkMultiple('see', $result_strings, $result_box);


        $actual_after = [
            'fri_18' => $I->grabNumRecords('groups', $criteria['fri_18']),
            '2_19' => $I->grabNumRecords('groups', $criteria['2_19']),
        ];


        $I->assertNotSame($expected_vals_groups['2018']['fri'], $actual_after['fri_18']);
        $I->assertSame($expected_vals_groups['2019']['2'], $actual_after['2_19']);

        $I->selectOption($segment_selector, 'Fritids');
        $I->click($missing_group_btn);
        $I->pause();

        $result_strings = [
            'Grupp',
            'Fritids',
            'Valstaskolan',
        ];
        $I->checkMultiple('see', $result_strings, $result_box);

        $last_after = [
            'fri_18' => $I->grabNumRecords('groups', $criteria['fri_18']),
            '2_19' => $I->grabNumRecords('groups', $criteria['2_19']),
        ];

        $I->assertSame($expected_vals_groups['2018']['fri'], $last_after['fri_18']);
        $I->assertSame($expected_vals_groups['2019']['2'], $last_after['2_19']);
    }

    // codecept run acceptance AdminCest:setColleagues --steps -f
    public function setColleagues(A $I)
    {
        $I->amOnPage('admin/batch/set_colleagues');
        $I->pause(0.7);

        $first_row = '//tr[@data-id="102"]';
        $I->seeElement($first_row);

        $I->assertEquals(0, $I->grabNumRecords('colleagues_visits'));

        $I->click($first_row . '//td[@data-colleague-id="1"]');
        $I->pause(1);

        $I->assertEquals(1, $I->grabNumRecords('colleagues_visits'));
    }


}
