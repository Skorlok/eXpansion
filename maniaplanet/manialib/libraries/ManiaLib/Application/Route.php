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

abstract class Route
{

	protected static $separator;

	static function separatorToUpperCamelCase($string)
	{
		return implode('',
				array_map('ucfirst', explode(self::getSeparator(), $string)));
	}

	static function separatorToCamelCase($string)
	{
		$string = implode('',
			array_map('ucfirst', explode(self::getSeparator(), $string)));
		$string[0] = strtolower($string[0]);
		return $string;
	}

	static function camelCaseToSeparator($string)
	{
		$patterns = array(
			'/([a-z0-9])([A-Z])/u',
			'/([A-Z])([A-Z])/u'
		);
		$replacements = array(
			'$1-$2',
			'$1-$2',
		);
		return strtolower(preg_replace($patterns, $replacements, $string));
	}

	protected static function getSeparator()
	{
		return '-';
	}

	/**
	 * @param string A route like "/home/index/" or "/home/"
	 * @return array[string] An array of (controller, action)
	 */
	static function getActionAndControllerFromRoute($route)
	{
		$defaultController = Config::getInstance()->defaultController;

		if(substr($route, 0, 1) == '/')
			$route = substr($route, 1);
		if(substr($route, -1, 1) == '/')
			$route = substr($route, 0, -1);
		$route = explode('/', $route, 2);

		$controller = \ManiaLib\Utils\Arrays::getNotNull($route, 0, $defaultController);
		$controller = Route::separatorToUpperCamelCase($controller);

		$action = \ManiaLib\Utils\Arrays::get($route, 1);
		$action = $action ? Route::separatorToCamelCase($action) : null;

		return array($controller, $action);
	}

	static function computeRoute($controller, $action)
	{
		$controller = static::camelCaseToSeparator($controller);
		$action = static::camelCaseToSeparator($action);
		$route = '/';
		if($controller)
		{
			$route .= $controller.'/';
			if($action)
			{
				$route .= $action.'/';
			}
		}
		return $route;
	}

}

?>