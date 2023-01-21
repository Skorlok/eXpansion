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
 */
abstract class Tools
{

	/**
	 * Returns a escaped and quoted string so you can use it a func param.
	 * Eg. if you pass ubisoft"nadeo il will return "ubisoft\"nadeo"
	 * @return string
	 */
	static function quote($string)
	{
		return sprintf('"%s"', self::escapeString($string));
	}

	static function escapeString($string)
	{
		$string = addcslashes($string, '\"');
		$string = str_replace(array("\n", "\r"), array('\n', ''), $string);
		return $string;
	}

	/**
	 * Converts a PHP array to a Text[] ManiaScript array (note the type!).
	 * Usefull when used with "ManiaScript Framework Actions"
	 */
	static function array2maniascript(array $array, $preserveKeys = false)
	{
		if(!$array)
		{
			// hack because "[]" is not supported and "Text[]" doesnt work yet
			return $preserveKeys ? '[""=>""]' : '[""]';
		}
		foreach($array as $k => $v)
		{
			if(is_array($v))
			{
				if($preserveKeys)
				{
					$array[$k] = sprintf('"%s"=>%s', self::escapeString($k), self::array2maniascript($v, $preserveKeys));
				}
				else
				{
					$array[$k] = self::array2maniascript($v, $preserveKeys);
				}
			}
			elseif($preserveKeys)
			{
				$array[$k] = sprintf('"%s"=>"%s"', self::escapeString($k), self::escapeString($v));
			}
			else
			{
				$array[$k] = sprintf('"%s"', self::escapeString($v));
			}
		}
		return '['.implode(', ', $array).']';
	}

}

?>