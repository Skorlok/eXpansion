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

namespace ManiaLib\Utils;

/**
 * @method \ManiaLib\Utils\LoggerConfig getInstance()
 */
class LoggerConfig extends \ManiaLib\Utils\Singleton
{

	public $path;
	public $prefix;

	function __construct()
	{
		$this->path = MANIALIB_APP_PATH.'logs/';
	}

}

?>