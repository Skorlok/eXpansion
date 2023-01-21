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

abstract class URI
{

	/**
	 * Returns the Current URL.
	 * 
	 * @return string The current URI
	 * @see http://code.google.com/p/oauth2-php
	 * @author Originally written by Naitik Shah <naitik@facebook.com>.
	 * @author Update to draft v10 by Edison Wong <hswong3i@pantarei-design.com>
	 */
	static function getCurrent()
	{
		$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://'
				: 'http://';
		$current_uri = $protocol.$_SERVER['HTTP_HOST'].self::getRequestURI();
		$parts = parse_url($current_uri);

		$query = '';
		if(!empty($parts['query']))
		{
			$params = array();
			parse_str($parts['query'], $params);
			$params = array_filter($params);
			if(!empty($params))
			{
				$query = '?'.http_build_query($params, '', '&');
			}
		}

		// Use port if non default.
		$port = isset($parts['port']) &&
			(($protocol === 'http://' && $parts['port'] !== 80) || ($protocol === 'https://' && $parts['port'] !== 443))
				? ':'.$parts['port'] : '';

		// Rebuild.
		return $protocol.$parts['host'].$port.$parts['path'].$query;
	}

	/**
	 * Since $_SERVER['REQUEST_URI'] is only available on Apache, we
	 * generate an equivalent using other environment variables.
	 * 
	 * @see http://code.google.com/p/oauth2-php
	 * @author Originally written by Naitik Shah <naitik@facebook.com>.
	 * @author Update to draft v10 by Edison Wong <hswong3i@pantarei-design.com>
	 */
	protected static function getRequestURI()
	{
		if(isset($_SERVER['REQUEST_URI']))
		{
			$uri = $_SERVER['REQUEST_URI'];
		}
		else
		{
			if(isset($_SERVER['argv']))
			{
				$uri = $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['argv'][0];
			}
			elseif(isset($_SERVER['QUERY_STRING']))
			{
				$uri = $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];
			}
			else
			{
				$uri = $_SERVER['SCRIPT_NAME'];
			}
		}
		// Prevent multiple slashes to avoid cross site requests
		$uri = '/'.ltrim($uri, '/');

		return $uri;
	}

}

?>