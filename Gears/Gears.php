<?php

namespace ManiaLivePlugins\eXpansion\Gears;

use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;

class Gears extends ExpPlugin
{
    public function eXpOnReady()
    {
        $this->GearsWindow = Gui\Widgets\GearsWidget::Create(null, true);
        $this->GearsWindow->setSize(60, 12);
        $this->GearsWindow->setPosition(-30, 58);
        $cmd = $this->registerChatCommand("gear", "Show_Gear", 0, true);
        $cmd->help = 'Display gear widget';
    }

    public function Show_Gear($login)
    {
        $this->GearsWindow->show($login);
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();
    }
}
