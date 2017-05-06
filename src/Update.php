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
    /** @var array The request data sent into the constructor */
    private $RQ;
    /** @var boolean Is true if any changes have been made that should be flushed by the EM */
    private $is_changed = false;
    /** @var Any new entity that should be saved for future reference */
    private $new_entity;
    /** @var array  */
    private $Return = [];
    /** @var array  */
    private $Errors = [];

    /* request data: ["updateMethod" => "", "entity_class" => "",
    *     "entity_id" => "", "property" => "", "value" => ""]
    */

    public function __construct($request_data)
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->setRQ($request_data);
    }

    /**
    * A static wrapper to both create and execute a Update at the same time.
    *
    * @param  array $request_data The data that should be sent to the constructor
    * @return Fridde\Update Returns itself for chaining.
    */
    public static function create($request_data)
    {
        $THIS = new self($request_data);
        return $THIS->execute();
    }

    /**
    * Executes the actual update according to the value of "updateMethod" given in the
    * array given to the constructor. The updateMethod (or update_method) has to correspond
    * to a method of this class to be executed.
    *
    * @return Fridde\Update Returns itself for chaining.
    */
    public function execute()
    {
        $update_method = $this->getUpdateMethod();
        if(empty($update_method)){
            $this->addError("Error: The updateMethod cannot be empty");
        } elseif (!method_exists($this, $update_method)){
            $this->addError("Error: The update method <". $update_method . "> is not a valid method");
        } else {
            $this->$update_method();
        }
        if ($this->is_changed) {
            $this->N->ORM->EM->flush();
            if(!empty($this->new_entity)){
                $this->setReturn("new_id", $this->new_entity->getId());
            }
        }
        return $this;
    }

    public function handleRequest()
    {

    }



    private function getUpdateMethod()
    {
        $possible_keys = ["update_method", "updateMethod"];
        return array_shift(array_intersect_key($this->RQ, array_flip($possible_keys)));
    }

    public function updateProperty(array $array = null)
    {
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

        $entity->$setter($value);
        $this->N->ORM->EM->persist($entity);
        $this->announceChange();
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

/**
 * Creates new Visits having certain topic using the dates given.
 *
 * Expects $RQ["entity_id"] to contain the id of the topic and $RQ["value"] to be
 * the date array in the format ["YYYY-MM-DD", "YYYY-MM-DD", ...]
 */
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

/**
 * [setVisits description]
 */
    public function setVisits()
    {
        $big_array = $this->getRQ("value");
        $row_to_group_translator = [];
        $group_dates = [];
        foreach($big_array as $array){
            foreach($array as $row_index => $row){
                $class_id = explode("_", $row);
                $class = $class_id[0];
                if(empty($class_id[1]) || $class_id[1] === "null"){
                    $entity_id = null;
                } else {
                    $entity_id =$class_id[1];
                }
                if($class === "group"){
                    if(empty($entity_id)){
                        $e_msg = 'Empty group given at row <' . $row_index;
                        $e_msg .= '>. This is not supposed to happen.';
                        throw new \Exception($e_msg);
                    }
                    $row_to_group_translator[$row_index] = $entity_id;
                } elseif($class === "visit"){
                    $group_dates[$row_index] = $entity_id;
                } else {
                    throw new \Exception("The class <" . $class . "> is not implemented.");
                }
            }
        }
        foreach($group_dates as $row_index => $visit_id){
            $group_id = $row_to_group_translator[$row_index] ?? false;
            if($group_id !== false){
                $visit = $this->findById("Visit", $visit_id);
                $group = $this->findById("Group", $group_id);

                // TODO: continue here
            }
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



    private function createNewEntityFromModel(string $entity_class, $model_entity_id = null)
    {
        $full_class_name = $this->N->ORM->qualifyClassname($entity_class);
        $entity = new $full_class_name();

        if($model_entity_id === 0 || !empty($model_entity_id)){  // since 0 would also count as "empty"
            $model_entity = $this->N->ORM->getRepository($entity_class)->find($model_entity_id);
            $entity = $this->syncProperties($entity, $model_entity, $entity_class);
        }
        return $entity;
    }

    private function createNewEntity(string $entity_class = null, array $properties = [], $model_entity_id = null)
    {
        $properties = $this->replaceIdsWithObjects($entity_class, $properties, $model_entity_id);
        $this->N->ORM->createNewEntity($entity_class, $properties);
        $this->announceChange();
    }

    private function replaceIdsWithObjects($entity_class, $properties, $model_entity_id)
    {
    }

    // event, trackables
    /**
    * Logs a change
    *
    * @return [type] [description]
    */
    private function logChange(bool $new_object = false)
    {
        $repo = $this->N->ORM->getRepository("Change");
        $entity_class = $this->getClassFromEventObject();
        $entity_id = $this->getRQ("event")->getObject()->getId();
        $event = $this->getRQ("event");

        if($new_object){
            $rq = ["update_method" => "createNewEntity"];
            $rq["entity_class"] = "Change";
            // the new object is a Change, but the parameter is EntityClass
            $props["EntityClass"] = $entity_class;
            $props["EntityId"] = $entity_id;
            $rq["properties"] = $props;
            return self::create($rq);
        }

        $change_criteria[] = ["isNull", "Processed"];
        $change_criteria[] = ["EntityClass", $entity_class];
        $change_criteria[] = ["EntityId", $entity_id];

        $common_keys = ["EntityClass", "EntityId", "Property", "OldValue"];
        $props_to_track = $this->getRQ("trackables");
        foreach($props_to_track as $property){
            if($event->hasChangedField($property)){
                $change_criteria[] = ["Property", $property];
                $result = $repo->selectAnd($change_criteria);
                if(empty($result)){
                    $rq = ["update_method" => "createNewEntity"];
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

    private function logNewEntity()
    {
        $this->logChange(true);
    }

    private function getClassFromEventObject($event = null)
    {
        $event = $event ?? $this->getRQ("event");
        $ec_array = explode('\\', get_class($event->getObject()));
        return array_pop($ec_array);
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

    private function syncProperties($entity, $model_entity, string $entity_class = null)
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


    public function changeGroupName()
    {
        $a["entity_class"] = "Group";
        $a["property"] = "Name";
        $a["value"] = $this->getRQ("value");
        $a["entity_id"] = $this->getRQ("entity_id");
        $this->setReturn("newName", $this->getRQ("value"));
        $this->setReturn("groupId", $this->getRQ("entity_id"));
        $this->updateProperty($a);
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
    *  Quick function to get "updateMethod" from $RQ
    *
    * @return string The updateMethod.
    */
    public function getUpdateType()
    {
        return $this->getRQ("updateMethod");
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
