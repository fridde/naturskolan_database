<?php
/**
 * Contains the class Update.
 */

namespace Fridde;

use Fridde\Controller\LoginController;
use Fridde\Entities\Group;
use Carbon\Carbon;
use Fridde\Entities\GroupRepository;
use Fridde\Entities\Hash;
use Fridde\Entities\Location;
use Fridde\Entities\LocationRepository;
use Fridde\Entities\School;
use Fridde\Entities\SchoolRepository;
use Fridde\Security\Authenticator;


/**
 * Contains the logic to update records in the database.
 */
class Update extends DefaultUpdate
{
    /** @var Naturskolan $N */
    protected $N;

    /** @var array  */
    protected static $object_required = [
        'User' => ['School'],
        'Group' => ['User'],
        'Visit' => ['Group', 'Topic'],
    ];

    /* @var array */
    public const METHOD_ARGUMENTS = [
        'checkPassword' => ['password', 'school_id'],
        'addDates' => ['topic_id', 'dates'],
        'addDatesForMultipleTopics' => ['dates'],
        'setVisits' => ['value'],
        'setCookie' => ['school', 'url'],
        'removeCookie' => ['hash'],
        'logChange' => ['event', 'trackables'],
        'sliderUpdate' => ['entity_class', 'entity_id', 'property', 'value'],
        'updateVisitOrder' => ['order'],
        'updateBusRule' => ['school_id', 'location_id', 'needs_bus'],
        'confirmVisit' => ['visit_id'],
        'changeGroupName' => ['entity_id', 'value'],
        'batchSetGroupCount' => ['group_numbers', 'start_year'],
        'changeTaskActivation' => ['task_name', 'status'],
        'createMissingGroups' => ['segment'],
        'fillEmptyGroupNames' => ['segment'],
    ];

    /**
     * Update constructor.
     * @param array $request_data
     */
    public function __construct(array $request_data = [])
    {
        $this->N = $GLOBALS['CONTAINER']->get('Naturskolan');
        parent::__construct($request_data, $this->N->ORM);
    }

    public static function getMethodArgs(string $method_name, array $additional = [])
    {
        return parent::getMethodArgs($method_name, self::METHOD_ARGUMENTS);
    }

    /**
     * Checks if password corresponds to any school and saves the matching
     * school_id into $RETURN for the callback to receive.
     *
     * @param string $password
     * @return void
     */
    public function checkPassword(string $password, $school_id = null): void
    {
        $school = $this->N->Auth->getSchoolFromPassword($password);
        if(empty($school) || $school->getId() !== $school_id){
            $this->addError('Wrong password!');
            usleep(1000 * 2000); // to avoid brute force methods
            return;
        }
        $hash_string = $this->setCookie($school_id);
        $this->N->Auth->setSessionKey($hash_string);
    }

    /**
     * Creates new Visits having certain topic using the dates given.
     *
     * Expects $RQ['entity_id'] to contain the id of the topic and $RQ['value'] to be
     * the date array in the format ['YYYY-MM-DD', 'YYYY-MM-DD', ...]
     */
    public function addDates(int $topic_id, array $dates = [], $flush = true)
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}$/';
        $topic = $this->findById('Topic', $topic_id);
        $properties = ['Topic' => $topic];
        foreach ($dates as $date_string) {
            $date_string = trim($date_string);
            if(substr_count($date_string, ':') === 1){
                list(,$date) = explode(':', $date_string);
            } else {
                $date = $date_string;
            }
            if(preg_match($pattern, $date) !== 1){
                throw new \Exception('The date' . $date . 'didn\'t match ISO 8601.');
            }
            $properties['Date'] = $date;
            $this->createNewEntity('Visit', $properties, false);
        }
        if($flush){
            $this->flush();
        }
    }

    public function addDatesForMultipleTopics(array $dates = [])
    {
        $dates_by_topic = [];
        foreach($dates as $topic_date_string){
            $topic_date_array = explode(':', $topic_date_string);
            if(count($topic_date_array) !== 2){
                $this->addError('The string "' . $topic_date_string . '" has an invalid format.');
                return;
            }
            list($topic_id, $date) = $topic_date_array;
            $dates_by_topic[$topic_id][] = $date;
        }

        foreach($dates_by_topic as $topic_id => $date_array){
            $this->addDates($topic_id, $date_array, false);
        }
        $this->flush();
    }

    /**
     * @param array $big_array An array of
     * @throws \Exception
     */
    public function setVisits(array $big_array)
    {
        $row_to_group_translator = [];
        $group_dates = [];
        foreach ($big_array as $array) {
            foreach ($array as $row_index => $row) {
                $class_id = explode('_', $row);
                $class = $class_id[0];
                if (empty($class_id[1]) || $class_id[1] === 'null') {
                    $entity_id = null;
                } else {
                    $entity_id = $class_id[1];
                }
                if ($class === 'group') {
                    if (empty($entity_id)) {
                        $e_msg = 'Empty group given at row <'.$row_index;
                        $e_msg .= '>. This is not supposed to happen.';
                        throw new \Exception($e_msg);
                    }
                    $row_to_group_translator[$row_index] = $entity_id;
                } elseif ($class === 'visit') {
                    $group_dates[$row_index][] = $entity_id;
                } else {
                    throw new \Exception('The class <'.$class.'> is not implemented.');
                }
            }
        }
        foreach ($group_dates as $row_index => $visits) {
            $group_id = $row_to_group_translator[$row_index] ?? null;
            foreach ($visits as $visit_id) {
                if (isset($group_id, $visit_id)) {
                    $visit = $this->findById('Visit', $visit_id);
                    $group = $this->findById('Group', $group_id);
                    $visit->setGroup($group);
                    $this->ORM->EM->persist($visit);
                }
            }
        }
        $this->ORM->EM->flush();
    }

    protected function setCookie(string $school_id)
    {
        $category = Hash::CATEGORY_SCHOOL_COOKIE_KEY;
        $hash_string = $this->N->Auth->createAndSaveCode($school_id, $category);
        $exp_date = $this->N->Auth->getExpirationDate($category);
        $this->N->Auth->setCookieKeyInBrowser($hash_string, $exp_date);

        return $hash_string;
    }

    public function removeCookie(string $hash)
    {
        $login_controller = new LoginController();
        $login_controller->logout();

        //$cookie = $this->N->getRepo('Hash')->findByHash($hash);
        //$this->ORM->delete($cookie);
    }


    public function sliderUpdate($entity_class, $entity_id, $property, $value)
    {
        $this->setReturnFromRequest(['sliderId', 'sliderLabelId']);
        $this->setReturn('newValue', $value);

        return $this->updateProperty($entity_class, $entity_id, $property, $value)->flush();
    }

    public function updateVisitOrder(array $order)
    {
        foreach ($order as $index => $id) {
            $this->updateProperty('School', $id, 'VisitOrder', $index + 1);
        }

        return $this->flush();
    }

    public function confirmVisit(int $visit_id)
    {
        return $this->updateProperty('Visit', $visit_id, 'Confirmed', true);

    }


    public function changeGroupName($entity_id, $value)
    {
        $this->setReturn('groupId', $entity_id);
        $this->setReturn('newName', $value);
        $this->updateProperty('Group', $entity_id, 'Name', $value)->flush();
    }

    public function updateBusRule(string $school_id, int $location_id, bool $needs_bus)
    {
        /* @var SchoolRepository $school_repo  */
        /* @var LocationRepository $location_repo  */
        /* @var School $school  */
        /* @var Location $location  */
        $school_repo = $this->ORM->getRepository('School');
        $location_repo = $this->ORM->getRepository('Location');

        $school = $school_repo->find($school_id);
        $location = $location_repo->find($location_id);

        $school->updateBusRule($location, $needs_bus);

        return $this->flush();
    }

    /**
     * @param string $segment_id
     * @param int $start_year
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createMissingGroups(string $segment_id, int $start_year = null)
    {
        $all_schools = $this->N->getRepo('School')->findAll();
        /* @var \Fridde\Entities\School $school */
        foreach ($all_schools as $school) {
            $actual_count = $school->getActiveGroupsBySegmentAndYear($segment_id, $start_year);
            $expected_count = $school->getGroupNumber($segment_id, $start_year);
            $diff = $expected_count - $actual_count;

            for ($i = 0; $i < $diff; $i++) {
                $group = new Group();
                $group->setName();
                $group->setSchool($school);
                $group->setSegment($segment_id);
                $group->setStartYear($start_year);
                $group->setStatus(1);
                $this->ORM->EM->persist($group);
            }

            if ($diff < 0) { // too many groups
                // TODO: log this situation somewhere
            }
        }
        $this->ORM->EM->flush();
    }

    public function fillEmptyGroupNames(string $segment_id, int $start_year = null)
    {
        /* @var GroupRepository $group_repo  */
        $group_repo = $this->N->ORM->getRepository('Group');

        $start_year = $start_year ?? Carbon::today()->year;
        $criteria = [['Segment', $segment_id], ['StartYear', $start_year]];
        $groups = $group_repo->selectAnd($criteria);
        $groups_without_name = array_filter(
            $groups,
            function (Group $g) {
                return !$g->hasName();
            }
        );
        array_walk(
            $groups_without_name,
            function (Group &$g) {
                $g->setName();
            }
        );
        $this->N->ORM->EM->flush();
    }

    /**
     * @param array $group_numbers An array of strings where each string contains 3
     *        comma-separated values: The school_id, the segment and the new number of groups
     * @param int|null $start_year
     */
    public function batchSetGroupCount(array $group_numbers, int $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;
        foreach ($group_numbers as [$school_id, $segment_id, $count]) {
            /* @var School $school */
            $school = $this->N->ORM->find('School', $school_id);
            $school->setGroupNumber($segment_id, $count, $start_year);
        }
        $this->ORM->EM->flush();
    }

    public function changeTaskActivation(string $task_name, $status)
    {
        $status = (int) in_array($status, [1, 'true', true], true);
        $this->N->setCronTask($task_name, $status);
    }


}
