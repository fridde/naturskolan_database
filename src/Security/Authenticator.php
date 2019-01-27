<?php


namespace Fridde\Security;

use Carbon\Carbon;
use Fridde\Entities\Hash;
use Fridde\Entities\HashRepository;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Naturskolan;
use Fridde\ORM;
use Fridde\Timing;
use Fridde\Utility;
use MongoDB\BSON\Timestamp;

class Authenticator
{
    /* @var ORM $ORM */
    private $ORM;
    /* @var PasswordHandler $PWH */
    private $PWH;

    public const COOKIE_KEY_NAME = 'AuthKey';

    private const COOKIE_KEY_LENGTH = 32; //can't be longer, created by md5()
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
     */
    public function __construct(ORM $ORM, PasswordHandler $PWH)
    {
        $this->ORM = $ORM;
        $this->PWH = $PWH;
    }

    public function calculatePasswordForSchool(School $school, $version = null): string
    {
        if (empty($version)) {
            $version = $this->getLatestValidVersion($school->getId(), Hash::CATEGORY_SCHOOL_PW);
        }
        if (empty($version)) {
            throw new NException(Error::DATABASE_INCONSISTENT,  ['valid password', $school->getName()]);
        }

        return $this->PWH->calculatePasswordForId($school->getId(), $version);


    }

    public function createCookieKey(): string
    {
        return $this->PWH::createRandomKey(self::COOKIE_KEY_LENGTH);
    }

    public function createUrlCode(): string
    {
        return $this->PWH::createRandomKey(self::URL_CODE_LENGTH);
    }

    public function createVisitConfirmationCode(): string
    {
        return $this->PWH::createRandomKey(self::VISIT_CONFIRMATION_CODE_LENGTH);
    }

    public function getObjectFromCode(string $code = null, array $criteria = [], string $object_class)
    {
        if (empty($code)) {
            return null;
        }
        $criteria['accept_expired'] = $criteria['accept_expired'] ?? true;
        $hash = $this->getHashRepo()->findByPassword($code, $criteria);

        if (empty($hash)) {
            return null;
        }

        if($hash->isExpired()){
            throw new NException(Error::EXPIRED_CODE,[$code]);
        }

        return $this->ORM->find($object_class, $hash->getOwnerId());
    }

    public function getVisitFromCode(string $code)
    {
        $criteria['category'] = Hash::CATEGORY_VISIT_CONFIRMATION_CODE;

        return $this->getObjectFromCode($code, $criteria, Visit::class);
    }

    public function getUserFromUrlCode(string $code)
    {
        $criteria['category'] = Hash::CATEGORY_USER_URL_CODE;

        return $this->getObjectFromCode($code, $criteria, User::class);
    }

    public function getSchoolFromPassword($password): ?School
    {

        $criteria = ['category' => Hash::CATEGORY_SCHOOL_PW];
        $criteria['accept_expired'] = false;

        /* @var Hash $hash */
        $hash = $this->getHashRepo()->findByPassword($password, $criteria);
        if (empty($hash)) {
            return null;
        }
        $school_id = $hash->getOwnerId();
        /* @var School $school */
        $school = $this->ORM->find('School', $school_id);

        return $school ?? null;
    }

    public function schoolHasPassword(string $school_id, string $password): bool
    {
        $criteria['category'] = Hash::CATEGORY_SCHOOL_PW;
        $criteria['owner_id'] = $school_id;

        return !empty($this->getHashRepo()->findByPassword($password, $criteria));
    }


    private function getHashRepo(): HashRepository
    {
        /* @var HashRepository $hash_repo */
        $hash_repo = $this->ORM->getRepository('Hash');

        return $hash_repo;
    }


    public function getPWH(): PasswordHandler
    {
        return $this->PWH;
    }

    /*
     * ['function to create the code', 'key in SETTINGS['values']['validity'] ']
     * */
    private function getCategorySettings(): array
    {
        return [
            Hash::CATEGORY_USER_URL_CODE => ['createUrlCode', 'url_key'],
            Hash::CATEGORY_USER_COOKIE_KEY => ['createCookieKey', 'cookie_key'],
            Hash::CATEGORY_SCHOOL_COOKIE_KEY => ['createCookieKey', 'cookie_key'],
            Hash::CATEGORY_VISIT_CONFIRMATION_CODE => ['createVisitConfirmationCode', 'visit_confirmation_code'],
            Hash::CATEGORY_USER_REMOVAL_CODE => ['createUrlCode', 'url_key']
        ];
    }

    public function getExpirationDate(int $category): Carbon
    {
        $cat_settings = $this->getCategorySettings();
        $path = ['values', 'validity', $cat_settings[$category][1]];
        $expiration = Utility::resolve(SETTINGS, $path);

        if(defined('DEBUG') && !empty(DEBUG)){
            $now = Carbon::createFromTimestampUTC(time());
            return Timing::addDuration($expiration, $now);
        }

        return Timing::addDurationToNow($expiration);
    }

    private function getFunctionForCategory(int $category): string
    {
        $cat_settings = $this->getCategorySettings();

        return $cat_settings[$category][0];
    }

    public function createAndSaveCode($owner_id, int $category): string
    {
        $code = $owner_id . self::OWNER_SEPARATOR;
        $code .= call_user_func([$this, $this->getFunctionForCategory($category)]);
        $hash = new Hash();
        $hash->setCategory($category);
        $hash->setValue(password_hash($code, PASSWORD_DEFAULT));
        $hash->setOwnerId($owner_id);
        $hash->setExpiresAt($this->getExpirationDate($category));

        $this->ORM->EM->persist($hash);
        $this->ORM->EM->flush();

        return $code;
    }

    public function setCookieKeyInBrowser($value, Carbon $exp_date): void
    {
        $key = self::COOKIE_KEY_NAME;
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

    public static function isUser($object): bool
    {
        return ($object instanceof User);
    }

    public static function isSchool($object): bool
    {
        return ($object instanceof School);
    }

    public static function isVisit($object): bool
    {
        return ($object instanceof Visit);
    }

    private function getLatestValidVersion($owner_id, int $category): ?string
    {
        return $this->getHashRepo()->findYoungestValidVersion($owner_id, $category);
    }


}
