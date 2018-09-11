<?php

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
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Security\Authenticator;


/**
 * Contains the logic to update records in the database.
 */
class Update extends DefaultUpdate
{
    /** @var Naturskolan $N */
    protected $N;

    /**
     * Update constructor.
     * @param array $request_data
     */
    public function __construct(array $request_data = [])
    {
        $this->N = $GLOBALS['CONTAINER']->get('Naturskolan');
        parent::__construct($request_data, $this->N->ORM);
    }


    /**
     * @param mixed ...$args
     * @return Update
     *
     * @PostArgs("entity_class, entity_id, property, value")
     * @SecurityLevel(SecurityLevel::ACCESS_ALL_EXCEPT_GUEST)
     * @NeedsSameSchool
     */
    public function updateProperty(string $entity_class, $entity_id, string $property, $value)
    {
        parent::updateProperty($entity_class, $entity_id, $property, $value);

        return $this;
    }

    /**
     * @param array $array_of_updates
     * @return $this
     *
     * @PostArgs("array_of_updates")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function batchUpdateProperties(array $array_of_updates)
    {
        parent::batchUpdateProperties($array_of_updates);

        return $this;
    }

    /**
     * @param string $entity_class
     * @param array $properties
     * @param bool $flush
     * @return $this
     *
     * @PostArgs("entity_class, properties")
     * @SecurityLevel(SecurityLevel::ACCESS_ALL_EXCEPT_GUEST)
     * @NeedsSameSchool
     */
    public function createNewEntity(string $entity_class, array $properties = [], bool $flush = true)
    {
        parent::createNewEntity($entity_class, $properties, $flush);

        return $this;
    }


    /**
     * Checks if password corresponds to any school and saves the matching
     * school_id into $RETURN for the callback to receive.
     *
     * @param string $password
     * @return void
     *
     * @PostArgs("password, school_id")
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function checkPassword(string $password, $school_id = null): void
    {
        if (empty($school_id) || ! $this->N->Auth->schoolHasPassword($school_id, $password)) {
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
     * Expects $RQ['topic_id'] to contain the id of the topic and $RQ['dates'] to be
     * the date array in the format ['YYYY-MM-DD', 'YYYY-MM-DD', ...]
     *
     * @PostArgs("topic_id, dates")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function addDates(int $topic_id, array $dates = [], $flush = true)
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}$/';
        $topic = $this->findById('Topic', $topic_id);
        $properties = ['Topic' => $topic];
        foreach ($dates as $date_string) {
            $date_string = trim($date_string);
            if (substr_count($date_string, ':') === 1) {
                $date = array_pop($x = explode(':', $date_string));
            } else {
                $date = $date_string;
            }
            if (preg_match($pattern, $date) !== 1) {
                throw new NException(Error::WRONG_FORMAT, [$date, 'ISO 8601']);
            }
            $properties['Date'] = $date;
            $this->createNewEntity('Visit', $properties, false);
        }
        if ($flush) {
            $this->flush();
        }
    }

    /**
     * @param array $dates
     * @throws \Exception
     *
     * @PostArgs("dates")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function addDatesForMultipleTopics(array $dates = [])
    {
        $dates_by_topic = [];
        foreach ($dates as $topic_date_string) {
            $topic_date_array = explode(':', $topic_date_string);
            if (count($topic_date_array) !== 2) {
                $this->addError('The string "'.$topic_date_string.'" has an invalid format.');

                return;
            }
            [$topic_id, $date] = $topic_date_array;
            $dates_by_topic[$topic_id][] = $date;
        }

        foreach ($dates_by_topic as $topic_id => $date_array) {
            $this->addDates($topic_id, $date_array, false);
        }
        $this->flush();
    }

    /**
     * @param array $big_array An array of
     *
     * @PostArgs("value")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function distributeVisits(array $big_array)
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
                        throw new NException(Error::LOGIC, ['Empty group at row ' . $row_index]);
                    }
                    $row_to_group_translator[$row_index] = $entity_id;
                } elseif ($class === 'visit') {
                    $group_dates[$row_index][] = $entity_id;
                } else {
                    throw new NException(Error::LOGIC, [$class . ' not implemented']);
                }
            }
        }
        foreach ($group_dates as $row_index => $visits) {
            $group_id = $row_to_group_translator[$row_index] ?? null;
            foreach ($visits as $visit_id) {
                if (isset($group_id, $visit_id)) {
                    $visit = $this->findById('Visit', $visit_id);
                    $group = $this->findById('Group', $group_id);
                    /** @noinspection NullPointerExceptionInspection */
                    $visit->setGroup($group);
                    $this->ORM->EM->persist($visit);
                }
            }
        }
        $this->ORM->EM->flush();
    }

    /**
     * @param string $school_id
     * @return string
     *
     * @PostArgs("school, url")
     */
    protected function setCookie(string $school_id)
    {
        $category = Hash::CATEGORY_SCHOOL_COOKIE_KEY;
        $hash_string = $this->N->Auth->createAndSaveCode($school_id, $category);
        $exp_date = $this->N->Auth->getExpirationDate($category);
        $this->N->Auth->setCookieKeyInBrowser($hash_string, $exp_date);

        return $hash_string;
    }

    /**
     * @param string $hash
     *
     * @PostArgs("hash")
     */
    public function removeCookie(string $hash)
    {
        // TODO: clean up this function. why hash?
        $login_controller = new LoginController();
        $login_controller->logout();
    }


    /**
     * @param $entity_class
     * @param $entity_id
     * @param $property
     * @param $value
     * @return $this
     *
     * @PostArgs("entity_class, entity_id, property, value")
     * @SecurityLevel(SecurityLevel::ACCESS_ALL_EXCEPT_GUEST)
     * @NeedsSameSchool
     */
    public function sliderUpdate($entity_class, $entity_id, $property, $value)
    {
        $this->setReturnFromRequest(['sliderId', 'sliderLabelId']);
        $this->setReturn('newValue', $value);

        return $this->updateProperty($entity_class, $entity_id, $property, $value)->flush();
    }

    /**
     * @param array $order
     * @return $this
     *
     * @PostArgs("order, entity_class")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function updateVisitOrder(array $order, string $entity_class)
    {
        foreach ($order as $index => $id) {
            $this->updateProperty($entity_class, $id, 'VisitOrder', $index + 1);
        }

        return $this->flush();
    }

    /**
     * @param int $visit_id
     * @return $this
     *
     * @PostArgs("visit_id")
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function confirmVisit(int $visit_id)
    {
        return $this->updateProperty('Visit', $visit_id, 'Confirmed', true);

    }


    /**
     * @param $entity_id
     * @param $value
     *
     * @PostArgs("entity_id, value")
     * @SecurityLevel(SecurityLevel::ACCESS_ALL_EXCEPT_GUEST)
     * @NeedsSameSchool
     */
    public function changeGroupName($entity_id, $value)
    {
        $this->setReturn('groupId', $entity_id);
        $this->setReturn('newName', $value);
        $this->updateProperty('Group', $entity_id, 'Name', $value)->flush();
    }

    /**
     * @param string $school_id
     * @param int $location_id
     * @param bool $needs_bus
     * @return $this
     *
     * @PostArgs("school_id, location_id, needs_bus")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function updateBusRule(string $school_id, int $location_id, bool $needs_bus)
    {
        /* @var SchoolRepository $school_repo */
        /* @var LocationRepository $location_repo */
        /* @var School $school */
        /* @var Location $location */
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
     *
     * @PostArgs("segment")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function createMissingGroups(string $segment_id, int $start_year = null): void
    {
        $all_schools = $this->N->getRepo('School')->findAll();
        $start_years = (array)$start_year;
        if (empty($start_years)) {
            $this_year = Carbon::today()->year;
            $start_years = [$this_year, $this_year + 1];
        }

        $added_groups = [];
        foreach ($start_years as $year) {
            /* @var \Fridde\Entities\School $school */
            foreach ($all_schools as $school) {
                $actual_count = $school->getNrActiveGroupsBySegmentAndYear($segment_id, $year);
                $expected_count = $school->getGroupCountNumber($segment_id, $year);
                $diff = $expected_count - $actual_count;

                for ($i = 0; $i < $diff; $i++) {
                    $group = new Group();
                    $group->setName('Grupp ' .  Naturskolan::getRandomAnimalName());
                    $group->setSchool($school);
                    $group->setSegment($segment_id);
                    $group->setStartYear($year);
                    $group->setStatus(Group::ACTIVE);
                    $this->ORM->EM->persist($group);

                    $label = $group->getName() . ', ';
                    $label .= $group->getSegmentLabel() . ', ';
                    $label .= $group->getSchool()->getName();
                    $added_groups[] = $label;
                }
            }
        }
        $this->ORM->EM->flush();

        $this->setReturn('added_groups', $added_groups);
    }

    /**
     * @param string $segment_id
     * @param int|null $start_year
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @PostArgs("segment")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function fillEmptyGroupNames(string $segment_id, int $start_year = null)
    {
        /* @var GroupRepository $group_repo */
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
                $g->setName('Grupp ' .  Naturskolan::getRandomAnimalName());
            }
        );
        $this->N->ORM->EM->flush();
    }

    /**
     * @param array $group_numbers An array of strings where each string contains 3
     *        comma-separated values: The school_id, the segment and the new number of groups
     * @param int|null $start_year
     *
     * @PostArgs("group_numbers, start_year")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function batchSetGroupCount(array $group_numbers, int $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;
        foreach ($group_numbers as [$school_id, $segment_id, $count]) {
            /* @var School $school */
            $school = $this->N->ORM->find('School', $school_id);
            $school->setGroupCount($segment_id, $count, $start_year);
        }
        $this->ORM->EM->flush();
    }

    /**
     * @param string $task_name
     * @param $status
     *
     * @PostArgs("task_name, status")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function changeTaskActivation(string $task_name, $status)
    {
        $status = (int)in_array($status, [1, '1', 'true', true], true);
        $this->N->setCronTask($task_name, $status);
    }


}
