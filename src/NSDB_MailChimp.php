<?php 
	namespace Fridde;
	
	class NSDB_MailChimp extends \DrewM\MailChimp\MailChimp {
		
		private $api_key;
		private $list_id;
		private $max_count;
		//the key in the config file that contains the id's, names and categories of the interest groups
		public $interest_index = "mailchimp_interests"; 
		public $ini_file = "config.ini";
		
		public function __construct($api_key = null, $list_id = null)
		{
			$this->setApiKey($api_key);
			parent::__construct($this->api_key);
			$this->setListId($list_id);
			$this->setMaxCount();
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function setApiKey($api_key = null)
		{
			if(isset($api_key)){
				$this->api_key = $api_key;
			}
			else if(isset($GLOBALS["ini_array"]["security"]["mailchimp_api_key"])) {
				$this->api_key = $GLOBALS["ini_array"]["security"]["mailchimp_api_key"];
			}
			else {
				$ini_array = parse_ini_file("config.ini", true);
				if($ini_array != false && isset($ini_array["security"]["mailchimp_api_key"])){
					$this->api_key = $ini_array["security"]["mailchimp_api_key"];
				}
				else {
					throw new \Exception("config.ini and/or the mailchimp_api_key couldn't be found");
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
		public function setListId($list_id = null)
		{
			if(isset($list_id)){
				$this->list_id = $list_id;
			}
			else if(isset($GLOBALS["ini_array"]["mailchimp_list_ids"]["larare"])) {
				$this->list_id = $GLOBALS["ini_array"]["mailchimp_list_ids"]["larare"];
			}
			else {
				$ini_array = parse_ini_file("config.ini", true);
				if($ini_array != false && isset($ini_array["mailchimp_list_ids"]["larare"])){
					$this->list_id = $ini_array["mailchimp_list_ids"]["larare"];
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
		public function addMember()
		{
			$path = "/lists/$this->list_id/members";
			$member = [];
			$merge_fields = [];
			$interests = [];
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/
		public function updateInterests()
		{
			$delimiter = "::";
			$ini_file = parse_ini_file($this->ini_file, true);
			$old_interests = $ini_file[$this->interest_index];
			$new_interests = $this->getInterests();
			$update_array = array();
			foreach($new_interests as $interest_id => $interest_array){
				$sql_name = str_repeat("?", 10);
				if(isset($old_interests[$interest_id])){
					$old_interest = explode($delimiter,$old_interests[$interest_id]);
					$sql_name = array_pop($old_interest);
				}
				$interest_array["sql_name"] = $sql_name;
				$update_array[$interest_id] = implode($delimiter, $interest_array);
			}
			$ini_file[$this->interest_index] = $update_array;
			\Fridde\Utility::writeIniFile($ini_file);
		} 
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/
		public function getInterests()
		{
			$return_array = array();
			$interests = array();
			$cat_url = "lists/$this->list_id/interest-categories";
			$interest_categories = $this->get($cat_url);
			foreach($interest_categories["categories"] as $key => $category){
				$cat_id = $category["id"];
				$cat_title = $category["title"];
				$interests = $this->get($cat_url. "/$cat_id/interests");
				foreach($interests["interests"] as $interest){
					$interest_id = $interest["id"];
					$interest_name = $interest["name"];
					$return_array[$interest_id] = ["cat_id" => $cat_id, "cat_title" => $cat_title, "interest_name" => $interest_name];
				}
			}
			return $return_array;
		}
	}																															