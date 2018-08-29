<?php


namespace Fridde\Error;


class Error
{
    public const SEVERITY_FATAL = 0;
    public const SEVERITY_BAD_DATA = 1;
    public const SEVERITY_SECURITY = 2;
    public const SEVERITY_USER_INTERACTION = 3;

    public const DEFAULT = 0;
    public const PAGE_NOT_FOUND = 1;
    public const CRASH = 2;
    public const DATABASE_INCONSISTENT = 3;
    public const MISSING_SETTINGS = 4;
    public const INVALID_ARGUMENT = 5;
    public const INVALID_OPTION = 6;
    public const FILE_SYSTEM = 7;
    public const LOGIC = 8;

    public const NOT_RESOLVABLE = 10;
    public const WRONG_FORMAT = 11;

    public const UNAUTHORIZED_ACTION = 20;

    public const EXPIRED_CODE = 30;
    //public const  = ;
    //public const  = ;
    //public const  = ;
    //public const  = ;
    //public const  = ;
    //public const  = ;
    //public const  = ;
    //public const  = ;


    public static function getAllErrors()
    {
        return [
            self::SEVERITY_FATAL => [
                self::DEFAULT => '',
                self::CRASH => '<h2>Internal error</h2><p>The site has encountered an internal error and could not respond to your request.</p><p>The admin has been informed. Try again later!</p>',
                self::DATABASE_INCONSISTENT => 'A matching entity of "%s" with the criteria "%s" was not found or was duplicate. This should not happen.',
                self::MISSING_SETTINGS => 'No settings given or found in the global scope',
                self::INVALID_ARGUMENT => 'An important variable was missing or in the wrong format: %s',
                self::INVALID_OPTION => 'The option %s is not implemented in this function.',
                self::FILE_SYSTEM => 'A file was not writable, readable or did not exist: %s',
                self::LOGIC => 'A logical error occurred. Check your programming! %s',

            ],
            self::SEVERITY_BAD_DATA => [
                self::NOT_RESOLVABLE => 'The path given couldn\'t be resolved to a valid string. The path: %s',
                self::WRONG_FORMAT => 'There was data in the wrong format. Data given: "%s" but format should be "%s"',
            ],
            self::SEVERITY_SECURITY => [
                self::UNAUTHORIZED_ACTION => 'Someone tried to perform an authorized action: %s'
            ],
            self::SEVERITY_USER_INTERACTION => [
                self::EXPIRED_CODE => 'The code "%s" you used to login has expired.',
            ],

        ];
    }


    public static function getTemplate(int $code): string
    {
        foreach (self::getAllErrors() as $severity => $codes) {
            $text = $codes[$code] ?? null;
            if (!empty($text)) {
                return $text;
            }
        }

        return '';
    }

    public static function getSeverity(int $code): ?int
    {
        foreach (self::getAllErrors() as $severity => $codes) {
            if (array_key_exists($code, $codes)) {
                return $severity;
            }
        }

        return null;
    }

}
