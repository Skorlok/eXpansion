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

namespace ManiaLib\Cache;

const APC = 'apc';
const MEMCACHE = 'memcache';
const MEMCACHED = 'memcached';
const NONE = 'nocache';

/**
 * La classe qu'on a du mal à la trouver
 */
abstract class Cache
{

	/**
	 * Factory for getting instances on cache objects.
	 * You specify the driver to use as a parameter, and
	 * it automatically falls back to the "NoCache" driver
	 * if not found
	 * @return \ManiaLib\Cache\CacheInterface
	 */
	static function factory($driver = null)
	{
		try
		{
			switch($driver)
			{
				case APC: return static::getDriver('APC');
				case MEMCACHED: 
					try
					{
						return static::getDriver('Memcached');
					}
					catch (Exception $e)
					{
						return static::getDriver('Memcache');
					}
				case MEMCACHE: return static::getDriver('Memcache');
				default: throw new Exception();
			}
		}
		catch(Exception $e)
		{
			$config = Config::getInstance();
			$driver = $config->fallbackDriver ? : 'NoCache';
			return static::getDriver($driver);
		}
	}

	/**
	 * Returns a unique prefix to avoid cache collisions
	 * between several applications
	 * @return string
	 */
	static function getPrefix()
	{
		return crc32(__FILE__).'_';
	}

	protected static function getDriver($driver)
	{
		$className = __NAMESPACE__.'\\Drivers\\'.$driver;
		if(!class_exists($className))
		{
			throw new Exception(sprintf('Cache driver %s does not exist', $className));
		}
		return call_user_func(array($className, 'getInstance'));
	}

}

?>