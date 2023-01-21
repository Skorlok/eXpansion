<?php

namespace ManiaLivePlugins\eXpansion\Overlay_Positions;

use ManiaLivePlugins\eXpansion\Core\Structures\ExpPlayer;

/**
 * Description of Overlay_Positions
 *
 * @author Reaby
 */
class Overlay_Positions extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $update = false;
    private $retiredPlayers = array();
    private $isPodium = false;
    private $wasWarmup = false;

    public function eXpOnInit()
    {
        $this->exp_addTitleSupport("TM");
        $this->exp_addTitleSupport("Trackmania");
        $this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_ROUNDS);
        $this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TEAM);
        $this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_LAPS);
        $this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);
        $this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_SCRIPT);
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();
        $this->update = true;
    }

    public function onTick()
    {
        if ($this->update) {
            $this->update = false;
            if ($this->isPodium) {
                return;
            }

            foreach ($this->retiredPlayers as $login => $player) {
                $this->showWidget($login);
            }
            foreach ($this->storage->spectators as $login => $player) {
                $this->showWidget($login);
            }
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->wasWarmup) {
            return;
        }

        $this->isPodium = true;
        $this->update = false;
        $this->retiredPlayers = array();
        Gui\Widgets\PositionPanel::EraseAll();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->isPodium = false;
    }

    public function hideWidget($login)
    {
        Gui\Widgets\PositionPanel::Erase($login);
    }

    public function showWidget($login)
    {
        $pospanel = Gui\Widgets\PositionPanel::Create($login);
        $pospanel->setSize(80, 90);
        $pospanel->setPosition(-158, 40);

        $teamMaxPoint = 10;
        if ($this->storage->gameInfos->gameMode == \Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_SCRIPT) {
            $settings = $this->connection->getModeScriptSettings();
            if (array_key_exists("S_MaxPointsPerRound", $settings)) {
                $teamMaxPoint = $settings['S_MaxPointsPerRound'];
            }
        } else {
            $teamMaxPoint = $this->storage->gameInfos->teamPointsLimit;
        }

        $pospanel->setData(
            \ManiaLivePlugins\eXpansion\Core\Core::$playerInfo,
            self::eXpGetCurrentCompatibilityGameMode(),
            $teamMaxPoint
        );
        $pospanel->setScale(1.1);
        $pospanel->show();
    }

    public function onPlayerInfoChanged($playerInfo)
    {
        if ($this->storage->serverStatus->code != 4) {
            return;
        }

        $player = \Maniaplanet\DedicatedServer\Structures\PlayerInfo::fromArray($playerInfo);
        // hide widget for players who change from spectate to play
        // on team mode, show infos when player is finished

        if ($player->temporarySpectator == 1) {
            $this->retiredPlayers[$player->login] = $this->storage->getPlayerObject($player->login);
        } else {
            if (array_key_exists($player->login, $this->retiredPlayers)) {
                unset($this->retiredPlayers[$player->login]);
            }
        }

        $this->update = true;
    }

    public function onBeginRound()
    {
        $this->retiredPlayers = array();
        $this->wasWarmup = $this->connection->getWarmUp();
    }

    public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
    {
        $this->update = true;
    }

    public function onPlayerFinish($playerUid, $login, $timeOrScore)
    {
        // on first thing when a round or match begins, onPlayerFinish is triggered for server login
        // so hide the widget for players
        if ($playerUid == 0) {
            foreach ($this->retiredPlayers as $login => $player) {
                $this->hideWidget($login);
            }
            $this->retiredPlayers = array();

            foreach ($this->storage->players as $login => $player) {
                $this->hideWidget($login);
            }
        }

        if (!$this->storage->currentMap->lapRace) {
            // display widget to finished player
            if ($timeOrScore > 0) {
                $this->retiredPlayers[$login] = $this->storage->getPlayerObject($login);
            }
            $this->update = true;
        }
    }

    public function onPlayerGiveup(ExpPlayer $player)
    {
        $this->update = true;
        $this->retiredPlayers[$player->login] = $player;
    }

    public function onPlayerDisconnect($login, $disconnectionReason = null)
    {
        $this->update = true;
    }

    public function eXpOnUnload()
    {
        $this->disableTickerEvent();
    }
}
