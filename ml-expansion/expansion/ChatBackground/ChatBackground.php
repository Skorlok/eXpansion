<?php

namespace ManiaLivePlugins\eXpansion\ChatBackground;

use ManiaLivePlugins\eXpansion\ChatBackground\Gui\Windows\BoxWindow;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;

class ChatBackground extends ExpPlugin
{
    public function eXpOnReady()
    {
        $window = BoxWindow::Create(null);
        $window->show();
    }

    public function onSettingsChanged(Variable $var)
    {
        if ($var->getPluginId() === $this->getId()) {
            BoxWindow::EraseAll();
            $window = BoxWindow::Create(null);
            $window->show();
        }
    }

    public function eXpOnUnload()
    {
        BoxWindow::EraseAll();
        parent::eXpOnUnload();
    }
}
