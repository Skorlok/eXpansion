<?php
/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 * 
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Gui\Elements;

use ManiaLib\Gui\Manialink;

/**
 * Can be used to add already parsed xml to
 * a manialink window.
 * 
 * @author Florian Schnell
 */
class Xml extends \ManiaLive\Gui\Element
{
	protected $xml;
	
	function __construct($xml = '')
	{
		$this->xml = new \DOMDocument();
		if ($xml) {
			$this->setContent($xml);
		}
	}
	
	function setContent($xml)
	{
		if (!$this->xml->loadXML($xml)) {
			throw new \Exception("Error parsing xml: \n" . $xml, 2);
		}
	}

	function getContent()
	{
		return $this->xml;
	}
	
	function save()
	{
		if ($this->xml->firstChild) {
			$node = Manialink::$domDocument->importNode($this->xml->firstChild, true);
			end(Manialink::$parentNodes)->appendChild($node);
		}
	}
}

?>