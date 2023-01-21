<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Widgets;

use ManiaLivePlugins\eXpansion\ManiaExchange\Config;

class MxWidget extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{
    protected $_windowFrame;
    protected $_mainWindow;
    protected $_minButton;

    protected function eXpOnBeginConstruct()
    {
        $storage = \ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance();
        if ($storage->simpleEnviTitle == "TM") {
            $this->edgeWidget = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/EdgeWidget");
            $this->registerScript($this->edgeWidget);
        }
        $this->setName("ManiaExchange Panel");

        $login = $this->getRecipient();

        $this->_windowFrame = new \ManiaLive\Gui\Controls\Frame();
        $this->_windowFrame->setAlign("left", "top");
        $this->_windowFrame->setId("Frame");
        $this->_windowFrame->setScriptEvents(true);

        $this->_mainWindow = new \ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround(6, 6);
        $this->_mainWindow->setId("MainWindow");
        $this->_windowFrame->addComponent($this->_mainWindow);

        $this->_minButton = new \ManiaLib\Gui\Elements\Quad(5, 5);
        $this->_minButton->setScriptEvents(true);
        $this->_minButton->setId("minimizeButton");
        $this->_minButton->setAlign("left", "top");
        $this->_minButton->setPosition(0, 0);
        $this->_minButton->setImage(Config::getInstance()->iconMx, true);
        $this->_minButton->setAction(\ManiaLivePlugins\eXpansion\ManiaExchange\ManiaExchange::$openInfosAction);
        $this->_windowFrame->addComponent($this->_minButton);
        $this->addComponent($this->_windowFrame);

        $this->setScriptEvents(true);
    }

    protected function eXpOnEndConstruct()
    {
        $this->setSize(7, 7);
    }
}
