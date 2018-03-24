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

    private $settings;

    public function get(...$keys)
    {
        if (empty($this->settings)) {
            $this->settings = Yaml::parseFile(__DIR__.'/../acceptance/settings.yml');
        }
        $array = $this->settings;
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

    public function seeInSubject(string $string_of_words)
    {
        $word_array = explode(' ', $string_of_words);
        foreach ($word_array as $word) {
            $this->seeInOpenedEmailSubject($word);
        }
    }

    public function seeInBody(...$parts)
    {
        foreach ($parts as $part) {
            $this->seeInOpenedEmailBody($part);
        }
    }

    public function setTestDate()
    {
        Carbon::setTestNow(Carbon::parse($this->get('test_date')));
    }
}
