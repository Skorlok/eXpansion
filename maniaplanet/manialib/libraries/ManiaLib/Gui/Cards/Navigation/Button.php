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

class Button extends \ManiaLib\Gui\Elements\Bgs1
{

	/**
	 * TrackMania formatting string appended to the text when a button
	 * is selected (default is just a light blue color)
	 */
	static public $unselectedTextStyle = '$fff';

	/**
	 * @var \ManiaLib\Gui\Elements\Label
	 */
	public $text;

	/**
	 * @var \ManiaLib\Gui\Elements\Icon
	 */
	public $icon;
	public $iconSizeMinimizer = 1.5;
	public $textSizeMinimizer = 10;

	/**
	 * @var \ManiaLib\Gui\Elements\Icons64x64_1
	 */
	protected $selectedIcon;
	protected $isSelected = false;
	protected $forceLinks = true;

	function __construct($sizeX = 69, $sizeY = 8.5)
	{
		parent::__construct($sizeX, $sizeY);

		$this->setSubStyle(\ManiaLib\Gui\Elements\Bgs1::BgEmpty);

		$this->cardElementsValign = 'center2';

		$this->text = new \ManiaLib\Gui\Elements\Label(45);
		$this->text->setSizeY(0);
		$this->text->setValign("center");
		$this->text->setPosition(8);
		$this->text->setStyle(\ManiaLib\Gui\Elements\Label::TextButtonNav);
		$this->addCardElement($this->text);

		$this->icon = new \ManiaLib\Gui\Elements\Icons128x128_1($this->sizeY);
		$this->icon->setValign("center");
		$this->icon->setPosition(55, 0, 0.1);
		$this->addCardElement($this->icon);
	}

	/**
	 * Sets the button selected and change its styles accordingly
	 */
	function setSelected()
	{
		$this->isSelected = true;

		$this->selectedIcon = new \ManiaLib\Gui\Elements\Icons64x64_1(11);
		$this->selectedIcon->setSubStyle(\ManiaLib\Gui\Elements\Icons64x64_1::ShowRight);
		$this->selectedIcon->setValign('center');
		$this->selectedIcon->setPosX(71);
		$this->addCardElement($this->selectedIcon);
	}

	protected function preFilter()
	{
		if(!$this->isSelected && $this->text->getText())
		{
			$this->text->setText(self::$unselectedTextStyle.$this->text->getText());
		}

		$this->text->addLink($this);
		$this->icon->addLink($this);
	}

}

?>