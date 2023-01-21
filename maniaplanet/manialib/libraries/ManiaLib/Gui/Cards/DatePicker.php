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

use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Elements\Entry;
use ManiaLib\Gui\Elements\Button;

class DatePicker extends \ManiaLib\Gui\Component
{
	/**
	 * @var \ManiaLib\Gui\Elements\Entry
	 */
	public $entry;

	/**
	 * @var \ManiaLib\Gui\Button
	 */
	public $button;

	function __construct ()
	{
		$this->entry = new Entry();
		$this->entry->setId('datepicker-result');
		$this->entry->setScriptEvents();
		$this->entry->setVAlign('center');
		$this->entry->setPosition(0, -4);

		$this->button = new Button();
		$this->button->setId('datepicker-button');
		$this->button->setScriptEvents();
		$this->button->setText('Select');
		$this->button->setPosition(25);
	}

	function save()
	{
		$this->entry->save();
		$this->button->save();

		Manialink::appendScript('manialib_ui_datepicker_init("'.$this->entry->getId().'", "'.$this->button->getId().'");');
	}
}
?>