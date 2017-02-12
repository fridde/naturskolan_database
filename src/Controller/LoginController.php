<?php

namespace Fridde\Controller;

use Fridde\{Naturskolan, HTMLForTwig as H};

class LoginController {

    public static function checkCookie()
    {
        $hash = $_COOKIE["Hash"] ?? null;
        if(!empty($hash)){
            $N = new Naturskolan();
        	$hash = $N->ORM->getRepository("Password")->findByHash($hash);
        	return empty($hash) ? null : $hash->getSchool();
        }
        return null;
    }

    public static function checkPassword()
    {
    }

    public static function getModal()
    {
        $H = new H();
        $H->setTemplate("password_modal")->setBase();
        $H->addDefaultJs("index")->addDefaultCss("index");

        $H->render();
    }
/*
    public static function getAllGroups($params = [])
    {
        $school_id = $params["school"];
        $hash = $_COOKIE["Hash"] ?? null;

        if(!empty($hash)){
            $hash = $N->ORM->getRepository("Password")->findByHash($hash);
            $school = empty($hash) ? null : $hash->getSchool();
            if(empty($school) || $school->getId() != $school_id){
                return Login::getModal();
            }
        }

        $N = new Naturskolan();
        $school =  $N->ORM->getRepository("School")->find($school_id);
        $SETTINGS = $GLOBALS['SETTINGS'];

        $DATA = [];
        $DATA["teachers"] = array_map(function($u){
            return ["id" => $u->getId(), "full_name" => $u->getFullName()];
        }, $school->getUsers()->toArray());
        $DATA["student_limits"] = array_combine(["min", "max"], $SETTINGS["values"]["min_max_students"]);
        $DATA["school_name"] = $school->getName();

        $groups = $school->getGroups();
        $grades_at_this_school = $school->getGradesAvailable(true);

        foreach($grades_at_this_school as $grade_val => $grade_label){
            $groups_current_grade = $groups->filter(function($g) use ($grade_val){
                return $g->isGrade($grade_val);
            });
            $tab = ["id" => $grade_val, "grade_label" => $grade_label];
            $extract_group_info = function($g){
                $r["id"] = $g->getId();
                $r["name"] = $g->getName();
                $r["teacher_id"] = $g->getUser()->getId();
                $r["nr_students"] = $g->getNumberStudents();
                $r["food"] = $g->getFood();
                $r["info"] = $g->getInfo();
                $r["visits"] = array_map(function($v){
                    $r["id"] = $v->getId();
                    $r["date"] = $v->getDate()->toDateString();
                    $r["topic_short_name"] = $v->getTopic()->getShortName();
                    $r["topic_url"] = $v->getTopic()->getUrl();
                    $r["confirmed"] = $v->isConfirmed();
                    return $r;
                }, $g->getVisits()->toArray());

                return $r;
            };
            $groups_current_grade_formatted = array_map($extract_group_info, $groups_current_grade->toArray());

            $group_columns = H::partition($groups_current_grade_formatted); // puts items in two equally large columns
            $tab["col_left"] = $group_columns[0] ?? [];
            $tab["col_right"] = $group_columns[1] ?? [];

            $DATA["tabs"][] = $tab;
        }
        bdump($DATA);

        $H = new H();
        $H->setTitle();
        $H->addDefaultJs("index")->addDefaultCss("index")
        ->setTemplate("group_settings");

        $H->addVariable("DATA", $DATA);
        $H->render();
    }
    */
}
