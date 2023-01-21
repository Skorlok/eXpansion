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

use ManiaLib\Gui\Component;
use ManiaLib\Gui\Drawable;
use ManiaLib\Gui\Layouts\AbstractLayout;
use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Tools;

class Frame extends Component implements Drawable
{
    protected $xml;
    protected $xmlTagName = 'frame';

    /**
     * @var AbstractLayout
     */
    protected $layout;

    /**
     * @var Component[]
     */
    protected $children;

    function __construct($sizeX = 0, $sizeY = 0)
    {
        $this->sizeX    = $sizeX;
        $this->sizeY    = $sizeY;
        $this->children = array();
    }

    /**
     * @return AbstractLayout
     */
    function getLayout()
    {
        return $this->layout;
    }

    function setLayout(AbstractLayout $layout)
    {
        $this->layout = $layout;
    }

    function onResize($oldX, $oldY)
    {
        if ($this->layout instanceof AbstractLayout) {
            $this->layout->setSize($this->sizeX, $this->sizeY);
        }
    }

    function onScale($oldScale)
    {
        if ($this->layout instanceof AbstractLayout) {
            $this->layout->setScale($this->scale);
        }
    }

    function add(Component $component)
    {
        $this->children[] = $component;
    }

    function remove(Component $component)
    {
        $key = array_search($component, $this->children);
        if ($key !== false) {
            unset($this->children[$key]);
        }
    }

    function preFilter()
    {
        
    }

    function postFilter()
    {

    }

    function buildXML()
    {
        if (!$this->xml) {
            $this->xml = Manialink::createElement($this->xmlTagName);
            $this->getParentNode()->appendChild($this->xml);
        }

        if ($this->id !== null) $this->xml->setAttribute('id', $this->id);

        if ($this->posX || $this->posY || $this->posZ) {
            $this->xml->setAttribute('posn', $this->posX.' '.$this->posY.' '.$this->posZ);
        }
        if ($this->scale !== null) $this->xml->setAttribute('scale', $this->scale);

        if ($this->getAttribute('clip')) $this->xml->setAttribute('clip', $this->getAttribute('clip'));
        if ($this->getAttribute('clipsizen')) $this->xml->setAttribute('clipsizen', $this->getAttribute('clipsizen'));
        if ($this->getAttribute('clipposn')) $this->xml->setAttribute('clipposn', $this->getAttribute('clipposn'));
        if ($this->getAttribute('class')) $this->xml->setAttribute('class', $this->getAttribute('class'));
        if ($this->hidden == true) $this->xml->setAttribute('hidden', '1');
    }

    function getDOMElement()
    {
        return $this->xml;
    }

    final function save()
    {
        if ($this->visible === false) {
            return;
        }

        $this->preFilter();

        if ($this->layout instanceof AbstractLayout) {
            $layout = $this->getParentLayout();
            if ($layout instanceof AbstractLayout) {
                $layout->preFilter($this->layout);
                $layout->updateComponent($this);
            }
        }

        $frame = $this->getParentFrame();
        if ($frame instanceof Frame) {
            if ($frame->getSizeX()) {
                $x = Tools::getAlignedPosX(0, $frame->getSizeX(), $frame->getHalign('left'), $this->getRelativeHalign('left'));
                $this->incPosX($x);
            }
            if ($frame->getSizeY()) {
                $y = Tools::getAlignedPosY(0, $frame->getSizeY(), $frame->getValign('top'), $this->getRelativeValign('top'));
                $this->incPosY($y);
            }
        }

        $this->buildXML();


        foreach ($this->children as $child) {
            $child->setParentNode($this->xml);
            $child->setParentLayout($this->layout);
            $child->setParentFrame($this);
            $child->incPosZ(0.1);
            if ($child instanceof Drawable) {
                $child->save();
            }
        }

        if ($this->layout instanceof AbstractLayout) {
            if ($layout instanceof AbstractLayout) {
                $layout->postFilter($this->layout);
            }
        }

        $this->postFilter();
    }
}
?>