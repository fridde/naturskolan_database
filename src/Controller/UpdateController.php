<?php

namespace Fridde\Controller;

use Fridde\Entities\Group;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Security\Authorizer;
use Fridde\Update;
use Fridde\Utility as U;

class UpdateController extends BaseController
{

    protected $Security_Levels = [
        'updateProperty' => Authorizer::ACCESS_ALL_EXCEPT_GUEST,
        'createNewEntity' => Authorizer::ACCESS_ALL_EXCEPT_GUEST,
        'batchUpdateProperties' => Authorizer::ACCESS_ADMIN_ONLY,
        'checkPassword' => Authorizer::ACCESS_ALL,
        'addDates' => Authorizer::ACCESS_ADMIN_ONLY,
        'addDatesForMultipleTopics' => Authorizer::ACCESS_ADMIN_ONLY,
        'setVisits' => Authorizer::ACCESS_ADMIN_ONLY,
        'sliderUpdate' => Authorizer::ACCESS_ALL_EXCEPT_GUEST,
        'updateVisitOrder' => Authorizer::ACCESS_ADMIN_ONLY,
        'confirmVisit' => Authorizer::ACCESS_ALL,
        'changeGroupName' => Authorizer::ACCESS_ALL_EXCEPT_GUEST,
        'createMissingGroups' => Authorizer::ACCESS_ADMIN_ONLY,
        'fillEmptyGroupNames' => Authorizer::ACCESS_ADMIN_ONLY,
        'batchSetGroupCount' => Authorizer::ACCESS_ADMIN_ONLY,
        'changeTaskActivation' => Authorizer::ACCESS_ADMIN_ONLY,
    ];

    protected static $needs_same_school = [
        'updateProperty',
        'createNewEntity',
        'sliderUpdate',
        'changeGroupName',
    ];

    protected static $allowed_by_user = ['User', 'Group'];

    public function __construct($params)
    {
        parent::__construct($params, true);
    }

    public function handleRequest()
    {
        $update = new Update($this->getFromRequest());
        $update_method = $this->getFromRequest('updateMethod') ?? $this->getFromRequest('update_method');
        if (empty($update_method)) {
            throw new \InvalidArgumentException('Missing updateMethod in $_REQUEST');
        }
        $authorized = $this->Authorizer->authorize($this, $update_method);
        if ($authorized && $this->checkIfValidUpdate($update_method)) {
            $args = U::pluck($this->getFromRequest(), Update::getMethodArgs($update_method));
            call_user_func_array([$update, $update_method], $args);
            $update->flush();
            $return = $update->getReturn();
        } else {
            $return = ['success' => false, 'errors' => ['The updateMethod was not authorized']];
        }

        echo json_encode($return);
    }

    protected function checkIfValidUpdate(string $method): bool
    {
        if (!in_array($method, self::$needs_same_school, true)){
            return true;
        }
        if ($this->Authorizer->getVisitorSecurityLevel() === User::ROLE_ADMIN) {
            return true;
        }
        $visitor_school = $this->Authorizer->getVisitor()->getSchool();
        if(!($visitor_school instanceof School)){
            return false;
        }
        $entity_class = $this->getFromRequest('entity_class');
        $entity_id = $this->getFromRequest('entity_id');

        if(!in_array($entity_class, self::$allowed_by_user, true)){
            return false;
        }

        if($entity_class === 'Group'){
            $group = $this->N->ORM->find('Group', $entity_id);
            if($group instanceof Group){
                return $group->getSchoolId() === $visitor_school->getId();
            }
            throw new \Exception('Tried to update a group that doesn\'t exist');
        }
        if($entity_class === 'User' && $method === 'createNewEntity'){
            $user_school_id = $this->getFromRequest('properties')['School'] ?? null;
            $user_school = $this->N->ORM->find('School', $user_school_id);
            if($user_school instanceof School){
                return $user_school->getId() === $visitor_school->getId();
            }
            return false;
        }
        if($entity_class === 'User'){
            $user = $this->N->ORM->find('User', $entity_id);
            if($user instanceof User){
                return $user->getSchoolId() === $visitor_school->getId();
            }
        }
        return false;
    }

}
