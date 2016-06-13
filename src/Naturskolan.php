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
		public $allowed_object_types = ["user", "school", "group", "visit", "message", "topic", "event", "busstrip", "password", "location", "session"];
		public $unusual_plurals = []; //e.g. ["pony" => "ponies"]
		public $standardColumns = ["school" => "ShortName"];
		//
		
		function __construct ()
		{
			$this->SQL = new SQL;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			*
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
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
				$return["object"] = (is_null($object)) ? [] : $object ;
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
			$c->query->execute();
			
		}
		
		public function delete($object_type, $criteria)
		{
			extract($this->prepareMethod($object_type, "delete", $criteria));
			$c->delete();
			$this->applyWhere($c, $criteria);
			$c->query->execute();
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
			$column = "id";
			$object_type = $this->singularizeObject($object_type);
			if(isset($this->standardColumns[$object_type])){
				$column = $this->standardColumns[$object_type];
			}
			return $column;
		}
		
		private function standardizeCriteria($criteria, $object_type)
		{
			if(!is_array($criteria)){
				$criteria = [[$this->getStandardColumn($object_type), $criteria]];
			}
			elseif(count($criteria) > 0){
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
				$r = ["School" => $school, "IsRektor" => 0];
				break;
				
				case "":
				break;
				
				case "":
				break;
				
				default:
				$r = [];
				
			}
			return $r;
			
		}
		
		public function test($function, $arg)
		{
			return $this->$function($arg);
		}
	}
	
