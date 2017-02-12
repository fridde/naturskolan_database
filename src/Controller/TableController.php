<?php

namespace Fridde\Controller;


use Fridde\{Naturskolan, HTMLForTwig as H};

class TableController {

    public static function view($params = [])
    {
        $N = new Naturskolan();
        $ORM = $N->ORM;
        $entity_class = ucfirst($params["entity"]);

        $table_elements = $ORM->getRepository($entity_class)->findAll();
        //Remove in prod vvvvvvvvvvvv
        //$first = reset($table_elements);
        //dump($first);
        //exit();
        // Remove in prod ^^^^^^^^^^
        $t_settings = self::getTableSettings($entity_class);
        $t_settings = self::retrieveOptions($t_settings, $ORM, $entity_class);

        $DATA = ["headers" => array_keys($t_settings), "rows" => []];
        $DATA["entity_class"] = $entity_class;
        foreach($table_elements as $element){
            $row = ["id" => $element->getId()];
            foreach($t_settings as $name => $val){
                $val["value"] = call_user_func([$element, $val["value"]]);
                $row = array_merge($row, [$name => $val]);
            }
            $DATA["rows"][] = $row;
        }
        
        $H = new H();
        $H->setTitle();
        $H->addDefaultJs("index")->addJs(["js.bs.date.debug", "js.bs.date.sv"])
        ->addDefaultCss("index")->addCss(["css.bs.date"])
        ->setTemplate("table")->setBase();

        $H->addVariable("DATA", $DATA);
        $H->render();
    }

    private static function getTableSettings($entity_class)
    {
        $ec = $entity_class;
        $atts = [];
        $atts["id"]["value"] = "getId";
        $atts["id"]["type"] = "ignored";

        if(in_array($ec, ["User"])){
            array_push($atts, "FirstName", "LastName", "Mobil", "Mail");
            $atts["Role"]["options"] = "getRoleOptions";
            $atts[] = "Acronym";
        }

        if(in_array($ec, ["Group", "Location", "School"])){
            $atts[] = "Name";
        }

        if(in_array($ec, ["Group"])){
            $atts["User"]["value"] = "getUserId";
            $atts["User"]["options"] = ["User", "findAllUsersWithSchools"];
            $atts["StartYear"]["type"] = "integer";
            $atts["NumberStudents"]["type"] = "integer";
            $atts["Food"]["type"] = "textarea";
            $atts["Info"]["type"] = "textarea";
            $atts["Notes"]["type"] = "textarea";
        }
        if(in_array($ec, ["User", "Group"])){
            $atts["Status"]["options"] = "getStatusOptions";
            $atts["LastChange"]["type"] = "readonly";
            $atts["CreatedAt"]["type"] = "readonly";
        }

        if(in_array($ec, ["User", "Group", "Password"])){
            $atts["School"]["value"] = "getSchoolId";
            $atts["School"]["options"] = ["School", "findAllSchoolLabels"];
        }

        if(in_array($ec, ["Topic", "Group"])){
            $atts["Grade"]["options"] = "getGradeOptions";
        }

        if(in_array($ec, ["Topic", "School"])){
            $atts["VisitOrder"]["type"] = "integer";
        }

        if(in_array($ec, ["Topic"])){
            array_push($atts, "ShortName", "LongName");
            $atts["Location"]["value"] = "getLocationId";
            $atts["Location"]["options"] = ["Location", "findAllLocationLabels"];
            array_push($atts, "Food", "Url");
            $atts["IsLektion"]["type"] = "radio";
            $atts["IsLektion"]["options"] = "getIsLektionOptions";
        }

        if(in_array($ec, ["Location", "School"])){
            array_push($atts, "Coordinates");
        }

        if(in_array($ec, ["Password"])){
            $atts["Type"]["options"] = "getTypeOptions";
            array_push($atts, "Value");
            $atts["Rights"]["options"] = "getRightsOptions";
        }

        if(in_array($ec, ["School"])){
            $atts["GroupsAk2"]["type"] = "integer";
            $atts["GroupsAk5"]["type"] = "integer";
            $atts["GroupsFbk"]["type"] = "integer";
        }

        if(in_array($ec, ["Visit"])){
            $atts["Group"]["value"] = "getGroupId";
            $atts["Group"]["options"] = ["Group", "findAllGroupsWithNameAndSchool"];
            $atts["Date"]["value"] = "getDateString";
            $atts["Date"]["type"] = "date";
            $atts["Topic"]["value"] = "getTopicId";
            $atts["Topic"]["options"] = ["Topic", "findAllTopicsWithGrade"];
            $atts["Colleagues"]["value"] = "getColleaguesIdArray";
            $atts["Colleagues"]["options"] = ["School", "getStaffWithNames"];
            $atts["Confirmed"]["type"] = "radio";
            $atts["Confirmed"]["options"] = "getConfirmedOptions";
            //$atts["Time"]["type"] = "time";
            //TODO: implement time-picker
            array_push($atts, "Time");
        }

        return self::fillInDefaultValues($atts);
    }

    private static function fillInDefaultValues($atts = [])
    {
        $return = [];
        foreach($atts as $key => $value){
            if(is_integer($key)){
                $key = $value;
            }
            $value =  $atts[$key]["value"] ?? "get" . $key;
            $options = $atts[$key]["options"] ?? null;
            $type =  $atts[$key]["type"] ?? null;

            if(empty($type)){
                $type = empty($options) ? "text" : "select";
            }
            $return[$key] = compact("value", "options", "type");
        }
        return $return;
    }

    private static function retrieveOptions($atts, $ORM, $entity_class)
    {
        foreach($atts as $name => $val){
            if(!empty($val["options"])){
                if(is_array($val["options"])){
                    $repo = $ORM->getRepository($val["options"][0]);
                    $atts[$name]["options"] = call_user_func([$repo, $val["options"][1]]);
                } elseif (is_string($val["options"])){
                    $entity = $ORM->getRepository($entity_class)->findOneBy([]);
                    $atts[$name]["options"] = call_user_func([$entity, $val["options"]]);
                } else {
                    throw new Exception("Options could not be retrieved for parameter " . var_export($val["options"], true));
                }
            }

        }
        return $atts;
    }
}
