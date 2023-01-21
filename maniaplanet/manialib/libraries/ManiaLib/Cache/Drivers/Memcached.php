<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 *
 * @see         http://code.google.com/p/manialib/
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Cache\Drivers;

/**
 * Memcache driver based on the PECL\Memcache extension
 * @see http://www.php.net/manual/en/book.memcached.php
 */
class Memcached extends \ManiaLib\Utils\Singleton implements \ManiaLib\Cache\CacheInterface
{

	/**
	 * @var \Memcached
	 */
	protected $memcached;

	protected function __construct()
	{
		if(!class_exists('Memcached'))
		{
			throw new Exception('PECL\Memcached extension not found');
		}
		$this->memcached = new \Memcached();

		$config = MemcacheConfig::getInstance();
		foreach($config->hosts as $host)
		{
			$this->memcached->addServer($host);
		}
	}

	/**
	 * @deprecated
	 */
	function exists($key)
	{
		return!($this->fetch($key) === false);
	}

	function fetch($key)
	{
		$key = str_replace('\\', '/', $key);
		return $this->memcached->get($key);
	}

	function add($key, $value, $ttl=0)
	{
		$key = str_replace('\\', '/', $key);
		if(!$this->memcached->add($key, $value, $ttl))
		{
			$message = sprintf('Memcache::set() with key "%s" failed', $key);
			\ManiaLib\Utils\Logger::error($message);
		}
	}

	function replace($key, $value, $ttl=0)
	{
		$key = str_replace('\\', '/', $key);
		if(!$this->memcached->replace($key, $value, $ttl))
		{
			$message = sprintf('Memcache::replace() with key "%s" failed', $key);
			\ManiaLib\Utils\Logger::error($message);
		}
	}

	function delete($key)
	{
		$key = str_replace('\\', '/', $key);
		if(!$this->memcached->delete($key))
		{
			$message = sprintf('Memcache::delete() with key "%s" failed', $key);
			\ManiaLib\Utils\Logger::error($message);
		}
	}

	function inc($key)
	{
		$key = str_replace('\\', '/', $key);
		if(!$this->memcached->increment($key))
		{
			$message = sprintf('Memcache::increment() with key "%s" failed', $key);
			\ManiaLib\Utils\Logger::error($message);
		}
	}

}

?>