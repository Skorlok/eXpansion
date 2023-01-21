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

namespace ManiaLib\Application\Rendering;

use ManiaLib\Application\ActionNotFoundException;
use ManiaLib\Application\ControllerNotFoundException;

class SimpleTemplates implements RendererInterface
{

	static function exists($viewName)
	{
		$viewName = str_replace('\\', DIRECTORY_SEPARATOR, $viewName);
		return file_exists(MANIALIB_APP_PATH.'resources/'.$viewName.'.php') || file_exists(MANIALIB_APP_PATH.'ressources/'.$viewName.'.php');
	}

	static function render($viewName)
	{
		$viewName = str_replace('\\', DIRECTORY_SEPARATOR, $viewName);
		if(!self::exists($viewName))
		{
			throw new ViewNotFoundException('View not found: '.$viewName);
		}


		// Add some useful vars to the response
		$config = \ManiaLib\Application\Config::getInstance();
		$session = \ManiaLib\Application\Session::getInstance();
		$response = \ManiaLib\Application\Response::getInstance();
		$tracking = \ManiaLib\Application\Tracking\Config::getInstance();

		$response->login = $session->login;
		$response->mediaURL = $config->getMediaURL();
		$response->appURL = $config->getLinkCreationURL();
		$response->baseURL = $config->URL;
		$response->trackingAccount = $tracking->account;

		$vars = \ManiaLib\Application\Response::getInstance()->getAll();
		extract($vars);

		error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

		if(file_exists(MANIALIB_APP_PATH.'resources/'.$viewName.'.php'))
		{
			require MANIALIB_APP_PATH.'resources/'.$viewName.'.php';
		}
		else
		{
			require MANIALIB_APP_PATH.'ressources/'.$viewName.'.php';
		}

		error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
	}

	static function redirect($URL)
	{
		header('Location: '.$URL);
		exit;
	}

	static function header()
	{
		header('Content-Type: text/html; charset=UTF-8');
	}

	static function registerException(\Exception $e)
	{
		if($e instanceof ControllerNotFoundException || $e instanceof ActionNotFoundException || $e instanceof ViewNotFoundException)
		{
			header('HTTP/1.0 404 Not Found');
		}
	}

}

?>