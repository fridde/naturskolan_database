<?php
namespace Fridde;

use Fridde\Entities\{Cookie};
use Carbon\Carbon;

class Update
{
    private $N;
    private $RQ;  // contains $_REQUEST
    private $is_changed = false;
    private $new_entity;
    private $Return = [];
    private $Errors = [];

    private $defined_methods = ["updateProperty", "checkPassword", "setCookie",
    "deleteCookie", "updateGroupName", "sliderUpdate", "updateVisitOrder",
    "confirmVisit", "createNewEntity"];

    /* request data: ["updateType" => "", "entity_class" => "",
    *     "entity_id" => "", "property" => "", "value" => ""]
    */
    public function __construct($request_data = []){

        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->setRQ($request_data);
    }

    public static function create($request_data){
        $THIS = new self($request_data);
        return $THIS->execute();
    }

    public function execute()
    {
        $updateType = $this->getRQ("updateType");
        if(empty($updateType)){
            $this->addError("Error: The updateType can not be empty");
        } elseif (!in_array($updateType, $this->defined_methods)){
            $this->addError("Error: The UpdateType ". $updateType . "was not recognized.");
        } else {
            $this->$updateType();
        }
        if ($this->is_changed) {
            $this->N->ORM->EM->flush();
            if(!empty($this->new_entity)){
                $this->setReturn("new_id", $this->new_entity->getId());
            }
        }
        return $this;
    }

    public function checkPassword()
    {
        $school_id = $this->N->checkPassword($this->getRQ("password"));
        if($school_id){
            $this->setReturn("school", $school_id);
        } else {
            $this->addError("Wrong password!");
        }

    }

    public function setCookie()
    {
        $hash = $this->N->createHash();
        $expiration_date = Carbon::now()->addDays(90)->toIso8601String();
        $cookie = new Cookie();
        $school = $this->N->ORM->getRepository("School")->find($this->getRQ("school"));
        $cookie->setValue($hash)->setName("Hash")->setSchool($school);
        $cookie->setRights("school_only");
        $this->N->ORM->EM->persist($cookie);
        $this->announceChange();
        $this->setReturn("hash", $hash)->setReturn("school", $school->getId());
    }

    public function updateProperty($array = null)
    {
        if(empty($array)){
            $rq = ["entity_class" => "entity", "entity_id", "property", "value"];
            extract($this->getRQ($rq));
        } else {
            extract($array);
        }

        $parameter_array = compact("entity_class", "entity_id", "property", "value");
        if(! $this->checkParameters($parameter_array)){
            return;
        }
        if(substr($entity_id, 0, 3) == "new"){
            $this->setReturn("old_id", $entity_id);
            $temp = explode("#", $entity_id);
            $model_entity_id = array_pop($temp);
            $entity = $this->createNewEntityFromModel($entity_class,  $model_entity_id);
            $this->new_entity = $entity;
        } else {
            $entity = $this->N->ORM->getRepository($entity_class)->find($entity_id);
            if(empty($entity)){
                $e = "No entity of the class<" . $entity_class . "> with the id <";
                $e .= $entity_id . "> could be found.";
                throw new \Exception($e);
            }
        }
        $setter = "set" . $property;

        if (! method_exists($entity, $setter)) {
            $this->addError("The method " . $setter . " for the class " . $entity . " could not be found");
            return null;
        }

        $entity->$setter($value, $this->N->ORM);
        $this->N->ORM->EM->persist($entity);
        $this->announceChange();
    }

    private function createNewEntityFromModel($entity_class, $model_entity_id = null)
    {
        $full_class_name = $this->N->ORM->qualifyClassname($entity_class);
        $entity = new $full_class_name();

        if($model_entity_id === 0 || !empty($model_entity_id)){
            $model_entity = $this->N->ORM->getRepository($entity_class)->find($model_entity_id);
            $entity = $this->syncProperties($entity, $model_entity, $entity_class);
        }
        return $entity;
    }

    private function createNewEntity()
    {
        $entity_class = $this->getRQ("entity_class");
        $properties = $this->getRQ("properties");
        $full_class_name = $this->N->ORM->qualifyClassname($entity_class);
        $entity = new $full_class_name();
        foreach($properties as $property => $value){
            $method_name = "set" . ucfirst($property);
            $entity->$method_name($value);
        }
        $this->N->ORM->EM->persist($entity);
        $this->announceChange();
    }

    private function checkParameters($parameters)
    {
        $missing_parameter = false;
        array_walk($parameters, function($val, $key) use(&$missing_parameter){
            if(!isset($val)){
                $this->addError('Important parameter "'. $key .'" missing. Can\'t update.');
                $missing_parameter = true;
            }
        });
        if($missing_parameter){
            return false;
        }
        return true;
    }

    private function syncProperties($entity, $model_entity, $entity_class = null)
    {
        if(empty($entity_class)){
            $tmp = explode('\\', get_class($model_entity));
            $entity_class = array_pop($tmp);
        }

        switch($entity_class){
            case "User":
            $entity->setSchool($model_entity->getSchool());
            break;
        }
        return $entity;
    }

    public function sliderUpdate()
    {
        $this->setReturn(["sliderId", "sliderLabelId"]);
        $this->setReturn("newValue", $this->getRQ("value"));
        $this->updateProperty();
    }

    private function updateVisitOrder()
    {
        $ordered_ids = $this->getRQ("order");
        foreach($ordered_ids as $index => $id){
            $a["entity_class"] = "School";
            $a["entity_id"] = $id;
            $a["property"] = "VisitOrder";
            $a["value"] = $index + 1;
            $this->updateProperty($a);
        }

    }

    private function confirmVisit()
    {
        $a["entity_class"] = "Visit";
        $a["entity_id"] = $this->getRQ("visit_id");
        $a["property"] = "Confirmed";
        $a["value"] = true;
        $this->updateProperty($a);
    }

    public function deleteCookie()
    {
    }

    public function updateGroupName()
    {
    }

    public function getRQ($key = null)
    {
        if(empty($this->RQ)){
            $this->addError("Error: The request data was empty");
            return null;
        } elseif (is_array($key)){
            return array_map(function($v){
                return $this->getRQ($v);
            }, $this->unmixArray($key));
        } elseif(is_string($key)){
            return $this->RQ[$key] ?? null;
        } else {
            return $this->RQ;
        }
    }

    public function setRQ($RQ)
    {
        $this->RQ = $RQ;
        return $this;
    }

    public function getReturn($key = null)
    {
        if(empty($key)){
            $this->setReturn("onReturn");
            $this->setReturn("success", !$this->hasErrors());
            $this->setReturn("errors", $this->getErrors());
            return $this->Return;
        }
        return $this->Return[$key];
    }

    public function setReturn($key, $value = null, $ignoreValue = false)
    {
        if(is_array($key)){
            array_walk($key, [$this, "setReturn"], true);
        } elseif(isset($value) && !$ignoreValue){
            $this->Return[$key] = $value;
        } else {
            $this->Return[$key] = $this->getRQ($key);
        }
        return $this;
    }


    public function getUpdateType(){
        return $this->getRQ("updateType");
    }

    public function getErrors()
    {
        return $this->Errors;
    }
    public function addError($error_string){$this->Errors[] = $error_string;}

    public function hasErrors(){return !empty($this->getErrors());}

    private function announceChange()
    {
        $this->is_changed = true;
    }

    private function findById($entity_class, $id)
    {
        $e = $this->N->ORM->getRepository($entity_class)->find($id);
        return $e;
    }

    private function getUser($id)
    {
        return $this->findById("User", $id);
    }



    private function unmixArray($array)
    {
        $return = [];
        foreach($array as $key=>$val){
            $key = is_integer($key) ? $val : $key;
            $return[$key] = $val;
        }
        return $return;
    }

}
