<?php

namespace Fridde;

use Carbon\Carbon;
use Fridde\Entities\Change;
use Fridde\Entities\ChangeRepository;
use Fridde\Entities\GroupRepository;
use Fridde\Entities\Message;
use Fridde\Entities\School;
use Fridde\Entities\Topic;
use Fridde\Entities\User;
use Fridde\Entities\UserRepository;
use Fridde\Entities\Visit;
use Fridde\Entities\VisitRepository;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Messenger\AbstractMessageController;
use Fridde\Messenger\Mail;
use Fridde\Timing as T;
use Fridde\Entities\Group;


/**
 * This class compiles a summary of the state of the system and the database and informs
 * about potential errors and inconsistencies.
 * @package naturskolan_database
 */
class AdminSummary
{
    /** @var Naturskolan shortcut for the Naturskolan object in the global container */
    private $N;

    private $summary;

    /** @var array Contains group changes younger than a certain amount of time.
     *              This is a temporary variable to avoid reproducing the table.
     */
    private $recent_group_changes;

    private static $method_translator = [
        'bad_mobil' => 'getBadMobileNumbers',
        'bus_or_food_order_outdated' => 'getOutdatedBusOrFoodOrders',
        'duplicate_mail_addresses' => 'getDuplicateMailAddresses',
        'files_left_in_temp' => 'getFilesLeftInTemp',
        'food_changed' => 'getChangedFood',
        'missing_orders' => 'getMissingOrders',
        'inactive_group_visit' => 'getInactiveGroupVisits',
        'info_changed' => 'getChangedInfo',
        'nr_students_changed' => 'getChangedStudentNrs',
        'soon_last_visit' => 'getLoomingLastVisit',
        'too_many_students' => 'getWrongStudentNumbers',
        'user_profile_incomplete' => 'getIncompleteUserProfiles',
        'visit_not_confirmed' => 'getUnconfirmedVisits',
        'wrong_group_count' => 'getWrongGroupCounts',
        'wrong_group_leader' => 'getWrongGroupLeaders',
        'wrong_visit_order' => 'getWrongVisitOrder',
    ];

    /**
     * @return void
     */
    public function __construct()
    {
        $this->N = $GLOBALS['CONTAINER']->get('Naturskolan');
    }

    /**
     * Calls compileSummary() and sends the content to the current admin mail address
     *
     * @return AbstractMessageController The Controller object returned by the request
     */
    public function send()
    {
        $this->compileSummary();
        if (empty($this->summary)) {
            return null;
        }
        $params = ['subject_int' => Message::SUBJECT_ADMIN_SUMMARY];
        $params['data']['errors'] = $this->summary;
        $params['data']['labels'] = $this->N->getTextArray('admin_summary');
        $mail = new Mail($params);

        return $mail->buildAndSend();
    }


    /**
     * Performs a variety of checks of the whole system (visits, missing or bad information, etc)
     * and saves any anomalies in the parameter *summary* using addToAdminMail().
     *
     * Afterwards, $summary contains an array containing the error_types as index and an array
     * containing each incident of the error occurring as a string
     */
    private function compileSummary()
    {
        $summary = [];
        foreach (self::$method_translator as $error_type => $method_name) {
            $summary[$error_type] = call_user_func([$this, $method_name]);
        }

        $this->summary = array_filter($summary);
    }

    /**
     * @return array
     */
    private function getBadMobileNumbers()
    {
        $rows = [];
        $imm_date = Task::getStartDate('immunity');
        $users_with_bad_mob = $this->N->getRepo('User')->findUsersWithBadMobil($imm_date);
        /* @var User[] $users_with_bad_mob */
        foreach ($users_with_bad_mob as $u) {
            $row = $u->getFullName().', ';
            $row .= $u->getSchool()->getName().': ';
            $row .= 'Mobil: '.$u->getMobil();
            $rows[] = $row;
        }

        return $rows;
    }

    private function formatGroupChange($change, string $property_name)
    {
        /* @var Group $g */
        $g = $change['group'];

        $text = $g->getSegmentLabel().', ';
        $text .= 'Grupp '.($g->getName() ?? '???').' från '.$g->getSchool()->getName();
        $text .= ', möter oss härnäst ';
        $text .= $g->getNextVisit()->getDate()->toDateString().': ';
        $text .= $property_name;
        $text .= ' ändrades från "'.$change['from'].'" till "'.$change['to'].'"';

        return $text;
    }

    private function getChangedFood()
    {
        return array_map(
            function ($change) {
                return $this->formatGroupChange($change, 'Specialkost');
            },
            $this->getRecentGroupChangesFor('Food')
        );
    }

    private function getInactiveGroupVisits()
    {
        $rows = [];
        $visits = $this->N->getRepo('Visit')->findFutureVisits();
        $bad_visits = array_filter(
            $visits,
            function (Visit $v) {
                return $v->hasGroup() && !$v->getGroup()->isActive(); //empty groups are okay
            }
        );
        /* @var $v Visit */
        foreach ($bad_visits as $v) {
            $row = 'Ogiltigt besök på ';
            $row .= $v->getDate()->toDateString().': ';
            $row .= $v->getGroup()->getName().' från ';
            $row .= $v->getGroup()->getSchool()->getName();
            $row .= ', '.$v->getGroup()->getSegmentLabel();
            $rows[] = $row;
        }

        return $rows;
    }

    private function getChangedInfo()
    {
        return array_map(
            function ($change) {
                return $this->formatGroupChange($change, 'Information från läraren');
            },
            $this->getRecentGroupChangesFor('Info')
        );
    }

    private function getChangedStudentNrs()
    {
        return array_map(
            function ($change) {
                return $this->formatGroupChange($change, 'Antal elever');
            },
            $this->getRecentGroupChangesFor('NumberStudents')
        );
    }

    private function getMissingOrders()
    {
        /* @var VisitRepository $visit_repo */
        $visit_repo = $this->N->getRepo('Visit');
        $summary_settings = Naturskolan::getSetting('admin', 'summary');

        $visits = [
            'bus' => 'missing_bus_warning',
            'food' => 'missing_bus_warning',
        ];

        $rows = [];
        foreach ($visits as $index => $setting_key) {
            $future_visits = $visit_repo->findFutureVisitsWithin($summary_settings[$setting_key]);
            foreach ($future_visits as $v) {
                if ($index === 'bus' && $v->needsBus() && empty($v->getBusIsBooked())) {
                    $rows[] = 'Bussen för '.$v->getDateString().' är inte bokad eller bekräftad.';
                }
                if ($index === 'food' && $v->needsFoodOrder() && empty($v->getFoodIsBooked())) {
                    $rows[] = 'Maten för '.$v->getDateString().' är inte bokad eller bekräftad.';
                }
            }
        }

        return $rows;
    }

    private function getLoomingLastVisit()
    {
        $time_left = Naturskolan::getSetting('admin', 'summary', 'soon_last_visit');
        $last_visit_deadline = T::addDurationToNow($time_left);
        $last_visit = $this->N->getRepo('Visit')->findLastVisit();
        if (empty($last_visit) || $last_visit->getDate()->lte($last_visit_deadline)) {
            $text = 'Snart är sista planerade mötet med eleverna. Börja planera nästa termin!';
            $text .= ' Sista möte: ';
            $text .= empty($last_visit) ? 'Inga fler besök.' : $last_visit->getDate()->toDateString();

            return [$text];
        }

        return false;
    }

    /**
     * @return array
     */
    private function getWrongStudentNumbers()
    {
        $rows = [];
        $range = Naturskolan::getSetting('admin', 'summary', 'allowed_group_size');

        $ill_sized_groups = array_filter(
            $this->N->getRepo('Group')->findActiveGroups(),
            function (Group $g) use ($range) {
                $nr_students = $g->getNumberStudents();

                return $nr_students > 0 && ($nr_students < $range[0] || $nr_students > $range[1]);
            }
        );
        foreach ($ill_sized_groups as $g) {
            /* @var Group $g */
            $row = $g->getName().', '.$g->getSegmentLabel().', ';
            $row .= 'från '.$g->getSchool()->getName();
            $row .= ' har '.$g->getNumberStudents().' elever.';
            $rows[] = $row;
        }

        return $rows;
    }

    private function getDuplicateMailAddresses()
    {
        /* @var UserRepository $user_repo */
        $user_repo = $this->N->ORM->getRepository('User');

        $all_mail_addresses = array_map(
            function (User $u) {
                return trim($u->getMail());
            },
            $user_repo->findActiveUsers()
        );
        $counter = array_count_values($all_mail_addresses);

        $ignored_addresses = SETTINGS['admin']['summary']['ignore_duplicates'];

        return array_keys(
            array_filter(
                $counter,
                function ($count, $address) use ($ignored_addresses) {
                    $ignored = in_array($address, $ignored_addresses, true);

                    return !$ignored && $count > 1;
                },
                ARRAY_FILTER_USE_BOTH
            )
        );
    }

    private function getIncompleteUserProfiles()
    {
        /* @var UserRepository $u_repo */
        $u_repo = $this->N->getRepo('User');

        $rows = [];
        $imm_date = Task::getStartDate('immunity');
        $incomplete_users = $u_repo->findIncompleteUsers($imm_date);
        $incomplete_users = array_filter(
            $incomplete_users,
            function (User $u) {
                return $u->hasGroups(1, true, true);
            }
        );

        /* @var User $u */
        foreach ($incomplete_users as $u) {
            $row = $u->getFullName().', ';
            $row .= $u->getSchool()->getName().': ';
            $row .= 'Mobil: '.($u->hasMobil() ? $u->getMobil() : '???').', ';
            $row .= 'Mejl: '.($u->hasMail() ? $u->getMail() : '???').', ';
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array
     */
    private function getUnconfirmedVisits()
    {
        $rows = [];
        $no_conf_interval = Naturskolan::getSetting('admin', 'summary', 'no_confirmation_warning');
        $close_date = T::addDurationToNow($no_conf_interval);
        $unconfirmed_visits = $this->N->getRepo('Visit')->findUnconfirmedVisitsUntil($close_date);
        $unconfirmed_visits = array_filter(
            $unconfirmed_visits,
            function (Visit $v) {
                return $v->hasGroup();
            }
        );

        /* @var Visit $visit */
        foreach ($unconfirmed_visits as $visit) {
            $g = $visit->getGroup();
            $u = $g->getUser();
            $row = $visit->getDate()->toDateString().': ';
            $row .= $visit->getTopic()->getShortName().' med ';
            $row .= ($g->getName() ?? '???').' från ';
            $row .= $g->getSchool()->getName().'. Lärare: ';
            if (!empty($u)) {
                $row .= $u->getFullName().', ';
                $row .= $u->getMobil().', '.$u->getMail();
            } else {
                $row .= '???';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function getWrongGroupCounts()
    {
        $years_to_check = [Carbon::today()->year];
        $years_to_check[] = Carbon::today()->copy()->subMonths(6)->year;
        $years_to_check[] = Carbon::today()->copy()->addMonths(6)->year;
        $years_to_check = array_unique($years_to_check);

        $rows = [];
        $schools = $this->N->getRepo('School')->findAll();
        /* @var School $school */
        foreach ($schools as $school) {
            foreach (Group::getSegmentLabels() as $segment_id => $label) {
                foreach ($years_to_check as $start_year) {
                    $active = $school->getNrActiveGroupsBySegmentAndYear($segment_id, $start_year);
                    $expected = $school->getGroupCountNumber($segment_id, $start_year);
                    if ($expected !== $active) {
                        $row = $school->getName().' har fel antal grupper i segment ';
                        $row .= $label.' och år '.$start_year.'. Det finns ';
                        $row .= $active.' grupper, men det borde vara '.$expected.'. ';
                        $rows[] = $row;
                    }
                }
            }
        }

        return $rows;
    }

    private function getWrongGroupLeaders()
    {
        /* @var GroupRepository $g_repo  */
        $g_repo = $this->N->getRepo('Group');
        /* @var UserRepository $u_repo  */
        $u_repo = $this->N->getRepo('User');

        $rows = [];

        $forbidden_roles = [User::ROLE_SCHOOL_MANAGER, User::ROLE_ADMIN, User::ROLE_SUPERUSER];

        $groups = $g_repo->findActiveGroups();
        /* @var Group $group */
        foreach ($groups as $group) {
            $u = $group->getUser();
            $reasons = [];
            if (empty($u) || empty($u_repo->find($u->getId()))) {
                $reasons['nonexistent'] = true;
            } else {
                $reasons['inactive'] = !$u->isActive();
                $reasons['not_teacher'] = $u->hasOneOfRoles($forbidden_roles);
                $reasons['wrong_school'] = $u->getSchool()->getId() !== $group->getSchool()->getId();

            }
            $reasons = array_keys(array_filter($reasons));
            if (count($reasons) === 0) {
                continue;
            }

            $row = $group->getSegmentLabel().': ';
            $row .= $group->getName().' från '.$group->getSchool()->getName();
            $row .= '. Skäl: ';
            $reason_texts = [];
            foreach ($reasons as $reason) {
                $reason_texts[] = $this->N->getText('admin_summary/'.$reason);
            }
            $row .= implode(' ', $reason_texts);
            $rows[] = $row;
        }

        return $rows;
    }

    private function getOutdatedBusOrFoodOrders()
    {
        $rows = [];

        /* @var ChangeRepository $change_repo */
        $change_repo = $this->N->ORM->getRepository('Change');
        /* @var VisitRepository $visit_repo */
        $visit_repo = $this->N->ORM->getRepository('Visit');

        $crit = [['EntityClass', 'Visit']];
        $crit[] = ['Type', Change::TYPE_UPDATE];
        $crit[] = ['in', 'Property', ['Group', 'Topic', 'Time']];

        $visit_changes = $change_repo->findNewChanges($crit);
        $checked_visits = [];

        /* @var Change $change */
        foreach ($visit_changes as $change) {
            /* @var Visit $visit */
            $this->N->log('Visit ' . $change->getEntityId() . ' doesn\'t exist.');
            $visit = $visit_repo->find($change->getEntityId());
            if (empty($visit)) {
                throw new NException(Error::DATABASE_INCONSISTENT, ['visit', $change->getEntityId()]);
            }
            $not_checked = !in_array($visit->getId(), $checked_visits, false);
            $in_future = $visit->isInFuture();
            $check_bus = $visit->needsBus() && $visit->getBusIsBooked();
            $check_food = $visit->needsFoodOrder() && $visit->getFoodIsBooked();

            if ($not_checked && $in_future) {
                if ($check_bus || $check_food) {
                    $row = $visit->getLabel().' har ';
                    if ($visit->getStatus() === Visit::STATUS_ARCHIVED) {
                        $row .= ' tagits bort.';
                    } else {
                        $row .= ' förändrats.';
                    }
                    $row .= $check_bus ? ' Kontrollera bussbeställning.' : '';
                    $row .= $check_food ? ' Kontrollera matbeställning.' : '';
                    $rows[] = $row;
                    Task::processChange($change);
                    $checked_visits[] = $visit->getId();
                }
            }
        }

        $crit = [['in', 'EntityClass', ['Topic', 'School']]];
        $topic_or_location_changes = $change_repo->findNewChanges($crit);
        foreach ($topic_or_location_changes as $change) {
            $entity_class = $change->getEntityClass();
            $property = $change->getProperty();
            $entity_id = $change->getEntityId();
            if ($entity_class === 'Topic' && $property === 'Location') {
                /* @var Topic $topic */
                $topic = $this->N->ORM->find('Topic', $entity_id);
                $row = $topic->getShortName().' har ändrat plats.';
                $rows[] = $row;
            } elseif ($entity_class === 'School' && $property === 'BusRule') {
                /* @var School $school */
                $school = $this->N->ORM->find('School', $entity_id);
                $row = $school->getName().' har förändrat sina bussrutiner.';
                $rows[] = $row;
            }
            Task::processChange($change);
        }
        $this->N->ORM->EM->flush();

        return $rows;
    }

    private function getWrongVisitOrder()
    {
        $rows = [];
        /* @var GroupRepository $group_repo */
        $group_repo = $this->N->ORM->getRepository('Group');

        $groups = $group_repo->findActiveGroups();
        /* @var Group $group */
        foreach ($groups as $group) {
            $visits = array_values($group->getFutureVisits());
            $visits = array_filter(
                $visits,
                function (Visit $v) {
                    $topic = $v->getTopic();

                    return (empty($topic) ? false : $topic->getOrderIsRelevant());
                }
            );

            $nr_visits = count($visits);
            if ($nr_visits < 2) {
                continue;
            }
            /* @var Visit $visit */
            foreach ($visits as $key => $visit) {
                /* @var Visit $first_visit */
                $first_visit = $visits[$key];
                /* @var Visit $second_visit */
                $second_visit = $visits[$key + 1] ?? null;
                if (empty($second_visit)) {
                    continue;
                }
                if ($first_visit->getTopic()->getVisitOrder() >= $second_visit->getTopic()->getVisitOrder()) {
                    $row = 'För '.$group->getName().' från '.$group->getSchool()->getName().': ';
                    $row .= $first_visit->getLabel('DT').' är före '.$second_visit->getLabel('DT');
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    private function getFilesLeftInTemp()
    {
        $files = scandir(BASE_DIR.'/temp', SCANDIR_SORT_ASCENDING);

        $files = array_diff($files, ['cache', '.', '..', '.gitignore']);

        array_walk(
            $files,
            function (&$v) {
                $v = 'Filen '.$v.' finns fortfarande i /temp. Ta bort den snarast!';
            }
        );

        return $files;
    }

    private function setRecentGroupChanges()
    {
        $deadline = T::addDurationToNow(Naturskolan::getSetting('admin', 'summary', 'important_info_changed'));
        $recent_group_changes = [];

        $crit = [['EntityClass', 'Group'], ['in', 'Property', ['Food', 'NumberStudents', 'Info']]];
        $group_changes = $this->N->getRepo('Change')->findNewChanges($crit);
        /* @var Change $change */
        foreach ($group_changes as $change) {
            /* @var Group $g */
            $g = $this->N->ORM->find('Group', $change->getEntityId());
            $next_visit = $g->getNextVisit();
            if (!empty($next_visit) && $next_visit->isBefore($deadline)) {
                $att = $change->getProperty();
                $method = 'get'.$att;
                $old_value = $change->getOldValue();
                $new_value = $g->$method();
                if ($old_value !== $new_value) {
                    $return = ['from' => $old_value, 'to' => $new_value];
                    $return['group'] = $g;
                    $recent_group_changes[$att][] = $return;
                }
            }
            Task::processChange($change);
        }
        $this->N->ORM->EM->flush();
        $this->recent_group_changes = $recent_group_changes;
    }

    private function getRecentGroupChangesFor(string $type)
    {
        if ($this->recent_group_changes === null) {
            $this->setRecentGroupChanges();
        }

        return $this->recent_group_changes[$type] ?? [];
    }


}
