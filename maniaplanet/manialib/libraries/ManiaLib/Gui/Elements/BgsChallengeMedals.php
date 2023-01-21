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

class BgsChallengeMedals extends Quad
{

	protected $style = Quad::BgsChallengeMedals;
	protected $subStyle = self::BgBronze;

	const BgBronze = 'BgBronze';
	const BgGold = 'BgGold';
	const BgNadeo = 'BgNadeo';
	const BgNotPlayed = 'BgNotPlayed';
	const BgPlayed = 'BgPlayed';
	const BgSilver = 'BgSilver';
}

?>