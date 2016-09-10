<?php
namespace Fridde;

use \Fridde\SQL;
use \Fridde\NSDB_Mailchimp as MC;
use \Fridde\Mailer;
use \Fridde\Utility as U;

class Naturskolan
{
	public $SQL;
	public $allowed_methods = ["create", "get", "update", "delete"];
	public $allowed_object_types = ["busstrip", "event", "group", "location",
	"password", "school", "sentmessage", "session", "task", "topic", "user", "visit"];
	public $unusual_plurals = []; //e.g. ["pony" => "ponies"]
	public $standardColumns = ["school" => "Name"];

	function __construct ()
	{
		$this->SQL = new SQL;
	}

	/**
	* Wrapper for CRUD-queries.
	*
	* [Description]
	*
	* @param [Type] $[Name] [Argument description]
	*
	* @return Array $return Contains "c": The SQL-object
	* "criteria": the standardized criteria for the queries (not for "create")
	* "object": the object to insert (or an empty array)
	* "get_first_only": boolean that tells if the word was singular
	*/
	private function prepareMethod($object_type, $method, $criteria = array(), $object = null){

		$this->setTable($object_type);
		$this->checkMethod($method);
		$return = array();
		$return["c"] = $this->SQL;

		if(in_array($method, ["get", "update", "delete"])){
			$return["criteria"] = $this->standardizeCriteria($criteria, $object_type);
		}
		if(in_array($method, ["update", "create"])){
			$return["object"] = $object ?? [] ;
		}
		if(in_array($method, ["get"])){
			$return["get_first_only"] = $this->isSingleObject($object_type);
		}
		return $return;
	}

	public function create($object_type, $object)
	{
		extract($this->prepareMethod($object_type, "create", [], $object));
		return $c->insert($object);
	}

	/**
	* [get description]
	* @param  [type] $object_type [description]
	* @param  [type] $criteria    [description]
	* @param  [type] $field       In case you just want to fetch a single field
	* from the table. The id-column will follow anyway.
	* @return [type]              [description]
	*/
	public function get($object_type, $criteria = [], $field = null)
	{
		$object_and_field = explode("/", $object_type);
		if(count($object_and_field) == 2){
			$object_type = $object_and_field[0];
			$field = $object_and_field[1];
		}
		extract($this->prepareMethod($object_type, "get", $criteria));
		$c->select();
		$this->applyWhere($c, $criteria);
		$c->query->execute();
		$result = $c->fetch();
		if(isset($field)){
			$result = array_combine(array_column($result, "id"), array_column($result, $field));
		}
		if ($get_first_only){
			$result = reset($result);
		}
		return $result;
	}

	public function update($object_type, $object, $criteria)
	{
		extract($this->prepareMethod($object_type, "update", $criteria, $object));
		$c->update();
		$this->applyWhere($c, $criteria);
		$this->applySet($c, $object);
		return $c->query->execute();

	}

	public function delete($object_type, $criteria)
	{
		extract($this->prepareMethod($object_type, "delete", $criteria));
		$c->delete();
		$this->applyWhere($c, $criteria);
		return $c->query->execute();
	}

	private function applyWhere($connection, $criteria){
		if(count($criteria) > 0){
			foreach($criteria as $criterium){
				if (count($criterium) == 2){
					$connection->query->where($criterium[0], $criterium[1]);
				}
				elseif (count($criterium) == 3){
					$connection->query->where($criterium[0], $criterium[1], $criterium[2]);
				}
				else {
					throw new \Exception("The array [". join("][", $criterium) . "]is not a valid argument for a where-query");
				}
			}
		}
	}

	private function applySet($connection, $object){

		if(count($object) > 0){
			foreach($object as $column => $value){
				$connection->query->set($column, $value);
			}
		}
	}

	private function isSingleObject($object_type)
	{
		$object_type = strtolower(trim($object_type));
		$is_plural = in_array($object_type, $this->unusual_plurals);
		$is_singular = isset($this->unusual_plurals[$object_type]);
		$ends_on_s = substr($object_type, -1) == "s";

		if (($ends_on_s && !$is_singular) || $is_plural){
			return false;
		}
		else {
			return true;
		}
	}

	private function singularizeObject($object_type)
	{
		if($this->isSingleObject($object_type)){
			return $object_type;
		}
		else {
			$unusal_singulars = array_flip($this->unusual_plurals);
			$return = (isset($unusal_singulars[$object_type])) ? $unusal_singulars[$object_type] :  substr($object_type, 0, -1);
			return $return;
		}
	}

	private function getStandardColumn($object_type)
	{
		$object_type = $this->singularizeObject($object_type);
		$column = $this->standardColumns[$object_type] ?? "id";

		return $column;
	}

	private function standardizeCriteria($criteria, $object_type)
	{
		if(!is_array($criteria)){
			$criteria = [[$this->getStandardColumn($object_type), $criteria]];
		} elseif(count($criteria) > 0){
			$first_element = reset($criteria);
			$criteria = (is_array($first_element)) ? $criteria : [$criteria] ;
		}
		return $criteria;
	}

	private function setTable($object_type, $direct = false)
	{
		/* for objects that are not stored in a table named "plural(object)", e.g. object "event" is NOT stored in a table "events"
		the naming rule is given in $unusual_tables as $object_name => $table_name
		*/
		if($direct){
			$table = $object_type;
		}
		else {
			$object_type = $this->singularizeObject($object_type);
			if (!in_array($object_type, $this->allowed_object_types)){
				throw new \Exception("'" . $object_type . "' is not an allowed object type");
			}
			$table = (isset($this->unusual_plurals[$object_type])) ? $this->unusual_plurals[$object_type] : $object_type . "s" ;
		}
		$this->SQL->setTable($table);
	}

	private function checkMethod($method)
	{
		if (!in_array($method, $this->allowed_methods)){
			throw new \Exception("'" . $method . "' is not an allowed function to use");
		}
	}

	public function createPassword($school, $length = 4)
	{
		$alpha = range('a', 'z');
		$password = $school . "_";
		foreach (range(1,$length) as $i){
			$password .= $alpha[mt_rand(0, count($alpha) - 1)];
		}
		return $password;
	}

	public function createHash()
	{
		$hash_string = password_hash(microtime(), PASSWORD_DEFAULT);
		$hash_array = explode("$", $hash_string);
		$hash = implode("", array_slice($hash_array, 3));

		return $hash;
	}

	public function orderSchools(){

	}

	/**
	* [Summary].
	*
	* [Description]
	*
	* @param array $unformatted_array multi-array where every row comprises one row from a certain table. Each row should at least contain "id", and the needed columns
	*
	* @return array $return_array contains array of formatted strings using the id as keys
	*/
	public function format($unformatted_array, $type = "")
	{
		$format = function($v, $k) use ($type){
			switch($type){

				case "user":
				$r =  $v["FirstName"] . " " . $v["LastName"] . ", " . strtoupper($v["School"]);
				break;

				case "group":
				$r = ($v["Name"] == "") ? $v["Grade"] . "." .$v["id"] : $v["Name"];
				$r .= ", " . strtoupper($v["School"]);
				break;

				case "topic":
				$r = $v["Grade"] . "." . $v["VisitOrder"] . " " . $v["ShortName"];
				break;

				case "colleague":
				$r = strtoupper(substr($v["FirstName"], 0, 1) . substr($v["LastName"], 0, 1));
				break;

				case "school":
				$r = $v["Name"];
				break;

				case "location":
				$r = $v["Name"];
				break;

				default:
				$r = $v;
				$v = ["id" => $k];
			}
			return [$r, $v["id"]];
		};
		$formatted_array = array_map($format, $unformatted_array, array_keys($unformatted_array));

		return $formatted_array;
	}

	public function getStandardValues($table_name, $old_id = null)
	{

		switch($table_name){
			case "users":
			$school = $this->get("user/School", ["id", $old_id]);
			$r = ["School" => $school, "Status" => 1, "DateAdded" => date("c")];
			break;


			default:
			$r = [];

		}
		return $r;

	}

	public function addTask($taskType, $options = [])
	{
		$user = $options["user"] ?? "admin";
		$min_delay = $options["min_delay"] ?? 3; //in hours
		$max_delay = $options["max_delay"] ?? 24; // in hours
		$parameters = $options["parameters"] ?? [];

		$existing_tasks = $this->get("tasks");

	}


	/**
	* Wrapper for the different regular tasks that are queued and executed
	* within Naturskolan Database
	* @return [boolean] $success
	*/
	public function executeTask($task_type, $list = [])
	{
		$success = false;

		switch($task_type){
			case "cal_rebuild":
			// TODO: implement rebuild_calendar().
			// if number of tasks in task_group <= 1, schedule rebuild in 24h
			// $success = ;
			break;

			case "mail":
			//TODO: implement compile_mail($task_group)
			//		$success = ;
			break;

			case "sms":
			//TODO: implement send_sms($task_group)
			//	$success = ;
			break;

			case "check_status":
			// TODO: checkStatus-function
			// 		$success = ;
			break;

			case "delete_user":
			break;

			default:
			//TODO: logg $type as unknown
		}

		return $success;
	}

}
