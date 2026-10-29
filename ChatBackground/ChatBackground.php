<?php

namespace ManiaLivePlugins\eXpansion\ChatBackground;

use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;

class ChatBackground extends ExpPlugin
{
    /** @var StaticWidget */
    private $widget;

    public function eXpOnReady()
    {
        $this->widget = new StaticWidget('ChatBackground\Gui\Windows\BoxWindow.xml');
        $this->widget->setName("Chat Background");
        $this->widget->setLayer("normal");
        $this->widget->setPosition(0, 0, -60);

        $this->showWidget();
    }

    private function showWidget()
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $this->widget->setParam("width", $config->width);
        $this->widget->setParam("widthHighlite", $config->width * 2);
        $this->widget->setParam("height", $config->height);
        $this->widget->setParam("posY", $config->posY);
        $this->widget->setParam("posZ", $config->posZ);
        $this->widget->setParam("opacity", $config->opacity);
        $this->widget->setParam("color", $config->color);
        $this->widget->setParam("colorHighlite", $config->colorHighlite);
        $this->widget->show(null, true);
    }

    public function onSettingsChanged(Variable $var)
    {
        if ($var->getPluginId() === $this->getId()) {
            $this->showWidget();
        }
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof StaticWidget) {
            $this->widget->erase();
        }
        $this->widget = null;

        parent::eXpOnUnload();
    }
}
