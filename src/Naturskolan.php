<?php
namespace Fridde;

use Fridde\{Utility as U, PasswordHandler as PW};
use Yosymfony\Toml\Toml;
use Carbon\Carbon;
use dotzero\Googl;

class Naturskolan
{
	public $ORM;
	private $Googl;
	private $PW;
	private $text_array;
	private $text_path = "texts";

	public function __construct ()
	{
		$this->ORM = new ORM();
		$this->PW = new PW();
	}

	public function getStatus($id)
	{
		$status = $this->ORM->getRepository("SystemStatus")->find($id);
		if(!empty($status)){
			return $status->getValue();
		}
		return null;
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
			if(!empty($e)){
				$e->$method($value);
				$this->ORM->EM->persist($e);
			} else {
				$msg = "No entity of the class <" . $repo . "> with the id <";
				$msg .= $id . "> could be found.";
				throw new \Exception($msg);
			}
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
			throw new \Exception("The parameter " . $shorthand . " is not defined.");
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

	public function getCronTasks()
	{
		$val = $this->getStatus("cron_tasks.activation");
		if(!empty($val)){
			return json_decode($val, true);
		}
		return null;
	}

	public function getLastRebuild()
	{
		return new Carbon($this->getStatus("calendar.last_rebuild"));
	}

	public function createPassword($school_id)
	{
		return $this->PW->createPassword($school_id);
	}

	public function createLoginUrl($user)
	{
		$params["school"] = $user->getSchoolId();
		$params["page"] = "personal";
		$params["code"] = $this->PW->createCodeFromInt($user->getId(), "user");
		return $this->generateUrl("school", $params);
	}

	private function getGoogl()
	{
		if(empty($this->Googl)){
			$api_key = SETTINGS["sms_settings"]["google_api_key"];
			$this->Googl = new Googl($api_key);
		}
		return $this->Googl;
	}

	public function shortenUrl($url)
	{
		return $this->getGoogl()->shorten($url);
	}

	public function createPasswordResetUrl($user_id)
	{
		$params["code"] = $this->PW->createCodeFromInt($user_id, "user");
		return $this->generateUrl("pwrecover", $params);
	}

	public function getIntFromCode($code, $entropy = "")
	{
		return $this->PW->getIntFromCode($code, $entropy);
	}

	public function getConfirmationUrl($visit_id)
	{
		$params["code"] = $this->PW->createCodeFromInt($visit_id, "visit");
		return $this->generateUrl('confirmvisit', $params);
	}

	public function checkPassword($school_id_password)
	{
		if(substr($school_id_password, 0, 5) == 'user$'){
			$code = substr($school_id_password, 5);
			$user_id = $this->getIntFromCode($code, "user");
			if(!empty($user_id)){
				$user = $this->ORM->getRepository("User")->find($user_id);
				return $user->getSchool()->getId();
			}
		} else {
			return $this->PW->checkPassword($school_id_password);
		}
	}

	public function createHash()
	{
		$hash_string = password_hash(microtime(), PASSWORD_DEFAULT);
		$hash_array = explode("$", $hash_string);
		$hash = implode("", array_slice($hash_array, 3));

		return $hash;
	}

	public function generateUrl($route_name, $params = [])
	{
		$router = $GLOBALS["CONTAINER"]->get("Router");
		$url_end = $router->generate($route_name, $params);
		return $_SERVER['HTTP_HOST'] . $url_end;
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

	private function setTextArrayfromFile($path = null)
	{
		if(empty($path)){
			$path = "text\labels.toml";
		}
		$complete_path = BASE_DIR . $path;
		if(is_readable($complete_path)){
			$this->text_array = Toml::Parse($complete_path);
		} else {
			throw new \Exception("The file " . $complete_path . " could not be read.");
		}
	}

	public function getText($index, $file_path = null)
	{
		if(empty($this->text_array) || !empty($file_path)){
			$this->setTextArrayfromFile($file_path);
		}
		$text = U::resolve($this->text_array, $index);
		if(!(isset($text) && is_string($text))){
			throw new \Exception("The path given couldn't be resolved to a valid string. The path: " . var_export($index, true));
		}
		return $text;
	}

	public function getReplacedText($index, $replacements = [], $file_path = null)
	{
		$text = $this->getText($index, $file_path);
		$search = $rep = [];
		foreach($replacements as $key => $val){
			$search[] = '%%' . $key . '%%';
			$rep[] = $val;
		}
		return str_replace($search, $rep, $text);
	}

}
