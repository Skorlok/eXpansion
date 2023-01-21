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

namespace ManiaLib\Gui;

abstract class Element extends Component implements Drawable
{

	const USE_ABSOLUTE_URL = true;

	protected $style;
	protected $subStyle;
	protected $manialink;
	protected $goto;
	protected $manialinkId;
	protected $url;
	protected $urlId;

	/**
	 * @deprecated
	 */
	protected $maniazone;
	protected $bgcolor;
	protected $bgcolorfocus;
	protected $opacity;

	/**
	 * @deprecated
	 */
	protected $addPlayerId;
	protected $scriptevents;
	protected $action;
	protected $actionKey;
	protected $image;
	protected $imageid;
	protected $imageFocus;
	protected $imageFocusid;
	// FIXME Change that to static & use late static binding
	protected $xmlTagName = 'xmltag'; // Redeclare this for each child
	protected $xml;

	/**
	 * Used by cards, all the elements in that array will be renderd before
	 * the post filter.
	 * @var array[\ManiaLib\Gui\Element]
	 */
	protected $cardElements = array();
	protected $cardElementsHalign = 'left';
	protected $cardElementsValign = 'top';
	protected $cardElementsPosX = 0;
	protected $cardElementsPosY = 0;
	protected $cardElementsPosZ = 0.1;

	/**
	 * @var \ManiaLib\Gui\Layouts\AbstractLayout
	 */
	protected $cardElementsLayout = null;

	/**
	 * Manialink element default constructor. It's common to specify the size of
	 * the element in the constructor.
	 *
	 * @param float Width of the element
	 * @param float Height of the element
	 */
	function __construct($sizeX = 20, $sizeY = 20)
	{
		$this->sizeX = $sizeX;
		$this->sizeY = $sizeY;
	}

	/**
	 * Sets the style of the element. See http://fish.stabb.de/styles/ of the
	 * manialink 'example' for more information on Manialink styles.
	 * @param string
	 */
	function setStyle($style)
	{
		$this->style = $style;
	}

	/**
	 * Sets the sub-style of the element. See http://fish.stabb.de/styles/ of
	 * the manialink 'example' for more information on Manialink styles.
	 * @param string
	 */
	function setSubStyle($substyle)
	{
		$this->subStyle = $substyle;
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
	 * Sets the Manialink id of the element. It works as a hyperlink.
	 * @param string Can be either a short Manialink or an URL pointing to a
	 * Manialink
	 */
	function setManialinkId($manialinkId)
	{
		$this->manialinkId = $manialinkId;
	}

	/**
	 * @ignore
	 */
	function setGoto($manialink)
	{
		$this->manialink = $manialink;
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

	/**
	 * Sets the Maniazones link of the element
	 * @param string
	 * @deprecated
	 */
	function setManiazone($maniazone)
	{
		$this->maniazone = $maniazone;
	}

	function setScriptEvents($scriptEvents = 1)
	{
		$this->scriptevents = $scriptEvents;
	}

	/**
	 * @deprecated
	 */
	function addPlayerId()
	{
		$this->addPlayerId = 1;
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

	/**
	 * Sets the background color of the element using a 3-digit RGB hexadecimal
	 * value. For example, "fff" is white and "000" is black
	 * @param string 3-digit RGB hexadecimal value
	 */
	function setBgcolor($bgcolor)
	{
		$this->bgcolor = $bgcolor;
		$this->setStyle(null);
		$this->setSubStyle(null);
	}
	
	function setBgcolorFocus($bgcolor)
	{
		$this->bgcolorfocus = $bgcolor;
		$this->setStyle(null);
		$this->setSubStyle(null);
	}
	
	/**
	 * Set the opacity of a quad or a label
	 * @param float $opacity
	 */
	function setOpacity($opacity)
	{
		$this->opacity = $opacity;
	}

	/**
	 * Applies an image to the element
	 * @param string The image filename (or URL)
	 * @param bool Whether to prefix the filename with the default images dir URL
	 */
	function setImage($image, $absoluteUrl = false)
	{
		$this->setStyle(null);
		$this->setSubStyle(null);
		if(!$absoluteUrl)
		{
			$this->image = Manialink::$imagesURL.$image;
		}
		else
		{
			$this->image = $image;
		}
	}

	/**
	 * Set the image id of the element, used for internationalization
	 */
	function setImageid($imageid)
	{
		$this->setStyle(null);
		$this->setSubStyle(null);
		$this->imageid = $imageid;
	}

	/**
	 * Applies an image to the highlighter state of the element
	 * @param string The image filename (or URL)
	 * @param bool Whether to prefix the filename with the default images dir URL
	 */
	function setImageFocus($imageFocus, $absoluteUrl = false)
	{
		$this->setStyle(null);
		$this->setSubStyle(null);
		if(!$absoluteUrl)
		{
			$this->imageFocus = Manialink::$imagesURL.$imageFocus;
		}
		else
		{
			$this->imageFocus = $imageFocus;
		}
	}

	/**
	 * Set the image focus id of the element, used for internationalization
	 */
	function setImageFocusid($imageFocusid)
	{
		$this->setStyle(null);
		$this->setSubStyle(null);
		$this->imageFocusid = $imageFocusid;
	}

	/**
	 * Returns the style of the element
	 * @return string
	 */
	function getStyle()
	{
		return $this->style;
	}

	/**
	 * Returns the substyle of the element
	 * @return string
	 */
	function getSubStyle()
	{
		return $this->subStyle;
	}

	/**
	 * Returns the Manialink hyperlink of the element
	 * @return string
	 */
	function getManialink()
	{
		return $this->manialink;
	}

	/**
	 * @ignore
	 */
	function getGoto()
	{
		return $this->goto;
	}

	/**
	 * Returns the Manialink hyperlink id of the element
	 * @return string
	 */
	function getManialinkId()
	{
		return $this->manialinkId;
	}

	/**
	 * Returns the Maniazones hyperlink of the element
	 * @return string
	 * @deprecated
	 */
	function getManiazone()
	{
		return $this->maniazone;
	}

	function getScriptEvents()
	{
		return $this->scriptevents;
	}

	/**
	 * Returns the hyperlink of the element
	 * @return string
	 */
	function getUrl()
	{
		return $this->url;
	}

	/**
	 * Returns the hyperlink id of the element
	 * @return string
	 */
	function getUrlId()
	{
		return $this->urlId;
	}

	/**
	 * Returns the action associated to the element
	 * @return int
	 */
	function getAction()
	{
		return $this->action;
	}

	/**
	 * Returns the action key associated to the element
	 * @return int
	 */
	function getActionKey()
	{
		return $this->actionKey;
	}

	/**
	 * Returns whether the elements adds player information parameter to the URL
	 * when it's clicked
	 * @return boolean
	 */
	function getAddPlayerId()
	{
		return $this->addPlayerId;
	}

	/**
	 * Returns the background color of the element
	 * @return string 3-digit RGB hexadecimal value
	 */
	function getBgcolor()
	{
		return $this->bgcolor;
	}
	
	function getBgcolorFocus()
	{
		return $this->bgcolorfocus;
	}
	
	function getOpacity()
	{
		return $this->opacity;
	}

	/**
	 * Returns the image placed in the element
	 * @return string The image URL
	 */
	function getImage()
	{
		return $this->image;
	}

	function getImageid()
	{
		return $this->imageid;
	}

	/**
	 * Returns the image placed in the element in its highlighted state
	 * @return string The image URL
	 */
	function getImageFocus()
	{
		return $this->imageFocus;
	}

	function getImageFocusid()
	{
		return $this->imageFocusid;
	}

	/**
	 * Imports links and actions from another Manialink element
	 * @param \ManiaLib\Gui\Element The source object
	 */
	function addLink(\ManiaLib\Gui\Element $object)
	{
		$this->manialink = $object->getManialink();
		$this->url = $object->getUrl();
		$this->maniazone = $object->getManiazone();
		$this->goto = $object->getGoto();
		$this->action = $object->getAction();
		$this->actionKey = $object->getActionKey();
		if($object->getAddPlayerId())
		{
			$this->addPlayerId = 1;
		}
	}

	/**
	 * Returns whether the object has a link or an action (either Manialink,
	 * Maniazones link, hyperlink or action)
	 * @return string
	 */
	function hasLink()
	{
		return $this->manialink || $this->url || $this->action || $this->maniazone || $this->scriptevents;
	}

	function setCardElementPosition($posX = 0, $posY = 0, $posZ = 0)
	{
		$this->cardElementsPosX = $posX;
		$this->cardElementsPosY = $posY;
		$this->cardElementsPosZ = $posZ;
	}

	/**
	 * @deprecated
	 */
	protected function addCardElement(\ManiaLib\Gui\Element $element)
	{
		$this->cardElements[] = $element;
	}

	/**
	 * Override this method in subclasses to perform some action before
	 * rendering the element
	 * @ignore
	 */
	protected function preFilter()
	{
		
	}

	/**
	 * Override this method in subclasses to perform some action after rendering
	 * the element
	 * @ignore
	 */
	protected function postFilter()
	{
		
	}

	final protected function buildXML()
	{
		if(!$this->xmlTagName)
		{
			return;
		}

		$this->xml = Manialink::createElement($this->xmlTagName);
		$this->getParentNode()->appendChild($this->xml);

		// Add id
		if($this->id !== null) $this->xml->setAttribute('id', $this->id);

		// Add pos
		if($this->posX || $this->posY || $this->posZ)
		{
			$this->xml->setAttribute('posn', $this->posX.' '.$this->posY.' '.$this->posZ);
		}

		// Add size
		if($this->sizeX || $this->sizeY)
		{
			$this->xml->setAttribute('sizen', $this->sizeX.' '.$this->sizeY);
		}
		if($this->scale !== null) $this->xml->setAttribute('scale', $this->scale);

		// Add alignement
		if($this->halign !== null) $this->xml->setAttribute('halign', $this->halign);
		if($this->valign !== null) $this->xml->setAttribute('valign', $this->valign);

		// Add styles
		if($this->style !== null) $this->xml->setAttribute('style', $this->style);
		if($this->subStyle !== null) $this->xml->setAttribute('substyle', $this->subStyle);
		if($this->bgcolor !== null) $this->xml->setAttribute('bgcolor', $this->bgcolor);
		if($this->bgcolorfocus !== null) $this->xml->setAttribute('bgcolorfocus', $this->bgcolorfocus);
		if($this->opacity !== null) $this->xml->setAttribute('opacity', $this->opacity);

		// Add links
		if(Manialink::$linksEnabled)
		{
			if($this->addPlayerId !== null) $this->xml->setAttribute('addplayerid', $this->addPlayerId);
			if($this->manialink !== null) $this->xml->setAttribute('manialink', $this->manialink);
			if($this->goto !== null) $this->xml->setAttribute('goto', $this->goto);
			if($this->manialinkId !== null) $this->xml->setAttribute('manialinkId', $this->manialinkId);
			if($this->url !== null) $this->xml->setAttribute('url', $this->url);
			if($this->urlId !== null) $this->xml->setAttribute('urlid', $this->urlId);
			if($this->maniazone !== null) $this->xml->setAttribute('maniazone', $this->maniazone);

			// Add action
			if($this->action !== null) $this->xml->setAttribute('action', $this->action);
		}
		if($this->actionKey !== null) $this->xml->setAttribute('actionkey', $this->actionKey);

		// Add images
		if($this->image !== null) $this->xml->setAttribute('image', $this->image);
		if($this->imageid !== null) $this->xml->setAttribute('imageid', $this->imageid);
		if($this->imageFocus !== null) $this->xml->setAttribute('imagefocus', $this->imageFocus);
		if($this->imageFocusid !== null) $this->xml->setAttribute('imagefocusid', $this->imageFocusid);

		// Add Script Attributes
		if($this->id) $this->xml->setAttribute('id', $this->id);
		if($this->scriptevents !== null) $this->xml->setAttribute('scriptevents', $this->scriptevents);
		if($this->hidden !== null) $this->xml->setAttribute('hidden', $this->hidden);
                
                // Add all other attributes
                foreach($this->attributes as $name => $value)
                {
                    $this->xml->setAttribute($name, $value);
                }
	}

	final protected function handleCardElements()
	{
		if(!$this->cardElements)
		{
			return;
		}

		// Align relative to the parent elements
		$arr = Tools::getAlignedPos($this, $this->cardElementsHalign, $this->cardElementsValign);
		$x = $arr["x"];
		$y = $arr["y"];

		Manialink::beginFrame(
			$x + $this->cardElementsPosX, $y + $this->cardElementsPosY, $this->posZ + $this->cardElementsPosZ, $this->scale);
		Manialink::beginFrame(0, 0, 0, null, $this->cardElementsLayout);

		foreach($this->cardElements as $element)
		{
			$element->save();
		}

		Manialink::endFrame();
		Manialink::endFrame();
	}

	/**
	 * Saves the object in the Manialink object stack for further rendering.
	 * Thanks to the use of \ManiaLib\Gui\Element::preFilter() and \ManiaLib\Gui\Element::
	 * postFilter(), you shouldn't have to override this method
	 */
	final function save()
	{
		if($this->visible === false)
		{
			return;
		}

		$this->preFilter();

		$layout = $this->getParentLayout();
		if($layout instanceof Layouts\AbstractLayout)
		{
			$layout->preFilter($this);
			$layout->updateComponent($this);
		}

		$frame = $this->getParentFrame();
		if($frame instanceof Elements\Frame)
		{
			if($frame->getSizeX())
			{
				$x = Tools::getAlignedPosX(0, $frame->getSizeX(), $frame->getHalign('left'), $this->getRelativeHalign('left'));
				$this->incPosX($x);
			}
			if($frame->getSizeY())
			{
				$y = Tools::getAlignedPosY(0, $frame->getSizeY(), $frame->getValign('top'), $this->getRelativeValign('top'));
				$this->incPosY($y);
			}
		}

		$this->buildXML();

		if($layout instanceof Layouts\AbstractLayout)
		{
			$layout->postFilter($this);
		}

		$this->handleCardElements();

		$this->postFilter();
	}

}

?>