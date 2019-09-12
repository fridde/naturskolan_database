<?php
/**
 * The School Controller
 */

namespace Fridde\Controller;

use Fridde\Annotations\SecurityLevel;
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

    public $methods = ['createSchoolPage', 'createRemoveUserPage'];

    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->setRequestSchool();
    }

    public function handleRequest()
    {
        $this->decreaseSecurityLevelIfFromRightSchool();

        $this->addToDATA('school_id', $this->request_school->getId());
        $this->addToDATA('school_name', $this->request_school->getName());
        $this->addToDATA($this->getAllUsers($this->request_school));

        parent::handleRequest();
    }


    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function createSchoolPage(): void
    {
        $this->addToDATA($this->getAllGroups($this->request_school));
        $this->setTemplate('school_page');
    }
    //

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ADMIN_ONLY)
     */
    public function createRemoveUserPage(): void
    {
        $this->setTemplate('remove_user_page');
    }


    /**
     * @param School $school
     * @return array An array filled with 'entity_class', 'headers', 'invisible_headers' and 'staff'
     */
    private function getAllUsers(School $school): array
    {
        $DATA = ['entity_class' => 'User'];

        $users = array_filter(
            $school->getUsers(),
            function (User $u) {
                return $u->isActive();
            }
        );

        usort(
            $users,
            function (User $u1, User $u2) {
                return strcasecmp($u1->getFullName(), $u2->getFullName());
            }
        );
        if (empty($users)) {
            $users[] = new User(); // dummy user
        }
        $keys = ['id', 'FirstName', 'LastName', 'Mobil', 'Mail', 'Acronym', 'FullName'];
        $invisible_keys = ['id', 'FullName'];
        if(!$school->isNaturskolan()){
            $invisible_keys[] = 'Acronym';
        }
        $DATA['headers'] = $keys;
        $DATA['invisible_headers'] = $invisible_keys;
        foreach ($keys as $key) {
            foreach ($users as $i => $user) {
                $method_name = 'get'.ucfirst($key);
                $DATA['staff'][$i][$key] = $user->$method_name();
            }
        }

        return $DATA;
    }

    /**
     * Collects all active groups for the specified school, adds relevant info
     * and orders them into a structured array to be viewed on the school page later.
     *
     * @example getAllGroupsExample.php
     * @param  School $school The School object.
     * @return array An array containing structured data. See example.
     */
    private function getAllGroups(School $school)
    {
        $DATA = [];
        $DATA['student_limits'] = SETTINGS['admin']['summary']['allowed_group_size'];
        $DATA['school_name'] = $school->getName();

        $segments_at_this_school = $school->getSegmentsAvailable(true);

        foreach ($segments_at_this_school as $segment_val => $segment_label) {
            $groups_current_segment = $school->getActiveGroupsBySegmentAndYear($segment_val, false);
            usort($groups_current_segment, function(Group $g1, Group $g2){
                // newer groups should appear first!
                return $g2->getStartYear() - $g1->getStartYear();
            });

            $seg = ['id' => $segment_val, 'label' => $segment_label];
            $groups_current_segment_formatted = array_map(
                function (Group $g) {
                    $r['id'] = $g->getId();
                    $r['name'] = $g->getName();
                    $user = $g->getUser();
                    $r['teacher_id'] = empty($user) ? null : $user->getId();
                    $r['nr_students'] = $g->getNumberStudents() ?? 0;
                    $r['food'] = $g->getFood();
                    $r['needs_food'] = !in_array($g->getSegment(), ['fri', '9'], false);
                    $r['info'] = $g->getInfo();
                    $r['visits'] = array_map(
                        function (Visit $v) {
                            $topic = $v->getTopic();
                            $r['id'] = $v->getId();
                            $r['date'] = $v->getDate()->toDateString();
                            $r['time'] = $v->hasTime() ? $v->getTime() : false;
                            $r['topic_short_name'] = $topic->getShortName();
                            $r['topic_url'] = $topic->getUrl();
                            $r['confirmed'] = $v->isConfirmed();
                            $dur1 = T::addDurationToNow(SETTINGS['values']['show_confirm_link']);
                            $in_future = $v->isInFuture();
                            if ($in_future && $v->isBefore($dur1)) {
                                $r['confirmation_url'] = $this->N->createConfirmationUrl($v, 'simple');
                            }
                            $dur2 = T::addDurationToNow(SETTINGS['values']['show_time_proposal']);
                            if ($in_future && $topic->isLektion() && $v->isBefore($dur2)) {
                                $r['time_proposal'] = $v->getTimeProposal() ?? '';
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

    private function decreaseSecurityLevelIfFromRightSchool(): void
    {
        $visitor = $this->Authorizer->getVisitor();

        if (empty($visitor) || !$visitor->isFromSchool($this->request_school)) {
            return;
        }
        foreach ($this->methods as $method) {
            $this->Authorizer->changeSecurityLevel(self::class, $method, Authorizer::ACCESS_ALL_EXCEPT_GUEST);
        }
    }

    private function setRequestSchool(): void
    {
        $school_id = $this->getParameter('school');
        $school = $this->N->ORM->find('School', $school_id);

        if($school instanceof School){
            $this->request_school = $school;
            return;
        }
        throw new \Exception('No valid school could be found for the school parameter "' . $school_id . '"');
    }


}
