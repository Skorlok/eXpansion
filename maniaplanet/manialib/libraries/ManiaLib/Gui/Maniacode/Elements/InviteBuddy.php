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

class InviteBuddy extends \ManiaLib\Gui\Maniacode\Component
{

	protected $xmlTagName = 'invite_buddy';
	protected $email;

	function __construct($email = '')
	{
		$this->email = $email;
	}

	function setEmail($email)
	{
		$this->email = $email;
	}

	function getEmail()
	{
		return $this->email;
	}

	protected function postFilter()
	{
		if(isset($this->email))
		{
			$elem = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement('email');
			$value = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createTextNode($this->email);
			$elem->appendChild($value);
			$this->xml->appendChild($elem);
		}
	}

}

?>