<?php
/**
* This file contains the Naturskolan class that acts as a basic helper class for the Naturskolan-Database app
*/

namespace Fridde;

use Fridde\PasswordHandler as PW;
use Fridde\Utility as U;
use Yosymfony\Toml\Toml;
use Carbon\Carbon;
use dotzero\Googl;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

/**
* The basic class to assist most other classes in the Naturskolan-Database application
*/
class Naturskolan
{
	/** @var \Fridde\ORM A doctrine ORM wrapped in own class */
	public $ORM;
	/** @var \GuzzleHttp\Client Contains an instance of GuzzleHttp\Client for HTTP requests */
	private $Client;
	/** @var \dotzero\Googl An instance of Googl to create short-links */
	private $Googl;
	/** @var \Fridde\PasswordHandler Instance of PasswordHandler */
	private $PW;
	/** @var array A text array containing labels and other text bits  */
	private $text_array;
	/** @var string the path for the text pieces */
	private $text_path = "text";

	/**
	* Constructor
	*
	* Creates an instance of $ORM and $PasswordHandler
	*/
	public function __construct ()
	{
		$this->ORM = new ORM();
        $this->PW = new PW();
	}

	/**
    * Wrapper for Naturskolan->ORM->getRepository()
    *
    * @param  string $repo The (non-qualified-) name of the class of entities
    * @return mixed The repository
    */
    public function getRepo($repo)
    {
        return $this->ORM->getRepository($repo);
    }

	/**
	* Get a certain value from SETTINGS using a chain of indices
	*
	* @param  string|array $indices The array-path to the setting. Can either be ONE array or
	*                               several strings.
	* @return mixed The value retrieved from the settings
	*/
	public static function getSetting(...$indices)
	{
		if(count($indices) === 1 && is_array($indices[0])){
			$indices = $indices[0];
		}
		return U::resolve(SETTINGS, $indices);
	}

	/**
	* Retrieve a value from the SystemStatus table using its id.
	*
	* @param  string $id The id of the SystemStatus value.
	*
	* @return string|null The value of the SystemStatus row. Returns null if row not exists,
	*                     or is an empty string.
	*/
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

	/**
	* Shorthand function to set a value for a certain entity in a certain repository.
	*
	* @param mixed $requests The function takes either 3-4 arguments corresponding to
	*                        $repo, $id, $value and $attribute_name (with default "Value").
	*                        Or it takes ONE array which in itself consists of one or more
	*                        arrays with each exactly 3-4 elements. The elements can either
	*                        be in right order or indexed with "repo", "id", "value" and "att_name".
	*
	* @return void
	*/
	public function set(...$requests)
	{
		if(count($requests) === 1 && is_array($requests[0])){
			$requests = $requests[0];
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

	/**
	* Execute a certain database update without specifying any parameters.
	*
	* @param  string $shorthand The type of update to perform.
	* @return void
	*/
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

	/**
	* Wrapper function to set the calendar.status in SystemStatus to "clean"
	*/
	public function setCalendarToClean()
	{
		$this->quickSet("calendar clean");
	}

	/**
	* Check to see if calendar should be updated.
	*
	* @return boolean Returns true if the value for calendar.status in SystemStatus is "dirty"
	*/
	public function calendarIsDirty()
	{
		return $this->getStatus("calendar.status") === "dirty";
	}

	/**
	* Gets the cron_tasks.activation from SystemStatus and returns it as an array.
	* The array deactivates certain Tasks to help debug or to be able to tinker
	* without being interrupted by the cron task.
	*
	* @return null|integer[] An array which each task name as index and either 0 or 1 as
	*                        deactivated or activated. Fridde\Task assumes a task as
	*                        "activated" if not present in this array.
	*
	*/
	public function getCronTasks()
	{
		$val = $this->getStatus("cron_tasks.activation");
		if(!empty($val)){
			return json_decode($val, true);
		}
		return null;
	}

    public function setCronTask(string $task_name, $status)
    {
        $cron_tasks = $this->getCronTasks() ?? [];
        $cron_tasks[$task_name] = $status;
        $this->setStatus("cron_tasks.activation", json_encode($cron_tasks));
	}

	/**
	* Gets the time the calendar was rebuilt the last time.
	*
	* @return \Carbon\Carbon DateTime of last build
	*/
	public function getLastRebuild()
	{
		return new Carbon($this->getStatus("calendar.last_rebuild"));
	}

	/**
	* Creates a password for a certain school for the current year.
	*
	* @param  string $school_id The id of the school.
	* @return string            The password
	*/
	public function createPassword($school_id)
	{
		return $this->PW->createPassword($school_id);
	}

	/**
	*
	*
	* @param  \Fridde\Entities\User $user The User object
	* @return string       The url a user can click to reach the school page
	*                      without writing any password.
	*/
	public function createLoginUrl(\Fridde\Entities\User $user)
	{
		$params["school"] = $user->getSchoolId();
		$params["page"] = "team";
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

	public function createConfirmationUrl($visit_id)
	{
		$params["code"] = $this->PW->createCodeFromInt($visit_id, "visit");
		return $this->generateUrl('confirmvisit', $params);
	}

    /**
     * @param string $school_id_password
     * @return string|bool $school_id or false if none is found
     */
    public function checkPassword(string $school_id_password)
	{
		if(substr($school_id_password, 0, 5) == 'user$'){
			$code = substr($school_id_password, 5);
			$user_id = $this->getIntFromCode($code, "user");
			if(!empty($user_id)){
				$user = $this->ORM->getRepository("User")->find($user_id);
				return $user->getSchool()->getId();
			}
		} else {
			return $this->PW->passwordToSchoolId($school_id_password);
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
        /* @var \AltoRouter $router  */
	    $router = $GLOBALS["CONTAINER"]->get("Router");
		$url_end = $router->generate($route_name, $params);
		return $url_end;
		//return $_SERVER['HTTP_HOST'] . $url_end;
	}

    /**
     * @param string $url
     * @param array $post_data
     * @param bool $use_api_key
     * @param bool $debug
     * @return \Psr\Http\Message\ResponseInterface
     */
	public function sendRequest(string $url, $post_data = [], $use_api_key = true, $debug = false)
	{
		$options = ["json" => $post_data, "cookies" => null, "debug" => false];

	    if($use_api_key){
			$options['json']['api_key'] = $this->getApiKey();
		}
		if($debug || ($GLOBALS["debug"] ?? false)){
			$options['cookies'] = CookieJar::fromArray(['XDEBUG_SESSION' => 'xdebug.api'], 'localhost');
            $options['json']["XDEBUG_SESSION_START"] = "api";
            $options['debug'] = true;
			$url .= '?XDEBUG_SESSION_START=api';
		}

		usleep(100 * 1000); // = 0.1 seconds to not choke the server
		return $this->getClient()->post($url, $options);
	}

	public function getApiKey()
	{
		return SETTINGS["values"]["api_key"] ?? null;
	}

	public function getClient($new_client = false)
	{
		if(empty($this->Client) || $new_client){
			$this->Client = new Client(['base_uri' => APP_URL]);
		}
		return $this->Client;
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
