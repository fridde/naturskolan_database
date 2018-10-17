<?php


namespace Fridde\Controller;

use Carbon\Carbon;

use Fridde\Entities\Group;
use Fridde\Entities\Note;
use Fridde\Entities\User;
use Fridde\Entities\UserRepository;
use Fridde\Entities\Visit;
use Fridde\Entities\VisitRepository;
use Fridde\Naturskolan;

class AdminController extends BaseController
{

    public function handleRequest()
    {
        if (!$this->hasAction()) {
            $this->addAction('assembleAdminOverview');
        }

        $this->setParameter('school', Naturskolan::ADMIN_SCHOOL);
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
        $sql .= 'FROM `log` ORDER BY `time` DESC LIMIT 0 , 500';

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
        $this->addJs('fullcal.sv');
        $this->addCss('fullcal');

        $this->setTemplate('admin/notes_calendar');

        /* @var VisitRepository $visit_repo */
        $visit_repo = $this->N->ORM->getRepository('Visit');
        $events = array_map(
            function (Visit $v) {
                $r = ['allDay' => true];
                $r['start'] = $v->getDateString();
                $r['title'] = $v->getLabel('TGSU');
                $r['url'] = $this->N->generateUrl('note', ['visit_id' => $v->getId()]);

                return $r;
            },
            $visit_repo->findAllActiveVisits()
        );


        $this->addToDATA('events', json_encode($events));
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

                if($visit_id === $this_visit_id){
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


}
