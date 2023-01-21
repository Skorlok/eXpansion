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

namespace ManiaLib\Gui\Maniacode\Elements;

class GotoLink extends \ManiaLib\Gui\Maniacode\Component
{

	protected $xmlTagName = 'goto';
	protected $link;

	function __construct($link = 'manialib')
	{
		$this->setLink($link);
	}

	function setLink($link)
	{
		$this->link = $link;
	}

	function getLink()
	{
		return $this->link;
	}

	protected function postFilter()
	{
		if(isset($this->link))
		{
			$elem = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement('link');
			$value = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createTextNode($this->link);
			$elem->appendChild($value);
			$this->xml->appendChild($elem);
		}
	}

}

?>