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
 * Methods to retrieve user-agent related information
 * UA example:  "ManiaPlanet/3.3.0 (Windows; rv: 2013-02-27_11_18; context: browser)"
 */
abstract class UserAgent
{

	const CONTEXT_BROWSER = 'browser';
	const CONTEXT_MENU = 'menu';

	static function get()
	{
		return Arrays::get($_SERVER, 'HTTP_USER_AGENT');
	}

	static function isManiaPlanet()
	{
		$userAgent = self::get();
		$expectedAgent = 'ManiaPlanet';
		$length = strlen($expectedAgent);
		if(strlen($userAgent) < $length || substr($userAgent, 0, $length) != $expectedAgent)
		{
			return false;
		}
		return true;
	}

	static function getContext($default = self::CONTEXT_BROWSER)
	{
		preg_match('/context: ([[:alpha:]]+)/', self::get(), $matches);
		return Arrays::get($matches, 1, $default);
	}

	static function getDistro()
	{
		preg_match('/distro: ([[:alpha:]]+)/', self::get(), $matches);
		return Arrays::get($matches, 1, null);
	}

}

?>