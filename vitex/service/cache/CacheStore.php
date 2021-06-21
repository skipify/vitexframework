<?php
/**
 * vitex 缓存service
 */

namespace vitex\service\cache;

/**
 * 缓存的store类型 doctrine 支持的驱动
 * @package vitex\service\cache
 */
class CacheStore
{
    const APCU = "Apcu";

    const ARR = "Array";

    const MONGODB = "MongoDB";

    const FILE = "FileSystem";

    const PHP_FILE = "PhpFile";

    const MEMCACHED = "Memcached";

    const PREDIS = "Predis";

    const REDIS = "Redis";

    const SQLLITE3 = "SQLite3";

    const VOID = "Void";

    const WIN_CACHE = "WinCache";

    const ZEND_DATA = "ZendData";
}