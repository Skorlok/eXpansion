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
 * @method \ManiaLib\Application\Dispatcher getInstance()
 */
class Dispatcher extends \ManiaLib\Utils\Singleton
{
	const PATH_INFO_OVERRIDE_PARAM = 'ml-forcepathinfo';

	/**
	 * @var bool
	 */
	protected $running;

	/**
	 * @var string
	 */
	protected $pathInfo;

	/**
	 * @var string
	 */
	protected $controller;

	/**
	 * @var string
	 */
	protected $action;

	/**
	 * @var string
	 */
	protected $calledURL;

	function run()
	{
		if($this->running)
		{
			throw new \Exception(get_called_class().'::run() was previously called!');
		}
		$this->running = true;

		$request = Request::getInstance();
		if($request->exists(self::PATH_INFO_OVERRIDE_PARAM))
		{
			$this->pathInfo = $request->get(self::PATH_INFO_OVERRIDE_PARAM, '/');
			$request->delete(self::PATH_INFO_OVERRIDE_PARAM);
		}
		else
		{
			$this->pathInfo = \ManiaLib\Utils\Arrays::getNotNull($_SERVER, 'PATH_INFO',
					'/');
		}

		list($this->controller, $this->action) = Route::getActionAndControllerFromRoute($this->pathInfo);

		$this->calledURL = $request->createLink();

		$viewsNS = & Config::getInstance()->viewsNS;
		$currentViewsNS = Config::getInstance()->namespace.'\\Views\\';
		if(!in_array($currentViewsNS, $viewsNS))
		{
			array_unshift($viewsNS, $currentViewsNS);
		}

		try
		{
			Controller::factory($this->controller)->launch($this->action);
			Response::getInstance()->render();
		}
		catch(\Exception $e)
		{
			call_user_func(Bootstrapper::$errorHandlingCallback, $e);
			Response::getInstance()->render();
		}
	}

	function getController()
	{
		return $this->controller;
	}

	function getAction($defaultAction = null)
	{
		return $this->action ? $this->action : $defaultAction;
	}

	function getPathInfo()
	{
		return $this->pathInfo;
	}

	function getCalledURL()
	{
		return $this->calledURL;
	}

}

?>