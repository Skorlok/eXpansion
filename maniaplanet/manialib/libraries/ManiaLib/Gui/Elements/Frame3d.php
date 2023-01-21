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

use ManiaLib\Gui\Manialink;

/*
 * Yep it should extend both Frame and Quad but eh!
 */
class Frame3d extends Frame
{
	protected $xmlTagName = 'frame3d';

	const BaseStation = 'BaseStation';
	const BaseBoxCase = 'BaseBoxCase';
	const Titlelogo = 'Titlelogo';
	const ButtonBack = 'ButtonBack';
	const ButtonNav = 'ButtonNav';
	const ButtonH = 'ButtonH';
	const Window = 'Window';
	const Title = 'Title';
	const TitleEditor = 'TitleEditor';
	
	protected $style = \ManiaLib\Gui\Elements\Bgs1::Bgs1;
	protected $subStyle = \ManiaLib\Gui\Elements\Bgs1::BgTitle3_2;
	protected $style3d = self::Window;

	protected $manialink;
	protected $maniazone;
	protected $manialinkId;
	protected $url;
	protected $urlId;
	protected $scriptevents;
	protected $action;
	protected $actionKey;

	function __construct($sizeX, $sizeY)
	{
		parent::__construct($sizeX, $sizeY);
		$this->setLayout(new \ManiaLib\Gui\Layouts\Spacer($sizeX, $sizeY));

		$this->halign = 'left';
		$this->valign = 'top';
	}

	/**
	 * Sets the Manialink of the element. It works as a hyperlink.
	 * @param string Can be either a short Manialink or an URL pointing to a
	 * Manialink
	 */
	function setManialink($manialink)
	{
		$this->manialink = $manialink;
	}
	
	/**
	 * Sets the Manialink of the element. It works as a hyperlink.
	 * @param string Can be either a short Manialink or an URL pointing to a
	 * Manialink
	 */
	function setManiazone($manialink)
	{
		$this->maniazone = $manialink;
	}

	/**
	 * Sets the Manialink id of the element. It works as a hyperlink.
	 * @param string Can be either a short Manialink or an URL pointing to a
	 * Manialink
	 */
	function setManialinkId($manialinkId)
	{
		$this->manialinkId = $manialinkId;
	}

	/**
	 * Sets the hyperlink of the element
	 * @param string An URL
	 */
	function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 * Sets the hyperlink id of the element
	 * @param string An URL
	 */
	function setUrlId($urlId)
	{
		$this->urlId = $urlId;
	}

	function setScriptEvents($scriptEvents = 1)
	{
		$this->scriptevents = $scriptEvents;
	}

	/**
	 * Sets the action of the element. For example, if you use the action "0" in
	 * the explorer, it closes the explorer when you click on the element.
	 * @param int
	 */
	function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * Sets the action key associated to the element. Only works on dedicated
	 * servers.
	 * @param int
	 */
	function setActionKey($actionKey)
	{
		$this->actionKey = $actionKey;
	}
	
	function setStyle($style)
	{
		$this->style = $style;
	}
	
	function setSubStyle($subStyle)
	{
		$this->subStyle = $subStyle;
	}
	
	function setStyle3D($style3D)
	{
		$this->style3d = $style3D;
	}
	
	function buildXML()
	{
		parent::buildXML();
		
		if($this->scriptevents) $this->xml->setAttribute('scriptevents', $this->scriptevents);

		if($this->style !== null) $this->xml->setAttribute('style', $this->style);
		if($this->subStyle !== null) $this->xml->setAttribute('substyle', $this->subStyle);
		if($this->style3d !== null) $this->xml->setAttribute('style3d', $this->style3d);

		if($this->sizeX || $this->sizeY)
		{
			$this->xml->setAttribute('sizen', $this->sizeX.' '.$this->sizeY);
		}

		// Add alignement
		if($this->halign !== null) $this->xml->setAttribute('halign', $this->halign);
		if($this->valign !== null) $this->xml->setAttribute('valign', $this->valign);

		// Add links
		if(Manialink::$linksEnabled)
		{
			if($this->manialink !== null) $this->xml->setAttribute('manialink', $this->manialink);
			if($this->maniazone !== null) $this->xml->setAttribute('maniazone', $this->maniazone);
			if($this->manialinkId !== null) $this->xml->setAttribute('manialinkId', $this->manialinkId);
			if($this->url !== null) $this->xml->setAttribute('url', $this->url);
			if($this->urlId !== null) $this->xml->setAttribute('urlid', $this->urlId);

			// Add action
			if($this->action !== null) $this->xml->setAttribute('action', $this->action);
		}
		if($this->actionKey !== null) $this->xml->setAttribute('actionkey', $this->actionKey);
		
	}
}
?>