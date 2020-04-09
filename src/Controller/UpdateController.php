<?php

namespace Fridde\Controller;

use Fridde\Annotations\NeedsSameSchool;
use Fridde\Entities\Group;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Update;
use Fridde\Utility as U;

class UpdateController extends BaseController
{

    protected static $allowed_by_user = ['User', 'Group'];

    public function __construct($params)
    {
        parent::__construct($params, true);
    }

    public function handleRequest(): ?string
    {
        $update = new Update($this->getFromRequest());
        $update_method = $this->getFromRequest('updateMethod') ?? $this->getFromRequest('update_method');
        if (empty($update_method)) {
            throw new NException(Error::INVALID_ARGUMENT, ['updateMethod in $_REQUEST']);
        }
        $authorized = $this->Authorizer->authorize(Update::class, $update_method);
        if ($authorized && $this->checkIfValidUpdate($update_method)) {
            $args = U::pluck($this->getFromRequest(), $update->getMethodArgs($update_method));
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
        if (!$this->needsSameSchool($method)) {
            return true;
        }
        if ($this->Authorizer->getVisitorSecurityLevel() >= User::ROLE_ADMIN) {
            return true;
        }
        $visitor_school = $this->Authorizer->getVisitor()->getSchool();
        if (!($visitor_school instanceof School)) {
            return false;
        }
        $entity_class = $this->getFromRequest('entity_class');
        $entity_id = $this->getFromRequest('entity_id');

        if (!in_array($entity_class, self::$allowed_by_user, true)) {
            return false;
        }

        // the method requires that the visitor is from the same school as the User or Group the update concerns
        if ($entity_class === 'Group') {
            $group = $this->N->ORM->find('Group', $entity_id);
            if ($group instanceof Group) {
                return $group->getSchoolId() === $visitor_school->getId();
            }
            throw new NException(Error::UNAUTHORIZED_ACTION, ['Update group '. $entity_id]);
        }
        if ($entity_class === 'User' && $method === 'createNewEntity') {
            $user_school_id = $this->getFromRequest('properties')['School'] ?? null;
            $user_school = $this->N->ORM->find('School', $user_school_id);
            if ($user_school instanceof School) {
                return $user_school->getId() === $visitor_school->getId();
            }

            return false;
        }
        if ($entity_class === 'User') {
            $user = $this->N->ORM->find('User', $entity_id);
            if ($user instanceof User) {
                return $user->getSchoolId() === $visitor_school->getId();
            }
        }

        return false;
    }

    private function needsSameSchool(string $method_name)
    {
        $reader = $this->N->ORM->getAnnotationReader();

        return $reader->hasMethodAnnotation(Update::class, $method_name, NeedsSameSchool::class);
    }

}
