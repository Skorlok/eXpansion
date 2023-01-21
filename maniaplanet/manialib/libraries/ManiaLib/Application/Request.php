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

use InvalidArgumentException;
use ManiaLib\Utils\Singleton;

/**
 * Handles HTTP requests: retrieves params, creates links and redirections
 * 
 * @method Request getInstance()
 */
class Request extends Singleton
{

	protected $requestParams = array();
	protected $params = array();
	protected $protectedParams = array();
	protected $globalParams = array();
	protected $fragment;
	protected $registerRefererAtDestruct;
	protected $appURL;
	protected $action;
	protected $controller;
	protected $defaultController;
	protected $referer;

	protected function __construct()
	{
		if(array_key_exists(Session::NAME, $_GET))
		{
			Session::$id = $_GET[Session::NAME];
			unset($_GET[Session::NAME]);
		}

		// This is a hack because of a bug on the master server
		// May it cause bugs ? IDK
		foreach($_GET as $key => $value)
			$this->params[str_replace('amp;', '', $key)] = $value;

		if(get_magic_quotes_gpc())
		{
			$this->params = array_map('stripslashes', $this->params);
		}
		$this->requestParams = $this->params;
		$config = Config::getInstance();
		$this->appURL = $config->getLinkCreationURL();
		$this->defaultController = $config->defaultController;

		$session = Session::getInstance();
		$this->referer = $session->get('referer');
		if($this->referer)
		{
			$this->referer = rawurldecode($this->referer);
		}
	}

	function __destruct()
	{
		if($this->registerRefererAtDestruct)
		{
			if(!Response::getInstance()->dialog)
			{
				$session = Session::getInstance();
				$session->set('referer', rawurlencode($this->registerRefererAtDestruct));
			}
		}
	}

	function exists($name)
	{
		return array_key_exists($name, $this->params);
	}
	
	/**
	 * Retriece the fragment
	 * @return mixed
	 */
	function getFragment()
	{
		return $this->fragment;
	}

	/**
	 * Retrieves a GET parameter, or the default value if not found
	 * @param string
	 * @param mixed
	 * @return mixed
	 */
	function get($name, $default = null)
	{
		if(array_key_exists($name, $this->params))
		{
			return $this->params[$name];
		}
		else
		{
			return $default;
		}
	}
	
	/**
	 * Retrieves a POST parameter, or the default value if not found
	 * @param string
	 * @param mixed
	 * @return mixed
	 */
	function getPost($name, $default = null)
	{
		if(array_key_exists($name, $_POST))
		{
			return $_POST[$name];
		}
		else
		{
			return $default;
		}
	}

	function getAll()
	{
		return $this->params;
	}

	/**
	 * Retrieves a GET parameter, or throws an exception if not found or null
	 * @param string
	 * @param string Optional human readable name for error dialog
	 * @return mixed
	 */
	function getStrict($name, $message = null)
	{
		if(array_key_exists($name, $this->params) && $this->params[$name])
		{
			return $this->params[$name];
		}
		elseif($message)
		{
			throw new UserException($message);
		}
		else
		{
			throw new InvalidArgumentException($name);
		}
	}
	
	/**
	 * Retrieves a POST parameter, or throws an exception if not found or null
	 * @param string
	 * @param string Optional human readable name for error dialog
	 * @return mixed
	 */
	function getPostStrict($name, $message = null)
	{
		if(array_key_exists($name, $_POST) && $_POST[$name])
		{
			return $_POST[$name];
		}
		elseif($message)
		{
			throw new UserException($message);
		}
		else
		{
			throw new InvalidArgumentException($name);
		}
	}

	/**
	 * Sets a GET parameter
	 * 
	 * @param string
	 * @param mixed
	 */
	function set($name, $value)
	{
		$this->params[$name] = $value;
	}
	
	function setFragment($value)
	{
		$this->fragment = $value;
	}

	/**
	 * Deletes a GET parameter
	 * 
	 * @param string
	 */
	function delete($name)
	{
		unset($this->params[$name]);
	}
	
	
	function deleteFragment()
	{
		$this->fragment = null;
	}

	/**
	 * Restores a GET parameter to the value it had when the page was loaded
	 * 
	 * @param string
	 */
	function restore($name)
	{
		if(array_key_exists($name, $this->requestParams))
		{
			$this->params[$name] = $this->requestParams[$name];
		}
		else
		{
			$this->delete($name);
		}
	}

	/**
	 * @deprecated
	 */
	public function getAction($defaultAction = null)
	{
		return Dispatcher::getInstance()->getAction($defaultAction);
	}

	/**
	 * @deprecated
	 */
	public function getController()
	{
		return Dispatcher::getInstance()->getController();
	}

	/**
	 * Registers the current page as referer
	 */
	function registerReferer()
	{
		$this->registerRefererAtDestruct = $this->createLink();
	}

	/**
	 * Returns the referer, or the specified default page, or index.php
	 * @param string
	 */
	function getReferer($default = null)
	{
		return $this->referer ? : ($default ? : $this->appURL);
	}

	/**
	 * Redirects to the specified route with all defined GET vars in the URL
	 */
	function redirect($route)
	{
		Response::getInstance()->redirect($this->createLink($route));
	}

	/**
	 * Redirects to the specified route with, with names of GET vars as parameters of the method
	 */
	function redirectArgList($route = '')
	{
		$args = func_get_args();
		array_shift($args);
		$args = $this->filterArgs($args);
		$manialink = $this->createLinkString($route, $args);
		Response::getInstance()->redirect($manialink);
	}

	/**
	 * Creates a Manialink redirection to the specified absolute URI
	 */
	function redirectAbsolute($URL)
	{
		Response::getInstance()->redirect($URL);
	}

	/**
	 * Creates a Manialink redirection to the previously registered referer, or
	 * the index if no referer was previously registered
	 */
	function redirectToReferer()
	{
		Response::getInstance()->redirect($this->getReferer());
	}

	/**
	 * Creates a link to the specified route with all defined GET vars in the URL
	 * @param string Can be the name of a controller or a class const of Route
	 * @param string Can be the name of an action or a class const of Route 
	 */
	public function createLink($route = '')
	{
		return $this->createLinkString($route, $this->params);
	}

	/**
	 * Creates a link to the specified route with, with names of GET vars as parameters of the method
	 */
	function createLinkArgList($route = '')
	{
		$args = func_get_args();
		array_shift($args);
		$args = $this->filterArgs($args);
		return $this->createLinkString($route, $args);
	}

	/**
	 * Returns an URL with the request parameters specified as method arguments
	 * 
	 * @param string The absolute URL
	 * @return string
	 * @deprecated Old code, not sure if it is still used?
	 */
	function createAbsoluteLinkArgList($absoluteLink)
	{
		$args = func_get_args();
		array_shift($args);
		$args = $this->filterArgs($args);
		return $absoluteLink.($args ? '?'.http_build_query($args) : '');
	}

	protected function createLinkString($route = '', $params = array())
	{
		// Current pages
		if(!$route || $route == '.')
		{
			$controller = $this->getController();
			$action = $this->getAction(null);
		}
		// Root
		elseif($route == '/')
		{
			$controller = null;
			$action = null;
		}
		// Controller root
		elseif($route == '..' || $route == '../')
		{
			$controller = $this->getController();
			$action = null;
		}
		else
		{
			// Absolute route
			if(substr($route, 0, 1) == '/')
			{
				list($controller, $action) = Route::getActionAndControllerFromRoute($route);
			}
			// Relative route
			elseif(strlen($route) > 3 && substr($route, 0, 3) == '../')
			{
				$_route = substr($route, 3);
				if(substr($_route, -1, 1) == '/')
				{
					$_route = substr($_route, 0, -1);
				}

				$controller = $this->getController();
				$action = $_route;
			}
			// Syntax error
			else
			{
				throw new Exception('Request link: syntax error');
			}
		}

		$config = Config::getInstance();

		if($controller == $config->defaultController && !$action)
		{
			$controller = null;
		}

		$url = $config->getLinkCreationURL();
		$url .= Route::computeRoute($controller, $action);
		$addSid = $config->sessionUseURL && defined('SID') && SID && !array_key_exists(Session::NAME, $_COOKIE);
		$sid = $addSid ? htmlspecialchars(SID) : '';
		$queryString = http_build_query($params, '', '&');
		return $url.($sid || $queryString ? '?' : '').$sid.($sid ? '&' : '').$queryString.($this->fragment ? '#'.$this->fragment : '');
	}

	/**
	 * @return array
	 */
	protected function filterArgs(array $args)
	{
		$result = array();
		foreach($args as $elt)
		{
			if(array_key_exists($elt, $this->params))
			{
				$result[$elt] = $this->params[$elt];
			}
		}
		return $result;
	}

}

?>