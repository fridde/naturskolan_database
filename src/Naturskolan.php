<?php
/**
 * This file contains the Naturskolan class that acts as a basic helper class for the Naturskolan-Database app
 */

namespace Fridde;

use Fridde\Entities\Cookie;
use Fridde\Entities\SystemStatus;
use Fridde\Entities\User;
use Fridde\PasswordHandler as PW;
use Fridde\Utility as U;
use Carbon\Carbon;
use dotzero\Googl;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

/**
 * The basic class to assist most other classes in the Naturskolan-Database application
 */
class Naturskolan
{
    /** @var \Fridde\ORM A doctrine ORM wrapped in own class */
    public $ORM;
    /** @var \GuzzleHttp\Client Contains an instance of GuzzleHttp\Client for HTTP requests */
    private $Client;
    /** @var \dotzero\Googl An instance of Googl to create short-links */
    private $Googl;
    /** @var \Fridde\PasswordHandler Instance of PasswordHandler */
    private $PW;
    /* @var Authorization $Auth  */
    public $Auth;
    /** @var array A text array containing labels and other text bits */
    private $text_array;
    /** @var string the path for the text pieces */
    private $text_path = 'config/labels.yml';

    /**
     * Constructor
     *
     * Creates an instance of $ORM and $PasswordHandler
     */
    public function __construct()
    {
        $this->ORM = new ORM();
        $this->PW = new PW();
        $this->Auth = new Authorization($this->ORM, $this->PW);
        $this->ORM->EM->getEventManager()->addEventSubscriber(new EntitySubscriber());
    }

    /**
     * Wrapper for Naturskolan->ORM->getRepository()
     *
     * @param  string $repo The (non-qualified-) name of the class of entities
     * @return mixed The repository
     */
    public function getRepo(string $repo)
    {
        return $this->ORM->getRepository($repo);
    }

    /**
     * Get a certain value from SETTINGS using a chain of indices
     *
     * @param  string|array $indices The array-path to the setting. Can either be ONE array or
     *                               several strings.
     * @return mixed The value retrieved from the settings
     */
    public static function getSetting(...$indices)
    {
        if (count($indices) === 1 && is_array($indices[0])) {
            $indices = $indices[0];
        }

        return U::resolve(SETTINGS, $indices);
    }

    /**
     * Retrieve a value from the SystemStatus table using its id.
     *
     * @param  string $id The id of the SystemStatus value.
     *
     * @return string|null The value of the SystemStatus row. Returns null if row not exists,
     *                     or is an empty string.
     */
    public function getStatus($id)
    {
        $status = $this->ORM->find('SystemStatus', $id);
        if (!empty($status)) {
            return $status->getValue();
        }

        return null;
    }

    public function setStatus(string $id, string $value, bool $flush_after = true)
    {
        $status = $this->ORM->find('SystemStatus', $id);
        if(empty($status)){
            $status = new SystemStatus();
            $status->setId($id);
        }
        $status->setValue($value);
        $this->ORM->EM->persist($status);

        if($flush_after){
            $this->ORM->EM->flush();
        }

    }

    /**
     * @param string $task
     * @return null|Carbon
     */
    public function getLastRun(string $task)
    {
        $last_run = $this->getStatus('last_run.' . $task);
        if(!empty($last_run)){
            return Carbon::parse($last_run);
        }
        return null;
    }

    public function setLastRun(string $task)
    {
       $this->setStatus('last_run.'. $task, Carbon::now()->toIso8601String());
    }

    /**
     * Shorthand function to set a value for a certain entity in a certain repository.
     *
     * @param mixed $requests The function takes either 3-4 arguments corresponding to
     *                        $repo, $id, $value and $attribute_name (with default 'Value').
     *                        Or it takes ONE array which in itself consists of one or more
     *                        arrays with each exactly 3-4 elements. The elements can either
     *                        be in right order or indexed with 'repo', 'id', 'value' and 'att_name'.
     *
     * @return void
     */
    public function set(...$requests)
    {
        if (count($requests) === 1 && is_array($requests[0])) {
            $requests = $requests[0];
        } else {
            $requests = [$requests];
        }

        foreach ($requests as $args) {
            $repo = $args[0] ?? $args['repo'];
            $id = $args[1] ?? $args['id'];
            $value = $args[2] ?? $args['value'];
            $attribute_name = $args[3] ?? ($args['att_name'] ?? 'Value');
            $e = $this->ORM->getRepository($repo)->find($id);
            $method = 'set'.$attribute_name;
            if (!empty($e)) {
                $e->$method($value);
            } else {
                $msg = 'No entity of the class <'.$repo.'> with the id <';
                $msg .= $id.'> could be found.';
                throw new \Exception($msg);
            }
        }
    }

    public function setAndFlush(...$requests)
    {
        $this->set(...$requests);
        $this->ORM->EM->flush();
    }

    /**
     * Execute a certain database update without specifying any parameters.
     *
     * @param  string $shorthand The type of update to perform.
     * @return void
     */
    public function quickSet($shorthand)
    {
        switch ($shorthand) {

            default:
                throw new \Exception('The parameter '.$shorthand.' is not defined.');
                break;
        }
    }

    /**
     * Wrapper function to set the calendar.status in SystemStatus
     */
    public function setCalendarTo(string $new_status, bool $flush_after = false)
    {

        if($new_status === SystemStatus::CLEAN){
            $now_string = Carbon::now()->toIso8601String();
            $this->set('SystemStatus', 'calendar.last_rebuild', $now_string);
        }
        if($flush_after){
            $this->ORM->EM->flush();
        }
    }

    /**
     * Check to see if calendar should be updated.
     *
     * @return boolean Returns true if the value for calendar.status in SystemStatus is 'dirty'
     */
    public function calendarIsDirty()
    {
        $trackables = ['Group', 'Location', 'Topic', 'User', 'Visit'];
        $last_rebuild = $this->getStatus('calendar.last_rebuild');
        if(empty($last_rebuild)){
            return true;
        }
        $selector = ['gt', 'LastChange', $last_rebuild];

        foreach($trackables as $entity_type){
            if(count($this->ORM->getRepository($entity_type)->select([$selector])) > 0){
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the cron_tasks.activation from SystemStatus and returns it as an array.
     * The array deactivates certain Tasks to help debug or to be able to tinker
     * without being interrupted by the cron task.
     *
     * @return null|integer[] An array which each task name as index and either 0 or 1 as
     *                        deactivated or activated. Fridde\Task assumes a task as
     *                        'activated' if not present in this array.
     *
     */
    public function getCronTaskActivationStatus()
    {
        $val = $this->getStatus('cron_tasks.activation');
        if (!empty($val)) {
            return json_decode($val, true);
        }

        return null;
    }

    public function setCronTask(string $task_name, $status)
    {
        $cron_tasks = $this->getCronTaskActivationStatus() ?? [];
        $cron_tasks[$task_name] = $status;
        $this->setStatus('cron_tasks.activation', json_encode($cron_tasks));
    }

    /**
     * Gets the time the calendar was rebuilt the last time.
     *
     * @return \Carbon\Carbon DateTime of last build
     */
    public function getLastRebuild()
    {
        $last_rebuild = $this->getStatus('calendar.last_rebuild');
        return (empty($last_rebuild) ? null : new Carbon($last_rebuild));
    }

    /**
     * Creates a password for a certain school for the current year.
     *
     * @param  string $school_id The id of the school.
     * @return string            The password
     */
    public function createPassword($school_id, $custom_salt = false)
    {
        return $this->PW->createPassword($school_id, $custom_salt);
    }

    public function isAuthorizedFor(string $school_id)
    {
        return $this->Auth->getSchooldIdFromCookie() === $school_id;
    }

    /**
     *
     *
     * @param  User $user The User object
     * @return string       The url a user can click to reach the school page
     *                      without writing any password.
     */
    public function createLoginUrl(User $user, string $page = 'staff', $absolute = true)
    {
        $params['school'] = $user->getSchoolId();
        $params['page'] = $page;
        $params['code'] = $this->PW->createCodeFromInt($user->getId(), 'user');

        return $this->generateUrl('school', $params, $absolute);
    }

    private function getGoogl()
    {
        if (empty($this->Googl)) {
            $api_key = SETTINGS['sms_settings']['google_api_key'];
            $this->Googl = new Googl($api_key);
        }

        return $this->Googl;
    }

    public function shortenUrl($url)
    {
        if(DEBUG ?? false){
            return $url;
        }
        return $this->getGoogl()->shorten($url);
    }

    public function getIntFromCode($code, $entropy = '')
    {
        return $this->PW->getIntFromCode($code, $entropy);
    }

    public function createConfirmationUrl($visit_id, $absolute = false)
    {
        $params['parameters'] = $this->PW->createCodeFromInt($visit_id, 'visit');
        $params['action'] = 'confirmVisit';

        return $this->generateUrl('api', $params, $absolute);
    }

    /**
     * @param string $school_id_password
     * @return string|bool $school_id or false if none is found
     */
    public function checkPassword(string $password, $school_id = null)
    {
        if (0 === strpos($password, 'user$')) {
            $code = substr($password, 5);
            $user = $this->Auth->getUserFromCode($code);
            if (!empty($user)) {
                return $user->getSchoolId();
            }
        } else {
            return $this->PW->checkPasswordForSchool($school_id, $password);
        }
    }

    public function createHash(string $extra_entropy = '')
    {
        $hash_string = password_hash(microtime() . $extra_entropy, PASSWORD_DEFAULT);
        $hash_array = explode('$', $hash_string);

        return implode('', array_slice($hash_array, 3));
    }

    public function setCookieHash(string $school_id, int $rights = Cookie::RIGHTS_SCHOOL_ONLY)
    {
        $update = new Update();
        $update->setCookie($school_id);
    }

    public function generateUrl($route_name, array $params = [], bool $absolute = false)
    {
        /* @var \AltoRouter $router */
        $router = $GLOBALS['CONTAINER']->get('Router');
        $url = $router->generate($route_name, $params);
        if($absolute && !empty(SETTINGS['debug']['base_path'])){
            $url = SETTINGS['debug']['base_path'] . $url;
        }

        return $url;
    }

    /**
     * @param string $url
     * @param array $post_data
     * @param bool $use_api_key
     * @param bool $debug
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendRequest(string $url, array $post_data = [], bool $use_api_key = true, bool $debug = false)
    {
        $options = ['json' => $post_data, 'cookies' => null, 'debug' => false];

        if ($use_api_key) {
            $options['json']['api_key'] = $this->getApiKey();
        }
        if ($debug || ($GLOBALS['debug'] ?? false)) {
            $options['cookies'] = CookieJar::fromArray(['XDEBUG_SESSION' => 'xdebug.api'], 'localhost');
            $options['json']['XDEBUG_SESSION_START'] = 'api';
            $options['debug'] = true;
            $url .= '?XDEBUG_SESSION_START=api';
        }

        usleep(100 * 1000); // = 0.1 seconds to not choke the server

        return $this->getClient()->post($url, $options);
    }

    public function getApiKey()
    {
        return SETTINGS['values']['api_key'] ?? null;
    }

    public function getClient($new_client = false)
    {
        if (empty($this->Client) || $new_client) {
            $this->Client = new Client(['base_uri' => APP_URL]);
        }

        return $this->Client;
    }


    /**
     * [Summary].
     *
     * [Description]
     *
     * @param array $unformatted_array multi-array where every row comprises one row from a certain table. Each row should at least contain 'id', and the needed columns
     *
     * @return array $return_array contains array of formatted strings using the id as keys
     */
    public function format($unformatted_array, $type = '')
    {
        $format = function ($v, $k) use ($type) {
            switch ($type) {

                case 'User':
                    $r = $v->getFullName().', '.$v->getSchool()->getName();
                    break;

                case 'Group':
                    $r = $v->hasName() ? $v->getName() : $v->getPlaceholderName();
                    $r .= ', '.$v->getSchool->getName();
                    break;

                case 'Topic':
                    $r = $v->getGrade().'.'.$v->getVisitOrder();
                    $r .= ' '.$v->getShortName();
                    break;

                case 'Colleague':
                    $r = $v->getAcronym();
                    break;

                case 'School':
                    $r = $v->getName();
                    break;

                case 'Location':
                    $r = $v->getName();
                    break;

                default:
                    $r = $v;
                    $custom_id = $k;
            }
            $id = $custom_id ?? $v->getId();

            return [$r, $id];
        };
        $formatted_array = array_map($format, $unformatted_array, array_keys($unformatted_array));

        return $formatted_array;
    }

    public function getTimestamp()
    {
        return Carbon::now()->toAtomString();
    }

    private function setTextArrayfromFile($path = null)
    {
        $path = $path ?? $this->text_path;
        $this->text_array = Settings::getArrayFromFile($path);
        return $this->text_array;
    }

    public function getText($index, string $file_path = null)
    {
        if (empty($this->text_array) || !empty($file_path)) {
            $this->setTextArrayfromFile($file_path);
        }
        $text = U::resolve($this->text_array, $index);
        if (!(isset($text) && is_string($text))) {
            $e_msg = 'The path given couldn\'t be resolved to a valid string. The path: ';
            $e_msg .= var_export($index, true);
            throw new \InvalidArgumentException($e_msg);
        }

        return $text;
    }

    public function getTextArray(string $index = null)
    {
        $text = $this->text_array ?? $this->setTextArrayfromFile();
        return (empty($index) ? $text : U::resolve($text, $index));
    }

    public function getReplacedText($index, array $replacements = [], string $file_path = null)
    {
        $text = $this->getText($index, $file_path);
        $search = $rep = [];
        foreach ($replacements as $key => $val) {
            $search[] = '%%'.$key.'%%';
            $rep[] = $val;
        }

        return str_replace($search, $rep, $text);
    }

    /**
     * @param string $msg
     * @param string|null $source
     */
    public function log(string $msg, string $source = null)
    {
        $GLOBALS['CONTAINER']->get('Logger')->addInfo($msg, ['source' => $source]);
    }

}
