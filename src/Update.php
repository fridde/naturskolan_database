<?php
/**
 * Contains the class Update.
 */

namespace Fridde;

use Fridde\Entities\Group;
use Fridde\Entities\Cookie;
use Carbon\Carbon;
use Fridde\Entities\School;


/**
 * Contains the logic to update records in the database.
 */
class Update extends DefaultUpdate
{
    /** @var Naturskolan $N */
    protected $N;

    /** @var array $object_required */
    protected $object_required = [
        "User" => ["School"],
        "Group" => ["User"],
        'Visit' => ['Group', 'Topic'],
    ];

    /* @var array */
    public const METHOD_ARGUMENTS = [
        "checkPassword" => ["password"],
        "addDates" => ["topic_id", "dates"],
        'addDatesForMultipleTopics' => ['dates'],
        "setVisits" => ["value"],
        "setCookie" => ["school", "url"],
        "removeCookie" => ["hash"],
        "logChange" => ["event", "trackables"],
        "sliderUpdate" => ["entity_class", "entity_id", "property", "value"],
        "updateVisitOrder" => ["order"],
        "confirmVisit" => ["visit_id"],
        "changeGroupName" => ["entity_id", "value"],
        "batchSetGroupCount" => ['group_numbers', "start_year"],
        'changeTaskActivation' => ['task_name', 'status'],
        'createMissingGroups' => ['grade'],
        'fillEmptyGroupNames' => ['grade'],
    ];

    /**
     * Update constructor.
     * @param array $request_data
     */
    public function __construct(array $request_data = [])
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
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
    public function checkPassword(string $password)
    {
        $school_id = $this->N->checkPassword($password);
        if ($school_id) {
            $this->setReturn("school", $school_id);
        } else {
            $this->addError("Wrong password!");
        }

    }

    /**
     * Creates new Visits having certain topic using the dates given.
     *
     * Expects $RQ["entity_id"] to contain the id of the topic and $RQ["value"] to be
     * the date array in the format ["YYYY-MM-DD", "YYYY-MM-DD", ...]
     */
    public function addDates(int $topic_id, array $dates = [], $flush = true)
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}$/';
        $topic = $this->findById("Topic", $topic_id);
        $properties = ["Topic" => $topic];
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
            $properties["Date"] = $date;
            $this->createNewEntity("Visit", $properties, false);
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
                $class_id = explode("_", $row);
                $class = $class_id[0];
                if (empty($class_id[1]) || $class_id[1] === "null") {
                    $entity_id = null;
                } else {
                    $entity_id = $class_id[1];
                }
                if ($class === "group") {
                    if (empty($entity_id)) {
                        $e_msg = 'Empty group given at row <'.$row_index;
                        $e_msg .= '>. This is not supposed to happen.';
                        throw new \Exception($e_msg);
                    }
                    $row_to_group_translator[$row_index] = $entity_id;
                } elseif ($class === "visit") {
                    $group_dates[$row_index][] = $entity_id;
                } else {
                    throw new \Exception("The class <".$class."> is not implemented.");
                }
            }
        }
        foreach ($group_dates as $row_index => $visits) {
            $group_id = $row_to_group_translator[$row_index] ?? null;
            foreach ($visits as $visit_id) {
                if (isset($group_id, $visit_id)) {
                    $visit = $this->findById("Visit", $visit_id);
                    $group = $this->findById("Group", $group_id);
                    $visit->setGroup($group);
                    $this->ORM->EM->persist($visit);
                }
            }
        }
        $this->ORM->EM->flush();
    }

    public function setCookie(string $school_id, string $url)
    {
        $hash = $this->N->createHash();
        $cookie = new Cookie();
        $school = $this->N->getRepo("School")->find($school_id);
        $cookie->setValue($hash)->setName("Hash")->setSchool($school);
        $cookie->setRights("school_only");
        $this->ORM->save($cookie);
        $this->setReturn("hash", $hash)->setReturn("school", $school->getId())->setReturn("url", $url);
    }

    public function removeCookie(string $hash)
    {
        $cookie = $this->N->getRepo("Cookie")->findByHash($hash);
        $this->ORM->delete($cookie);
    }


    public function sliderUpdate($entity_class, $entity_id, $property, $value)
    {
        $this->setReturnFromRequest(["sliderId", "sliderLabelId"]);
        $this->setReturn("newValue", $value);

        return $this->updateProperty($entity_class, $entity_id, $property, $value)->flush();
    }

    public function updateVisitOrder(array $order)
    {
        foreach ($order as $index => $id) {
            $this->updateProperty("School", $id, "VisitOrder", $index + 1);
        }

        return $this->flush();
    }

    public function confirmVisit(int $visit_id)
    {
        return $this->updateProperty("Visit", $visit_id, "Confirmed", true);

    }


    public function changeGroupName($entity_id, $value)
    {
        $this->setReturn("groupId", $entity_id);
        $this->setReturn("newName", $value);
        $this->updateProperty("Group", $entity_id, "Name", $value)->flush();
    }

    /**
     * @param $grade
     * @param null $start_year
     */
    public function createMissingGroups(string $grade, int $start_year = null)
    {
        $all_schools = $this->N->getRepo("School")->findAll();
        /* @var \Fridde\Entities\School $school */
        foreach ($all_schools as $school) {
            $actual_count = $school->getActiveGroupsByGradeAndYear($grade, $start_year);
            $expected_count = $school->getGroupNumber($grade, $start_year);
            $diff = $expected_count - $actual_count;

            for ($i = 0; $i < $diff; $i++) {
                $group = new Group();
                $group->setName();
                $group->setSchool($school);
                $group->setGrade($grade);
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

    public function fillEmptyGroupNames(string $grade, int $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;
        $criteria = [['Grade', $grade], ['StartYear', $start_year]];
        $groups = $this->N->ORM->getRepository('Group')->selectAnd($criteria);
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
     *        comma-separated values: The school_id, the grade and the new number of groups
     * @param int|null $start_year
     */
    public function batchSetGroupCount(array $group_numbers, int $start_year = null)
    {
        $start_year = $start_year ?? Carbon::today()->year;
        foreach ($group_numbers as $school_grade_nr) {
            list($school_id, $grade, $nr) = $school_grade_nr;
            /* @var School $school */
            $school = $this->N->getRepo("School")->find($school_id);
            $school->setGroupNumber($grade, $nr, $start_year);
        }
        $this->ORM->EM->flush();
    }

    public function changeTaskActivation(string $task_name, $status)
    {
        $status = (int) in_array($status, [1, "true", true], true);
        $this->N->setCronTask($task_name, $status);
    }


}
