<?php 
	namespace Fridde;
	
	class NSDB_MailChimp extends \DrewM\MailChimp\MailChimp {
		
		private $api_key;
		private $list_id;
		private $translator;
		private $max_count;
		//the key in the config file that contains the id's, names and categories of the interest groups
		public $interest_index = "mailchimp_interests"; 
		public $settings_file = "settings.toml";
		
		public function __construct()
		{
			$this->setConfiguration();
			parent::__construct($this->api_key);
			$this->setMaxCount();
		}
		
		public function setConfiguration()
		{
			$this->api_key = $GLOBALS["settings"]["mailchimp"]["api_key"] ?? false;
			$this->list_id = $GLOBALS["settings"]["mailchimp"]["list_ids"]["larare"] ?? false;
			
			if(!($this->api_key && $this->list_id)){
				$file_name = $this->settings_file;
				$toml_class = "Yosymfony\Toml\Toml";
				if(is_readable($file_name)){
					if(class_exists($toml_class)){
						$parseFunction = $toml_class . "::Parse";
						$settings = $parseFunction($file_name);
					}
					else {
						throw new \Exception("Tried to parse a toml-configuration file without a parser class defined.");
					}
				}
				else {
					throw new \Exception("File <" . $file_name . "> not readable or doesn't exist.");
				}
				$this->api_key = $settings["mailchimp"]["api_key"] ?? false;
				$this->list_id = $settings["mailchimp"]["lists"]["larare"]["id"] ?? false;
			} 
			else {
				throw new \Exception("settings.toml and/or the mailchimp_api_key couldn't be found");
			}
		}
		
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function setApiKey($api_key)
		{
			$this->api_key = $api_key;
			
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function setListId($list_id = null)
		{
			if(isset($list_id)){
				$this->list_id = $list_id;
			}
			else if(isset($GLOBALS["ini_array"]["mailchimp_list_ids"]["larare"])) {
				$this->list_id = $GLOBALS["ini_array"]["mailchimp_list_ids"]["larare"];
			}
			else {
				$settings = parse_settings_file("config.ini", true);
				if($settings != false && isset($settings["mailchimp_list_ids"]["larare"])){
					$this->list_id = $settings["mailchimp_list_ids"]["larare"];
				}
				else {
					throw new \Exception("config.ini and/or the list_id for \'larare\' couldn't be found");
				}
			}
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function setMaxCount($max_count = 999)
		{
			$this->max_count = $max_count;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param mixed $members Either an array of e-mail adresses or subscriber-hashes (even mixed) OR a single string with an adress or hash.
			* If void, all members are returned
			*
			* @return array $member_array [description]
		*/ 
		public function getMembers($members = null, $args = array())
		{
			$args["count"] = $this->max_count;
			$member_array = array();
			
			if(isset($members)){
				if(is_string($members)){
					$members = [$members];
				}
				foreach($members as $member_id){
					if(strpos($member_id, "@") !== false){ // rudimentary check if email-adress given
						$member_id = $this->subscriberHash($member_id);
					}
					$member_array[] = $this->get("lists/$this->list_id/members/" . $member_id, $args);
					
				}
			}
			else {
				$member_array = $this->get("lists/$this->list_id/members", $args);
			}
			$member_array = $member_array["members"];
			if(count($member_array) == 1){
				$member_array = $member_array[0];
			}
			return $member_array;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addMember($member)
		{
			$path = "/lists/$this->list_id/members";
			$new_member = ["status" => "subscribed"];
			// email_address, fname, lname
			foreach($member as $sql_column => $value){
				if(isset($settings["mailchimp_translator"][$sql_column])){
					$translation = explode(":", $settings["mailchimp_translator"][$sql_column]);
				}
			}
			$merge_fields = [];
			$interests = [];
			
			$result = $this->post($path, $member);
			//return 
		}
		
		
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/
		public function getCategoriesAndInterests()
		{
			$cat_url = "lists/" . $this->list_id . "/interest-categories";
			$c = $this->get($cat_url);
			if($c === false){
				exit("The API-request returned an error. Check the Mailchimp-API for errors.");
			}
			$categories = array_map(function($v){return ["id" => $v["id"], "title" => $v["title"]];}, $c["categories"]);
			$include_interests = function($v) use($cat_url){
				$i = $this->get($cat_url . "/" . $v['id'] . "/interests");
				$v["interests"] = array_map(function($vv){return ["id" => $vv["id"], "name" => $vv["name"]];}, $i["interests"]); 
				return $v;
			};
			
			$categories_with_interests = array_map($include_interests, $categories);
			
			return $categories_with_interests;
		}
	}																																																							