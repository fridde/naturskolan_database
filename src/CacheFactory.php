<?php


namespace Fridde;


use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;

class CacheFactory
{
    /* @var string  */
    private $base_dir;
    /* @var string  */
    private $environment;
    /* @var array  */
    private $settings;
    /* @var CacheProvider $cache */
    private $cache;

    // second parameter describes the key in the ini file
    public static $cache_classes = [
        Essentials::ENV_DEV => FilesystemCache::class,
        Essentials::ENV_TEST => \Memcache::class,
        Essentials::ENV_PROD => \Memcached::class,
        //Essentials::ENV_PROD => [FilesystemCache::class, 'FilesystemCache']  //as a backup
    ];

    public static $options_file = '/config/cache_options.ini';

    public static $flush_needed_filename = '/config/.flush_needed';

    public function __construct(string $environment, string $dir = '')
    {
        $this->base_dir = $dir;
        $this->environment = $environment;

        $this->settings = $this->readSettings();
        $this->cache = $this->createNewInstance();
        $this->flushIfNeeded();

    }

    public function getCache(): CacheProvider
    {
        return $this->cache;
    }

    private function readSettings()
    {
        $settings = parse_ini_file($this->base_dir.self::$options_file, true);

        return $settings[$this->environment];
    }

    private function createNewInstance(): CacheProvider
    {
        $class = self::$cache_classes[$this->environment];
        $args_from_settings = $this->settings['args'];
        $args = [];

        if ($class === FilesystemCache::class) {
            $args[] = $this->base_dir.$args_from_settings[0];
        }
        $cache = new $class(...$args);

        if ($cache instanceof \Memcache) {
            $cache->connect(...$args_from_settings);
            $mcc = new MemcacheCache();  // is deprecated, but no alternative for WAMP exists
            $mcc->setMemcache($cache);

            return $mcc;
        }
        if ($cache instanceof \Memcached) {
            $cache->addServer(...$args_from_settings);
            $mcc = new MemcachedCache();
            $mcc->setMemcached($cache);

            return $mcc;
        }
        if ($cache instanceof FilesystemCache) {
            return $cache;
        }
    }

    public function flushIfNeeded(): void
    {
        $file = $this->base_dir . self::$flush_needed_filename;

        if (file_exists($file)) {
            $this->cache->save(Settings::FLUSH_NOW_KEY, true);
            $this->cache->flushAll();
            unlink($file);
        }
    }

}
