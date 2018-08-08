<?php

use Carbon\Carbon;
use Codeception\Util\Locator;
use Symfony\Component\Yaml\Yaml;


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */

    private $test_items;

    private $wait_time = 4.0;

    public function get(...$keys)
    {
        if (empty($this->test_items)) {
            $this->test_items = Yaml::parseFile(__DIR__.'/../acceptance/test_items.yml');
        }
        $array = $this->test_items;
        foreach ($keys as $key) {
            $array = &$array[$key];
        }

        return ($array ?? null);
    }

    /**
     * @return float
     */
    public function getWaitTime(): float
    {
        return $this->wait_time;
    }

    /**
     * @param float $wait_time
     */
    public function setWaitTime(float $wait_time): void
    {
        $this->wait_time = $wait_time;
    }

    public function pause(float $wait_factor = 1.0)
    {
        return $this->wait($this->wait_time * $wait_factor);
    }


    public function checkMultiple(string $function_name, array $elements = [], ...$extra_args)
    {
        foreach ($elements as $element) {
            $args = array_merge((array)$element, $extra_args);
            call_user_func_array([$this, $function_name], $args);
        }
    }

    public function checkMultipleEmails(array $mails)
    {
        array_walk($mails, [$this, 'checkEmail']);
    }

    public function checkEmail(array $mail_parts)
    {
        $this->openNextUnreadEmail();
        foreach ($mail_parts as $part => $value) {
            switch ($part) {
                case 'sub':
                    $this->seeInSubject($value);
                    break;
                case 'from':
                    $this->seeInOpenedEmailSender($value);
                    break;
                case 'to':
                    $this->seeInOpenedEmailRecipients($value);
                    break;
                case 'body':
                    $this->seeInBody(...$value);
                    break;
            }
        }
    }

    public function seeInSubject(string $subject)
    {
        if (mb_detect_encoding($subject, 'ASCII', true) === false) {
            $subject = str_replace(' ', '_', $subject);
        }
        $this->seeInOpenedEmailSubject($subject);

    }

    public function seeInBody(...$parts)
    {
        foreach ($parts as $part) {
            if (mb_detect_encoding($part, 'ASCII', true) === false) {
                //$part = quoted_printable_encode($part);
            }
            $this->seeInOpenedEmailBody($part);
        }
    }


    public function setTestDate(string $test_date = null)
    {
        $test_date = $test_date ?? $this->get('test_date');
        $this->amOnPage('/api/updateTestDate/'.htmlentities($test_date));
        $this->pause(0.7);
    }

    public function changeTestDate(string $modifier)
    {
        $current = Carbon::parse($this->get('test_date'));
        $modified = $current->modify($modifier);

        $this->setTestDate($modified->toIso8601String());
    }

    public function emptyFolder(string $folder, array $exceptions = ['.gitignore'])
    {
        foreach ($this->getFileNamesFromFolder($folder) as $file) {
            if (!in_array($file, $exceptions, true)) {
                $this->deleteFile($file);
            }
        }
    }

    public function getFileNamesFromFolder(string $folder)
    {
        $dir_path = codecept_root_dir().'/'.$folder;

        return glob($dir_path.'/*');
    }

    public function getAddRowButton()
    {
        return Locator::find('button', ['id' => 'add-row-btn']);
    }

    public function getTableRows(string $entity)
    {
        $row_path = '//table[@data-entity="'.$entity.'"]//tbody//tr';

        return $this->grabMultiple($row_path);
    }

    public function getFieldFromLastRow(string $entity, string $name, string $element_type = 'input')
    {
        $path = '//table[@data-entity="';
        $path .= ucfirst($entity);
        $path .= '"]//tbody//tr[last()]//';
        $path .= $element_type.'[@name="';
        $path .= ucfirst($name);
        $path .= '"]';

        return $path;
    }

    public function runCronTask(string $task)
    {

        $cron_tasks = array_keys($this->get('cron_items'));
        if (!in_array($task, $cron_tasks, true)) {
            throw new \Exception('The task "'.$task.'" was not defined in the test settings');
        }

        $this->amOnPage('/admin');
        foreach ($cron_tasks as $cron_task) {
            $path = '//input[@name="'.$cron_task.'"]';
            if ($cron_task === $task) {
                $this->checkOption($path);
            } else {
                $this->uncheckOption($path);
            }
        }
        $this->runActivatedCronTasks();
    }

    public function runActivatedCronTasks()
    {
        $this->amOnPage('/cron/');
        $this->pause(0.7);
    }

    public function seeStringsInThisFile(array $strings)
    {
        foreach ($strings as $string) {
            $this->seeInThisFile($string);
        }
    }

    public function clickAway(int $dx = 0, int $dy = -50)
    {
        return $this->clickWithLeftButton(null, $dx, $dy);
    }

    public function getGroupNumbersForSchool(string $school_id): ?array
    {
        $group_numbers = $this->grabColumnFromDatabase('schools', 'GroupNumbers', ['id' => $school_id]);

        return json_decode($group_numbers[0], true);
    }

}
