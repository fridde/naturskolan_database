<?php
namespace Fridde;

use \Fridde\{SQL, Calendar, NSDB_Mailchimp as MC, Mailer, Utility as U,
	HTML as H};
use \Yosymfony\Toml\Toml;
use \Carbon\Carbon as C;

	class Naturskolan
	{
		public $SQL;
		public $_NOW_;
		public $_NOW_UNIX_;
		public $tables;
		public $table_names = ["busstrips", "events", "changes", "groups", "locations", "log",
		"passwords", "schools", "messages", "sessions", "tasks", "topics", "users", "visits"];
		private $text_path = "texts";

		function __construct ()
		{
			$this->SQL = new SQL;
			$this->tables = array_fill_keys($this->table_names, null);
			$this->_NOW_ =  C::now();
			$this->_NOW_UNIX_ = time();
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
		*/
		private function prepareMethod($table_name, $method, $criteria = array(), $object = null){

			$this->setTable($table_name);
			$return["c"] = $this->SQL;

			if(in_array($method, ["get", "update", "delete"])){
				$return["criteria"] = $this->standardizeCriteria($criteria, $table_name);
			}
			if(in_array($method, ["update", "create"])){
				$return["object"] = $object ?? [] ;
			}
			return $return;
		}

		public function create($table_name, $object)
		{
			extract($this->prepareMethod($table_name, "create", [], $object));
			return $c->insert($object);
		}

		/**
		* [get description]
		* @param  [type] $table_name [description]
		* @param  [type] $criteria    [description]
		* @param  [type] $field       In case you just want to fetch a single field
		* from the table. The id-column will follow anyway.
		* @return [type]              [description]
		*/
		public function get($table_name, $criteria = [], $field = null)
		{
			$table_and_field = explode("/", $table_name);
			if(count($table_and_field) == 2){
				list($table_name, $field) = $table_and_field;
			}
			extract($this->prepareMethod($table_name, "get", $criteria));
			$c->select();
			$this->applyWhere($c, $criteria);
			$c->query->execute();
			$result = $c->fetch();
			if(isset($field)){
				$result = array_combine(array_column($result, "id"), array_column($result, $field));
			}
			return $result;
		}

		/**
		* [getOne description]
		* @param  [type] $table_with_field [description]
		* @param  [type] $criteria         [description]
		* @param  [type] $field            [description]
		* @return [type]                   [description]
		*/
		public function getOne($table_with_field, $criteria = [], $field = null){

			if(!is_array($criteria)){
				$table_name = reset(explode('/', $table_with_field));
				$criteria = [["id", $criteria]];
			}
			$result = $this->get($table_with_field, $criteria, $field);
			if(count($result) !== 1){
				trigger_error('The query "' . var_dump($criteria) . '" from the table '
				. reset(explode('/', $table_with_field)) . ' should have given a single
				result, but gave ' . count($result));
			}
			return reset($result);
		}

		/**
		* [batchUpdate description]
		* @param  [type] $table_name            [description]
		* @param  [type] $objects_with_criteria A multi-dimensional array,
		* where each array contains one or two arrays. The first array is the object with the new values,
		* the other (optional) array contains the criterium or criteria.
		* @return array $return_array 			An array containing the return values of each update-execution
		*/
		public function batchUpdate($table_name, $objects_with_criteria)
		{
			$return_array = [];
			foreach($objects_with_criteria as $owc){
				$object = array_shift($owc);
				$criteria = array_shift($owc) ?? [];
				$return_array[] = $this->update($table_name, $object, $criteria);
			}
			return $return_array;
		}

		/**
		* Takes an array containing column names as keys and the new values as
		* @param  [type] $table_name [description]
		* @param  array $object     Array of values to change. The structure should be ["column name" => "new value", ...]
		* Non-changing elements can be emitted
		* @param  [type] $criteria   [description]
		* @return [type]             [description]
		*/
		public function update($table_name, $object, $criteria)
		{
			extract($this->prepareMethod($table_name, "update", $criteria, $object));
			$c->update();
			$this->applyWhere($c, $criteria);
			$this->applySet($c, $object);
			return $c->query->execute();
		}

		public function delete($table_name, $criteria)
		{
			extract($this->prepareMethod($table_name, "delete", $criteria));
			$c->delete();
			$this->applyWhere($c, $criteria);
			return $c->query->execute();
		}

		/**
		* [applyWhere description]
		* @param  [type] $connection [description]
		* @param  [type] $criteria   [description]
		* @return [type]             [description]
		*/
		private function applyWhere($connection, $criteria = []){

			if( ! U::onlyArrays($criteria)){
				throw new \Exception("You can't mix arrays with non-arrays when using an array of criteria");
			}

			if(!empty($criteria)){
				foreach($criteria as $criterium){
					if (count($criterium) == 2){
						$connection->query->where($criterium[0], $criterium[1]);
					}
					elseif (count($criterium) == 3){
						$connection->query->where($criterium[0], $criterium[1], $criterium[2]);
					} else {
						throw new \Exception("One of the criteria has the wrong amount of arguments. Criterium: " . var_dump($criterium));
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


		private function standardizeCriteria($criteria)
		{
			if(! empty($criteria) && ! U::arrayIsMulti($criteria)){
				return [$criteria];
			}
			return $criteria;
		}


		private function setTable($table_name)
		{
			$this->SQL->setTable($table_name);
		}

		public function getTable($tables = null, $force_sql_request = false)
		{
			if(is_null($tables)){
				return $this->getAllTables();
			}
			$tables = (array) $tables;

			$return = [];
			foreach($tables as $table_name){
				if($force_sql_request || is_null($this->tables[$table_name])){
					$this->tables[$table_name] = $this->get($table_name);
				}
				$return[strtoupper($table_name)] = $this->tables[$table_name];
			}
			if(count($return) === 1){
				return reset($return);
			} else {
				return $return;
			}
		}

		public function getAllTables($force_sql_request = false)
		{
			foreach($this->tables as $table_name => &$table_rows){
				$table_rows = $this->getTable($table_name, $force_sql_request);
			}
			return array_change_key_case($this->tables, CASE_UPPER);
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

		public function getTimestamp()
		{
			return $this->_NOW_->toAtomString();
		}

		/**
		* [getStandardValues description]
		* @param  [type] $table_name [description]
		* @param  [type] $old_id     [description]
		* @return [type]             [description]
		*/
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


		/**
		* [getText description]
		* @param  [type] $index     [description]
		* @param  [type] $variables [description]
		* @return [type]            [description]
		*/
		public function getText($index, $variables = [])
		{
			$index = explode("/", $index);
			$file_name = array_shift($index) . '.toml';
			$path = $this->text_path . "/" . $file_name;

			$toml_array = Toml::Parse($path);
			$text = U::resolvePath($toml_array, $index);
			if($text !== false){
				if(! is_string($text)){
					throw new \Exception("The path given couldn't be resolved to a valid string. The path: " . var_export($index, true));
				}
				$pattern = array_map(function($k){return '/%%' . $k . '%%/';}, array_keys($variables));
				$text = preg_replace($pattern, array_values($variables), $text);
			}
			return $text;
		}

	}
