<?php
/**
 * The School Controller
 */

namespace Fridde\Controller;

use Fridde\Entities\Group;
use Fridde\HTML as H;
use Fridde\Utility as U;
use Fridde\Controller\LoginController;

class SchoolController
{
    /** @var \Fridde\Naturskolan */
    private $N;
    private $params;
    /** @var \Fridde\Entities\School */
    public $school;
    /** @var \Fridde\Entities\User */
    private $user;
    private $LoginController;

    public function __construct($params = [])
    {
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
        $this->params = $params;
        $this->LoginController = new LoginController($this->params);
        $this->school = $this->LoginController->getSchoolFromCookie();
        $this->user = $this->LoginController->checkCode();
    }

    public function handleRequest()
    {
        $authorized = false;
        if (!empty($this->user)) {
            $this->school = $this->user->getSchool();
            $authorized = true;
        } elseif (!empty($this->school)) {
            if ($this->school->isNaturskolan()) {
                $authorized = true;
                $this->school = $this->N->ORM->getRepository("School")
                    ->find($this->params["school"]);
            } elseif ($this->school->getId() === $this->params["school"]) {
                $authorized = true;
            }
        }
        if (!$authorized) {
            return $this->LoginController->checkPassword();
        }
        if (empty($this->school)) {
            echo "The school with the index <" . $this->params["school"] . "> does not exist.";
            return null;
        }
        $page = $this->params["page"] ?? "groups";
        if ($page == "groups") {
            $DATA = $this->getAllGroups($this->school);
            $template = "group_settings";
        } elseif ($page == "staff") {
            $DATA = $this->getAllUsers($this->school);
            $template = "team_list";
        } else {
            throw new \Exception("No action was defined for the page variable $page .");
        }

        $H = new H();
        $H->setTitle()->addNav();
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
        foreach ($keys as $key) {
            $DATA["headers"][] = $key;
            foreach ($users as $i => $user) {
                $method_name = "get" . ucfirst($key);
                $DATA["users"][$i][$key] = $user->$method_name();
            }
        }
        return $DATA;
    }

    /**
     * Collects all active groups for the specified school, adds relevant info
     * and orders them into a structured array to be viewed on the school page later.
     *
     * @example getAllGroupsExample.php
     * @param  \Fridde\Entities\School $school The School object.
     * @return array An array containing structured data. See example.
     */
    public function getAllGroups($school)
    {
        $DATA = [];
        $DATA["teachers"] = array_map(function ($u) {
            return ["id" => $u->getId(), "full_name" => $u->getFullName()];
        }, $school->getUsers()->toArray());
        $DATA["student_limits"] = SETTINGS["values"]["min_max_students"];
        $DATA["school_name"] = $school->getName();

        $groups = $school->getGroups();
        $grades_at_this_school = $school->getGradesAvailable(true);

        foreach ($grades_at_this_school as $grade_val => $grade_label) {
            $groups_current_grade = $groups->filter(function ($g) use ($grade_val) {
                return $g->isGrade($grade_val);
            });
            $tab = ["id" => $grade_val, "grade_label" => $grade_label];
            $groups_current_grade_formatted = array_map(function (\Fridde\Entities\Group $g) {
                $r["id"] = $g->getId();
                $r["name"] = $g->getName();
                $r["teacher_id"] = $g->getUser()->getId();
                $r["nr_students"] = $g->getNumberStudents();
                $r["food"] = $g->getFood();
                $r["info"] = $g->getInfo();
                $r["visits"] = array_map(function (\Fridde\Entities\Visit $v) {
                    $r["id"] = $v->getId();
                    $r["date"] = $v->getDate()->toDateString();
                    $r["topic_short_name"] = $v->getTopic()->getShortName();
                    $r["topic_url"] = $v->getTopic()->getUrl();
                    $r["confirmed"] = $v->isConfirmed();
                    $dur = U::addDuration(SETTINGS["values"]["show_confirm_link"]);
                    if($v->isBefore($dur)){
                        $r["confirmation_url"] = $this->N->createConfirmationUrl($v->getId());
                    }
                    return $r;
                }, $g->getSortedVisits()->toArray());

                return $r;
            }, $groups_current_grade->toArray());

            $group_columns = H::partition($groups_current_grade_formatted); // puts items in two equally large columns
            $tab["col_left"] = $group_columns[0] ?? [];
            $tab["col_right"] = $group_columns[1] ?? [];

            $DATA["tabs"][] = $tab;
        }

        return $DATA;
    }
}
