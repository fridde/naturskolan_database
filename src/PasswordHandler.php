<?php
namespace Fridde;

use Hashids\Hashids as Hasher;


class PasswordHandler
{
    /* @var string $salt  */
    private $salt;
    /* @var array $settings */
    private $settings;
    /* @var int $min_length  */
    private $min_length = 3;
    /* @var int $long_code_length  */
    private $long_code_length = 10;

    public function __construct()
    {
        $this->settings = $GLOBALS["SETTINGS"]["values"];
        $this->salt = $this->settings["pw_salt"];
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
        list($school_id, $pw) = explode(".", $school_id_password);
        $ini_string = $this->addSalt($school_id);
        $numbers = $this->decode($ini_string, $pw);

        if(count($numbers) === 2){
            $school_int = $this->stringToInt($school_id);
            $right_school = $this->digitSum($school_int) === $numbers[0];
            $right_year = in_array($numbers[1], $this->getYears());
            if($right_school && $right_year){
                return $school_id;
            }
        }
        return false;
    }

    public function createPassword(string $school_id)
    {
        $ini_string = $this->addSalt($school_id);
        $school_int = $this->stringToInt($school_id);
        $dig_sum = $this->digitSum($school_int);
        $year = intval(date("y"));
        $numbers = [$dig_sum, $year];
        return $school_id . "." . $this->encode($ini_string, $numbers);
    }

    public function createCodeFromInt(int $int, string $entropy = "")
    {
        return $this->encode($this->addSalt($entropy), [$int], $this->long_code_length);
    }

    public function getIntFromCode(string $code, string $entropy = "")
    {
        $ini_string = $this->addSalt($entropy);
        $numbers = $this->decode($ini_string, $code, $this->long_code_length);
        return $numbers[0] ?? null;
    }

    private function addSalt(string $string = "")
    {
        return $string . $this->settings["pw_salt"];
    }

    private function stringToInt(string $string, string $type = "ascii")
    {
        if($type == "md5"){
            return base_convert(md5($string), 16, 10);
        } elseif($type == "ascii") {
            $int_string = "";
            $string_array = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
            foreach($string_array as $ch){
                $int = ord($ch) - 32; // we don't need control-characters
                $int_string .= str_pad($int, 2, "0", STR_PAD_LEFT);
            }
            return intval($int_string);
        }
    }


    private function intToString(string $int_string, string $type = "ascii")
    {
        if($type == "ascii"){
            $int_string = strlen($int_string) % 2 == 0 ?: "0" . $int_string;
            $int_array = str_split($int_string, 2);

            return implode("", array_map(function($i){
                return chr(intval($int) + 32); // we don't need control-characters
            }, $int_array));
        } else {
            throw new \Exception("The conversion type <$type> is not defined.");
        }
    }


    private function getYears()
    {
        $current_year = intval(date("y"));
        $val = $this->settings["school_pw_validity"];
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
