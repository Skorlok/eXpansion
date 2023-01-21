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

abstract class FileDownload extends \ManiaLib\Gui\Maniacode\Component
{

	protected $url;

	function __construct($name = '', $url = '')
	{
		$this->name = $name;
		$this->url = $url;
	}

	/**
	 * This method sets the url to download the file
	 *
	 * @param string $url The url to download the file
	 * @return void
	 *
	 */
	function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 * This method gets the Url of the element
	 *
	 * @return void
	 *
	 */
	function getUrl()
	{
		return $this->url;
	}

	protected function postFilter()
	{
		if(isset($this->url))
		{
			$elem = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement('url');
			$value = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createTextNode($this->url);
			$elem->appendChild($value);
			$this->xml->appendChild($elem);
		}
	}

}

?>