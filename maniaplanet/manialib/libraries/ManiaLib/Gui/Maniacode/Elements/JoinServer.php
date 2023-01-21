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

class JoinServer extends \ManiaLib\Gui\Maniacode\Component
{
	const PLAY = 1;
	const SPEC = 2;
	const REFEREE = 3;

	protected $xmlTagName = 'join_server';
	protected $ip;
	protected $password;
	protected $connectionType;

	function __construct($connectionType = self::PLAY)
	{
		$this->connectionType = $connectionType;
	}

	function setIp($ip)
	{
		$this->ip = $ip;
	}

	function getIp()
	{
		return $this->ip;
	}

	function setPassword($password)
	{
		$this->password = $password;
	}

	function getPassword()
	{
		return $this->password;
	}

	function setConnectionType($connection)
	{
		$this->connectionType = $connection;
	}

	function getConnectionType()
	{
		return $this->connectionType;
	}

	protected function postFilter()
	{
		if(isset($this->ip))
		{
			$elem = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement('ip');
			$value = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createTextNode($this->ip);
			$elem->appendChild($value);
			$this->xml->appendChild($elem);
		}

		if(isset($this->password))
		{
			$elem = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement('password');
			$value = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createTextNode($this->password);
			$elem->appendChild($value);
			$this->xml->appendChild($elem);
		}

		if(isset($this->connectionType))
		{
			$elem = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createElement('connection_type');
			$value = \ManiaLib\Gui\Maniacode\Maniacode::$domDocument->createTextNode($this->connectionType);
			$elem->appendChild($value);
			$this->xml->appendChild($elem);
		}
	}

}

?>