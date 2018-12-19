<?php
/**
 * This file contains the Naturskolan class that acts as a basic helper class for the Naturskolan-Database app
 */

namespace Fridde;

use Fridde\Entities\Group;
use Fridde\Entities\Hash;
use Fridde\Entities\Location;
use Fridde\Entities\Note;
use Fridde\Entities\School;
use Fridde\Entities\SystemStatus;
use Fridde\Entities\Topic;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Security\Authenticator;
use Fridde\Security\PasswordHandler as PWH;
use Fridde\Utility as U;
use Carbon\Carbon;
use dotzero\Googl;
use GuzzleHttp\Client;


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
    /* @var \Fridde\Security\Authenticator $Auth */
    public $Auth;
    /** @var array A text array containing labels and other text bits */
    private $text_array;
    /** @var string the path for the text pieces */
    private $text_path = 'config/labels.yml';

    public const ADMIN_SCHOOL = 'natu';

    /**
     * Constructor
     *
     * Creates an instance of $ORM and $PasswordHandler
     */
    public function __construct()
    {
        $this->ORM = new ORM();
        $this->Auth = new Authenticator($this->ORM, new PWH());
        $this->ORM->EM->getEventManager()->addEventSubscriber(new EntitySubscriber($this->ORM));
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
        if (empty($status)) {
            $status = new SystemStatus();
            $status->setId($id);
        }
        $status->setValue($value);
        $this->ORM->EM->persist($status);

        if ($flush_after) {
            $this->ORM->EM->flush();
        }

    }

    /**
     * @param string $task
     * @return null|Carbon
     */
    public function getLastRun(string $task): ?Carbon
    {
        $last_run = $this->getStatus('last_run.'.$task);
        if (!empty($last_run)) {
            return Carbon::parse($last_run);
        }

        return null;
    }

    public function setLastRun(string $task)
    {
        $this->setStatus('last_run.'.$task, Carbon::now()->toIso8601String());
    }



    /**
     * Check to see if calendar should be updated.
     */
    public function calendarIsDirty(): bool
    {
        $trackables = [Group::class, Location::class, Topic::class, User::class, Visit::class, Note::class];
        $last_rebuild = $this->getStatus('last_run.rebuild_calendar');
        if (empty($last_rebuild)) {
            return true;
        }
        $selector = ['gt', 'LastChange', $last_rebuild];

        foreach ($trackables as $entity_type) {
            if (count($this->ORM->getRepository($entity_type)->select([$selector])) > 0) {
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
     * @return integer[] An array which each task name as index and either 0 or 1 as
     *                        deactivated or activated. Fridde\Task assumes a task as
     *                        'activated' if not present in this array.
     *
     */
    public function getCronTaskActivationStatus(): array
    {
        $val = $this->getStatus('cron_tasks.activation');
        if (!empty($val)) {
            return json_decode($val, true);
        }

        return [];
    }

    public function setCronTask(string $task_name, $status)
    {
        $cron_tasks = $this->getCronTaskActivationStatus() ?? [];
        $cron_tasks[$task_name] = $status;
        $this->setStatus('cron_tasks.activation', json_encode($cron_tasks));
    }

    /**
     *
     *
     * @param  User $user The User object
     * @return string       The url a user can click to reach the school page
     *                      without writing any password.
     */
    public function createLoginUrl(User $user, bool $absolute = true)
    {
        $params['code'] = $this->Auth->createAndSaveCode($user->getId(), Hash::CATEGORY_USER_URL_CODE);

        return $this->generateUrl('login', $params, $absolute);
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
        if (DEBUG ?? false) {
            return $url;
        }

        return $this->getGoogl()->shorten($url);
    }


    public function createConfirmationUrl($visit_id, string $security = 'check_hash', $absolute = false)
    {
        if ($security === 'simple') {
            $code = $visit_id.'/simple';
        } elseif ($security === 'check_hash') {
            $code = $this->Auth->createAndSaveCode($visit_id, Hash::CATEGORY_VISIT_CONFIRMATION_CODE);
        } else {
            throw new NException(Error::INVALID_OPTION, ['security']);
        }

        $params['parameters'] = $code;
        $params['action'] = 'confirmVisit';

        return $this->generateUrl('api', $params, $absolute);
    }

    /**
     * @param string $school_id_password
     * @return string|bool $school_id or false if none is found
     */
    public function checkPasswordForSchool(string $password, string $school_id)
    {
        $school = $this->Auth->getSchoolFromPassword($password);
        if (empty($school)) {
            return false;
        }

        return $school->getId() === $school_id;
    }

    public function generateUrl(string $route_name, array $params = [], bool $absolute = true)
    {
        /* @var Router $router */
        $router = $GLOBALS['CONTAINER']->get('Router');
        return $router->generate($route_name, $params, $absolute);
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
                    $r = $v->getSegment().'.'.$v->getVisitOrder();
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
        if (empty($text) || !is_string($text)) {
            throw new NException(Error::NOT_RESOLVABLE, [var_export($index, true)]);
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
     * @param mixed $source
     */
    public function log(string $msg, $source = '')
    {
        if (is_array($source)) {
            $source = $source[0].'->'.$source[1].'()';
        }
        $extra['source'] = $source;
        $extra['datetime'] = Carbon::now()->toIso8601String();

        $GLOBALS['CONTAINER']->get('Logger')->addInfo($msg, $extra);
    }


    /**
     * @return School|object
     */
    public function getAdminSchool()
    {
        return $this->ORM->find('School', self::ADMIN_SCHOOL);
    }

    public static function isAdminSchool(School $school = null): bool
    {
        if (empty($school)) {
            return false;
        }

        return $school->getId() === self::ADMIN_SCHOOL;
    }

    public static function getRandomAnimalName()
    {
        $alias_names = self::getSetting('defaults', 'placeholder', 'animals');

        return $alias_names[random_int(0, count($alias_names) - 1)];
    }

}
