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

namespace ManiaLib\Gui\Cards\Navigation;

/**
 * @method \ManiaLib\Gui\Cards\Navigation\Config getInstance()
 */
class Config extends \ManiaLib\Utils\Singleton
{

	/**
	 * @var string URL of the image for header of the menu. Image ratio is 1:1
	 */
	public $titleBgURL;

}

?>