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

abstract class Maniacode
{

	public static $domDocument;
	public static $parentNodes;

	/**
	 * Loads the Maniacode GUI Toolkit. This should be called before doing anything with the toolkit
	 *
	 * @param bool True if you don't want to see a message at the end of the execution of the maniacode
	 * @param bool Wheter you want to create the root "<maniacode>" element in the XML
	 */
	final public static function load($noconfirmation = false,
		$createManialinkElement = true)
	{
		self::$domDocument = new \DOMDocument('1.0', 'utf8');
		self::$parentNodes = array();

		if($createManialinkElement)
		{
			$maniacode = self::$domDocument->createElement('maniacode');
			if($noconfirmation)
				$maniacode->setAttribute('noconfirmation', $noconfirmation);
			self::$domDocument->appendChild($maniacode);
			self::$parentNodes[] = $maniacode;
		}
	}

	/**
	 * Renders the Maniacode if no return the script will be stopped
	 * @param bool Whether you want to return the XML instead of printing it
	 * @return mixed The XML string if param true, in other case it returns void
	 */
	final public static function render($return = false)
	{
		if($return)
		{
			return self::$domDocument->saveXML();
		}
		else
		{
			header('Content-Type: text/xml; charset=utf-8');
			echo self::$domDocument->saveXML();
			exit();
		}
	}

	/**
	 * Append some XML code to the document
	 * @param string $XML The given XML
	 */
	static function appendXML($XML)
	{
		$doc = new \DOMDocument('1.0', 'utf8');
		$doc->loadXML($XML);
		$node = self::$domDocument->importNode($doc->firstChild, true);
		end(self::$parentNodes)->appendChild($node);
	}

}

?>