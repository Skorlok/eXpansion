<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Application;

use ManiaLib\Utils\Path;
use ManiaLive\Utilities\Console;
use ManiaLive\Utilities\Logger;
use ManiaLivePlugins\eXpansion\Core\Analytics;

abstract class ErrorHandling
{
	static $errorCount = 0;

	/** @var null|Analytics */
	static $errorReporter = null;

	/**
	 * Counts number of errors that have been thrown
	 * and stops the application at a certain amount.
	 */
	public static function increaseErrorCount()
	{
		self::$errorCount++;

		// worst case, the application has reported maximal possible number of errors
		$config = \ManiaLive\Config\Config::getInstance();
		if ($config->maxErrorCount !== false && self::$errorCount > $config->maxErrorCount) {
			self::displayAndLogError(new ErrorLimitReached("Reached error limit of " . self::$errorCount . ". ManiaLive is shutting down"));
			die();
		}
	}

	/**
	 * Takes a php error and converts it into an exception.
	 *
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param integer $errline
	 *
	 * @throws \ErrorException
	 */
	public static function createExceptionFromError($errno, $errstr, $errfile, $errline)
	{
		if (str_contains($errstr, "Creation of dynamic property"))
			return;

		echo "[PHP Warning] $errstr on line $errline in file $errfile", PHP_EOL, PHP_EOL;
		// We don't want to crash ML because of a silly notice or strict error.
		static $ignores = array(2, 8, 32, 512, 1024, 2048);

		if (error_reporting() & $errno) {
			$exception =  new \ErrorException($errstr, $errno, 0, $errfile, $errline);
			if (in_array($errno, $ignores)) {
				// Just log.
				self::logError($exception);
			} else {
				// Propagate the error.
				throw $exception;
			}
		}
	}

	/**
	 * Process an exception and decides what to do with it.
	 *
	 * @param \Exception $e
	 */
	public static function processRuntimeException(\Exception $e)
	{
		self::logError($e);
		self::displayAndLogError($e, "Runtime ");
		self::increaseErrorCount();
	}

	public static function logError(\Exception $e)
	{
		if (!is_null(self::$errorReporter)) {
			self::$errorReporter->ping($e);
		}
	}

	/**
	 * Process an exception and decides what to do with it.
	 *
	 * @param \Exception $e
	 */
	public static function processModuleException(\Exception $e)
	{
		self::logError($e);
		// FatalException will cause program to quit in any case
		// CriticalEventException can be caught by upper module exception handler
		if ($e instanceof FatalException || $e instanceof CriticalEventException)
			throw $e;
		// display message and continue if possible
		else {
			self::displayAndLogError($e, "Module process ");
			self::increaseErrorCount();
		}
	}

	/**
	 * This will stop an event from being processed!
	 *
	 * @param \Exception $e
	 *
	 * @throws \Exception
	 */
	public static function processEventException(\Exception $e)
	{
		if ($e instanceof CriticalEventException) {
			if (!($e instanceof SilentCriticalEventException)) {
				self::logError($e);
				self::displayAndLogError($e, "Event process ");
				self::increaseErrorCount();
			}
		} // anything else, this normally should(!) be a fatalexception ...
		else
			throw $e;
	}

	/**
	 * Writes error message into the standard log file and also
	 * prints it to the console window.
	 *
	 * @param \Exception $e
	 */
	public static function displayAndLogError(\Exception $e, $type = "")
	{
		$log = PHP_EOL . '    Occured on ' . date("d.m.Y") . ' at ' . date("H:i:s") . ' at process with ID #' . getmypid() . PHP_EOL
			. '    ---------------------------------' . PHP_EOL;
		Console::println('');
		foreach (self::computeMessage($e) as $line) {
			$log .= $line . PHP_EOL;
		}
		Console::println("[Error] " . $e->getMessage() . " on line " . $e->getLine() . " in file " . $e->getFile());
		Console::println('');

		Logger::error($log);

		// write into global error log if config says so
		if (\ManiaLive\Config\Config::getInstance()->globalErrorLog)
			error_log($log, 3, Path::getInstance()->getLog(true) . 'GlobalErrorLog.txt');
	}

	/**
	 * Process an exception and decides what to do with it.
	 *
	 * @param \Exception $e
	 */
	public static function processStartupException(\Exception $e)
	{
		$message = PHP_EOL . 'Critical startup error!' . PHP_EOL;
		foreach (self::computeMessage($e) as $line)
			$message .= wordwrap($line, 73, PHP_EOL . '      ', true) . PHP_EOL;
		$message .= PHP_EOL;

		// log and display error, then die!
		error_log($message, 3, Path::getInstance()->getLog(true) . 'ErrorLog_' . getmypid() . '.txt');

		die($message);
	}

	/**
	 * Computes a human readable log message from any exception.
	 */
	static protected function computeMessage(\Exception $e)
	{
		$line = $e->getLine();
		$code = $e->getCode();
		$file = $e->getFile();
		$message = $e->getMessage();
		$trace = $e->getTraceAsString();

		$buffer = array();
		$buffer[] = ' -> ' . get_class($e) . ' with code ' . $code;
		$buffer[] = '    ' . $message;
		$buffer[] = '  - in ' . $file . ' on line ' . $line;
		$buffer[] = '  - Stack: ';

		$lines = explode("\n", $trace);
		foreach ($lines as $i => $line) {
			if ($i == 0)
				$buffer[count($buffer) - 1] .= $line;
			else
				$buffer[] = '           ' . $line;
		}

		return $buffer;
	}
}

class FatalException extends \Exception
{
}

class CriticalEventException extends \Exception
{
}

class SilentCriticalEventException extends CriticalEventException
{
}

class ErrorLimitReached extends \Exception
{
}

?>