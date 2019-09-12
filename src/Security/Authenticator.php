<?php


namespace Fridde\Security;

use Carbon\Carbon;
use Doctrine\Common\Cache\CacheProvider;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\ORM;
use Fridde\Timing;


class Authenticator
{
    /* @var ORM $ORM */
    private $ORM;
    /* @var PasswordHandler $PWH */
    private $PWH;
    /* @var CacheProvider $cache  */
    public $cache;

    public const COOKIE_KEY_NAME = 'AuthKey';

    private const COOKIE_KEY_LENGTH = 32;
    private const URL_CODE_LENGTH = 10;
    private const VISIT_CONFIRMATION_CODE_LENGTH = 5;

    public const ROLE_GUEST = 0;
    public const ROLE_USER = 1;
    public const ROLE_ADMIN = 2;

    public const OWNER_SEPARATOR = '_';


    /**
     * Authenticator constructor.
     * @param ORM $ORM
     * @param PasswordHandler $PWH
     * @param CacheProvider|null $cache
     */
    public function __construct(ORM $ORM, PasswordHandler $PWH, CacheProvider $cache = null)
    {
        $this->ORM = $ORM;
        $this->PWH = $PWH;
        $this->cache = $cache;
    }

    public function calculatePasswordForSchool(School $school, int $year = null): string
    {
        $year = $year ?? $this->getCurrentYear();

        $id = [$school->getId(), $year, $this->PWH->getSalt()];
        $id = implode('.', $id);

        return $this->PWH->calculatePasswordForId($id);
    }

    public function checkPasswordForSchool(School $school, string $given_password): bool
    {
        $years = $this->getYears();

        $hash = false;
        foreach($years as $year){
            $index = $school->getId().'.'.$year;
            if($this->cache->contains($index)){
                $hash = $this->cache->fetch($index);
                if(password_verify($given_password, $hash)){
                    return true;
                }
            }
        }
        if($hash === false){
            $hash = password_hash($this->calculatePasswordForSchool($school), PASSWORD_DEFAULT);
            $index = $school->getId().'.'.$this->getCurrentYear();
            $this->cache->save($index, $hash);
        }
        return password_verify($given_password, $hash);
    }

    public static function createHashFromString(string $string, int $length = -1): string
    {
        $hash = hash('sha256', $string);
        if($length >= 0){
            $hash = substr($hash, 0, $length);
        }

        return $hash;
    }

    public function createCookieKeyForUser(User $user, int $year = null): string
    {
        return $this->createCookieKeyFor($user, $year);
    }

    public function createCookieKeyForSchool(School $school, int $year = null): string
    {
        return $this->createCookieKeyFor($school, $year);
    }

    private function createCookieKeyFor($object, int $year = null): string
    {
        if(!($object instanceof School || $object instanceof User)){
            // throw error
        }

        $year = $year ?? Carbon::today()->year;

        $id = [$object->getId(), $year, $this->PWH->getSalt()];
        $id = implode('.', $id);

        $key = $object->getId() . self::OWNER_SEPARATOR;
        $key .= self::createHashFromString($id, self::COOKIE_KEY_LENGTH);

        return $key;
    }

    private function getObjectFromCode(string $code = null)
    {
        if(empty($code)){
            return null;
        }
        $id = explode(self::OWNER_SEPARATOR, $code)[0];
        if(is_numeric($id)){
            /* @var User $obj  */
            $obj = $this->ORM->find(User::class, $id);
            return $obj;
        }
        /* @var School $obj  */
        $obj = $this->ORM->find(School::class, $id) ?? null;
        return $obj;
    }

    public function getUserFromCode(string $code = null): ?User
    {
        $user = $this->getObjectFromCode($code);

        return ($user instanceof User ? $user : null);
    }

    public function getSchoolFromCode(string $code = null): ?School
    {
        $school = $this->getObjectFromCode($code);

        return ($school instanceof School ? $school : null);
    }

    public function checkCookieKeyForUser(User $user = null, string $cookie_key = null): bool
    {
        if(empty($user) || empty($cookie_key)){
            return false;
        }
        foreach($this->getYears() as $year){
            if($this->createCookieKeyForUser($user, $year) === $cookie_key){
                return true;
            }
        }

        return false;
    }

    public function checkCookieKeyForSchool(School $school = null, string $cookie_key = null): bool
    {
        if(empty($school) || empty($cookie_key)){
            return false;
        }
        foreach($this->getYears() as $year){
            if($this->createCookieKeyForSchool($school, $year) === $cookie_key){
                return true;
            }
        }

        return false;
    }

    public function createUserUrlCode(User $user): string
    {
        $id = ['u', $user->getId(), Carbon::today()->year, $this->PWH->getSalt()];
        $id = implode('.', $id);

        return $user->getId() . self::OWNER_SEPARATOR . self::createHashFromString($id, self::URL_CODE_LENGTH);
    }

    public function createVisitConfirmationCode(Visit $visit): string
    {
        $id = ['v', $visit->getId(), $this->PWH->getSalt()];
        $id = implode('.', $id);

        $code = $visit->getId() . '-';
        $code .= self::createHashFromString($id, self::VISIT_CONFIRMATION_CODE_LENGTH);
        
        return $code; 
    }

    public function getVisitFromCode(string $code): Visit
    {
        $parts = explode('-', $code);
        /* @var Visit $visit  */
        $visit = $this->ORM->find(Visit::class, $parts[0]);
        
        return $visit;
    }

    public function checkCodeForVisit(Visit $visit, string $code): bool
    {
        return $this->createVisitConfirmationCode($visit) === $code;
    }

    public function getPWH(): PasswordHandler
    {
        return $this->PWH;
    }


    public function setCookieKeyInBrowser(string $value, Carbon $exp_date = null): void
    {
        $key = self::COOKIE_KEY_NAME;
        $exp_date = $exp_date ?? Timing::addDurationToNow($this->PWH->getCookieValidity());

        setcookie($key, $value, $exp_date->timestamp, '/');
    }

    public function removeCookieKeyFromBrowser(): void
    {
        $key = self::COOKIE_KEY_NAME;
        setcookie($key, null, -1, '/');
    }

    public function setSessionKey(string $value): void
    {
        $_SESSION[self::COOKIE_KEY_NAME] = $value;
    }

    public function emptySession(): void
    {
        session_unset();
    }

    public function getYears(int $number = 2): array
    {
        $current_year = Carbon::today()->year;
        $years = [];
        foreach(range(0, $number -1) as $distance){
            $years[] = $current_year - $distance;
        }

        return $years;
    }

    public function getCurrentYear(): int
    {
        return $this->getYears(1)[0];
    }


}
