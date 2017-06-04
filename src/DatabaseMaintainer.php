<?php
namespace Fridde;

use Carbon\Carbon;
use Fridde\{Dumper};

class DatabaseMaintainer
{
    private $N;

    public function __construct(){
        $this->N = $GLOBALS["CONTAINER"]->get("Naturskolan");
    }

    public function backup()
    {
        $dumper = new Dumper();
        $dumper->export();
    }

    public function cleanOldBackups()
    {
        $files = scandir(BASE_DIR . "/backup");
        foreach($files as $file){
            if(is_readable($file)){
                $date_string = explode("_", $file)[0];
                $date = new Carbon($date_string);

                if(!$this->isSafe($date)){
                    unlink($file);
                }
            }
        }
    }

    private function isSafe($date)
    {
        $today = Carbon::today();
        $daynr = $date->diffInDays($today);
        $checks = [];

        $checks[] = $daynr < 10;
        $checks[] = $daynr < 90 && $daynr % 5 === 0;
        $checks[] = $daynr < 300 && $daynr % 30 === 0 ;
        $checks[] = $daynr % 90 == 0;

        return !empty(array_filter($checks));
    }

    public function clean()
    {
        $entities = ["Change", "Cookie", "Group", "Message"];
        $remove = [];
        $nameless = [];

        foreach($entities as $entity){

            $repo = $this->N->ORM->getRepository($entity);

            switch($entity){
                case "Change":
                $date = Carbon::today()->subDays(90);
                $remove[] = $repo->findChangesOlderThan($date);
                break;
                case "Cookie":
                $date = Carbon::today()->subDays(300);
                $remove[] = $repo->findCookiesOlderThan($date);
                break;
                case "Group":
                $date = Carbon::today()->subYears(2);
                $remove[] = $repo->findGroupsOlderThan($date);
                $nameless = $repo->findGroupsWithoutName();
                break;
                case "Message":
                $date = Carbon::today()->subDays(400);
                $remove[] = $repo->findMessagesOlderThan($date);
                break;
            }
        }

        array_walk_recursive($remove, function($entity){
            $this->N->ORM->EM->remove($entity);
        });
        array_walk($nameless, function($g){
            $g->setName();
        });

        $this->N->ORM->EM->flush();

        // Errors get special treatment
        $ts = Carbon::today()->subDays(300)->timestamp;
        $stmt = "DELETE FROM errors WHERE time < " . $ts . ";";
        $this->N->ORM->EM->getConnection()->executeQuery($stmt);
    }

}
