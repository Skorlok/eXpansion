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

class Logger
{

	protected static $loaded = false;
	protected static $path;
	protected static $prefix;

	static function info($message, $addDate = true)
	{
		self::log($message, $addDate, 'info.log');
	}

	static function error($message, $addDate = true)
	{
		self::log($message, $addDate, 'error.log');
	}

	static function user($message, $addDate = true)
	{
		self::log($message, $addDate, 'user.log');
	}

	static function log($message, $addDate = true, $logFilename = 'debug.log', $nl="\n")
	{
		if(self::load())
		{
			$message = ($addDate ? date('c').'  ' : '').print_r($message, true).$nl;
			$filename = self::$path.self::$prefix.$logFilename;
			file_put_contents($filename, $message, FILE_APPEND);
		}
	}

	static protected function load()
	{
		if(!self::$loaded)
		{
			$config = LoggerConfig::getInstance();
			if(file_exists($path = $config->path))
			{
				self::$path = $path;
				self::$prefix = $config->prefix ? $config->prefix.'-' : '';
				self::$loaded = true;
			}
		}
		return!empty(self::$path);
	}

}

?>