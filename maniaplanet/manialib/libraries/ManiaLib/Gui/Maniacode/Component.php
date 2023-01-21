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

namespace ManiaLib\Gui\Maniacode;

abstract class Component
{

	protected $xmlTagName;
	protected $xml;
	protected $name;

	/**
	 * This method sets the Name of the file once download
	 *
	 * @param string $name The name of the file once download
	 * @return void
	 *
	 */
	function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * This method gets the Name of the element
	 *
	 * @return string This return the name of the file once download
	 *
	 */
	function getName()
	{
		return $this->name;
	}

	protected function preFilter()
	{
		
	}

	protected function postFilter()
	{
		
	}

	final function save()
	{
		$this->preFilter();

		$this->xml = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement($this->xmlTagName);
		end(\ManiaLib\Gui\Maniacode\Maniacode::$parentNodes)->appendChild($this->xml);

		if(isset($this->name))
		{
			$elem = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement('name');
			$value = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createTextNode($this->name);
			$elem->appendChild($value);
			$this->xml->appendChild($elem);
		}

		$this->postFilter();
	}

}

?>