<?php


namespace Fridde;


use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;

class CacheFactory
{
    private $base_dir;
    private $environment;
    private $settings;

    private $cache;

    // second parameter describes the key in the ini file
    public static $cache_classes = [
        Essentials::ENV_DEV => [FilesystemCache::class, 'FilesystemCache'],
        Essentials::ENV_TEST => [\Memcache::class, 'Memcache'],
        Essentials::ENV_PROD => [\Memcached::class, 'Memcached'],
    ];

    public static $options_file = '/config/cache_options.ini';

    public function __construct(string $environment, string $dir = '')
    {
        $this->base_dir = $dir;
        $this->environment = $environment;

        $this->settings = $this->readSettings();

        $this->cache = $this->createNewInstance();
    }

    public function getCache(): Cache
    {
        return $this->cache;
    }

    private function readSettings()
    {
        $settings = parse_ini_file($this->base_dir.self::$options_file, true);

        return $settings[self::$cache_classes[$this->environment][1]];
    }

    private function createNewInstance(): Cache
    {
        $class = self::$cache_classes[$this->environment][0];
        $args_from_settings = $this->settings['args'];
        $args = [];

        if ($class === FilesystemCache::class) {
            $args[] = $args_from_settings[0];
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

}
