<?php
namespace Fridde;

//use Carbon\Carbon;
use Fridde\{Naturskolan, ORM};
use Fridde\Entities\{Password};

class Update
{
    private $N;
    private $ORM;
    private $RQ;
    private $Return = [];
    private $UpdateType;
    private $Errors = [];
    // translates UpdateType to method
    private $defined_methods = ["checkPassword", "setCookie", "deleteCookie", "updateGroupName"];


    public function __construct($request_data = []){

        $this->ORM = new ORM();
        $this->setRQ($request_data);
    }

    public function execute()
    {
        $updateType = $this->getUpdateType();
        if(empty($this->defined_methods[$updateType])){
            $this->addError("Error: The UpdateType ". $updateType . "was not recognized.");
        } else {
            $this->$updateType();
        }
        return $this;
    }

//"", "", "", ""
    public function checkPassword()
    {
    }

    public function setCookie()
    {
        $hash = Naturskolan::createHash();
		$expiration_date = Carbon::now()->addDays(90)->toIso8601String();
        $pw = new Password();
        $school = $this->ORM->getRepository("School")->find($this->getRQ("school"));
        $pw->setValue($hash)->setType(Password::COOKIE_HASH)->setSchool($school);
        $pw->setRights(Password::SCHOOL_ONLY);
        $this->ORM->EM()->persist($pw);
        $this->ORM->EM()->flush();

		$this->setReturn("hash", $hash);
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
        } elseif(!empty($key)){
            return $this->RQ[$key];
        } else{
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
            return $this->Return;
        }
        return $this->Return[$key];
    }

    public function setReturn($key, $value)
    {
        $this->Return[$key] = $value;
        return $this;
    }

    public function getUpdateType(){return $this->UpdateType;}

    public function setUpdateType($UpdateType = null)
    {
        if(empty($UpdateType) && !empty($this->RQ["UpdateType"])){
            $this->UpdateType = $this->RQ["UpdateType"];
        }
        $this->UpdateType = $UpdateType;
        if(empty($UpdateType)){
            $this->addError("UpdateType could not be defined.");
        }
        return $this;
    }

    public function getErrors()
    {
        return $this->Errors;
    }
    public function addError($error_string){$this->Errors[] = $error_string;}

    public function hasErrors(){return !empty($this->getErrors());}

}
