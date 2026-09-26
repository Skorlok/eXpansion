<?php

namespace ManiaLivePlugins\eXpansion\Gui\Elements;

use ManiaLivePlugins\eXpansion\Gui\Config;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

/**
 * Description of Pager
 *
 * @author Petri
 */
class Pager extends \ManiaLivePlugins\eXpansion\Gui\Control implements \ManiaLivePlugins\eXpansion\Gui\Structures\ScriptedContainer
{

    protected $pager;
    protected $items = array();
    protected $scroll;
    protected $scrollBg;
    protected $scrollUp;
    protected $scrollDown;
    protected $barFrame;
    protected $itemSizeY = 6;
    protected $myScript;

    public function __construct()
    {
        $config = Config::getInstance();

        $this->pager = new \ManiaLive\Gui\Controls\Frame();
        $this->pager->setId("Pager");
        $this->pager->setScriptEvents();
        $this->addComponent($this->pager);

        $this->barFrame = new \ManiaLive\Gui\Controls\Frame(0, -5);
        $this->addComponent($this->barFrame);

        $this->scrollBg = new \ManiaLib\Gui\Elements\Quad(4, 40);
        $this->scrollBg->setAlign("center", "top");
        $this->scrollBg->setId("ScrollBg");
        $this->scrollBg->setStyle("Bgs1InRace");
        $this->scrollBg->setSubStyle('BgPlayerCard');

        $this->scrollBg->setOpacity(0.9);
        $this->barFrame->addComponent($this->scrollBg);

        $this->scroll = new \ManiaLib\Gui\Elements\Quad(3, 15);
        $this->scroll->setAlign("center", "top");
        $this->scroll->setStyle("BgsPlayerCard");
        $this->scroll->setSubStyle('BgRacePlayerName');
        $this->scroll->setId("ScrollBar");
        $this->scroll->setScriptEvents();
        $this->barFrame->addComponent($this->scroll);

        $this->scrollDown = new \ManiaLib\Gui\Elements\Quad(6.5, 6.5);
        $this->scrollDown->setAlign("center", "top");
        $this->scrollDown->setStyle("Icons64x64_1");
        $this->scrollDown->setSubStyle("ArrowDown");
        $this->scrollDown->setId("ScrollDown");
        $this->scrollDown->setScriptEvents();
        $this->barFrame->addComponent($this->scrollDown);

        $this->scrollUp = new \ManiaLib\Gui\Elements\Quad(6.5, 6.5);
        $this->scrollUp->setAlign("center", "bottom");
        $this->scrollUp->setStyle("Icons64x64_1");
        $this->scrollUp->setSubStyle("ArrowUp");
        $this->scrollUp->setId("ScrollUp");
        $this->scrollUp->setScriptEvents();
        $this->barFrame->addComponent($this->scrollUp);

        $this->myScript = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui\Scripts\Pager");
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);

        $this->pager->setSize($this->sizeX - 6, $this->sizeY);

        $this->myScript->setParam("pagerSizeY", $this->myScript->getNumber($this->sizeY));

        $this->scroll->setPosition($this->sizeX - 3, 0);
        $this->scrollBg->setPosition($this->sizeX - 3, -0);
        $this->scrollBg->setSizeY($this->sizeY - 9);

        $this->scrollDown->setPosition($this->sizeX - 3, -($this->sizeY - 10));
        $this->scrollUp->setPosition($this->sizeX - 3, -1);

        foreach ($this->items as $item) {
            $scale = $item->getScale();
            if ($scale == "") {
                $scale = 1;
            }

            $item->setSizeX($this->sizeX / $scale - 4);
        }
    }

    public function setStretchContentX($value)
    {
        // do nothing xD
    }

    public function addItem(\ManiaLib\Gui\Component $component)
    {
        $scale = $component->getScale();
        if ($scale == "") {
            $scale = 1;
        }
        $component->setSizeX($this->sizeX / $scale - 8);
        $component->setAlign("left", "top");
        if ($component->getSizeY() > 0) {
            $this->itemSizeY = $component->getSizeY();
        }
        $item = new \ManiaLive\Gui\Controls\Frame();
        $item->setAlign("left", "top");
        $item->setScriptEvents();
        $item->addComponent($component);
        $hash = spl_object_hash($item);
        $this->items[$hash] = $item;
        $this->pager->addComponent($this->items[$hash]);
    }

    public function clearItems()
    {
        if (isset($this->pager) && $this->pager != null) {
            $this->pager->destroyComponents();
            $this->items = array();
        }
    }

    public function removeItem(\ManiaLib\Gui\Component $item)
    {
        $hash = spl_object_hash($item);
        $this->pager->removeComponent($this->items[$hash]);
        $this->items[$hash]->destroy();
        unset($this->items[$hash]);
    }

    public function destroy()
    {
        if (isset($this->pager) && $this->pager != null) {
            $this->pager->destroyComponents();
            $this->pager->destroy();
            $this->items = array();
        }
        parent::destroy();
    }

    public function onIsRemoved(\ManiaLive\Gui\Container $target)
    {
        parent::onIsRemoved($target);
        $this->destroy();
    }

    public function getScript()
    {
        $this->myScript->setParam("sizeY", $this->myScript->getNumber($this->itemSizeY));

        return $this->myScript;
    }

    /**
     * Generate the Pager XML structure (scrollable container + scrollbar chrome).
     *
     * @param \ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window $mlClass   ManiaLink class name (e.g. "ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window")
     * @param float  $sizeX  Total width
     * @param float  $sizeY  Total height
     * @param string $itemsVarName  Name of the variable containing the pre-generated XML string of items to embed in the Pager frame
     * @param float  $posX   Horizontal position
     * @param float  $posY   Vertical position
     * @param float  $itemSizeY  Height of one item row
     * @return string
     */
    public static function getXML($mlClass, $sizeX = 100, $sizeY = 50, $itemsVarName = "", $posX = 0, $posY = 0, $itemSizeY = 6)
    {
        if (!is_object($mlClass)) {
            Helper::logError('Pager: Invalid $mlClass parameter', array("Gui", "Pager"));
            return "";
        }
        if (!$mlClass instanceof \ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window) {
            Helper::logError('Pager: $mlClass parameter must be an instance of ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window', array("Gui", "Pager"));
            return "";
        }

        $pagerWidth  = $sizeX - 6;
        $scrollX     = $sizeX - 3;
        $scrollBgH   = $sizeY - 9;
        $scrollDownY = $sizeY - 10;

        $items = $mlClass->getParam($itemsVarName);

        $xml  = '<frame posn="' . $posX . ' ' . $posY . ' 0">';
        $xml .= '<frame id="Pager" sizen="' . $pagerWidth . ' ' . $sizeY . '" scriptevents="1">';
        $xml .= $items;
        $xml .= '</frame>';
        $xml .= '<frame posn="0 -5 0">';
        $xml .= '<quad id="ScrollBg" posn="' . $scrollX . ' 0 0" sizen="4 ' . $scrollBgH . '" halign="center" valign="top" style="Bgs1InRace" substyle="BgPlayerCard" opacity="0.9"/>';
        $xml .= '<quad id="ScrollBar" posn="' . $scrollX . ' 0 1" sizen="3 15" halign="center" valign="top" style="BgsPlayerCard" substyle="BgRacePlayerName" scriptevents="1"/>';
        $xml .= '<quad id="ScrollDown" posn="' . $scrollX . ' -' . $scrollDownY . ' 0" sizen="6.5 6.5" halign="center" valign="top" style="Icons64x64_1" substyle="ArrowDown" scriptevents="1"/>';
        $xml .= '<quad id="ScrollUp" posn="' . $scrollX . ' -1 0" sizen="6.5 6.5" halign="center" valign="bottom" style="Icons64x64_1" substyle="ArrowUp" scriptevents="1"/>';
        $xml .= '</frame>';
        $xml .= '</frame>';

        $script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui\Scripts\Pager");
        $script->setParam("sizeY",      $script->getNumber($itemSizeY));
        $script->setParam("pagerSizeY", $script->getNumber($sizeY));
        $mlClass->registerOrOverrideScript($script);

        return $xml;
    }
}
