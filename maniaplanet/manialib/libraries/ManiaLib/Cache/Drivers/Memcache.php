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
 * @see http://www.php.net/manual/en/book.memcache.php
 */
class Memcache implements \ManiaLib\Cache\CacheInterface
{
	/**
	 * @var array[\ManiaLib\Database\ConnectionParams]
	 */
	static protected $connections = array();

	/**
	 * @var \Memcache
	 */
	protected $memcache;
	
	static function getInstance()
	{
		if(\array_key_exists('default', static::$connections))
		{
			return static::$connections['default'];
		}

		$config = MemcacheConfig::getInstance();

		$params = new MemcacheConnectionParams();
		$params->id = 'default';
		$params->hosts = $config->hosts ? : array('127.0.0.1');

		return static::factory($params);
	}
	
	static function factory(MemcacheConnectionParams $params)
	{
		if(!$params->id)
		{
			throw new \Exception('MemcacheConnectionParams object has no ID');
		}
		if(!array_key_exists($params->id, static::$connections))
		{
			static::$connections[$params->id] = new static($params);
		}
		return static::$connections[$params->id];
	}

	protected function __construct(MemcacheConnectionParams $params)
	{
		if(!class_exists('Memcache'))
		{
			throw new Exception('PECL\Memcache extension not found');
		}
		$this->memcache = new \Memcache();

		foreach($params->hosts as $host)
		{
			$this->memcache->addServer($host);
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
		return $this->memcache->get($key);
	}

	function add($key, $value, $ttl=0)
	{
		$key = str_replace('\\', '/', $key);
		if(!$this->memcache->add($key, $value, false, $ttl))
		{
			$message = sprintf('Memcache::set() with key "%s" failed', $key);
			\ManiaLib\Utils\Logger::error($message);
		}
	}

	function replace($key, $value, $ttl=0)
	{
		$key = str_replace('\\', '/', $key);
		if(!$this->memcache->replace($key, $value, false, $ttl))
		{
			$message = sprintf('Memcache::replace() with key "%s" failed', $key);
			\ManiaLib\Utils\Logger::error($message);
		}
	}

	function delete($key)
	{
		$key = str_replace('\\', '/', $key);
		if(!$this->memcache->delete($key, 0))
		{
			$message = sprintf('Memcache::delete() with key "%s" failed', $key);
			\ManiaLib\Utils\Logger::error($message);
		}
	}

	function inc($key)
	{
		$key = str_replace('\\', '/', $key);
		if(!$this->memcache->increment($key))
		{
			$message = sprintf('Memcache::increment() with key "%s" failed', $key);
			\ManiaLib\Utils\Logger::error($message);
		}
	}

}

?>