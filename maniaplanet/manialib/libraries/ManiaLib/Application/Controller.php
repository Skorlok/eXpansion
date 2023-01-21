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

class Controller
{

	/**
	 * Overrride this to define the controller's default action name
	 * @var string
	 */
	protected $defaultAction;

	/**
	 * Current controller name
	 */
	protected $controllerName;

	/**
	 * Current action name
	 */
	protected $actionName;

	/**
	 * @var array[\ManiaLib\Application\Filterable]
	 */
	protected $filters = array();

	/**
	 * @var array[ReflectionMethod]
	 */
	protected $reflectionMethods = array();

	/**
	 * @var \ManiaLib\Application\Request
	 */
	protected $request;

	/**
	 * @var \ManiaLib\Application\Session
	 */
	protected $session;

	/**
	 * @var \ManiaLib\Application\Response
	 */
	protected $response;

	/**
	 * @return \ManiaLib\Application\Controller
	 */
	final static public function factory($controllerName)
	{
		$controllerClass =
			Config::getInstance()->namespace.'\\'.
			'Controllers\\'.
			$controllerName;
		if(!class_exists($controllerClass))
		{
			throw new ControllerNotFoundException('Controller not found: /'.$controllerName.'/ ; '.\ManiaLib\Utils\Arrays::get($_SERVER, 'HTTP_REFERER'));
		}

		$viewsNS = & Config::getInstance()->viewsNS;
		$currentViewsNS = Config::getInstance()->namespace.'\Views\\';
		if(!in_array($currentViewsNS, $viewsNS))
		{
			array_unshift($viewsNS, $currentViewsNS);
		}

		return new $controllerClass($controllerName);
	}

	final function launch($actionName)
	{
		$actionName = $actionName ? : $this->defaultAction;

		foreach($this->filters as $filter)
		{
			$filter->preFilter();
		}

		$this->executeAction($actionName);

		foreach(array_reverse($this->filters) as $filter)
		{
			$filter->postFilter();
		}
	}

	/**
	 * If you want to do stuff at instanciation, override self::onConstruct()
	 */
	protected function __construct($controllerName)
	{
		$this->controllerName = $controllerName;
		if(!$this->defaultAction)
		{
			$this->defaultAction = Config::getInstance()->defaultAction;
		}
		$this->request = \ManiaLib\Application\Request::getInstance();
		$this->response = \ManiaLib\Application\Response::getInstance();
		$this->session = \ManiaLib\Application\Session::getInstance();
		$this->onConstruct();
	}

	/**
	 * Stuff to be executed when the controller is instanciated; override this in your controllers
	 */
	protected function onConstruct()
	{

	}

	/**
	 * Add a filter to the curent controller
	 * Typically you should call that in your controller's onConstruct() method
	 */
	final protected function addFilter(\ManiaLib\Application\Filterable $filter)
	{
		$this->filters[] = $filter;
	}

	final protected function checkActionExists($actionName)
	{
		if(!array_key_exists($actionName, $this->reflectionMethods))
		{
			try
			{
				$this->reflectionMethods[$actionName] = new \ReflectionMethod(get_class($this), $actionName);
			}
			catch(\Exception $e)
			{
				throw new ActionNotFoundException(
					'Action not found: /'.$this->controllerName.'/'.$actionName.'/ ; '.\ManiaLib\Utils\Arrays::get($_SERVER, 'HTTP_REFERER'));
			}
		}
		if(!$this->reflectionMethods[$actionName]->isPublic())
		{
			throw new ActionNotFoundException(
				'Action not found: /'.$this->controllerName.'/'.$actionName.'/ ; '.\ManiaLib\Utils\Arrays::get($_SERVER, 'HTTP_REFERER'));
		}
		if($this->reflectionMethods[$actionName]->isFinal())
		{
			throw new ActionNotFoundException(
				'Action not found: /'.$this->controllerName.'/'.$actionName.'/ ; '.\ManiaLib\Utils\Arrays::get($_SERVER, 'HTTP_REFERER'));
		}
	}

	protected function executeAction($actionName, $registerView=true,
		$resetViews=false)
	{
		$this->checkActionExists($actionName);

		if($resetViews)
		{
			$this->response->resetViews();
		}

		if($registerView)
		{
			$this->response->registerView($this->response->getViewClassName($this->controllerName,
					$actionName));
		}

		$callParameters = array();
		$requiredParameters = $this->reflectionMethods[$actionName]->getParameters();
		foreach($requiredParameters as $parameter)
		{
			if($parameter->isDefaultValueAvailable())
			{
				$callParameters[] = $this->request->get($parameter->getName(),
					$parameter->getDefaultValue());
			}
			else
			{
				$pname = $parameter->getName();
				$pmessage = 'Undefined parameter: $<$o'.$pname.'$>';
				$callParameters[] = $this->request->getStrict($pname, $pmessage);
			}
		}

		$this->actionName = $actionName;
		call_user_func_array(array($this, $actionName), $callParameters);
	}

}

class ControllerNotFoundException extends UserException
{

}

class ActionNotFoundException extends UserException
{

}

?>