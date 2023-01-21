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
use ManiaLib\Utils\Logger;
use ManiaLib\Utils\Arrays;
use ManiaLib\Utils\Formatting;
use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Elements\Bgs1;

/**
 * A simple card to display an array of (title, label) inside a Bgs1 quad
 */
class Data extends Bgs1
{

	/**
	 * Array of (title, label)
	 */
	public $data = array();

	protected static function formatLine($t, $l='', $ts = '$o$09f', $ls = '')
	{
		return ($t ? '$<'.$ts.$t.'$<$n $>:$>' : '').($l ? '    '.$ls.$l : '');
	}

	function __construct($sizeX = 70, $sizeY = 0)
	{
		parent::__construct($sizeX, $sizeY);

		$this->setSubStyle(Bgs1::BgWindow2);

		$this->cardElementsPosX = 3;
		$this->cardElementsPosY = -3;
		$this->cardElementsLayout = new Column();
	}

	function addData(array $data)
	{
		$this->data = array_merge($this->data, $data);
	}

	function preFilter()
	{
		foreach($this->data as $data)
		{
			$ui = new Label($this->sizeX - $this->cardElementsPosX * 2, 6);
			$ui->setText(self::formatLine(
					Arrays::get($data, 0, ''), Arrays::get($data, 1, '')
				));
			$this->addCardElement($ui);
		}
		$this->setSizeY($this->sizeY + count($this->data) * 6 + 6);
	}

}

?>