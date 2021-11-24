<?php

namespace ManiaLivePlugins\eXpansion\Autotime;

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

    public function onStatusChanged($statusCode, $statusName)
    {
        if ($statusCode == 4) {

            if (!isset($this->connection->getModeScriptSettings()['S_TimeLimit'])) {
                return;
            }

            $this->config = Config::getInstance();

            $map = $this->connection->getCurrentMapInfo();
            $laps = $map->nbLaps;

            if ($map->nbLaps <= 1) {
                $laps = 1;
            }

            if ($this->config->medal !== "author" && $this->config->medal !== "gold" && $this->config->medal !== "silver" && $this->config->medal !== "bronze") {
                $newLimit = floor((intval($map->authorTime)) * floatval($this->config->timelimit_multiplier));
                $this->console("[WARNING] invalid parameter for MEDAL in autotime configuration");
            }
            if ($this->config->medal == "author") {
                $newLimit = floor((intval($map->authorTime)) * floatval($this->config->timelimit_multiplier));
            }
            if ($this->config->medal == "gold") {
                $newLimit = floor((intval($map->goldTime)) * floatval($this->config->timelimit_multiplier));
            }
            if ($this->config->medal == "silver") {
                $newLimit = floor((intval($map->silverTime)) * floatval($this->config->timelimit_multiplier));
            }
            if ($this->config->medal == "bronze") {
                $newLimit = floor((intval($map->bronzeTime)) * floatval($this->config->timelimit_multiplier));
            }

            $max = TimeConversion::MStoTM($this->config->max_timelimit);
            $min = TimeConversion::MStoTM($this->config->min_timelimit);

            if ($newLimit > $max) {
                $newLimit = $max;
            }
            if ($newLimit < $min) {
                $newLimit = $min;
            }
            
            $tatime = $newLimit/1000;

            if ($this->config->message == true){
                $this->eXpChatSendServerMessage('$ff0$iNew time limit: $fff%s $ff0seconds.',null,array(Time::fromTM($newLimit)));
            }
            $this->connection->setModeScriptSettings(["S_TimeLimit" => intval($tatime)]);
        }
    }
}
