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

namespace ManiaLib\Gui\Elements;

class MedalsBig extends Icon
{
	const MedalBronze = 'MedalBronze';
	const MedalGold = 'MedalGold';
	const MedalGoldPerspective = 'MedalGoldPerspective';
	const MedalNadeo = 'MedalNadeo';
	const MedalNadeoPerspective = 'MedalNadeoPerspective';
	const MedalSilver = 'MedalSilver';
	const MedalSlot = 'MedalSlot';

	protected $style = Quad::MedalsBig;
	protected $subStyle = self::MedalBronze;

}

?>