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

namespace ManiaLib\ManiaScript;

/**
 * @see http://code.google.com/p/manialib/source/browse/trunk/media/maniascript/manialib.xml
 * @method \ManiaLib\ManiaScript\Config getInstance()
 */
class Config extends \ManiaLib\Utils\Singleton
{

	/**
	 * Min version to use with the VersionCheck filter
	 * @var string eg. "2011-10-19_11_55" 
	 */
	public $minVersion = '2012-06-13_18_26';

}

?>