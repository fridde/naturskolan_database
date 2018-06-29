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
    /* @var ORM $ORM */
    private $ORM;

    private const ARBITRARY_DATE = '2018-01-01';

    public function __construct()
    {
        $this->N = $GLOBALS['CONTAINER']->get('Naturskolan');
        $this->ORM = $this->N->ORM;
    }

    public function backup()
    {
        $dumper = new Dumper();
        $dumper->export();
    }

    public function cleanOldBackups()
    {
        $files = glob(BASE_DIR.'/backup/*');
        foreach ($files as $file) {
            try {
                $file_piece = pathinfo($file, PATHINFO_FILENAME);
                $date_string = explode('_', $file_piece)[0];
                $date = Carbon::parse($date_string);

                if (!$this->isWorthSaving($date)) {
                    unlink($file);
                }
            } catch (\Exception $e) {
                $this->N->log($e->getMessage(), __METHOD__);
            }
        }
    }

    public function cleanOldGroupNumbers()
    {
        /* @var SchoolRepository $school_repo */
        /* @var School $school */
        $school_repo = $this->ORM->getRepository('School');

        $oldest_allowed_year = Carbon::today()->year - 2;

        foreach ($school_repo->findAll() as $school) {
            $group_numbers = $school->getGroupNumbers();
            foreach ($group_numbers as $startyear => $numbers) {
                if ($startyear < $oldest_allowed_year) {
                    unset($group_numbers[$startyear]);
                }
            }
            $school->setGroupNumbers($group_numbers);
        }
        $this->ORM->EM->flush();
    }

    private function isWorthSaving(Carbon $date): bool
    {
        $today = Carbon::today();
        $age_in_days = $date->diffInDays($today);

        $day_nr = $date->diffInDays(Carbon::parse(self::ARBITRARY_DATE));

        $any = false;

        $any |= $age_in_days < 10;
        $any |= $age_in_days < 90 && $day_nr % 5 === 0;
        $any |= $age_in_days < 300 && $day_nr % 30 === 0;
        $any |= $day_nr % 90 === 0;

        return $any;
    }

    public function clean()
    {
        $entities = ['Change', 'Cookie', 'Group', 'Message'];
        $remove = [];
        $nameless = [];

        foreach ($entities as $entity) {

            $repo = $this->ORM->getRepository($entity);

            switch ($entity) {
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

        array_walk_recursive(
            $remove,
            function ($entity) {
                $this->ORM->EM->remove($entity);
            }
        );
        array_walk(
            $nameless,
            function (Group $g) {
                $g->setName();
            }
        );

        $this->ORM->EM->flush();

        // Errors get special treatment as they are not managed by EM
        $ts = Carbon::today()->subDays(300)->timestamp;
        $stmt = 'DELETE FROM errors WHERE time < '.$ts.';';
        $this->ORM->EM->getConnection()->executeQuery($stmt);
    }

}
