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
 * Filterable implentation with access to Session, Request and Response
 * @see \ManiaLib\Application\Filterable
 */
abstract class AdvancedFilter implements \ManiaLib\Application\Filterable
{

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
	 * Call the parent constructor when you override it in your filters!
	 */
	function __construct()
	{
		$this->request = \ManiaLib\Application\Request::getInstance();
		$this->response = \ManiaLib\Application\Response::getInstance();
		$this->session = \ManiaLib\Application\Session::getInstance();
	}

	public function preFilter()
	{
		
	}

	public function postFilter()
	{
		
	}

}

?>