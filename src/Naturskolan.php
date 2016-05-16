<?php
	namespace Fridde;
	
	use \Fridde\SQL;
	use \Fridde\NSDB_Mailchimp as MC;
	use \Fridde\Mailer;
	
	class Naturskolan
	{
		public $SQL;
		public $allowed_methods = ["create", "get", "update", "delete"];
		public $allowed_object_types = ["user", "school", "group", "visit", "message", "topic", "event", "busstrip", "password", "location"];
		public $unusual_plurals = ["mars" => "marses"]; //e.g. ["pony" => "ponies"]
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
			$return["get_first_only"] = $this->isSingleObject($object_type);
			$return["criteria"] = $this->standardizeCriteria($criteria);
			
			return $return;
		}
		
		public function create($object_type, $object)
		{
			
		}
		
		public function get($object_type, $criteria)
		{
			if(!is_array($criteria)){
				$criteria = [$this->getStandardColumn($object_type), $criteria];
			}
			extract($this->prepareMethod($object_type, "get", $criteria));
			$c->select();
			foreach($criteria as $criterium){
				if (count($criterium) == 2){
					$c->query->where($criterium[0], $criterium[1]);
				}
				elseif (count($criterium) == 3){
					$c->query->where($criterium[0], $criterium[1], $criterium[2]);
				}
				else {
					throw new \Exception("The array [". join("][", $criterium) . "]is not a valid argument for a where-query");
				}
				
			}
			$c->query->execute();
			$result = $c->fetch();
			if ($get_first_only){
				$result = reset($result);
			}
			return $result;
		}
		
		public function update($object_type, $object, $criteria)
		{
			
		}
		public function delete($object_type, $criteria)
		{
			
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
		
		private function standardizeCriteria($criteria)
		{
			$first_element = reset($criteria);
			$criteria = (is_array($first_element)) ? $criteria : [$criteria] ;
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
		
		
		
		public function getUser($criteria, $get_first = true, $method = "id")
		{
			switch($method){
				
				case "":
				break;
				
				default:
				$user = $this->apply("user", "get", [$method => $criteria]);
				
			}
			if ($get_first !== false){
				$user = $this->SQL->getFirst($user, $get_first);
			}
			return $user; 
		}
		
		public function getSchool($criteria, $get_first = true, $method = "ShortName")
		{
			switch($method){
				case "user_id":
				$user_school = $this->getUser($criteria, "School");
				$school = $this->getSchool($user_school, false);
				break;
				
				default:
				$school = $this->apply("school", "get", [$method => $criteria]);
			}
			if ($get_first !== false){
				$school = $this->SQL->getFirst($school, $get_first);
			}
			return $school;
		}
		
		public function getGroup($criteria, $get_first = true, $method = "id")
		{
			switch($method){
				case "school_id":
				$group = $this->getGroup($criteria, false, "School");
				break;
				
				default:
				$group = $this->apply("group", "get", [$method => $criteria]);
			}
			if ($get_first !== false){
				$group = $this->SQL->getFirst($group, $get_first);
			}
			return $group;
		}
		
		public function test($function, $arg)
		{
			return $this->$function($arg);
		}
		
		/* get_school(user_id)
			get_groups(school_id)
			get_users(school_id)
			get_visits(group_id)
			get_group_data(group_id)
			get_recent_message(user_id)
			
			create_user
			get_user
			update_user
			delete_user
			
			crud: user, skola, grupp, m�te, tema, kalender-evenemang, busstur, meddelande
			
			
			
		*/	
	}
	
