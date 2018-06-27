<?php

use Carbon\Carbon;
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


    public function checkMultiple(string $function_name, array $elements = [], array $default_args = [])
    {
        foreach ($elements as $element) {
            $element = (array)$element;
            $element += $default_args;
            call_user_func_array([$this, $function_name], $element);
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
        if(mb_detect_encoding($subject, 'ASCII', true) === false){
            $subject = str_replace(' ', '_', $subject);
        }
        $this->seeInOpenedEmailSubject($subject);

    }

    public function seeInBody(...$parts)
    {
        foreach ($parts as $part) {
            if(mb_detect_encoding($part, 'ASCII', true) === false){
                //$part = quoted_printable_encode($part);
            }
            $this->seeInOpenedEmailBody($part);
        }
    }


    public function setTestDate(string $test_date = null)
    {
        $test_date = $test_date ?? $this->get('test_date');
        $this->amOnPage('/api/updateTestDate/' . htmlentities($test_date));
        $this->wait(2);
    }

    public function changeTestDate(string $modifier)
    {
        $current = Carbon::parse($this->get('test_date'));
        $modified = $current->modify($modifier);

        $this->setTestDate($modified->toIso8601String());
    }

    public function emptyTempFolder(array $exceptions = ['.gitignore'])
    {
        foreach($this->getFileNamesFromTempFolder() as $file){
            if(!in_array($file, $exceptions, true)){
               $this->deleteFile($file);
            }
        }
    }

    public function getFileNamesFromTempFolder()
    {
        $temp_dir = codecept_root_dir() . '/temp';
        return glob($temp_dir . '/*');
    }

}
