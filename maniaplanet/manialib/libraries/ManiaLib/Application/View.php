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

abstract class View
{

	/**
	 * @var \ManiaLib\Application\Request
	 */
	protected $request;

	/**
	 * @var \ManiaLib\Application\Response
	 */
	protected $response;

	/**
	 * @var \ManiaLib\Application\Session
	 */
	protected $session;

	final function __construct()
	{
		$this->request = Request::getInstance();
		$this->response = Response::getInstance();
		$this->session = Session::getInstance();
		$this->onConstruct();
	}

	final protected function renderSubView($viewName)
	{
		$className = get_class($this);
		$className = explode('\\', $className);
		array_pop($className);
		array_push($className, ucfirst($viewName));
		$className = implode('\\', $className);
		Rendering\Manialink::render($className);
	}

	final protected function render($viewName)
	{
		Rendering\Manialink::render($viewName);
	}

	protected function onConstruct()
	{
		
	}

	abstract function display();
}

?>