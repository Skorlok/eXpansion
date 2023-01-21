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
 * Cache emulation that does nothing, useful for development
 */
class NoCache extends \ManiaLib\Utils\Singleton implements \ManiaLib\Cache\CacheInterface
{

	/**
	 * @deprecated
	 */
	function exists($key)
	{
		return false;
	}

	function fetch($key)
	{
		return false;
	}

	function add($key, $value, $ttl=0)
	{
		
	}

	function replace($key, $value, $ttl=0)
	{
		
	}

	function delete($key)
	{
		
	}

	function inc($key)
	{
		
	}

}

?>