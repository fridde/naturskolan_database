<?php
namespace Fridde;

use Carbon\Carbon;
use Fridde\{
    Dumper, Entities\Group, Entities\School
};

class DatabaseMaintainer
{
    /** @var Naturskolan $N */
    private $N;
    /* @var ORM $ORM  */
    private $ORM;

    public function __construct(){
        $this->ORM = $GLOBALS['CONTAINER']->get('Naturskolan')->ORM;
    }

    public function backup()
    {
        $dumper = new Dumper();
        $dumper->export();
    }

    public function cleanOldBackups()
    {
        $files = scandir(BASE_DIR . '/backup', SCANDIR_SORT_ASCENDING);
        foreach($files as $file){
            if(is_readable($file)){
                $date_string = explode('_', $file)[0];
                $date = new Carbon($date_string);

                if(!$this->isSafe($date)){
                    unlink($file);
                }
            }
        }
    }

    public function cleanOldGroupNumbers()
    {
       $current_year = Carbon::today()->year;
        /** @var School $school */
        foreach($this->ORM->getRepository('School')->findAll() as $school){
           $group_numbers = $school->getGroupNumbers();
           foreach($group_numbers as $startyear => $numbers){
               if($startyear < $current_year - 2){
                   unset($group_numbers[$startyear]);
               }
           }
           $school->setGroupNumbers($group_numbers);
       }
       $this->ORM->EM->flush();
    }

    private function isSafe(Carbon $date)
    {
        $today = Carbon::today();
        $daynr = $date->diffInDays($today);
        $checks = [];

        $checks[] = $daynr < 10;
        $checks[] = $daynr < 90 && $daynr % 5 === 0;
        $checks[] = $daynr < 300 && $daynr % 30 === 0 ;
        $checks[] = $daynr % 90 === 0;

        return !empty(array_filter($checks));
    }

    public function clean()
    {
        $entities = ['Change', 'Cookie', 'Group', 'Message'];
        $remove = [];
        $nameless = [];

        foreach($entities as $entity){

            $repo = $this->ORM->getRepository($entity);

            switch($entity){
                case 'Change':
                $date = Carbon::today()->subDays(90);
                $remove[] = $repo->findChangesOlderThan($date);
                break;
                case 'Cookie':
                $date = Carbon::today()->subDays(300);
                $remove[] = $repo->findCookiesOlderThan($date);
                break;
                case 'Group':
                $date = Carbon::today()->subYears(2);
                $remove[] = $repo->findGroupsOlderThan($date);
                $nameless = $repo->findGroupsWithoutName();
                break;
                case 'Message':
                $date = Carbon::today()->subDays(400);
                $remove[] = $repo->findMessagesOlderThan($date);
                break;
            }
        }

        array_walk_recursive($remove, function($entity){
            $this->ORM->EM->remove($entity);
        });
        array_walk($nameless, function(Group $g){
            $g->setName();
        });

        $this->ORM->EM->flush();

        // Errors get special treatment as they are not managed by EM
        $ts = Carbon::today()->subDays(300)->timestamp;
        $stmt = 'DELETE FROM errors WHERE time < ' . $ts . ';';
        $this->ORM->EM->getConnection()->executeQuery($stmt);
    }

}
