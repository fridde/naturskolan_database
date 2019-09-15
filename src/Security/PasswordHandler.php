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
    /* @var integer $nr_words  */
    private $nr_words;
    private $settings;
    /* @var string $salt  */
    private $salt;

    private $word_file = 'config/words.txt';

    public const MD5_BASE = 16;

    public const NR_OF_WORDS_IN_PW = 3;
    public const PW_DELIMITER = '.';

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
        $this->salt = $settings['salt'] ?? '';
    }


    public static function createRandomKey(int $length = 32)
    {
        $key = '';
        while(strlen($key) < $length){
            $key .= md5(random_int(0, 999).microtime());
        }

        return substr($key, 0, $length);
    }

    public function getEncryptionKey(): Key
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
        return dirname($this->getWordFilePath()).'/';
    }

    private function setWordArrayFromFile()
    {
        $encrypted_word_string = file_get_contents($this->getWordFilePath());
        $encrypted_words = Crypto::decrypt($encrypted_word_string, $this->getEncryptionKey());
        $this->words = explode(self::PW_DELIMITER, $encrypted_words);

        return $this->words;
    }

    public function getWordsForId(string $id_string): array
    {
        $this->words = $this->words ?? $this->setWordArrayFromFile();

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

    public function calculatePasswordForId(string $id_string): string
    {
        return implode(self::PW_DELIMITER, $this->getWordsForId($id_string));
    }


     public function getWordFilePath(): string
    {
        $path = defined('BASE_DIR') ? BASE_DIR : '';
        $path .= '/' . $this->word_file;

        return $path;
    }


    public function getAllWordFiles(): array
    {
        return [$this->getWordFilePath()];
    }

    public function getSalt(): string
    {
        return $this->settings['salt'];

    }

    public function getCookieValidity(): array
    {
        return $this->settings['cookie_validity'];

    }

}
