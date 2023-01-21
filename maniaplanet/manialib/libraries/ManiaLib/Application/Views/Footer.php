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

namespace ManiaLib\Application\Views;

/**
 * Default footer
 */
class Footer extends \ManiaLib\Application\View
{
	function display()
	{
		\ManiaLib\Gui\Manialink::render();
	}
}

?>