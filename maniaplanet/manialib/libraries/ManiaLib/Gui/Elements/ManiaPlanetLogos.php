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

class ManiaPlanetLogos extends Quad
{
	const IconPlanets = 'IconPlanets';
	const IconPlanetsPerspective = 'IconPlanetsPerspective';
	const IconPlanetsSmall = 'IconPlanetsSmall';
	const ManiaPlanetLogoBlack = 'ManiaPlanetLogoBlack';
	const ManiaPlanetLogoBlackSmall = 'ManiaPlanetLogoBlackSmall';
	const ManiaPlanetLogoWhite = 'ManiaPlanetLogoWhite';
	const ManiaPlanetLogoWhiteSmall = 'ManiaPlanetLogoWhiteSmall';

	protected $style = Quad::ManiaPlanetLogos;
	protected $subStyle = self::IconPlanets;

}

?>