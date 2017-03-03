<?php

namespace Fridde\Controller;

use Fridde\{Update};
use GuzzleHttp\Client;


class APIController {

    public $N;
    private $params;

    public function __construct($params)
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->params = $params;
    }

    public function passwordReset($params = [])
    {
        $mail = trim($this->getRQ("mail"));
        if(!empty($mail)){
            $user = $this->N->ORM->getRepository("User")->findOneBy(["Mail" => $mail]);
            $return["success"] = !empty($user);
            if($return["success"]){
                $data["api_key"] = SETTINGS["smtp_settings"]["api_key"];
                $data["receiver"] = $user->getMail();
                $data["data"]["fname"] = $user->getFirstName();
                $data["data"]["password_link"] = $this->N->createPasswordResetUrl($user->getId());
                $url = $this->N->createMailUrl("password_reset");
                $client = new Client();
                $response = $client->request('POST', $url, ['form_params' => $data]);
            }
            echo json_encode($return);
        }
    }

    public function passwordRecover()
    {
        $return["success"] = false;
        $code = $this->params["code"] ?? null;
        if(!empty($code)){
            $id = $this->N->getIntFromCode($code, "user") ?? -1 ;
            $user = $this->N->ORM->getRepository("User")->find($id);
            if(!empty($user)){
                $school_id = $user->getSchool()->getId();
                $return["password"] = $this->N->createPassword($school_id);
                $return["success"] = true;
            }
        }
        echo json_encode($return);
    }

    private function getRQ($key)
    {
        return $_REQUEST[$key] ?? null;
    }

    public function confirmVisit()
    {
        $code = $this->params["code"] ?? null;
        if(!empty($code)){
            $id = $this->N->getIntFromCode($code, "visit");
            if(!empty($id)){
                $request["visit_id"] = $id;
                $request["updateType"] = "confirmVisit";
                $update = new Update($request);
                $return = $update->execute()->getReturn();
            }
        }
        if($return["success"] ?? false){
            $page = new PageController(["page" => "visit_confirmed"]);
            $page->show();
        } else {
            $error = new ErrorController(["type" => "visit_not_confirmed"]);
            $error->show();
        }


    }


}
