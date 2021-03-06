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

    private function getWordArray(): array
    {
        if(empty($this->words)){
            $this->setWordArrayFromFile();
        }

        return $this->words;
    }

    private function setWordArrayFromFile(): void
    {
        $encrypted_word_string = file_get_contents($this->getWordFilePath());
        $encrypted_words = Crypto::decrypt($encrypted_word_string, $this->getEncryptionKey());
        $this->words = explode(self::PW_DELIMITER, $encrypted_words);

        $this->nr_words = count($this->words);

        return;
    }

    public function getWordsForId(string $id_string): array
    {
        $words = [];
        $possible_words = $this->getWordArray();

        $hash = md5($id_string);
        mt_srand(hexdec(substr($hash,0,8))); // seeding the random generator

        foreach(range(1, self::NR_OF_WORDS_IN_PW) as $i){
            $word_index = mt_rand(0, $this->nr_words - 1);
            $words[] = $possible_words[$word_index];
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
