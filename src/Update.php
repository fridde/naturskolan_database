<?php

namespace Fridde;

use Fridde\Annotations\NeedsSameSchool;
use Fridde\Annotations\PostArgs;
use Fridde\Annotations\SecurityLevel;
use Fridde\Controller\LoginController;
use Fridde\Entities\Group;
use Carbon\Carbon;
use Fridde\Entities\GroupRepository;
use Fridde\Entities\Hash;
use Fridde\Entities\Location;
use Fridde\Entities\LocationRepository;
use Fridde\Entities\Message;
use Fridde\Entities\MessageRepository;
use Fridde\Entities\Note;
use Fridde\Entities\NoteRepository;
use Fridde\Entities\School;
use Fridde\Entities\SchoolRepository;
use Fridde\Entities\Topic;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Error\Error;
use Fridde\Error\NException;


/**
 * Contains the logic to update records in the database.
 */
class Update extends DefaultUpdate
{
    private const NEW_ENTITY_ID_KEY = 'new_entity_ids';

    /** @var Naturskolan $N */
    protected Naturskolan $N;

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
        $old_id = $this->getReturn('old_id') ?? '';
        $new_id = $this->getAlternativeIdFromCache($old_id);

        if (null !== $new_id) {   // i.e. it's not actually new, the DOM just hasn't been updated
            foreach ($properties as $prop_name => $prop_value) {
                $this->updateProperty($entity_class, $new_id, $prop_name, $prop_value);
            }
            $this->setReturn('new_id', $new_id);
            return $this;
        }
        parent::createNewEntity($entity_class, $properties, $flush);
        $new_id = $this->getReturn('new_id');
        if (null !== $new_id) {    // i.e. not all required fields where given
            $this->addAlternativeIdToCache($old_id, $new_id);
        }

        return $this;
    }


    private function getAlternativeIdFromCache(string $key = null)
    {
        if($key === ''){
            return null;
        }

        if (!$this->N->cache->contains(self::NEW_ENTITY_ID_KEY)) {
            return null;
        }
        $ids = $this->N->cache->fetch(self::NEW_ENTITY_ID_KEY);
        if (null === $key) {
            return $ids;
        }

        return $ids[$key] ?? null;
    }

    private function addAlternativeIdToCache(string $key, string $value): void
    {
        $ids = $this->getAlternativeIdFromCache() ?? [];

        $ids[$key] = $value;

        $this->N->cache->save(self::NEW_ENTITY_ID_KEY, $ids);
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
    public function checkPassword(string $password, string $school_id = null): void
    {
        if(empty($school_id)){
            $this->addError('No school id given');
            return;
        }

        $school = $this->ORM->find(School::class, $school_id);
        if(!($school instanceof School)){
            $this->addError('No school found for school id "'. $school_id .'"');
            usleep(1000 * 2000); // to avoid brute force methods
            return;
        }
        if(!$this->N->Auth->checkPasswordForSchool($school, $password)){
            $this->addError('Wrong password "'.$password.'" for school id "'.$school_id.'"');
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
        $topic = $this->ORM->find(Topic::class, $topic_id);
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
     * @throws NException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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
                        throw new NException(Error::LOGIC, ['Empty group at row '.$row_index]);
                    }
                    $row_to_group_translator[$row_index] = $entity_id;
                } elseif ($class === 'visit') {
                    $group_dates[$row_index][] = $entity_id;
                } else {
                    throw new NException(Error::LOGIC, [$class.' not implemented']);
                }
            }
        }
        foreach ($group_dates as $row_index => $visits) {
            $group_id = $row_to_group_translator[$row_index] ?? null;
            foreach ($visits as $visit_id) {
                if (isset($group_id, $visit_id)) {
                    $visit = $this->ORM->EM->find(Visit::class, $visit_id);
                    $group = $this->ORM->EM->find(Group::class, $group_id);
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
        /* @var School $school  */
        $school = $this->ORM->find(School::class, $school_id);
        $hash_string = $this->N->Auth->createCookieKeyForSchool($school);
        
        $this->N->Auth->setCookieKeyInBrowser($hash_string);

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
     * @param int|null $start_year
     *
     * @throws \Doctrine\ORM\ORMException
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
                $g->setName('Grupp '.Naturskolan::getRandomAnimalName());
            }
        );
        $this->N->ORM->EM->flush();
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

    /**
     * @PostArgs("visit_id, author_id, text")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function updateNoteToVisit(int $visit_id, int $author_id, string $text): Update
    {
        /* @var NoteRepository $note_repo */
        $note_repo = $this->N->ORM->getRepository('Note');
        $note = $note_repo->findByVisitAndAuthor($visit_id, $author_id);

        if (empty($note)) {
            $props = ['Visit' => $visit_id, 'User' => $author_id, 'Text' => $text];

            return $this->createNewEntity(Note::class, $props);
        }

        return $this->updateProperty('Note', $note->getId(), 'Text', $text);
    }

    /**
     * @PostArgs("message_log")
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function updateMessageLoggFromOutlook(string $message_log): Update
    {
        /* @var MessageRepository $message_repo */
        $message_repo = $this->ORM->getRepository(Message::class);
        $existing_messages = $message_repo->findAll();
        $existing_messages_by_users = [];
        foreach ($existing_messages as $existing_message){
            /* @var Message $existing_message */
            $user_id = $existing_message->getUser()->getId();
            $existing_messages_by_users[$user_id] ??= [];
            $existing_messages_by_users[$user_id][] = $existing_message;
        }

        $new_messages = json_decode($message_log, true);

        foreach($new_messages as $k => $new_message){
            $user_id =  $new_message['user_id'];
            $messages_for_user = $existing_messages_by_users[$user_id] ?? [];
            foreach($messages_for_user as $m){
                $nm_date = new Carbon($new_message['date']);
                /* @var Message $m */
                if($m->getSubject() === (int) $new_message['subject'] &&
                    $nm_date->isSameDay($m->getDate())){
                        $new_messages[$k] = null;
                }
            }
        }
        $new_messages = array_filter($new_messages);

        foreach($new_messages as $nm){
            /* @var User $user */
            $user = $this->ORM->getRepository(User::class)->find($nm['user_id']);
            if(empty($user)){
                $this->N->log('Mail import error: User with id '.  $nm['user_id'] . ' doesn\'t exist in the database.');
                continue;
            }
            $nm_entity = new Message();
            $nm_entity->setCarrier(Message::CARRIER_MAIL);
            $nm_entity->setDate($nm['date']);
            $nm_entity->setSubject($nm['subject']);

            $nm_entity->setUser($user);
            $this->ORM->EM->persist($nm_entity);
        }
        return $this->flush();
    }

}
