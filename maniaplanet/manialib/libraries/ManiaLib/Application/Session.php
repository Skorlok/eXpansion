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

/**
 * Session handling for humans
 * 
 * @method \ManiaLib\Application\Session getInstance()
 */
class Session extends \ManiaLib\Utils\Singleton
{
	const NAME = 'manialib-sid';

	static $id;
	protected static $started = false;
	public $login;
	public $nickname;
	public $lang;
	public $path;

	protected function __construct()
	{
		if(self::$started)
		{
			return;
		}

		session_name(self::NAME);
		if(self::$id)
		{
			session_id(self::$id);
		}
		session_start();
		self::$started = true;

		$keys = array('login', 'nickname', 'lang', 'path');
		$session = $this;
		array_walk($keys,
			function ($value) use ($session)
			{
				if(isset($_SESSION[$value]))
				{
					$session->$value = & $_SESSION[$value];
				}
				else
				{
					$_SESSION[$value] = & $session->$value;
				}
			});
	}

	/**
	 * Sets a session var
	 * @param string
	 * @param mixed
	 */
	function set($name, $value = null)
	{
		$_SESSION[$name] = $value;
	}

	/**
	 * Deletes a session var
	 * @param string
	 */
	function delete($name)
	{
		unset($_SESSION[$name]);
	}

	/**
	 * Gets a session var, or the default value if nothing was found
	 * @param string The name of the variable
	 * @param mixed The default value
	 * @return mixed
	 */
	function get($name, $default = null)
	{
		return array_key_exists($name, $_SESSION) ? $_SESSION[$name] : $default;
	}

	/**
	 * Gets a session var, throws an exception if not found
	 * @param string The name of the variable
	 * @return mixed
	 */
	function getStrict($name)
	{
		if(array_key_exists($name, $_SESSION))
		{
			return $_SESSION[$name];
		}
		throw new Exception('Session variable "'.$name.'" not found');
	}

	/**
	 * Checks if the specified session var exists
	 * @return boolean
	 */
	function exists($name)
	{
		return array_key_exists($name, $_SESSION);
	}

}

?>