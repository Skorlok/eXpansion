<?php

namespace ManiaLivePlugins\eXpansion\Gui\Widgets;

use ManiaLib\Gui\Elements\Label;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\ConfigManager;
use ManiaLivePlugins\eXpansion\Gui\Config;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\MetaData;
use ManiaLivePlugins\eXpansion\Gui\Widgets as WConfig;
use ManiaLivePlugins\eXpansion\Helpers\Maniascript;

/**
 * @abstract
 */
class Widget extends PlainWidget
{

    private $move;
    private $_coord;

    private $axisDisabled = "";
    private $script;

    /** @var Array */
    private $positions = array();

    /** @var Array */
    private $widgetVisible = array();
    private $visibleLayerInit = "normal";

    /** @var \ManiaLive\Data\Storage */
    private $storage;
    private static $config;
    public $currentSettings = array();

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->eXpOnBeginConstruct();
        $this->script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui\Scripts\WidgetScript");
        $this->script->setParam('disablePersonalHud', \ManiaLivePlugins\eXpansion\Gui\Config::getInstance()->disablePersonalHud ? 'True' : 'False');
        $this->registerScript($this->script);

        $this->move = new \ManiaLib\Gui\Elements\Quad(45, 7);
        $this->move->setStyle("Icons128x128_1");
        $this->move->setSubStyle("ShareBlink");
        $this->move->setScriptEvents();
        $this->move->setId("enableMove");
        $this->move->setPositionZ(100);
        $this->addComponent($this->move);
        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->_coord = new Label();
        $this->_coord->setAlign("center", "center");
        $this->_coord->setId("coordLabel");
        $this->_coord->setAttribute('hidden', "true");
        $this->addComponent($this->_coord);

        $this->eXpOnEndConstruct();
    }


    /**
     * When the Widget is being constructed.
     */
    protected function eXpOnBeginConstruct()
    {
    }

    /**
     * When the construction of the widget has ended
     */
    protected function eXpOnEndConstruct()
    {
    }

    protected function onDraw()
    {
        $this->script->setParam("name", $this->getName());
        $this->script->setParam("axisDisabled", $this->axisDisabled);
        $this->script->setParam("gameMode", $this->storage->getCleanGamemodeName());
        $this->script->setParam("visibleLayerInit", $this->visibleLayerInit);
        $this->script->setParam("forceReset", $this->getBoolean(DEBUG));
        parent::onDraw();
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->move->setSize($this->getSizeX(), $this->getSizeY());
        $this->_coord->setPosition($this->getSizeX() / 2, -$this->getSizeY() / 2);
    }

    public function closeWindow()
    {
        $this->erase($this->getRecipient());
    }

    public function destroy()
    {
        unset($this->currentSettings);
        unset($this->widgetVisible);
        unset($this->positions);
        parent::destroy();
    }

    /**
     * disable moving for certaint axis
     *
     * @param string $axis accepts values: "x" or "y"
     */
    public function setDisableAxis($axis)
    {
        $this->axisDisabled = $axis;
    }

    public function getWidgetVisible()
    {
        if (isset($this->widgetVisible[$this->storage->gameInfos->gameMode])) {
            $value = $this->widgetVisible[$this->storage->gameInfos->gameMode];

            return $this->getBoolean($value);
        }

        return "True";
    }

    public function setVisibleLayer($string)
    {
        $this->visibleLayerInit = $string;
    }

    public function getPosX()
    {
        if (isset($this->positions[$this->storage->gameInfos->gameMode])) {
            return $this->positions[$this->storage->gameInfos->gameMode][0];
        }

        return $this->posX;
    }

    public function getPosY()
    {
        if (isset($this->positions[$this->storage->gameInfos->gameMode])) {
            return $this->positions[$this->storage->gameInfos->gameMode][1];
        }

        return $this->posY;
    }
}
