<?php

namespace Fridde;

use Hashids\Hashids as Hasher;


class PasswordHandler
{
    /* @var string $salt */
    private $salt;
    /* @var array $settings */
    private $settings;
    /* @var int $min_length */
    private $min_length = 6;
    /* @var int $long_code_length */
    private $long_code_length = 12;
    private $alphabet;
    public const PW_DELIMITER = '.';

    public function __construct()
    {
        $this->settings = SETTINGS['values'];
        $this->salt = $this->settings['pw_salt'];
        $this->alphabet = array_merge([''], range('a', 'z'), ['å', 'ä', 'ö']);
    }

    private function encode(string $ini_string, array $numbers, int $min_length = null)
    {
        $min_length = $min_length ?? $this->min_length;
        $Hasher = new Hasher($ini_string, $min_length);

        return $Hasher->encode($numbers);
    }

    public function decode($ini_string, $encoded_string, $min_length = null)
    {
        $min_length = $min_length ?? $this->min_length;
        $Hasher = new Hasher($ini_string, $min_length);

        return $Hasher->decode($encoded_string);
    }

    /**
     * @param string $school_id_password
     * @return string|bool Returns school_id if possible, returns false otherwise
     */
    public function passwordToSchoolId(string $school_id_password)
    {
        $school_id_password = trim($school_id_password);
        list($school_id, $pw) = explode(self::PW_DELIMITER, $school_id_password);
        $ini_string = $this->addSalt($school_id);
        $numbers = $this->decode($ini_string, $pw);

        if (count($numbers) !== 2) {
            return false;
        }

        $school_int = $this->stringToInt($school_id);
        $right_school = $this->digitSum($school_int) === $numbers[0];
        $right_year = in_array($numbers[1], $this->getYears(), false);
        if ($right_school && $right_year) {
            return $school_id;
        }

        return false;

    }

    public function checkPasswordForSchool(string $school_id, string $password)
    {
        $password = trim($password);

        $ini_string = $this->addSalt($school_id);
        $numbers = $this->decode($ini_string, $password);

        if (count($numbers) !== 2) {
            return false;
        }
        $school_int = $this->stringToInt($school_id);
        $right_school = $this->digitSum($school_int) === $numbers[0];
        $right_year = in_array($numbers[1], $this->getYears(), false);
        if ($right_school && $right_year) {
            return $school_id;
        }

        return false;
    }

    public function createPassword(string $school_id, $custom_salt = false)
    {
        $ini_string = $this->addSalt($school_id, $custom_salt);
        $school_int = $this->stringToInt($school_id);
        $dig_sum = $this->digitSum($school_int);
        $year = (int) date('y');
        $numbers = [$dig_sum, $year];

        return $this->encode($ini_string, $numbers);
    }

    public function createCodeFromInt(int $int, string $entropy = '')
    {
        return $this->encode($this->addSalt($entropy), [$int], $this->long_code_length);
    }

    public function getIntFromCode(string $code, string $entropy = '')
    {
        $ini_string = $this->addSalt($entropy);
        $numbers = $this->decode($ini_string, $code, $this->long_code_length);

        return $numbers[0] ?? null;
    }

    private function addSalt(string $string = '', $custom_salt = false)
    {
        $salt = $custom_salt === false ? $this->salt : $custom_salt;
        return $string . $salt;
    }

    private function stringToInt(string $string, string $type = 'ascii')
    {
        if ($type === 'md5') {
            return base_convert(md5($string), 16, 10);
        } elseif ($type === 'ascii') {
            $base = count($this->alphabet);
            $id_array = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
            $int = 0;
            foreach (array_reverse($id_array) as $exponent => $ch) {
                $id = array_search(strtolower($ch), $this->alphabet, true);
                $int += $id * ($base ** $exponent);
            }

            return (int) $int;
        }
    }


    private function intToString($int)
    {
        $base = count($this->alphabet);
        $quot = (int) $int;
        $string = '';
        while ($quot !== 0) {
            $remainder = $quot % $base;
            $string .= $this->alphabet[$remainder];
            $quot = intdiv($quot, $base);
        }

        return strrev($string);
    }


    private function getYears()
    {
        $current_year = (int) date('y');
        $val = $this->settings['school_pw_validity'];

        return range($current_year, $current_year - $val + 1);
    }

    /**
     * Calculates the digit sum of the number.
     * Ex: 12345 becomes 1+2+3+4+5 = 16
     *
     * @param int $number
     * @return int
     */
    private function digitSum(int $number): int
    {
        return array_sum(str_split($number));
    }

}
