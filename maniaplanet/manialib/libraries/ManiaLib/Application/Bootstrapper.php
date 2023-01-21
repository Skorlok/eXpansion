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

namespace ManiaLib\Application;

abstract class Bootstrapper
{

	static $errorReporting = E_ALL;
	static $errorHandlingClass = '\ManiaLib\Application\ErrorHandling';
	static $errorHandlingCallback = '\ManiaLib\Application\ErrorHandling::exceptionHandler';
	static $errorHandler = 'exceptionErrorHandler';
	static $fatalExceptionHandler = 'fatalExceptionHandler';

	final static function run()
	{
		error_reporting(static::$errorReporting);
		set_error_handler(array(static::$errorHandlingClass, static::$errorHandler));

		try
		{
			static::onDispatch();
		}
		catch(\Exception $exception)
		{
			call_user_func(
				array(
				static::$errorHandlingClass,
				static::$fatalExceptionHandler), $exception);
		}
	}

	static protected function onDispatch()
	{
		\ManiaLib\Application\ConfigLoader::getInstance()->run();
		Dispatcher::getInstance()->run();
	}

}

?>