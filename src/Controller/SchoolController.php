<?php

namespace Fridde\Controller;

use Fridde\{HTMLForTwig as H};
use Fridde\Controller\{LoginController};

class SchoolController {

    //public $N;
    public $SETTINGS;
    public $params;
    public $school;

    public function __construct($params = [])
    {
        //$this->N = new Naturskolan();
        $this->SETTINGS = $GLOBALS['SETTINGS'];
        $this->params = $params;
        $this->school = LoginController::checkCookie();
    }

    public static function handleRequest($params = [])
    {
        $own_class_name = get_called_class();
        $THIS = new $own_class_name($params);

        if(empty($THIS->school) || $THIS->school->getId() != $THIS->params["school"]){
            return LoginController::getModal();
        }
        $page = $THIS->params["page"] ?? "groups";
        if($page == "groups"){
            $DATA = $THIS->getAllGroups($THIS->school);
            $template = "group_settings";
        } elseif ($page == "personal"){
            $DATA = $THIS->getAllUsers($THIS->school);
            $template = "staff_list";
        }

        $H = new H();
        $H->setTitle();
        $H->addDefaultJs("index")->addDefaultCss("index")
        ->setTemplate($template)->setBase();

        $H->addVariable("DATA", $DATA);
        $H->render();

    }

    public function getAllUsers($school)
    {
        $DATA = ["entity_class" => "User"];
        $users = $school->getUsers()->toArray();
        $keys = ["id", "FirstName", "LastName", "Mobil", "Mail"];
        foreach($keys as $key){
            $DATA["headers"][] = $key;
            foreach($users as $i => $user){
                $method_name = $key == "id" ? "getId" : "get" . $key;
                $DATA["users"][$i][$key] = $user->$method_name();
            }
        }
        return $DATA;
    }

    public function getAllGroups($school)
    {
        $DATA = [];
        $DATA["teachers"] = array_map(function($u){
            return ["id" => $u->getId(), "full_name" => $u->getFullName()];
        }, $school->getUsers()->toArray());
        $DATA["student_limits"] = $this->SETTINGS["values"]["min_max_students"];
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

        return $DATA;
    }
}
