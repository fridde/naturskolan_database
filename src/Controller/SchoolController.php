<?php
/**
 * The School Controller
 */

namespace Fridde\Controller;

use Doctrine\ORM\EntityManager;
use Fridde\Entities\Group;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Utility as U;

class SchoolController extends BaseController
{
    /** @var School $school */
    public $school;
    /** @var User $user */
    private $user;
    public const PAGE = ['staff' => 'staff', 'groups' => 'groups'];

    public const STAFF_PAGE = 'staff';
    public const GROUPS_PAGE = 'groups';

    public function __construct($params = [])
    {
        parent::__construct($params);
        if (!empty($this->params['school'])) {
            $this->school = $this->N->getRepo('School')->find($this->params['school']);
        }
        $this->user = $this->N->Auth->getUserFromCode($this->params['code'] ?? null);
    }

    public function handleRequest()
    {
        if (!$this->isAuthorized()) {
            $login_controller = new LoginController($this->getParameter());
            $login_controller->addAction('renderPasswordModal');
            return $login_controller->handleRequest();
        }
        $page = $this->getParameter('page') ?? self::GROUPS_PAGE;

        if ($page === self::GROUPS_PAGE) {
            $this->addToDATA($this->getAllGroups($this->school));
            $this->setTemplate('group_settings');
        } elseif ($page === self::STAFF_PAGE) {
            $this->addToDATA($this->getAllUsers($this->school));
            $this->addToDATA('school_id', $this->school->getId());
            $this->setTemplate('staff_list');
        } else {
            throw new \Exception("No action was defined for the page variable $page .");
        }
        $this->addToDATA(['school_id' => $this->school->getId()]);

        parent::handleRequest();
    }

    private function isAuthorized()
    {
        if ($this->N->Auth->getUserRole() === 'admin') {
            return true;
        }
        // user has valid code in url
        if (!empty($this->user)) {
            if ($this->user->getSchoolId() === $this->school->getId()) {
                return true;
            }
        }
        // compare cookie id with request
        if (!empty($this->school)) {
            if ($this->school->getId() === $this->N->Auth->getSchooldIdFromCookie()) {
                return true;
            }
        }

        return false;
    }

    public function getAllUsers(School $school)
    {
        $DATA = ['entity_class' => 'User'];
        $users = $school->getUsers()->toArray();
        if (empty($users)) {
            $users[] = new User(); // dummy user
        }
        $keys = ['id', 'FirstName', 'LastName', 'Mobil', 'Mail', 'Acronym'];
        foreach ($keys as $key) {
            $DATA['headers'][] = $key;
            foreach ($users as $i => $user) {
                $method_name = 'get'.ucfirst($key);
                $DATA['users'][$i][$key] = $user->$method_name();
            }
        }

        return $DATA;
    }

    /**
     * Collects all active groups for the specified school, adds relevant info
     * and orders them into a structured array to be viewed on the school page later.
     *
     * @example getAllGroupsExample.php
     * @param  \Fridde\Entities\School $school The School object.
     * @return array An array containing structured data. See example.
     */
    public function getAllGroups($school)
    {
        $DATA = [];
        $DATA['teachers'] = array_map(
            function (User $u) {
                return ['id' => $u->getId(), 'full_name' => $u->getFullName()];
            },
            $school->getUsers()->toArray()
        );
        $DATA['student_limits'] = SETTINGS['values']['min_max_students'];
        $DATA['school_name'] = $school->getName();

        $grades_at_this_school = $school->getGradesAvailable(true);

        foreach ($grades_at_this_school as $grade_val => $grade_label) {
            $groups_current_grade = $school->getActiveGroupsByGradeAndYear($grade_val, false);
            $tab = ['id' => $grade_val, 'grade_label' => $grade_label];
            $groups_current_grade_formatted = array_map(
                function (Group $g) {
                    $r['id'] = $g->getId();
                    $r['name'] = $g->getName();
                    $r['teacher_id'] = $g->getUser()->getId();
                    $r['nr_students'] = $g->getNumberStudents();
                    $r['food'] = $g->getFood();
                    $r['info'] = $g->getInfo();
                    $r['visits'] = array_map(
                        function (Visit $v) {
                            $r['id'] = $v->getId();
                            $r['date'] = $v->getDate()->toDateString();
                            $r['topic_short_name'] = $v->getTopic()->getShortName();
                            $r['topic_url'] = $v->getTopic()->getUrl();
                            $r['confirmed'] = $v->isConfirmed();
                            $dur = U::addDuration(SETTINGS['values']['show_confirm_link']);
                            if ($v->isInFuture() && $v->isBefore($dur)) {
                                $r['confirmation_url'] = $this->N->createConfirmationUrl($v->getId());
                            }

                            return $r;
                        },
                        $g->getSortedVisits()->toArray()
                    );

                    return $r;
                },
                $groups_current_grade
            );

            $group_columns = $this->H::partition($groups_current_grade_formatted); // puts items in two equally large columns
            $tab['col_left'] = $group_columns[0] ?? [];
            $tab['col_right'] = $group_columns[1] ?? [];

            $DATA['tabs'][] = $tab;
        }

        return $DATA;
    }

}
