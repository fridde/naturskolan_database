<?php


namespace Fridde\Controller;

use Carbon\Carbon;

use Fridde\Annotations\SecurityLevel;
use Fridde\Entities\Group;
use Fridde\Entities\Note;
use Fridde\Entities\User;
use Fridde\Entities\UserRepository;
use Fridde\Entities\Visit;
use Fridde\Entities\VisitRepository;
use Fridde\HTML;
use Fridde\Naturskolan;

class AdminController extends BaseController
{
    //label, description, address-function
    private const MAIL_LISTS = [
        [
            'Administratörer',
            'Samtliga administratörer på alla grundskolor med besökande klasser',
            'getAllManagers',
        ],
        [
            'Åk 2/3',
            'Lärare som är ansvariga för minst en klass i årskurs 2/3',
            'getAllTeachers#2',
        ],
        [
            'Åk 5',
            'Lärare som är ansvariga för minst en klass i årskurs 5',
            'getAllTeachers#5',
        ],
        [
            'Fritids',
            'Lärare som är ansvariga för minst en fritidsgrupp',
            'getAllTeachers#fri',
        ],
    ];


    public function handleRequest()
    {
        if (!$this->hasAction()) {
            $this->addAction('assembleAdminOverview');
        }

        $this->setParameter('school', Naturskolan::ADMIN_SCHOOL);
        $this->addJsToEnd('admin', HTML::INC_ASSET);
        $this->addCss('admin', HTML::INC_ASSET);
        parent::handleRequest();
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function assembleAdminOverview()
    {
        $task_keys = array_keys($this->N::getSetting('cronjobs', 'intervals'));
        $task_status = $this->N->getCronTaskActivationStatus();
        $tasks = [];
        foreach ($task_keys as $task_key) {
            $task = ['value' => $task_key];
            $task['label'] = implode(' ', array_map('ucfirst', explode('_', $task_key)));
            $task['status'] = $task_status[$task_key] ?? 0;
            $tasks[] = $task;
        }
        $this->addToDATA('tasks', $tasks);
        $this->addToDATA('mail_lists', $this->getMaillists());
        $this->addToDATA('segments', Group::getSegmentLabels());
        $this->addToDATA('school_id', $this->getParameter('school'));
        $this->setTemplate('admin/admin_area_overview');
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function showLog()
    {
        $conn = $this->N->ORM->EM->getConnection();

        $sql = 'SELECT `level`, `message`, `time`, `source` ';
        $sql .= 'FROM `log` ORDER BY `time` DESC LIMIT 300';

        $stmt = $conn->query($sql);

        $rows = [];
        while ($row = $stmt->fetch()) {
            $row['time'] = Carbon::createFromTimestamp($row['time'])->toIso8601String();
            $rows[] = $row;
        }

        $this->addToDATA('rows', $rows);
        $this->setTemplate('admin/show_log');
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function showNoteCalendar()
    {
        $this->addJs('fullcal');
        $this->addCss('fullcal');

        $this->setTemplate('admin/notes_calendar');

        /* @var VisitRepository $visit_repo */
        $visit_repo = $this->N->ORM->getRepository('Visit');
        $events = array_map(
            function (Visit $v) {
                $r = ['allDay' => true];
                $r['start'] = $v->getDateString();
                $r['title'] = mb_convert_encoding($v->getLabel('TGSU'), 'UTF-8', 'auto');
                $r['url'] = $this->N->generateUrl('note', ['visit_id' => $v->getId()]);

                return $r;
            },
            $visit_repo->findAllActiveVisits()
        );


        $this->addToDATA('events', json_encode(array_values($events)));
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function editNote()
    {
        /* @var VisitRepository $visit_repo */
        /* @var UserRepository $user_repo */
        $visit_repo = $this->N->ORM->getRepository('Visit');
        $user_repo = $this->N->ORM->getRepository('User');

        $this_visit_id = (int)$this->getParameter('visit_id');
        /* @var Visit $this_visit */
        $this_visit = $visit_repo->find($this_visit_id);
        $group = $this_visit->getGroup();
        // if(empty($group)) // TODO: throw error

        $group_details = ['name' => $group->getName()];
        $group_details['school'] = $group->getSchool()->getName();
        $group_details['teacher'] = $group->getUser()->getFullName();
        $this->addToDATA('group_details', $group_details);

        $visits = array_reverse($group->getSortedVisits());

        $notes = [];
        $visit_details = [];
        $this_visit_notes = [];

        foreach ($visits as $visit) {
            /* @var Visit $visit */
            $notes_for_visit = $visit->getNotes();
            $visit_id = $visit->getId();
            $visit_details[$visit_id] = $visit->getLabel('DT');
            foreach ($notes_for_visit as $note) {
                /* @var Note $note */
                $n = [];
                $n['timestamp'] = $note->getTimestamp()->format('Y-m-d H:i');
                $n['author'] = $note->getUser()->getAcronym();
                $n['text'] = $note->getText();
                $notes[$visit_id][] = $n;

                if ($visit_id === $this_visit_id) {
                    $this_visit_notes[$note->getUser()->getId()] = $note->getText();
                }
            }
        }

        $this->addToDATA('notes', $notes);
        $this->addToDATA('visit_details', $visit_details);
        $this->addToDATA('this_visit_id', $this_visit->getId());
        $this->addToDATA('this_visit_notes', json_encode($this_visit_notes));


        $colleagues = $user_repo->getActiveColleagues();
        $colleagues = array_map(
            function (User $u) {
                return ['id' => $u->getId(), 'acronym' => $u->getAcronym()];
            },
            $colleagues
        );

        $this->addToDATA('colleagues', $colleagues);

        $user = $this->Authorizer->getVisitor()->getUser();
        if (!empty($user)) {
            $this->addToDATA('user_id', $user->getId());
        }

        $this->setTemplate('admin/edit_note');
    }

    private function getMaillists(): array
    {
        return array_map(
            function ($ml) {
                $r = ['label' => $ml[0]];
                $r['description'] = $ml[1];
                $fn_plus_args = explode('#', $ml[2]);
                $fn = $fn_plus_args[0];
                $args = array_slice($fn_plus_args, 1);
                $r['addresses'] = call_user_func([$this, $fn], ...$args);

                return $r;
            },
            self::MAIL_LISTS
        );

    }

    private function getAllManagers(): array
    {
        /* @var UserRepository $u_repo */
        $u_repo = $this->N->getRepo('User');

        return array_map(
            function (User $u) {
                return $u->getMail();
            },
            $u_repo->getActiveManagers()
        );

    }

    private function getAllTeachers(string $segment = null): array
    {
        /* @var UserRepository $u_repo */
        $u_repo = $this->N->getRepo('User');

        return array_map(
            function (User $u) {
                return $u->getMail();
            },
            $u_repo->findActiveUsersHavingGroupInSegment($segment)
        );

    }


}
