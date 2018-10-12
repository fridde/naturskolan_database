<?php


namespace Fridde\Controller;

use Carbon\Carbon;

use Fridde\Entities\Group;
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


        $this->addToDATA('events', $events);

    }


}
