<?php

namespace ManiaLivePlugins\eXpansion\Gui\Widgets;

use ManiaLib\Gui\Elements\Label;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\ConfigManager;
use ManiaLivePlugins\eXpansion\Gui\Config;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
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
        $this->xml = new \ManiaLive\Gui\Elements\Xml();

        $this->_coord = new Label();
        $this->_coord->setAlign("center", "center");
        $this->_coord->setId("coordLabel");
        $this->_coord->setAttribute('hidden', "true");
        $this->addComponent($this->_coord);

        $this->eXpOnEndConstruct();
        $this->eXpLoadSettings();
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

    /**
     * When the settings of the widget has been loaded.
     */
    protected function eXpOnSettingsLoaded()
    {
    }

    private function eXpLoadSettings()
    {
        $widgetName = str_replace(" ", "", $this->getName());

        if (isset(self::$config[$widgetName])) {

            //Getting exact game mode
            $gameMode = $this->storage->gameInfos->gameMode;
            if ($gameMode == 0) {
                $gameMode = $this->storage->gameInfos->scriptName;
            }

            //Getting compatibility Game mode
            $compoMode = Gui::eXpGetCurrentCompatibilityGameMode();

            /**
             * @var \ManiaLivePlugins\eXpansion\Helpers\Storage $storage
             */
            $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();

            //Getting full title id
            $titleid = $storage->version->titleId;

            //Getting environnment based simple title id
            $enviTitle = $storage->simpleEnviTitle;


            $this->currentSettings = array();
            foreach (self::$config[$widgetName] as $name => $values) {
                if (isset($values[$gameMode])) {
                    $this->currentSettings[$name] = $values[$gameMode];
                } else {
                    if (isset($values[$compoMode])) {
                        $this->currentSettings[$name] = $values[$compoMode];
                    } else {
                        if (isset($values[$titleid])) {
                            $this->currentSettings[$name] = $values[$titleid];
                        } else {
                            if (isset($values[$enviTitle])) {
                                $this->currentSettings[$name] = $values[$enviTitle];
                            } else {
                                if (isset($values[WConfig::config_default])) {
                                    $this->currentSettings[$name] = $values[WConfig::config_default];
                                }
                            }
                        }
                    }
                }
            }
        }
        // override defaults!
        $positions = Config::getInstance()->allWidgetPositions;
        $name = str_replace(" ", "", $this->getName());
        if (array_key_exists($name, $positions)) {
            $pos = str_replace('–', '-', $positions[$name]);
            $pos = explode("x", $pos);


            $this->currentSettings['posX'] = floatval(Maniascript::getReal(trim($pos[0])));
            $this->currentSettings['posY'] = floatval(Maniascript::getReal(trim($pos[1])));

        }

        $this->autoSetPositions();
        $this->eXpOnSettingsLoaded();
    }

    protected function onDraw()
    {
        $this->script->setParam("name", $this->getName());
        $this->script->setParam("axisDisabled", $this->axisDisabled);
        $this->script->setParam("gameMode", Gui::fixString($this->storage->gameInfos->scriptName));
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

    protected function autoSetPositions()
    {
        $posX = $this->getParameter("posX");
        $posY = $this->getParameter("posY");

        if ($posX != null && $posY != null) {
            $this->setPosition($posX, $posY);
        }
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

    /**
     * set a custom position for a gamemode
     *
     * @param string $gameMode
     * @param float $posX
     * @param float $posY
     */
    public function setPositionForGamemode($gameMode, $posX, $posY)
    {
        $this->positions[$gameMode] = array($posX, $posY);
    }

    public function getWidgetVisible()
    {
        if (isset($this->widgetVisible[$this->storage->gameInfos->gameMode])) {
            $value = $this->widgetVisible[$this->storage->gameInfos->gameMode];

            return $this->getBoolean($value);
        }

        return "True";
    }

    /**
     * Sets visibility of the widget according to gamemode
     *
     * @param string $gameMode
     * @param bool $value
     */
    public function setVisibilityForGamemode($gameMode, $value)
    {
        $this->widgetVisible[$gameMode] = $value;
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

    public static function setParameter($widgetName, $name, $value)
    {
        if (!isset(self::$config[$widgetName])) {
            self::$config[$widgetName] = array();
        }

        self::$config[$widgetName][$name] = $value;
    }

    protected function getParameter($name)
    {
        return isset($this->currentSettings[$name]) ? $this->currentSettings[$name] : null;
    }
}
