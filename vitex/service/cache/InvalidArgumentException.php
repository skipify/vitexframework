<?php
/**
 * vitex 缓存service
 */

namespace vitex\service\cache;

/**
 * 缓存异常
 * @package vitex\service\cache
 */

class InvalidArgumentException extends CacheException implements \Psr\SimpleCache\InvalidArgumentException
{

}