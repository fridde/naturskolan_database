<?php
namespace Fridde;

use Carbon\Carbon;
use Fridde\{
    Dumper, Entities\Group, Entities\School, Entities\SchoolRepository
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
        /* @var SchoolRepository $school_repo  */
        /* @var School $school */
        $school_repo = $this->ORM->getRepository('School');

        $oldest_allowed_year = Carbon::today()->year - 2;
        
        foreach($school_repo->findAll() as $school){
           $group_numbers = $school->getGroupNumbers();
           foreach($group_numbers as $startyear => $numbers){
               if($startyear < $oldest_allowed_year){
                   unset($group_numbers[$startyear]);
               }
           }
           $school->setGroupNumbers($group_numbers);
       }
       $this->ORM->EM->flush();
    }

    private function isSafe(Carbon $date): bool
    {
        $today = Carbon::today();
        $age_in_days = $date->diffInDays($today);

        $arbitrary_constant_date = Carbon::parse('2018-01-01');
        $day_nr = $date->diffInDays($arbitrary_constant_date);

        $any = false;

        $any |= $age_in_days < 10;
        $any |= $age_in_days < 90 && $day_nr % 5 === 0;
        $any |= $age_in_days < 300 && $day_nr % 30 === 0 ;
        $any |= $day_nr % 90 === 0;

        return $any;
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
