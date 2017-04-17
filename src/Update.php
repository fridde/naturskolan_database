<?php
/**
* Contains the class Update.
*/
namespace Fridde;

use Fridde\Utility as U;
use Fridde\Entities\Cookie;
use Carbon\Carbon;


/**
* Contains the logic to update records in the database.
*/
class Update
{
    private $N;
    /** @var The request data sent into the constructor */
    private $RQ;
    /** @var Is true if any changes have been made that should be flushed by the EM */
    private $is_changed = false;
    /** @var Any new entity that should be saved for future reference */
    private $new_entity;
    /** @var  */
    private $Return = [];
    private $Errors = [];

    /* request data: ["updateType" => "", "entity_class" => "",
    *     "entity_id" => "", "property" => "", "value" => ""]
    */
    public function __construct($request_data = []){

        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->setRQ($request_data);
    }

    /**
    * A static wrapper to both create and execute a Update at the same time.
    *
    * @param  array $request_data The data that should be sent to the constructor
    * @return Fridde\Update Returns itself for chaining.
    */
    public static function create($request_data){
        $THIS = new self($request_data);
        return $THIS->execute();
    }

    /**
    * Executes the actual update according to the value of "updateType" given in the
    * array given to the constructor. The updateType (or update_type) has to correspond
    * to a method of this class to be executed.
    *
    * @return Fridde\Update Returns itself for chaining.
    */
    public function execute()
    {
        $updateType = $this->getRQ("updateType") ?? $this->getRQ("update_type"); // different spellings
        if(empty($updateType)){
            $this->addError("Error: The updateType cannot be empty");
        } elseif (!method_exists($this, $updateType)){
            $this->addError("Error: The UpdateType <". $updateType . "> is not a valid method");
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

    /**
    * Checks if $RQ["password"] corresponds to any school and saves the matching
    * school_id into $RETURN for the callback to receive.
    *
    * @return void
    */
    public function checkPassword()
    {
        $school_id = $this->N->checkPassword($this->getRQ("password"));
        if($school_id){
            $this->setReturn("school", $school_id);
        } else {
            $this->addError("Wrong password!");
        }

    }

    public function addDates()
    {
        $topic = $this->findById("Topic", $this->getRQ("entity_id"));
        $dates = $this->getRQ("value");
        $properties = ["Topic" => $topic];
        foreach($dates as $date){
            $properties["Date"] = trim($date);
            $this->createNewEntity("Visit", $properties);
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
            $rq = ["entity_class", "entity_id", "property", "value"];
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

    private function createNewEntity($entity_class = null, $properties = null)
    {
        $entity_class = $entity_class ?? $this->getRQ("entity_class");
        $properties = $properties ?? $this->getRQ("properties");
        $full_class_name = $this->N->ORM->qualifyClassname($entity_class);
        $entity = new $full_class_name();
        foreach($properties as $property => $value){
            $method_name = "set" . ucfirst($property);
            $entity->$method_name($value);
        }
        $this->N->ORM->EM->persist($entity);
        $this->announceChange();
    }

    // event, trackables
    /**
    * Logs a change
    *
    * @return [type] [description]
    */
    private function logChange()
    {
        $common_keys = ["EntityClass", "EntityId", "Property", "OldValue"];
        $repo = $this->N->ORM->getRepository("Change");
        $event = $this->getRQ("event");
        $change = [["isNull", "Processed"]];
        $ec_array = explode('\\', get_class($event->getObject()));
        $change[] = ["EntityClass", array_pop($ec_array)];
        $change[] = ["EntityId", $event->getObject()->getId()];

        $props_to_track = $this->getRQ("trackables");
        foreach($props_to_track as $property){
            if($event->hasChangedField($property)){
                $c = $change;
                $c[] = ["Property", $property];
                $result = $repo->select($c);
                if(empty($result)){
                    $rq = ["update_type" => "createNewEntity"];
                    // might be confusing, but this is the class for the new entity, i.e. the table row
                    $rq["entity_class"] = "Change";
                    $old_value = $event->getOldValue($property);
                    if(is_object($old_value)){
                        $old_value = $old_value->getId();
                    }
                    $c[] = ["OldValue", $old_value];
                    $rq["properties"] = U::pluck(array_column($c, 1, 0), $common_keys);
                    self::create($rq);
                }
            }
        }
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

    /**
    * Prepares and returns the answer to the request for further handling by JS or
    * other parts of the app.
    *
    * @param  string|null $key If specified, only the value of $Return[$key] is returned.
    * @return array|mixed If no key was specified, the whole $Return is returned.
    *                     It contains ["onReturn" => "...", "success" => true|false,
    *                     "errors" => [...]]
    */
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

    /**
    * Sets $Return[$key]  with either a given $value or with a value taken from the
    * initial request $RQ.
    *
    * @param string|array  $key The key to set. If array each key-value pair
    *                           are the arguments for this function.
    * @param mixed|null  $value  The value to set at $Return[$key]. If null,
    *                            the function looks in the request given to the constructor.
    * @param boolean $ignoreValue If true, the function looks automatically in the request
    *                             and ignores any value given as a parameter.
    */
    public function setReturn($key, $value = null, $ignoreValue = false)
    {
        // ["key1" => val1, "key2" => val2, ...]
        if(is_array($key)){
            array_walk($key, [$this, "setReturn"], true);
        }
        // setReturn("key", value, false)
        elseif(isset($value) && !$ignoreValue){
            $this->Return[$key] = $value;
        }
        // setReturn("key", null, false) OR setReturn("key", value, true)
        else {
            $this->Return[$key] = $this->getRQ($key);
        }
        return $this;
    }

    /**
    *  Quick function to get "updateType" from $RQ
    *
    * @return string The updateType.
    */
    public function getUpdateType()
    {
        return $this->getRQ("updateType");
    }

    /**
    * Returns $Errors.
    *
    * @return string[] All error strings as array.
    */
    public function getErrors()
    {
        return $this->Errors;
    }

    /**
    * Adds error string as element to $Errors.
    *
    * @param string $error_string A string describing the error.
    */
    public function addError($error_string)
    {
        $this->Errors[] = $error_string;
    }

    /**
    * Checks if $Errors is not empty.
    *
    * @return boolean Returns true if $Errors is not empty.
    */
    public function hasErrors()
    {
        return !empty($this->getErrors());
    }

    /**
    * Sets $is_changed to true.
    *
    * @return void
    */
    private function announceChange()
    {
        $this->is_changed = true;
    }

    /**
     * Quick shortcut to retrieving an entity by id.
     *
     * @param  string $entity_class The (unqualified) class name of the entity.
     * @param  integer|string $id The id of the entity to look for.
     * @return object|null The entity or null if no entity was found.
     */
    private function findById($entity_class, $id)
    {
        return $this->N->ORM->getRepository($entity_class)->find($id);
    }

    /**
     * Quick shortcut to get a user only using the id.
     *
     * @param  integer $id The id of the User to look for
     * @return Fridde\Entities\User The User (or null if no User found)
     */
    private function getUser($id)
    {
        return $this->findById("User", $id);
    }


    /**
     * Takes an array and converts all numerical indices to strings by using
     * its respective value as index.
     *
     * @param  array $array The array to unmix
     * @return [type]        [description]
     */
    private function unmixArray($array)
    {
        $strings_only = array_filter($array, "is_string");
        $unique = array_unique($array);
        //if(count($strings_only))
        //TODO: continue here with the check!
        $return = [];
        foreach($array as $key=>$val){
            $key = is_integer($key) ? $val : $key;
            $return[$key] = $val;
        }
        return $return;
    }

}
