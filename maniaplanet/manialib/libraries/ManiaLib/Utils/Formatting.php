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

namespace ManiaLib\Utils;

/*
 * Info: pattern (?<!\$)((?:\$\$)*) match an even number of $
 */
abstract class Formatting
{
	/**
	 * Removes each single character code in $codes string
	 * Adding l, h or p will also strip links
	 * Adding an hexadecimal char will also strip colors
	 */
	static function stripCodes($string, $codes)
	{
		if(preg_match('/[hlp]/iu', $codes))
			$string = self::stripLinks($string);
		if(preg_match('/[0-9a-f]/iu', $codes))
			$string = self::stripColors($string);
		return preg_replace('/(?<!\$)((?:\$\$)*)\$['.$codes.']/iu', '$1', $string);
	}
	
	/**
	 * Removes wide, bold and shadowed
	 */
	static function stripWideFonts($string)
	{
		return self::stripCodes($string, 'wos');
	}

	/**
	 * Removes links
	 */
	static function stripLinks($string)
	{
		return preg_replace('/(?<!\$)((?:\$\$)*)\$[hlp](?:\[.*?\]|\[.*?$)?(.*?)(?:\$[hlp]|(\$z)|$)/iu', '$1$2$3', $string);
	}

	/**
	 * Removes colors
	 */
	static function stripColors($string)
	{
		return preg_replace('/(?<!\$)((?:\$\$)*)\$(?:g|[0-9a-f][^\$]{0,2})/iu', '$1', $string);
	}

	/**
	 * Removes all styles
	 */
	static function stripStyles($string)
	{
		$string = preg_replace('/(?<!\$)((?:\$\$)*)\$[^$0-9a-hlp]/iu', '$1', $string);
		$string = self::stripLinks($string);
		$string = self::stripColors($string);
		return $string;
	}
	
	static function contrastColors($string, $background)
	{
		$background = Color::StringToRgb24($background);
		return preg_replace_callback('/(?<!\$)((?:\$\$)*)(\$[0-9a-f][^\$]{0,2})/iu',
				function($matches) use ($background)
				{
					$color = Color::StringToRgb24($matches[2]);
					$color = Color::Contrast($color, $background);
					$color = Color::Rgb24ToRgb12($color);
					$color = Color::Rgb12ToString($color);
					return $matches[1].'$'.$color;
				}, $string);
	}
}

?>