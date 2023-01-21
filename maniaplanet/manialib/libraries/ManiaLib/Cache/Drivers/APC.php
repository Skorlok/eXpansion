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
 * @see http://php.net/manual/en/book.apc.php
 */
class APC extends \ManiaLib\Utils\Singleton implements \ManiaLib\Cache\CacheInterface
{

	protected function __construct()
	{
		if(!function_exists('apc_add'))
		{
			throw new Exception('APC module is not available');
		}
	}

	/**
	 * @deprecated
	 */
	function exists($key)
	{
		return apc_exists($key);
	}

	function fetch($key)
	{
		return apc_fetch($key);
	}

	function add($key, $value, $ttl=0)
	{
		if(!apc_add($key, $value, $ttl))
		{
			\ManiaLib\Utils\Logger::error('apc_add('.$key.') failed');
		}
	}

	function replace($key, $value, $ttl=0)
	{
		if(!apc_store($key, $value, $ttl))
		{
			\ManiaLib\Utils\Logger::error('apc_store('.$key.') failed');
		}
	}

	function delete($key)
	{
		if(!apc_delete($key))
		{
			\ManiaLib\Utils\Logger::error('apc_delete('.$key.') failed');
		}
	}

	function inc($key)
	{
		apc_inc($key);
	}

}

?>