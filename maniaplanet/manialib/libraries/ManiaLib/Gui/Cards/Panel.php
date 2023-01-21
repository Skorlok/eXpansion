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

namespace ManiaLib\Gui\Cards;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Quad;

class Panel extends Quad
{

	/**
	 * @var \ManiaLib\Gui\Elements\Label
	 */
	public $title;

	/**
	 * Title background
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	public $titleBg;

	/**
	 * Panel background
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	protected $panelBg;

	function __construct($sx=187.5, $sy=200)
	{
		parent::__construct($sx, $sy);

		$this->setStyle(Quad::Bgs1);
		$this->setSubStyle(Bgs1::BgWindow2);

		$this->cardElementsHalign = 'center';

		$this->titleBg = new Quad(0, 16.25);
		$this->titleBg->setHalign('center');
		$this->titleBg->setStyle(Quad::Bgs1InRace);
		$this->titleBg->setSubStyle(Bgs1InRace::BgTitle3_1);
		$this->addCardElement($this->titleBg);

		$this->title = new Label();
		$this->title->setAlign('center', 'center2');
		$this->title->setPositionY(-8.25);
		$this->title->setStyle(Label::TextTitle3);
		$this->addCardElement($this->title);

		$this->panelBg = new Quad();
		$this->panelBg->setHalign('center');
		$this->addCardElement($this->panelBg);
	}

	function preFilter()
	{
		$this->panelBg->setStyle($this->getStyle());
		$this->panelBg->setSubStyle($this->getSubStyle());
		$this->setStyle(null);
		$this->setSubStyle(null);

		$this->titleBg->setSizeX($this->sizeX);
		$this->title->setSizeX($this->sizeX - 6);
		$this->panelBg->setSize($this->sizeX - 4,
			$this->sizeY - $this->titleBg->getSizeY() - 4.5);
		$this->panelBg->setPosY(-$this->titleBg->getSizeY() - 4.5);
	}

}

?>