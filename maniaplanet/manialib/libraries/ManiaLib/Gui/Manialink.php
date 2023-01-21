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

abstract class Manialink
{

	const DEFAULT_BACKGROUND = 1;
	const HIDE_BACKGROUND = 0;
	const STARS_BACKGROUND = 'stars';
	const STATIONS_BACKGROUND = 'stations';
	const TITLE_BACKGROUND = 'title';
	
	/**
	 * @var \DOMDocument 
	 */
	public static $domDocument;

	/**
	 * @var \DOMNode[]
	 */
	public static $parentNodes;

	/**
	 * @var Layouts\AbstractLayout[]
	 */
	public static $parentLayouts;

	/**
	 * @var Elements\Frame[]
	 */
	public static $parentFrames;
	public static $linksEnabled = true;
	public static $langsURL;
	public static $imagesURL;
	public static $mediaURL;

	/**
	 * Loads the Manialink GUI toolkit. This should be called before doing
	 * anything with the toolkit.
	 * @param bool Whether you want to create the root "<manialink>" element in the XML
	 */
	final public static function load($root = true, $timeout = 0, $version = 1, $background = self::DEFAULT_BACKGROUND, $navigable3d = false)
	{
		if(class_exists('\ManiaLib\Application\Config'))
		{
			$config = \ManiaLib\Application\Config::getInstance();
			self::$langsURL = $config->getLangsURL();
			self::$imagesURL = $config->getImagesURL();
			self::$mediaURL = $config->getMediaURL();
		}

		// Load the XML object
		self::$domDocument = new \DOMDocument('1.0', 'utf-8');
		self::$parentNodes = array();
		self::$parentLayouts = array();
		self::$parentFrames = array();

		if($root)
		{
			$nodeManialink = self::$domDocument->createElement('manialink');
			$nodeManialink->setAttribute('version', $version);
			$nodeManialink->setAttribute('background', $background);
			$nodeManialink->setAttribute('navigable3d', (int)$navigable3d);
			self::$domDocument->appendChild($nodeManialink);
			self::$parentNodes[] = $nodeManialink;

			$nodeTimeout = self::$domDocument->createElement('timeout');
			$nodeManialink->appendChild($nodeTimeout);
			$nodeTimeout->nodeValue = $timeout;
		}
		else
		{
			$frame = self::$domDocument->createElement('frame');
			self::$domDocument->appendChild($frame);
			self::$parentNodes[] = $frame;
		}
	}

	/**
	 * Renders the Manialink
	 * @param boolean Wehther you want to return the XML instead of printing it
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
		}
	}

	/**
	 * Creates a new Manialink frame, with an optionnal associated layout
	 *
	 * @param float X position
	 * @param float Y position
	 * @param float Z position
	 * @param float Scale (default is null or 1)
	 * @param \ManiaLib\Gui\Layouts\AbstractLayout The optionnal layout associated with the frame. If
	 * you pass a layout object, all the items inside the frame will be
	 * positionned using constraints defined by the layout
	 */
	final public static function beginFrame($x = 0, $y = 0, $z = 0, $scale = null,
		\ManiaLib\Gui\Layouts\AbstractLayout $layout = null)
	{
		$frame = new Elements\Frame();
		$frame->setPosition($x, $y, $z);
		$frame->setScale($scale);
		if($layout instanceof Layouts\AbstractLayout)
		{
			$frame->setLayout($layout);
		}
		$frame->buildXML();

		self::$parentFrames[] = $frame;
		self::$parentNodes[] = $frame->getDOMElement();
		self::$parentLayouts[] = $frame->getLayout();
	}

	/**
	 * Closes the current Manialink frame
	 */
	final public static function endFrame()
	{
		if(!end(self::$parentNodes)->hasChildNodes())
		{
			end(self::$parentNodes)->nodeValue = ' ';
		}
		array_pop(self::$parentNodes);
		array_pop(self::$parentLayouts);
		$frame = array_pop(self::$parentFrames);
		$frame->save();
	}

	final static function setFrameId($id)
	{
		end(self::$parentFrames)->setId($id);
	}

        final static function setFrameAttributes($attributes) {
            $frame = end(self::$parentNodes);
            foreach ($attributes as $key => $value) {
                $frame->setAttribute($key, $value);
            }
        }

	final static function setFrameScriptEvents($scriptEvents = 1)
	{
		$frame = end(self::$parentNodes);
		$frame->setAttribute('scriptevents', $scriptEvents);
	}

	/**
	 * Redirects the user to the specified Manialink
	 */
	final public static function redirect($link, $render = true)
	{
		self::$domDocument = new \DOMDocument('1.0', 'utf-8');
		self::$parentNodes = array();
		self::$parentLayouts = array();

		$redirect = self::$domDocument->createElement('redirect');
		$redirect->appendChild(self::$domDocument->createTextNode($link));
		self::$domDocument->appendChild($redirect);
		self::$parentNodes[] = $redirect;

		if($render)
		{
			if(ob_get_contents())
			{
				ob_clean();
			}
			header('Content-Type: text/xml; charset=utf-8');
			echo self::$domDocument->saveXML();
		}
		else
		{
			return self::$domDocument->saveXML();
		}
	}

	static function createElement($tagName)
	{
		return self::$domDocument->createElement($tagName);
	}

	static function comment($comment)
	{
		end(self::$parentNodes)->appendChild(self::$domDocument->createComment($comment));
	}

	/**
	 * Append some XML code to the document
	 * @param string Some XML code
	 */
	static function appendXML($XML)
	{
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->loadXML($XML);
		$node = self::$domDocument->importNode($doc->firstChild, true);
		end(self::$parentNodes)->appendChild($node);
	}

	static function appendScript($maniaScript)
	{
		$script = self::$domDocument->createElement('script');
		$script->appendChild(self::$domDocument->createTextNode($maniaScript));
		end(self::$parentNodes)->appendChild($script);
	}

	/**
	 * Disable all Manialinks, URLs and actions of GUIElement objects as long as it is disabled
	 */
	static function disableLinks()
	{
		self::$linksEnabled = false;
	}

	/**
	 * Enable links
	 */
	static function enableLinks()
	{
		self::$linksEnabled = true;
	}

}

?>