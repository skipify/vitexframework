<?php
/**
 * vitex 缓存service
 */

namespace vitex\service\cache;


use vitex\core\Exception;

/**
 * 缓存异常
 * @package vitex\service\cache
 */
class CacheException extends Exception implements \Psr\SimpleCache\CacheException
{

}