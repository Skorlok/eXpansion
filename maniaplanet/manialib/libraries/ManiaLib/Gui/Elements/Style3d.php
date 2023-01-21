<?php

/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */
namespace ManiaLib\Gui\Elements;

use ManiaLib\Gui\Manialink;

class Style3d implements \ManiaLib\Gui\Drawable
{
	const Floating = 'Floating';
	const Box = 'Box';
	
	protected $xml;
	protected $xmlTagName = 'style3d';
	protected $parentNode = false;
	
	protected $id;
	protected $model;
	protected $color;
	protected $thickness;
	protected $gloss;
	protected $colorFloatRed;
	protected $colorFloatGreen;
	protected $colorFloatBlue;
	
	function setId($id = null)
	{
		$this->id = $id;
	}
	
	function setModel($model = null)
	{
		$this->model = $model;
	}
	
	function setColor($color = null)
	{
		$this->color = $color;
	}
	
	function setColorFloat($red = null, $green = null, $blue = null)
	{
		$this->colorFloatRed = $red;
		$this->colorFloatGreen = $green;
		$this->colorFloatBlue = $blue;
	}
	
	function setThickness($thickness)
	{
		$this->thickness = $thickness;
	}
	
	function setGloss($gloss)
	{
		$this->gloss = $gloss;
	}
	
	function setParentNode(\DOMNode $node)
	{
		$this->parentNode = $node;
	}
	
	/**
	 * @return \DOMNode
	 */
	function getParentNode()
	{
		return $this->parentNode !== false ? $this->parentNode : end(Manialink::$parentNodes);
	}
	
	public function save()
	{
		if(!$this->xmlTagName)
		{
			return;
		}
		$this->xml = Manialink::createElement($this->xmlTagName);
		$this->getParentNode()->appendChild($this->xml);
		
		if($this->id !== null) $this->xml->setAttribute('id', $this->id);
		if($this->model !== null) $this->xml->setAttribute('model', $this->model);
		if($this->color !== null) $this->xml->setAttribute('color', $this->color);
		if($this->thickness !== null) $this->xml->setAttribute('thickness', $this->thickness);
		if($this->gloss !== null) $this->xml->setAttribute('gloss', $this->gloss);
		if($this->colorFloatRed !== null || $this->colorFloatGreen !== null || $this->colorFloatBlue !== null) 
			$this->xml->setAttribute('colorfloat', $this->colorFloatRed.' '.$this->colorFloatGreen.' '.$this->colorFloatBlue);
	}
}

?>