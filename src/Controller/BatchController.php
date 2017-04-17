<?php

namespace Fridde\Controller;

use Fridde\HTML;

class BatchController {

    private $N;
    private $params;
    private $H;
    private $operations = ["set_visits" => "setVisits", "add_dates" => "addDates"];

    public function __construct($params)
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->H = new HTML();
        $this->params = $params;
    }

    public function handleRequest()
    {
        $operation = $this->params["operation"] ?? null;
        if(empty($operation)){
            throw new \Exception("The operation parameter can not be empty");
        }
        $method = $this->operations[$operation] ?? $operation;
        $this->$method();
    }

    private function addDates()
    {
        $topic_id = $this->params["filter"] ?? null;
        $topic = $this->N->ORM->getRepository("Topic")->find($topic_id);
        if(empty($topic)){
            // return 404
            throw new \Exception("No topic with the id <" . $topic_id . "> found.");
        }
        $DATA["topic"] = ["id" => $topic_id];
        $DATA["topic"]["serial"] = $topic->getGrade() . "." . $topic->getVisitOrder();
        $DATA["topic"]["name"] = $topic->getShortName();

        $this->H->addDefaultJs("index")->addDefaultCss("index")
        ->setTemplate("add_dates")->setBase();

        $this->H->addVariable("DATA", $DATA);
        $this->H->render();
    }

    private function setVisits()
    {
        $filter = explode(",", $this->params["filter"] ?? null);
        $grade = $filter[0] ?? null;
        $start_year = $filter[1] ?? null;
        if(!empty($grade)){
            $criteria[] = ["Grade", $grade];
        }
        if(!empty($start_year)){
            $criteria[] = ["StartYear", $start_year];
        }
        $groups = $this->N->ORM->getRepository("Group")->selectAnd($criteria);
        usort($groups, function($g1, $g2){
            $v_order_1 = $g1->getSchool()->getVisitOrder();
            $v_order_2 = $g2->getSchool()->getVisitOrder();
            if($v_order_1 !== $v_order_2){
                return $v_order_1 - $v_order_2;
            }
            return $g1->getId() - $g2->getId();
        });
        $DATA["groups"] = array_map(function($g){
            $r = ["id" => $g->getId()];
            $r["name"] = $g->getName();
            $r["school"] = $g->getSchoolId();
            return $r;
        }, $groups);
        $topics = $this->N->ORM->getRepository("Topic")->findAllTopics($grade);
        usort($topics, function($t1, $t2){
            return $t1->getVisitOrder() - $t2->getVisitOrder();
        });
        $visit_repo =  $this->N->ORM->getRepository("Visit");
        $DATA["date_columns"] = [];
        foreach($topics as $t){
            $d = ["id" => $t->getId()];
            $d["name"] = $t->getShortName();
            $d["serial"] = $t->getGrade() . "." . $t->getVisitOrder();
            $d["visits"] = array_map(function($v){
                return ["id" => $v->getId(), "date" => $v->getDateString()];
            }, $visit_repo->findSortedVisits($t));
            $DATA["date_columns"][] = $d;
        }

        $this->H->addDefaultJs("index")->addDefaultCss("index")
        ->setTemplate("set_visits")->setBase();

        $this->H->addVariable("DATA", $DATA);
        $this->H->render();
    }




}
