<?php
namespace Fridde;

//use Carbon\Carbon;
use Fridde\{Naturskolan, ORM};
use Fridde\Entities\{Password};
use Carbon\Carbon;

class Update
{
    private $ORM;
    private $RQ;  // contains $_REQUEST
    private $is_changed = false;
    private $Return = [];
    private $Errors = [];

    private $defined_methods = ["updateProperty", "checkPassword", "setCookie",
    "deleteCookie", "updateGroupName", "sliderUpdate"];
    //const SPECIAL_SETTER = ["Group" => ["User"]];


    public function __construct($request_data = []){

        $this->ORM = new ORM();
        $this->setRQ($request_data);
    }

    public function execute()
    {
        $updateType = $this->getUpdateType();
        if(empty($updateType)){
            $this->addError("Error: The updateType can not be empty");
        } elseif (!in_array($updateType, $this->defined_methods)){
            $this->addError("Error: The UpdateType ". $updateType . "was not recognized.");
        } else {
            $this->$updateType();
        }
        if ($this->is_changed) {
            $this->ORM->EM->flush();
        }
        return $this;
    }

    public function checkPassword()
    {
        $password = $this->ORM->getRepository("Password")->findByPassword($this->getRQ("password"));
        if(!empty($password)){
            $this->setReturn("status", "success")->setReturn("school", $password->getSchool()->getId());
        }
    }

    public function setCookie()
    {
        $hash = Naturskolan::createHash();
		$expiration_date = Carbon::now()->addDays(90)->toIso8601String();
        $pw = new Password();
        $school = $this->ORM->getRepository("School")->find($this->getRQ("school"));
        $pw->setValue($hash)->setType(Password::COOKIE_HASH)->setSchool($school);
        $pw->setRights(Password::SCHOOL_ONLY);
        $this->ORM->EM->persist($pw);
        $this->announceChange();
		$this->setReturn("hash", $hash)->setReturn("school", $school->getId());
    }

    public function updateProperty()
    {
        $rq = ["entity_class" => "entity", "entity_id", "property", "value"];
        extract($this->getRQ($rq));

        $missing_parameter = false;
        $parameter_array = compact("entity_class", "entity_id", "property", "value");
        array_walk($parameter_array, function($val, $key) use(&$missing_parameter){
            if(!isset($val)){
                $this->addError('Important parameter "'. $key .'" missing. Can\'t update.');
                $missing_parameter = true;
            }
        });
        if($missing_parameter){
            return;
        }
        $entity = $this->ORM->getRepository($entity_class)->find($entity_id);
        $setter = "set" . $property;
        /* $special_setter_needed = in_array($property, self::SPECIAL_SETTER[$entity_class]);
        if($special_setter_needed){
            $value = $this->ORM->getRepository($property)->find($value);
        }
        */
        if (! method_exists($entity, $setter)) {
            $this->addError("The method " . $setter . " for the class " . $entity . " could not be found");
            return null;
        }

        $entity->$setter($value, $this->ORM);
        $this->ORM->EM->persist($entity);
        $this->announceChange();
    }

    public function sliderUpdate()
    {
        $this->setReturn(["sliderId", "sliderLabelId"]);
        $this->setReturn("newValue", $this->getRQ("value"));
        $this->updateProperty();
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
        return $this->RQ["updateType"] ?? null;
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
        $e = $this->ORM->getRepository($entity_class)->find($id);
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
