<?php
namespace Fridde;

use DrewM\MailChimp\MailChimp;

class NSDB_MailChimp extends MailChimp {

	private $api_key;
	private $list_id;
	private $translator;
	private $max_count;
	private $SETTINGS;
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
		$this->SETTINGS = SETTINGS["mailchimp"];
		$default_list = $this->SETTINGS["default_list"];
		$this->api_key = $this->SETTINGS["api_key"];
		$this->list_id = $this->SETTINGS["lists"][$default_list];
	}


	public function setApiKey($api_key)
	{
		$this->api_key = $api_key;

	}

	public function setListId($list_id = null)
	{
		$this->list_id = $list_id;


	}

	public function setMaxCount($max_count = 999)
	{
		$this->max_count = $max_count;
	}

	/**
	* [Summary].
	*
	* [Description]
	*
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
	*
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
			if(isset($this->SETTINGS["mailchimp_translator"][$sql_column])){
				$translation = explode(":", $this->SETTINGS["mailchimp_translator"][$sql_column]);
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
	*
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
