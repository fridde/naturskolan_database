<?php


namespace Fridde\Error;


class Error
{
    public const SEVERITY_FATAL = 0;

    public const DEFAULT = 0;
    public const PAGE_NOT_FOUND = 1;


    public static function getAllErrors()
    {
        return [
            self::SEVERITY_ => [
                self::DEFAULT => '',
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
