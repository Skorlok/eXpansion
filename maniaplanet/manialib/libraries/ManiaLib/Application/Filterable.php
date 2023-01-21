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
 * Filters allows you to execute code before and after every actions of a 
 * controller. It is usefull for things like authentication, etc.
 * 
 * @see \ManiaLib\Application\Controller::addFilter()
 */
interface Filterable
{

	public function preFilter();

	public function postFilter();
}

?>