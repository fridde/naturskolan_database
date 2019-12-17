<?php

namespace Fridde;

use Carbon\Carbon;
use Fridde\{
    Dumper, Entities\Change, Entities\Group, Entities\Hash, Entities\Message, Entities\School, Entities\SchoolRepository, Entities\User, Entities\UserRepository
};

class DatabaseMaintainer
{
    /* @var ORM $ORM */
    private $ORM;

    private const ARBITRARY_DATE = '2018-01-01'; //please, never change!

    public function __construct()
    {
        $this->ORM = $GLOBALS['CONTAINER']->get('Naturskolan')->ORM;
    }

    public function backup()
    {
        $settings['Connection_Details'] = SETTINGS['Connection_Details'];
        $settings['backup_dir'] = BASE_DIR . '/backup';

        $dumper = new Dumper($settings);
        $dumper->export();
    }

    public function cleanOldBackups()
    {
        $files = glob(BASE_DIR.'/backup/*');
        foreach ($files as $file) {

            $file_piece = pathinfo($file, PATHINFO_FILENAME);
            $date_string = explode('_', $file_piece)[0];
            $date = Carbon::parse($date_string);

            if (!$this->isWorthSaving($date)) {
                unlink($file);
            }

        }
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

    public function standardizeMobileNumbers()
    {
        /* @var UserRepository $user_repo */
        $user_repo = $this->ORM->getRepository(User::class);

        foreach ($user_repo->findActiveUsers() as $user) {
            /* @var User $user */
            $nr = $user->standardizeMobNr();
            $user->setMobil($nr);
        }
        $this->ORM->EM->flush();
    }

    public function prettifyMailAddresses()
    {
        /* @var UserRepository $user_repo */
        $user_repo = $this->ORM->getRepository(User::class);

        foreach ($user_repo->findActiveUsers() as $user) {
            /* @var User $user */
            $mail = strtolower(trim($user->getMail()));
            $user->setMail($mail);
        }
        $this->ORM->EM->flush();
    }

    public function removeOldRows()
    {
        $entities = [Change::class, Hash::class, Group::class, Message::class];
        $remove = [];
        $nameless = [];

        foreach ($entities as $entity) {

            $repo = $this->ORM->getRepository($entity);

            switch ($entity) {
                case Change::class:
                    $date = Carbon::today()->subDays(90);
                    $remove[] = $repo->findChangesOlderThan($date);
                    break;
                case Hash::class:
                    $date = Carbon::today()->subDays(300);
                    $remove[] = $repo->findHashesExpiredBefore($date);
                    break;
                case Group::class:
                    $date = Carbon::today()->subYears(2);
                    $remove[] = $repo->findGroupsOlderThan($date);
                    $nameless = $repo->findGroupsWithoutName();
                    break;
                case Message::class:
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
                $g->setName('Grupp '.Naturskolan::getRandomAnimalName());
            }
        );

        $this->ORM->EM->flush();

        // Errors get special treatment as they are not managed by EM
        $ts = Carbon::today()->subDays(300)->timestamp;
        $stmt = 'DELETE FROM errors WHERE time < '.$ts.';';
        $this->ORM->EM->getConnection()->executeQuery($stmt);
    }

}
