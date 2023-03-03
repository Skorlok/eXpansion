<?php
/**
 * @author       Oliver de Cramer (oliverde8 at gmail.com)
 * @copyright    GNU GENERAL PUBLIC LICENSE
 *                     Version 3, 29 June 2007
 *
 * PHP version 5.3 and above
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see {http://www.gnu.org/licenses/}.
 */

namespace ManiaLivePlugins\eXpansion\LocalRecords;

use ManiaLivePlugins\eXpansion\Core\Core;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Endurance\Endurance;

class LocalRecords extends LocalBase
{
    /**
     * onPlayerFinish()
     * Function called when a player finishes.
     *
     * @param int $playerUid
     * @param string $login
     * @param int $timeOrScore
     *
     * @return void
     */
    public function onPlayerFinish($playerUid, $login, $timeOrScore)
    {
        //Checking for valid time
        if (isset($this->storage->players[$login]) && $timeOrScore > 0) {
            
            //If laps mode we need to ignore. Laps has it's own end map event(end finish lap)
            //Laps mode has it own on Player finish event
            if (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS && $this->config->lapsModeCountAllLaps) {
                return;
            }

            $playerinfo = Core::$playerInfo;

            $time = microtime(true);
            //We add the record to the buffer
            if (isset($playerinfo[$login])) {
                $this->addRecord($login, $timeOrScore, $playerinfo[$login]->checkpoints);
            }

            $this->debug("#### NEW RANK IN : " . (microtime(true) - $time) . "s BAD?");
        }
        parent::onPlayerFinish($playerUid, $login, $timeOrScore);
    }

    /**
     * @param \ManiaLive\Data\Player $player
     * @param                        $time
     * @param                        $checkpoints
     * @param int $nbLap
     */
    public function onPlayerFinishLap($player, $time, $checkpoints, $nbLap)
    {
        if (($this->config->lapsModeCountAllLaps || (Endurance::$enduro || self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS)) && isset($this->storage->players[$player->login]) && $time > 0) {

            // if normal map, don't trigger the event for first lap :)
            if ($this->storage->currentMap->nbLaps == 0) {
                //  $this->console("mapNbLaps: " . $this->storage->currentMap->nbLaps);
                return;
            }

            $this->addRecord($player->login, $time, $checkpoints);
        }
        parent::onPlayerFinishLap($player, $time, $checkpoints, $nbLap);
    }

    /**
     * @param string $login
     * @param bool $isSpectator
     */
    public function onPlayerConnect($login, $isSpectator)
    {
        parent::onPlayerConnect($login, $isSpectator);
    }

    /**
     * @param string $login
     * @param null $reason
     */
    public function onPlayerDisconnect($login, $reason = null)
    {
        parent::onPlayerDisconnect($login, $reason);
    }

    /**
     * @return string
     */
    protected function getScoreType()
    {
        return self::SCORE_TYPE_TIME;
    }

    /**
     * @param $score
     *
     * @return float|int|number|string
     */
    public function formatScore($score)
    {
        $time = \ManiaLive\Utilities\Time::fromTM($score);
        if (substr($time, 0, 2) === "0:") {
            $time = substr($time, 2);
        }

        return $time;
    }

    /**
     * @param $newTime
     * @param $oldTime
     *
     * @return bool
     */
    protected function isBetterTime($newTime, $oldTime)
    {
        return $newTime <= $oldTime;
    }

    /**
     * @param $newTime
     * @param $oldTime
     *
     * @return float|int|number|string
     */
    protected function secureBy($newTime, $oldTime)
    {
        $securedBy = \ManiaLive\Utilities\Time::fromTM($newTime - $oldTime);
        if (substr($securedBy, 0, 3) === "0:0") {
            $securedBy = substr($securedBy, 3);
        } else {
            if (substr($securedBy, 0, 2) === "0:") {
                $securedBy = substr($securedBy, 2);
            }
        }

        return $securedBy;
    }

    /**
     * @return string
     */
    protected function getDbOrderCriteria()
    {
        return '`record_score` ASC, `record_date` ASC ';
    }

    /**
     * getNbOfLaps()
     * Helper function, gets number of laps.
     *
     * @return int $laps
     */
    public function getNbOfLaps()
    {
        if ($this->storage->currentMap->lapRace) {

            $scriptSettings = $this->connection->getModeScriptSettings();
            
            if (isset($scriptSettings['S_ForceLapsNb']) && $scriptSettings['S_ForceLapsNb'] > 0) {
                return ($scriptSettings['S_ForceLapsNb']);
            } else {
                if (Endurance::$enduro || (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS && $this->config->lapsModeCountAllLaps)) {
                    return 1;
                }
                return ($this->storage->currentMap->nbLaps);
            }

        } else {
            return 1;
        }
    }
}
