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

use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLib\Gui\Elements\Icons64x64_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Layouts\Column;

/**
 * Navigation menu
 * Looks like the navigation menu on the left in the game menus
 */
class Menu extends Bgs1
{
	const BUTTONS_TOP = true;
	const BUTTONS_BOTTOM = false;

	/**
	 * @var Label
	 */
	public $title;

	/**
	 * @var Label
	 */
	public $subTitle;

	/**
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	public $titleBg;

	/**
	 * @var \ManiaLib\Gui\Elements\Quad
	 */
	public $logo;

	/**
	 * @var \ManiaLib\Gui\Cards\Navigation\Button
	 */
	public $quitButton;

	/**
	 * @var \ManiaLib\Gui\Cards\Navigation\Button
	 */
	public $lastItem;
	protected $showQuitButton = true;
	protected $items = array();
	protected $bottomItems = array();
	protected $marginHeight = 1;
	protected $yIndex = -10;

	function __construct()
	{
		parent::__construct(70, 180);

		$this->setSubStyle(Bgs1::BgWindow1);
		$this->setPosition(-150, 90, 0.1);

		$this->titleBg = new Quad(70, 70);
		$this->titleBg->setImage(Config::getInstance()->titleBgURL, true);
		$this->addCardElement($this->titleBg);

		$this->logo = new Icons128x128_1(16);
		$this->logo->setPosition(4, -38, 0.1);
		$this->logo->setSubStyle(Icons128x128_1::Vehicles);
		$this->addCardElement($this->logo);

		$this->title = new Label(46);
		$this->title->setPosition(22, -41, 0.1);
		$this->title->setStyle(Label::TextTitle1);
		$this->title->setScriptEvents();
		$this->addCardElement($this->title);

		$this->subTitle = new Label(46);
		$this->subTitle->setPosition(22, -47.75, 0.1);
		$this->subTitle->setStyle(Label::TextSubTitle1);
		$this->addCardElement($this->subTitle);

		$this->quitButton = new Button();
		$this->quitButton->setPosition(-1, -163.5, 0.1);
		$this->quitButton->text->setText('Back');
		$this->quitButton->text->setStyle(Label::TextButtonNavBack);
		$this->quitButton->icon->setPosition(-8.5, -0.5, 0.1);
		$this->quitButton->icon->setStyle(Quad::Icons128x128_1);
		$this->quitButton->icon->setSubStyle(Icons128x128_1::BackFocusable);
		$this->quitButton->icon->setSize(11, 11);
	}

	/**
	 * Adds a navigation button to the menu
	 */
	function addItem($topItem = self::BUTTONS_TOP)
	{
		$item = new Button();
		$item->setSubStyle(Bgs1::BgEmpty);
		if($topItem == self::BUTTONS_TOP)
		{
			$this->items[] = $item;
		}
		else
		{
			$this->bottomItems[] = $item;
		}
		$this->lastItem = $item;
	}

	/**
	 * Adds a vertical gap before the next item
	 * @param float
	 */
	function addGap($gap = 4)
	{
		$item = new \ManiaLib\Gui\Elements\Spacer(1, $gap);
		$this->items[] = $item;
	}

	/**
	 * Hides the quit/back button
	 */
	function hideQuitButton()
	{
		$this->showQuitButton = false;
	}

	protected function preFilter()
	{
		$this->subTitle->setText('$o$999'.$this->subTitle->getText());
		if($this->showQuitButton)
		{
			$this->quitButton->text->setText('$09f'.$this->quitButton->text->getText());
			$this->quitButton->text->setPosX($this->quitButton->text->getPosX() - 1);
			$this->addCardElement($this->quitButton);
		}
	}

	protected function postFilter()
	{
		Manialink::beginFrame($this->posX, $this->posY, $this->posZ + 0.1);
		{
			if($this->items)
			{
				$layout = new Column();
				$layout->setMarginHeight(5);
				Manialink::beginFrame(0, -62, 0, 1, $layout);
				foreach($this->items as $item)
				{
					$item->save();
				}
				Manialink::endFrame();
			}
			if($this->bottomItems)
			{
				$this->bottomItems = array_reverse($this->bottomItems);

				$layout = new Column();
				$layout->setDirection(Column::DIRECTION_UP);
				$layout->setMarginHeight(5);
				Manialink::beginFrame(0, -160, 0, 1, $layout);
				foreach($this->bottomItems as $item)
				{
					$item->save();
				}
				Manialink::endFrame();
			}
		}
		Manialink::endFrame();
	}

}

?>