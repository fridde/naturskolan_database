<?php


namespace Fridde\Security;

use Fridde\Entities\Hash;
use Fridde\Entities\HashRepository;
use Fridde\Entities\School;
use Fridde\Entities\User;
use Fridde\Entities\Visit;
use Fridde\ORM;
use Fridde\Timing;
use Fridde\Utility;

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

    /**
     * Authorization constructor.
     * @param ORM $ORM
     */
    public function __construct(ORM $ORM, PasswordHandler $PWH)
    {
        $this->ORM = $ORM;
        $this->PWH = $PWH;
    }

    public function calculatePasswordForSchool(School $school, $version = null): string
    {
        $version = $version ?? $this->PWH->getLatestWordFileVersion();

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

    public function getUserFromCookie()
    {
        return $this->getObjectFromCode($this->getCookieKey(), Hash::CATEGORY_USER_COOKIE_KEY, 'User');
    }

    public function getSchoolFromCookie()
    {
        return $this->getObjectFromCode($this->getCookieKey(), Hash::CATEGORY_SCHOOL_COOKIE_KEY, 'School');
    }

    public function getUserOrSchoolFromCookie()
    {
        return $this->getUserFromCookie() ?? $this->getSchoolFromCookie();

    }

    private function getObjectFromCode(string $code, int $category, string $object_class)
    {
        $hash = $this->getHashRepo()->findByPassword($code, $category);

        if (empty($hash)) {
            return null;
        }

        return $this->ORM->find($object_class, $hash->getOwnerId);
    }

    public function getVisitFromCode(string $code)
    {
        return $this->getObjectFromCode($code, Hash::CATEGORY_VISIT_CONFIRMATION_CODE, 'Visit');
    }

    public function getUserFromUrlCode(string $code)
    {
        return $this->getObjectFromCode($code, Hash::CATEGORY_USER_URL_CODE, 'User');
    }

    public function getSchoolFromPassword($password): ?School
    {
        /* @var Hash $hash  */
        $hash = $this->getHashRepo()->findByPassword($password, Hash::CATEGORY_SCHOOL_PW);
        if(!empty($hash)){
            return null;
        }
        $school_id = $hash->getOwnerId();
        /* @var School $school  */
        $school = $this->ORM->find('School', $school_id);

        return $school ?? null;
    }

    private function getHashRepo(): HashRepository
    {
        /* @var HashRepository $hash_repo  */
        $hash_repo = $this->ORM->getRepository('Hash');
        return $hash_repo;
    }


    public function getPWH()
    {
        return $this->PWH;
    }

    public function createAndSaveCode($owner_id, int $category): string
    {
        $category_settings = [
            Hash::CATEGORY_USER_URL_CODE => ['createUrlCode', 'url_key'],
            Hash::CATEGORY_USER_COOKIE_KEY => ['createCookieKey', 'cookie_key'],
            Hash::CATEGORY_SCHOOL_COOKIE_KEY => ['createCookieKey', 'cookie_key'],
            Hash::CATEGORY_VISIT_CONFIRMATION_CODE => ['createVisitConfirmationCode', 'visit_confirmation_code']
        ];

        $code = call_user_func([$this, $category_settings[$category][0]]);
        $hash = new Hash();
        $hash->setCategory($category);
        $hash->setValue(password_hash($code, PASSWORD_DEFAULT));
        $hash->setOwnerId($owner_id);
        $path = ['values', 'validity', $category_settings[$category][1]];
        $expiration = Utility::resolve(SETTINGS, $path);
        $hash->setExpiresAt(Timing::addDurationToNow($expiration));

        $this->ORM->EM->persist($hash);
        $this->ORM->EM->flush();

        return $code;
    }

    private function getCookieKey()
    {
        return $_COOKIE[self::COOKIE_KEY_NAME] ?? null;
    }

    public static function isUser($object)
    {
        return ($object instanceof User);
    }

    public static function isSchool($object)
    {
        return ($object instanceof School);
    }

    public static function isVisit($object)
    {
        return ($object instanceof Visit);
    }



}
