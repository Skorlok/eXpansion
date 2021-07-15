<?php

namespace ManiaLivePlugins\eXpansion\ResetTime;

use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;

class ResetTime extends ExpPlugin
{
    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
    }

    function onBeginMatch()
    {
        $this->config = Config::getInstance();
        $this->connection->setModeScriptSettings(["S_TimeLimit" => $this->config->timelimit]);
    }
}
