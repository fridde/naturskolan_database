<?php

namespace Fridde\Controller;

use Fridde\{Mailer, HTML};

class MailController extends MessageController
{
    private $mail_status;
    protected $HTML;
    protected $Mailer;
    // PREPARE=1; SEND=2; UPDATE=4;
    protected $methods = ["admin_summary" => 3,"password_reset" => 3, "confirm_visit" => 3,
        "update_profile_reminder" => 3, "changed_groups_for_user" => 3];

    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->HTML = new HTML();
        $this->Mailer = new Mailer();
    }

    public function send()
    {
        $body = $this->HTML->inkify()->addInlineCss()->render(false);
        $this->Mailer->set("body", $body);
        $result = $this->Mailer->sendAway();
        if($result === false){
            $this->mail_status["success"] = false;
            $this->mail_status["errors"] = $result->ErrorInfo;
        } else {
            $this->mail_status["success"] = true;
        }
        return $this->mail_status;
    }

    protected function prepareAdminSummary()
    {
        $this->HTML->setTitle("Sammanfattning: Status av databasen");
        $this->HTML->setTemplate("admin_summary");
        $this->HTML->addCssFile("admin_summary.css");
        $receiver = SETTINGS["admin_summary"]["admin_adress"];
        $this->Mailer->set("receiver", $receiver);
        $this->Mailer->set("subject", "Dagliga sammanfattningen av databasen");

        $DATA = $this->getRQ("data");
        $this->HTML->addVariable("DATA", $DATA);
    }




    protected function preparePasswordReset()
    {
        $this->HTML->setTitle("Återställning av lösenordet");
        $this->HTML->setTemplate("password_reset");
        $this->HTML->addCssFile("mail.css");
        $this->Mailer->set("receiver", $this->getRQ("receiver"));
        $this->Mailer->set("subject", "Naturskolan: Återställning av lösenord");

        $DATA = $this->getRQ("data");
        $this->HTML->addVariable("DATA", $DATA);
    }

    protected function prepareUpdateProfileReminder()
    {
        $this->HTML->setTitle("Ofullständig profil");
        $this->HTML->setTemplate("incomplete_profile");
        $this->HTML->addCssFile("mail.css");
        $this->Mailer->set("receiver", $this->getRQ("receiver"));
        $this->Mailer->set("subject", "Vi behöver mer information från dig!");

        $DATA = $this->getRQ("data");
        $this->HTML->addVariable("DATA", $DATA);
    }

    protected function prepareConfirmVisit()
    {
        $DATA = $this->getRQ("data");
        $date_string = $DATA["visit_info"]["date_string"];
        $this->HTML->setTitle("Bekräfta ditt besök");
        $this->HTML->setTemplate("confirm_visit");
        $this->HTML->addCssFile("mail.css");
        $this->Mailer->set("receiver", $this->getRQ("receiver"));
        $this->Mailer->set("subject", "Bekräfta ditt besök på " . $date_string);
        $this->HTML->addVariable("DATA", $DATA);
    }

    protected function prepareChangedGroupsForUser()
    {
        $DATA = $this->getRQ("data");
        array_walk_recursive($DATA["groups"], function(&$g_id){
            $group = $this->N->ORM->getRepository("Group")->find($g_id);
            $g = ["group_id" => $g_id];
            $g["name"] = $group->getName();
            $g["grade"] = $group->getGradeLabel();
            $g_id = $g;
        });
        $groups = $DATA["groups"];
        $this->HTML->setTitle("Grupperna har ändrats");
        $this->HTML->setTemplate("changed_groups");
        $this->HTML->addCssFile("mail.css");
        $this->Mailer->set("receiver", $this->getRQ("receiver"));
        $has_removed = !empty($groups["removed"]);
        $has_new = !empty($groups["new"]);
        if($has_removed && $has_new){
            $subject = "Grupperna du förvaltar har ändrats";
        } elseif($has_removed){
            $subject = "Antal grupper du förvaltar har minskat.";
        } elseif($has_new){
            $subject = "Antal grupper du förvaltar har ökat.";
        } else {
            throw new \Exception("There were no groups given for this user.");
        }
        $this->Mailer->set("subject", $subject);
        $this->HTML->addVariable("DATA", $DATA);
    }

}
