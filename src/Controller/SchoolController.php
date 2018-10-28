<?php
/**
 * The School Controller
 */

namespace Fridde\Controller;

use Fridde\Entities\Group;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Security\Authorizer;
use Fridde\Timing as T;

class SchoolController extends BaseController
{
    /** @var School $request_school */
    public $request_school;

    public function __construct($params = [])
    {
        parent::__construct($params);
        $school_id = $this->getParameter('school');
        $this->request_school = $this->N->ORM->find('School', $school_id);
    }

    public function handleRequest()
    {
        $method = 'createSchoolPage';

        if($this->Authorizer->getVisitor()->isFromSchool($this->request_school)){
            $this->Authorizer->changeSecurityLevel(self::class, $method,  Authorizer::ACCESS_ALL_EXCEPT_GUEST);
        }

        $this->addAction($method);
        $this->addToDATA('school_id', $this->request_school->getId());

        parent::handleRequest();
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function createSchoolPage()
    {
        $this->addToDATA('school_name', $this->request_school->getName());
        $this->addToDATA($this->getAllUsers($this->request_school));
        $this->addToDATA($this->getAllGroups($this->request_school));
        $this->setTemplate('school_page');
    }



    private function getAllUsers(School $school): array
    {
        $DATA = ['entity_class' => 'User'];
        $users = $school->getUsers();
        usort($users, function(User $u1, User $u2){
            return strcasecmp($u1->getFullName(), $u2->getFullName());
        });
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
    private function getAllGroups($school)
    {
        $DATA = [];
        $DATA['teachers'] = array_map(
            function (User $u) {
                return ['id' => $u->getId(), 'full_name' => $u->getFullName()];
            },
            $school->getUsers()
        );
        $DATA['student_limits'] = SETTINGS['admin']['summary']['allowed_group_size'];
        $DATA['school_name'] = $school->getName();

        $segments_at_this_school = $school->getSegmentsAvailable(true);

        foreach ($segments_at_this_school as $segment_val => $segment_label) {
            $groups_current_segment = $school->getActiveGroupsBySegmentAndYear($segment_val, false);
            $seg = ['id' => $segment_val, 'label' => $segment_label];
            $groups_current_segment_formatted = array_map(
                function (Group $g) {
                    $r['id'] = $g->getId();
                    $r['name'] = $g->getName();
                    $user = $g->getUser();
                    $r['teacher_id'] = empty($user) ? null : $user->getId();
                    $r['nr_students'] = $g->getNumberStudents() ?? 0;
                    $r['food'] = $g->getFood();
                    $r['info'] = $g->getInfo();
                    $r['visits'] = array_map(
                        function (Visit $v) {
                            $r['id'] = $v->getId();
                            $r['date'] = $v->getDate()->toDateString();
                            $r['topic_short_name'] = $v->getTopic()->getShortName();
                            $r['topic_url'] = $v->getTopic()->getUrl();
                            $r['confirmed'] = $v->isConfirmed();
                            $dur = T::addDurationToNow(SETTINGS['values']['show_confirm_link']);
                            if ($v->isInFuture() && $v->isBefore($dur)) {
                                $r['confirmation_url'] = $this->N->createConfirmationUrl($v->getId(), 'simple');
                            }

                            return $r;
                        },
                        $g->getSortedVisits()
                    );

                    return $r;
                },
                $groups_current_segment
            );

            $group_columns = $this->H::partition(
                $groups_current_segment_formatted
            ); // puts items in two equally large columns
            $seg['col_left'] = $group_columns[0] ?? [];
            $seg['col_right'] = $group_columns[1] ?? [];

            $DATA['segments'][] = $seg;
        }

        return $DATA;
    }


}
