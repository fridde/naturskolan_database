<?php

namespace Fridde\Security;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Fridde\Error\Error;
use Fridde\Error\NException;


class PasswordHandler
{
    /* @var Key $key */
    private $key;
    /* @var array $words */
    private $words;
    private $nr_words;

    private $word_folder = 'config/words/';
    private $word_file_prefix = 'words_';

    public const MD5_BASE = 16;

    public const NR_OF_WORDS_IN_PW = 3;
    public const NR_OF_CHARS_IN_CODE = 12;
    public const PW_DELIMITER = '.';


    public static function createRandomKey(int $length = 32)
    {
        $key = '';
        $current_string = random_int(0, 999).microtime();

        while(strlen($key) < $length){
            $current_string = md5($current_string);
            $key .= $current_string;
        }

        return substr($key, 0, $length);
    }

    public function getKey(): Key
    {
        if (empty($this->key)) {
            $key = file_get_contents($this->getConfigDirectory().'.key');
            $key = preg_replace('/\s+/u', '', $key); //remove any whitespace
            $this->key = Key::loadFromAsciiSafeString($key);
        }

        return $this->key;
    }

    public function saveKeyToFile(string $key)
    {
        file_put_contents($this->getConfigDirectory().'.key', $key);
    }

    private function getConfigDirectory()
    {
        return realpath($this->getWordDirectory().'/../').'/';
    }

    private function setWordArrayFromFile(string $version = null)
    {
        $encrypted_word_string = file_get_contents($this->getWordFilePath($version));
        $encrypted_words = Crypto::decrypt($encrypted_word_string, $this->getKey());
        $this->words = explode(self::PW_DELIMITER, $encrypted_words);

        return $this->words;
    }

    public function getWordsForId(string $id_string, string $version = null): array
    {
        $this->words = $this->words ?? $this->setWordArrayFromFile($version);

        $hash = md5($id_string);
        $this->nr_words = $this->nr_words ?? count($this->words);
        $exponent = ceil(log($this->nr_words, self::MD5_BASE));
        $indices = array_slice(str_split($hash, $exponent), 0, self::NR_OF_WORDS_IN_PW);

        $max_val = self::MD5_BASE ** $exponent;
        $words = [];

        foreach ($indices as $i) {
            $dx = hexdec($i);
            $word_index = (int)floor(($dx / $max_val) * $this->nr_words);
            $words[] = $this->words[$word_index];
        }

        return $words;
    }

    public function calculatePasswordForId(string $owner_id, string $version): string
    {
        $id_string = $owner_id.'.'.$version;

        return implode(self::PW_DELIMITER, $this->getWordsForId($id_string, $version));
    }


    private function getWordFileName(string $version = null): string
    {
        if (empty($version)) {
            $file_version = $this->getLatestWordFileVersion();
        } else {
            $last_pos = strrpos($version, '_');
            $file_version = $last_pos === false ? $version : substr($version, 0, $last_pos);
        }

        return $this->word_file_prefix.$file_version.'.txt';
    }

    public function getWordFilePath(string $version = null): string
    {
        return $this->getWordDirectory().$this->getWordFileName($version);
    }

    public function getLatestWordFileVersion()
    {

        $newest_file = array_slice($e = $this->getAllWordFiles(), -1)[0];

        return substr(pathinfo($newest_file, PATHINFO_FILENAME), strlen($this->word_file_prefix));
    }

    public function getAllWordFiles(): array
    {
        $files = scandir($this->getWordDirectory(), SCANDIR_SORT_ASCENDING);

        $files = array_filter(
            $files,
            function ($f) {
                $r = pathinfo($f, PATHINFO_EXTENSION) === 'txt';

                return $r && strpos($f, $this->word_file_prefix) === 0;
            }
        );
        if (count($files) === 0) {
            $args = ['Word file missing in '.$this->getWordDirectory()];
            throw new NException(Error::FILE_SYSTEM, $args);
        }

        return $files;
    }

    public function getWordDirectory(): string
    {
        $dir = defined('BASE_DIR') ? BASE_DIR : '';
        $dir .= '/' . $this->word_folder;

        return $dir;
    }


}
