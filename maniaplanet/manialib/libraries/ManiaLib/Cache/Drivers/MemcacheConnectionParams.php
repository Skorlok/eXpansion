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
class MemcacheConnectionParams
{
	public $hosts = array();
	public $id;
}

?>
