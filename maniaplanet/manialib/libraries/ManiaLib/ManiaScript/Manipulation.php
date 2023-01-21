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

use ManiaLib\Gui\Manialink;

/**
 * @see http://code.google.com/p/manialib/source/browse/trunk/media/maniascript/manialib.xml
 */
abstract class Manipulation
{

	static function hide($controlId)
	{
		Manialink::appendScript(self::getHide($controlId));
	}

	static function getHide($controlId)
	{
		$script = 'manialib_hide("%s"); ';
		$controlId = Tools::escapeString($controlId);
		$script = sprintf($script, $controlId);
		return $script;
	}

	static function show($controlId)
	{
		Manialink::appendScript(self::getShow($controlId));
	}

	static function getShow($controlId)
	{
		$script = 'manialib_show("%s"); ';
		$controlId = Tools::escapeString($controlId);
		$script = sprintf($script, $controlId);
		return $script;
	}

	static function toggle($controlId)
	{
		Manialink::appendScript(self::getToggle($controlId));
	}

	static function getToggle($controlId)
	{
		$script = 'manialib_toggle("%s"); ';
		$controlId = Tools::escapeString($controlId);
		$script = sprintf($script, $controlId);
		return $script;
	}

	static function absolutePosx($controlId, $posx)
	{
		Manialink::appendScript(static::getAbsolutePosx($controlId, $posx));
	}

	static function getAbsolutePosx($controlId, $posx)
	{
		$script = 'manialib_absolute_posx("%s", %f); ';
		$controlId = Tools::escapeString($controlId);
		return sprintf($script, $controlId, $posx);
	}

	static function absolutePosy($controlId, $posy)
	{
		Manialink::appendScript(static::getAbsolutePosy($controlId, $posy));
	}

	static function getAbsolutePosy($controlId, $posy)
	{
		$script = 'manialib_absolute_posy("%s", %f); ';
		$controlId = Tools::escapeString($controlId);
		return sprintf($script, $controlId, $posy);
	}

	static function absolutePosz($controlId, $posz)
	{
		Manialink::appendScript(static::getAbsolutePosz($controlId, $posz));
	}

	static function getAbsolutePosz($controlId, $posz)
	{
		$script = 'manialib_absolute_posz("%s", %f); ';
		$controlId = Tools::escapeString($controlId);
		return sprintf($script, $controlId, $posz);
	}

	static function posx($controlId, $posx)
	{
		Manialink::appendScript(static::getPosx($controlId, $posx));
	}

	static function getPosx($controlId, $posx)
	{
		$script = 'manialib_posx("%s", %f); ';
		$controlId = Tools::escapeString($controlId);
		return sprintf($script, $controlId, $posx);
	}

	static function posy($controlId, $posy)
	{
		Manialink::appendScript(static::getPosy($controlId, $posy));
	}

	static function getPosy($controlId, $posy)
	{
		$script = 'manialib_posy("%s", %f); ';
		$controlId = Tools::escapeString($controlId);
		return sprintf($script, $controlId, $posy);
	}

	static function posz($controlId, $posz)
	{
		Manialink::appendScript(static::getPosz($controlId, $posz));
	}

	static function getPosz($controlId, $posz)
	{
		$script = 'manialib_posz("%s", %f); ';
		$controlId = Tools::escapeString($controlId);
		return sprintf($script, $controlId, $posz);
	}

	static function setText($controlId, $text)
	{
		Manialink::appendScript(static::getSetText($controlId, $text));
	}

	static function getSetText($controlId, $text)
	{
		$script = 'manialib_set_text("%s", "%s"); ';
		$controlId = Tools::escapeString($controlId);
		$text = Tools::escapeString($text);
		return sprintf($script, $controlId, $text);
	}

	static function setTextColor($controlId, $textColor)
	{
		Manialink::appendScript(static::getSetTextColor($controlId, $textColor));
	}

	static function getSetTextColor($controlId, $textColor)
	{
		$script = 'manialib_set_textcolor("%s", "%s"); ';
		$controlId = Tools::escapeString($controlId);
		$textColor = Tools::escapeString($textColor);
		return sprintf($script, $controlId, $textColor);
	}

	static function setColorize($controlId, $colorize)
	{
		return static::setColorize($controlId, $colorize);
	}

	static function getSetColorize($controlId, $colorize)
	{
		$script = 'manialib_set_colorize("%s", "%s"); ';
		$controlId = Tools::escapeString($controlId);
		$colorize = Tools::escapeString($colorize);
		return sprintf($script, $controlId, $colorize);
	}

	static function setModulateColor($controlId, $modulateColor)
	{
		return static::getSetModulateColor($controlId, $modulateColor);
	}

	static function getSetModulateColor($controlId, $modulateColor)
	{
		$script = 'manialib_set_modulateColor("%s", "%s"); ';
		$controlId = Tools::escapeString($controlId);
		$modulateColor = Tools::escapeString($modulateColor);
		return sprintf($script, $controlId, $modulateColor);
	}

	static function setEntryValue($controlId, $value)
	{
		Manialink::appendScript(static::getSetEntryValue($controlId, $value));
	}

	static function getSetEntryValue($controlId, $value)
	{
		$script = 'manialib_set_entry_value("%s", "%s"); ';
		$controlId = Tools::escapeString($controlId);
		$value = Tools::escapeString($value);
		return sprintf($script, $controlId, $value);
	}

	static function setImage($controlId, $URL)
	{
		Manialink::appendScript(static::getSetImage($controlId, $URL));
	}

	static function getSetImage($controlId, $URL)
	{
		$script = 'manialib_set_image("%s", "%s"); ';
		$controlId = Tools::escapeString($controlId);
		$URL = Tools::escapeString($URL);
		return sprintf($script, $controlId, $URL);
	}

	static function setImagefocus($controlId, $URL)
	{
		Manialink::appendScript(static::getSetImagefocus($controlId, $URL));
	}

	static function getSetImagefocus($controlId, $URL)
	{
		$script = 'manialib_set_imagefocus("%s", "%s"); ';
		$controlId = Tools::escapeString($controlId);
		$URL = Tools::escapeString($URL);
		return sprintf($script, $controlId, $URL);
	}

	static function setOpacity($controlId, $opacity)
	{
		Manialink::appendScript(static::getSetOpacity($controlId, $opacity));
	}

	static function getSetOpacity($controlId, $opacity)
	{
		$script = 'manialib_set_opacity("%s", %f); ';
		$controlId = Tools::escapeString($controlId);
		return sprintf($script, $controlId, $opacity);
	}

	static function setStyle($controlId, $style)
	{
		Manialink::appendScript(static::getSetStyle($controlId, $style));
	}

	static function getSetStyle($controlId, $style)
	{
		$script = 'manialib_set_style("%s", "%s"); ';
		$controlId = Tools::escapeString($controlId);
		$style = Tools::escapeString($style);
		return sprintf($script, $controlId, $style);
	}

	static function setSubstyle($controlId, $substyle)
	{
		Manialink::appendScript(static::getSetSubstyle($controlId, $substyle));
	}

	static function getSetSubstyle($controlId, $substyle)
	{
		$script = 'manialib_set_substyle("%s", "%s"); ';
		$controlId = Tools::escapeString($controlId);
		$substyle = Tools::escapeString($substyle);
		return sprintf($script, $controlId, $substyle);
	}

	static function disableLinks()
	{
		Manialink::appendScript(static::getDisableLinks());
	}

	static function getDisableLinks()
	{
		return 'manialib_disable_links(); ';
	}

	static function enableLinks()
	{
		Manialink::appendScript(static::getEnableLinks());
	}

	static function getEnableLinks()
	{
		return 'manialib_enable_links(); ';
	}

}
