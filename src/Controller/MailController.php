<?php

namespace Fridde\Controller;

use Fridde\{Mailer, HTML};

class MailController extends MessageController
{
    private $mail_status;
    private $params;
    protected $HTML;
    protected $Mailer;
    protected $type_mapper = ["admin_summary" => "AdminSummary", "default" => "DefaultMail",
    "password_reset" => "PasswordReset", "confirm_visit" => "ConfirmVisit"];

    public function __construct($params)
    {
        parent::__construct();
        $this->HTML = new HTML();
        $this->Mailer = new Mailer();
        $this->params = $params;
    }

    public function send()
    {
        $this->prepare($this->params);
        $body = $this->HTML->inkify()->addInlineCss()->render(false);
        $this->Mailer->set("body", $body);
        $result = $this->Mailer->sendAway();
        if($result === false){
            $this->mail_status["success"] = false;
            $this->mail_status["errors"] = $result->ErrorInfo;
        } else {
            $this->mail_status["success"] = true;
        }
        echo json_encode($this->mail_status);
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

    protected function prepareDefaultMail()
    {

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

    protected function prepareProfileUpdateReminderMail()
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

}
