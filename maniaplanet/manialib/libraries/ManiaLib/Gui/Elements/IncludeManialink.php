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

namespace ManiaLib\Gui\Elements;

class IncludeManialink extends \ManiaLib\Gui\Element
{

	function __construct()
	{
		
	}

	protected $xmlTagName = 'include';
	protected $halign = null;
	protected $valign = null;
	protected $posX = null;
	protected $posY = null;
	protected $posZ = null;

	function setUrl($url, $absoluteUrl = true)
	{
		if(!$absoluteUrl)
		{
			$this->url = \ManiaLib\Gui\Manialink::$mediaURL.'maniascript/'.$url;
		}
		else
		{
			$this->url = $url;
		}
	}

	function postFilter()
	{
		parent::postFilter();

		if($this->url !== null)
			$this->xml->setAttribute('url', $this->url);
	}

}

?>