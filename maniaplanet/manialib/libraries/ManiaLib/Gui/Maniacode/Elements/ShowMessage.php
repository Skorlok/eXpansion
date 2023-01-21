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

class ShowMessage extends \ManiaLib\Gui\Maniacode\Component
{

	protected $xmlTagName = 'show_message';
	protected $message;

	function __construct($message = 'This is a default message provided by Manialib')
	{
		$this->setMessage($message);
	}

	function setMessage($message)
	{
		$this->message = $message;
	}

	function getMessage()
	{
		return $this->message;
	}

	function postFilter()
	{
		if($this->message)
		{
			$elem = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement('message');
			$value = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createTextNode($this->message);
			$elem->appendChild($value);
			$this->xml->appendChild($elem);
		}
	}

}

?>