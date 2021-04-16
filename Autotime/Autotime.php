<?php

namespace ManiaLivePlugins\eXpansion\Autotime;

use ManiaLive\Gui\Window;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;

use ManiaLive\Utilities\Time;
use ManiaLivePlugins\eXpansion\AdminGroups\types\Time_ms;
use ManiaLivePlugins\eXpansion\Helpers\TimeConversion;

class Autotime extends ExpPlugin
{

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
    }

    function onBeginMatch()
    {
        $map = $this->connection->getCurrentMapInfo();
            $laps = $map->nbLaps;
            if ($map->nbLaps <= 1) {
                $laps = 1;
            }

            $newLimit = floor((intval($map->bronzeTime)) * floatval($this->config->timelimit_multiplier));

            $max = TimeConversion::MStoTM(Config::getInstance()->max_timelimit);
            $min = TimeConversion::MStoTM(Config::getInstance()->min_timelimit);

            if ($newLimit > $max) {
                $newLimit = $max;
            }
            if ($newLimit < $min) {
                $newLimit = $min;
            }
            
            $tatime = $newLimit/1000;

            if ($this->config->message == true){
                $this->eXpChatSendServerMessage('$ff0$iNew time limit: $fff' . Time::fromTM($newLimit) . ' $ff0seconds.');
            }
            $this->connection->setModeScriptSettings(["S_TimeLimit" => intval($tatime)]);
    }

    public function eXpOnUnload()
    {
        $this->connection->setModeScriptSettings(["S_TimeLimit" => intval(TimeConversion::MStoTM(Config::getInstance()->timelimit) / 1000)]);
        $this->eXpChatSendServerMessage('$ff0$iTimeLimit reset to: ' . $this->config->timelimit . ' seconds.');
    }
}
