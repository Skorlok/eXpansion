<?php

namespace ManiaLivePlugins\eXpansion\Overlay_Positions;

use \ManiaLivePlugins\eXpansion\Core\Structures\ExpPlayer;

/**
 * Description of Overlay_Positions
 *
 * @author Reaby
 */
class Overlay_Positions extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin {

    private $update = true;

    function exp_onInit() {
        $this->exp_addGameModeCompability(\DedicatedApi\Structures\GameInfos::GAMEMODE_ROUNDS);
        $this->exp_addGameModeCompability(\DedicatedApi\Structures\GameInfos::GAMEMODE_TEAM);
        $this->exp_addGameModeCompability(\DedicatedApi\Structures\GameInfos::GAMEMODE_LAPS);
        $this->exp_addGameModeCompability(\DedicatedApi\Structures\GameInfos::GAMEMODE_CUP);
    }

    public function exp_onReady() {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();
    }

    public function onTick() {
        if ($this->update) {
            $this->update = false;
            foreach ($this->storage->players as $login => $player) {
                $this->showWidget($login);
            }
            foreach ($this->storage->spectators as $login => $player) {
                $this->showWidget($login);
            }
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap) {
        $this->hideWidget();
    }

    public function hideWidget() {
        Gui\Widgets\PositionPanel::EraseAll();
    }

    public function showWidget($login) {
        $pospanel = Gui\Widgets\PositionPanel::Create($login);
        $pospanel->setSize(80, 90);
        $pospanel->setPosition(-158, 20);
        $pospanel->setData(\ManiaLivePlugins\eXpansion\Core\Core::$playerInfo);
        $pospanel->show();
    }

    public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {
        $this->update = true;
    }

    public function onPlayerFinish($playerUid, $login, $timeOrScore) {
        $this->update = true;
    }

    public function onPlayerGiveup(ExpPlayer $player) {
        $this->update = true;
    }

    public function onPlayerDisconnect($login, $disconnectionReason = null) {
        $this->update = true;
    }

}
