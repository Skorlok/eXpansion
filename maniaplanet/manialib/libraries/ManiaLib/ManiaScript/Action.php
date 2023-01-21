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
 * ManiaScript Framework Action helper.
 *
 * @see http://code.google.com/p/manialib/source/browse/trunk/media/maniascript/manialib.xml
 */
abstract class Action
{
	const manialink = 'manialink';
	const manialinkid = 'manialinkid';
	const external = 'external';
	const externalid = 'externalid';
	const gotolink = 'goto';
	const gotoid = 'gotoid';
	const hide = 'hide';
	const show = 'show';
	const toggle = 'toggle';
	const posx = 'posx';
	const posy = 'posy';
	const posz = 'posz';
	const scale = 'scale';
	const absolute_posx = 'absolute_posx';
	const absolute_posy = 'absolute_posy';
	const absolute_posz = 'absolute_posz';
	const set_text = 'set_text';
	const set_textcolor = 'set_textcolor';
	const set_entry_value = 'set_entry_value';
	const set_image = 'set_image';
	const set_imagefocus = 'set_imagefocus';
	const set_opacity = 'set_opacity';
	const set_style = 'set_style';
	const set_substyle = 'set_substyle';
	const set_colorize = 'set_colorize';
	const set_modulateColor = 'set_modulateColor';
	const disable_links = 'disable_links';
	const enable_links = 'enable_links';
	const set_clublink = 'set_clublink';
	const browser_back = 'browser_back';
	const browser_quit = 'browser_quit';
	const browser_home = 'browser_home';
	const none = 'none';
}

?>