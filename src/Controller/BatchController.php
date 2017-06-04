<?php

namespace Fridde\Controller;

use Fridde\HTML;
use Fridde\Utility as U;

class BatchController {

    /** @var \Fridde\Naturskolan The Naturskolan object obtained from the global container */
    private $N;
    private $params;
    /** @var \Fridde\HTML A Html object to build the page */
    private $H;
    /* @var array $operations An array that translates the operation parameter to the method executed  */
    private $operations = [];

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
        $method = $this->operations[$operation] ?? U::toCamelCase($operation);
        $this->$method();
    }

    /**
     * @route admin/batch/
     */
    private function addDates()
    {
        /* @var \Fridde\Entities\TopicRepository $topic_repo  */
        $topic_repo = $this->N->getRepo("Topic");
        $topics = $topic_repo->findLabelsForTopics();
        $DATA["topics"] = array_map(function($key, $value){
           return ["id" => $key, "label" => $value];
        }, array_keys($topics), $topics);

        $this->H->addDefaultJs("index")->addDefaultCss("index")
        ->setTemplate("add_dates")->setBase();

        $this->H->addNav();
        $this->H->addVariable("DATA", $DATA);
        $this->H->render();
    }

    /**
    * @example setVisitsExample.php
    * @return void
    */
    private function setVisits()
    {
        $filter = explode(",", $this->params["filter"] ?? null);
        $grade = $filter[0] ?? null;
        $start_year = $filter[1] ?? null;
        $criteria = [];
        if(!empty($grade)){
            $criteria[] = ["Grade", $grade];
        }
        if(!empty($start_year)){
            $criteria[] = ["StartYear", $start_year];
        }
        $visit_repo =  $this->N->ORM->getRepository("Visit");

        $groups = $this->N->ORM->getRepository("Group")->selectAnd($criteria);
        usort($groups, function($g1, $g2){
            return $g1->compareVisitOrder($g2);
        });

        $DATA["groups"] = array_map(function($g){
            $r = ["id" => $g->getId()];
            $r["name"] = $g->getName();
            $r["school"] = $g->getSchoolId();
            return $r;
        }, $groups);

        $group_to_row_translator = array_flip(array_column($DATA["groups"], "id"));
        $topics = $this->N->ORM->getRepository("Topic")->findTopicsForGrade($grade);

        usort($topics, function($t1, $t2){
            return $t1->getVisitOrder() - $t2->getVisitOrder();
        });

        $DATA["date_columns"] = [];
        foreach($topics as $t){
            $d = ["id" => $t->getId()];
            $d["name"] = $t->getShortName();
            $d["serial"] = $t->getGrade() . "." . $t->getVisitOrder();

            $visits = $visit_repo->findSortedVisitsForTopic($t);

            $orphan_visits = array_filter($visits, function($v){
                return !$v->hasGroup();
            });
            $visits_with_group = array_filter($visits, function($v) use ($start_year){
                if(!$v->hasGroup()){
                    return false;
                }
                return empty($start_year) || $v->getGroup()->getStartYear() == $start_year;
            });
            usort($visits_with_group, function($v1, $v2) use ($group_to_row_translator){
                $v1_index = $group_to_row_translator[$v1->getGroupId()];
                $v2_index = $group_to_row_translator[$v2->getGroupId()];
                return $v1_index - $v2_index;
            });
            foreach($visits_with_group as $vg){
                $index = $group_to_row_translator[$vg->getGroupId()];
                $orphan_visits = U::insertAt($orphan_visits, $index, $vg);
            }
            $fixed_visits = array_values(array_pad($orphan_visits, count($DATA["groups"]), null));

            $d["visits"] = array_map(function($v, $key){
                if(empty($v)){
                    return null;
                }
                $r = ["id" => $v->getId(), "date" => $v->getDateString()];
                $r["has_group"] = $v->hasGroup();
                $r["position"] = $key;
                return $r;
            }, $fixed_visits, array_keys($fixed_visits));
            $DATA["date_columns"][] = $d;
        }

        $this->H->addDefaultJs("index")->addDefaultCss("index")
        ->setTemplate("set_visits")->setBase();

        $this->H->addNav();
        $this->H->addVariable("DATA", $DATA);
        $this->H->render();
    }

    public function setGroupCount()
    {
        $this->H->addDefaultJs("index")->addDefaultCss("index")
            ->setTemplate("set_group_count")->setBase();
        $this->H->addNav();
        $this->H->render();
    }





}
