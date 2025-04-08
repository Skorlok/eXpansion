<?php

namespace ManiaLivePlugins\eXpansion\TM_HiddenLocalRecords;

use ManiaLivePlugins\eXpansion\Core\Core;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\LocalRecords\LocalBase;
use ManiaLivePlugins\eXpansion\LocalRecords\Gui\Windows\Records;

class TM_HiddenLocalRecords extends LocalBase
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
            if (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS && !$this->config->lapsModeCountAllLaps) {
                return;
            }

            $playerinfo = Core::$playerInfo;

            $time = microtime(true);
            //We add the record to the buffer
            if (isset($playerinfo[$login]) && $timeOrScore < 8388608) {
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
        if ((($this->config->lapsModeCountAllLaps && self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS) || $this->storage->getCleanGamemodeName() == "endurocup") && isset($this->storage->players[$player->login]) && $time > 0 && $time < 8388608) {

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
        if (is_string($score)) {
            return $score;
        }
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

                if (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS && $this->config->lapsModeCountAllLaps) {
                    return 1;
                }

                return ($scriptSettings['S_ForceLapsNb']);
            } else {
                $gamemode = self::eXpGetCurrentCompatibilityGameMode();

                if ($gamemode == GameInfos::GAMEMODE_LAPS) {
                    if ($this->config->lapsModeCountAllLaps) {
                        return 1;
                    } else {
                        return ($this->storage->currentMap->nbLaps);
                    }
                }

                if ($this->storage->getCleanGamemodeName() == "endurocup") {
                    return 1; // Endurance is consired as cup so let this above Cup check
                }

                if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_CUP || $gamemode == GameInfos::GAMEMODE_TEAM) {
                    return ($this->storage->currentMap->nbLaps);
                }
                
                
                return 1;
            }

        } else {
            return 1;
        }
    }

    // override these methods to prevent the local records from being shown

    public function showRecsWindow($login, $map = null)
    {
        Records::Erase($login);
        if ($map === null) {
            $records = $this->currentChallengeRecords;
            $map = $this->storage->currentMap;
        } else {
            $records = $this->getRecordsForMap($map);
        }
        $currentMap = false;
        if ($map == null || $map->uId == $this->storage->currentMap->uId) {
            $currentMap = true;
        }

        $hiddenRecords = $records;
        foreach ($hiddenRecords as $key => $record) {
            if ($record->login != $login) {
                $hiddenRecords[$key]->time = 'HIDDEN';
                $hiddenRecords[$key]->avgScore = 'HIDDEN';
                $hiddenRecords[$key]->nbFinish = 'HIDDEN';
                $hiddenRecords[$key]->ScoreCheckpoints = array();
            }
        }

        $window = Records::Create($login);
        /** @var Records $window */
        $window->setTitle(__('Records on a Map', $login));
        $window->centerOnScreen();
        $window->setSize(180, 100);
        $window->populateList($hiddenRecords, $this->config->recordsCount, $currentMap, $this);
        $window->show();
    }

    public function showCpWindow($login)
    {
        $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
    }

    public function showSecCpWindow($login)
    {
        $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
    }

    public function showCpDiffWindow($login, $params)
    {
        $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
    }

    public function showCpDiffNoDediWindow($login, $params)
    {
        $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
    }

    public function showSectorWindow($login)
    {
        $this->eXpChatSendServerMessage("#admin_error#Seeing other records is disable!", $login);
    }
}
