<?php
namespace Fridde;

use Fridde\{ Calendar, NSDB_Mailchimp as MC, Mailer, Utility as U};
use Yosymfony\Toml\Toml;
use Carbon\Carbon;

	class Naturskolan
	{
		public $ORM;
		private $text_path = "texts";

		public function __construct ()
		{
			$this->ORM = new ORM();
		}

		public function getStatus($id)
		{
			return $this->ORM->getRepository("SystemStatus")->find($id)->getValue();
		}

		public function setStatus($id, $value)
		{
			$this->set("SystemStatus", $id, $value);
		}

		public function set()
		{
			//$repo, $id, $value, $attribute_name = "Value"
			$requests = func_get_args();
			if(func_num_args() === 1 && is_array(func_get_arg(0))){
				$requests = func_get_arg(0);
			} else {
				$requests = [$requests];
			}

			foreach($requests as $args){
				$repo = $args[0] ?? $args["repo"];
				$id = $args[1] ?? $args["id"];
				$value = $args[2] ?? $args["value"];
				$attribute_name = $args[3] ?? ($args["att_name"] ?? "Value");
				$e = $this->ORM->getRepository($repo)->find($id);
				$method = "set" . $attribute_name;
				$e->$method($value);
				$this->ORM->EM->persist($e);
			}
			$this->ORM->EM->flush();
		}

		public function quickSet($shorthand)
		{
			switch($shorthand){
				case "calendar clean":
				$req[0] = ["SystemStatus", "calendar.status", "clean"];
				$now_string = Carbon::now()->toIso8601String();
				$req[1] = ["SystemStatus", "calendar.last_rebuild", $now_string];
				$this->set($req);
				break;

				default:
				throw \Exception("The parameter " . $shorthand . " is not defined.");
				break;
			}
		}

		public function setCalendarToClean()
		{
			$this->quickSet("calendar clean");
		}

		public function calendarIsDirty()
		{
			return $this->getStatus("calendar.status") === "dirty";
		}

		public function getLastRebuild()
		{
			return new Carbon($this->getStatus("calendar.last_rebuild"));
		}



		public static function createPassword($prefix = "", $length = 4)
		{
			$password = $prefix;
			$alpha = range('a', 'z');
			foreach (range(1,$length) as $i){
				$password .= $alpha[mt_rand(0, count($alpha) - 1)];
			}
			return $password;
		}

		public static function createHash()
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

					case "User":
					$r =  $v->getFullName() . ", " . $v->getSchool()->getName();
					break;

					case "Group":
					$r = $v->hasName() ? $v->getName() : $v->getPlaceholderName();
					$r .= ", " . $v->getSchool->getName();
					break;

					case "Topic":
					$r = $v->getGrade() . "." . $v->getVisitOrder();
					$r .= " " . $v->getShortName();
					break;

					case "Colleague":
					$r = $v->getAcronym();
					break;

					case "School":
					$r = $v->getName();
					break;

					case "Location":
					$r = $v->getName();
					break;

					default:
					$r = $v;
					$custom_id = $k;
				}
				$id = $custom_id ?? $v->getId();
				return [$r, $id];
			};
			$formatted_array = array_map($format, $unformatted_array, array_keys($unformatted_array));

			return $formatted_array;
		}

		public function getTimestamp()
		{
			return Carbon::now()->toAtomString();
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
