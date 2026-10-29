<?php

namespace ManiaLivePlugins\eXpansion\Overlay_Positions;

use ManiaLive\Utilities\Time;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\Structures\ExpPlayer;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

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

    /** @var Widget */
    private $widget;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();

        $this->widget = new Widget('Overlay_Positions\Gui\Widgets\PositionPanel.xml');
        $this->widget->setName("Position Panel");
        $this->widget->setLayer("normal");
        $this->widget->setSize(80, 70);
        $this->widget->setPosition(-158, 40, -60);

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
        $this->widget->erase();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->isPodium = false;
    }

    public function hideWidget($login)
    {
        $this->widget->erase($login);
    }

    public function showWidget($login)
    {
        $teamMaxPoint = 10;
        if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_SCRIPT) {
            $settings = $this->connection->getModeScriptSettings();
            if (array_key_exists("S_MaxPointsPerRound", $settings)) {
                $teamMaxPoint = $settings['S_MaxPointsPerRound'];
            }
        } else {
            $teamMaxPoint = $this->storage->gameInfos->teamPointsLimit;
        }

        $gamemode = self::eXpGetCurrentCompatibilityGameMode();
        $players = Core::$playerInfo;

        // total number of playing players, capped to the points at stake
        $total = 0;
        foreach ($players as $player) {
            if ($player->isPlaying) {
                $total++;
            }
        }
        if ($total > $teamMaxPoint) {
            $total = $teamMaxPoint;
        }

        $rows = array();
        $teamPoints = array(0 => 0, 1 => 0);
        $cpCount = 0;
        $x = 0;
        $posY = 0;

        foreach ($players as $player) {
            if (!$player->isPlaying) {
                continue;
            }

            $cpindex = $player->curCpIndex;
            if ($cpindex < 0) {
                $cpindex = 0;
            }
            $cpCount += $cpindex;

            if ($player->teamId >= 0 && !$player->hasRetired) {
                $points = ($total + 1) - ($player->position + 1);
                if ($points < 0) {
                    $points = 0;
                }
                $teamPoints[$player->teamId] += $points;
            }

            if ($x < 15) {
                $row = $this->buildRow($player, $login, $gamemode);
                $row['posY'] = $posY;
                $rows[] = $row;
                $posY -= 4.5;
            }
            $x++;
        }

        $this->widget->setParam("rows", $rows);
        $this->widget->setParam("teamScore", $this->getTeamScore($gamemode, $teamPoints, $cpCount));
        $this->widget->show($login);
    }

    /**
     * @param ExpPlayer $player
     * @param string $login the player the panel is built for, he gets his own color
     * @param int $gamemode
     *
     * @return array one row of the panel, every cell ready to be written in the template
     */
    private function buildRow(ExpPlayer $player, $login, $gamemode)
    {
        $color = 'eee';
        if ($player->teamId === 0) {
            $color = '2cf';
        }
        if ($player->teamId === 1) {
            $color = 'f22';
        }
        if ($player->login == $login) {
            $color = '5d5';
        }
        if ($player->teamId === 0 && $player->login == $login) {
            $color = "0bf";
        }
        if ($player->teamId === 1 && $player->login == $login) {
            $color = "f00";
        }
        if ($player->hasRetired && !$player->isFinished) {
            $color = '999';
        }

        $nickname = $player->nickName;
        if ($player->hasRetired) {
            $nickname = Formatting::stripColors($player->nickName);
        }

        $time = $this->stripLeadingZeros(Time::fromTM($player->time));
        if ($player->hasRetired && !$player->isFinished) {
            $time = "";
        }

        $diff = "+" . $this->stripLeadingZeros(Time::fromTM($player->deltaTimeTop1));
        if ($player->deltaCpCountTop1 > 0) {
            $diff = "+" . $player->deltaCpCountTop1 . "cp";
        }
        if ($player->hasRetired && !$player->isFinished) {
            $diff = "Out";
        }
        if ($player->deltaTimeTop1 == -1) {
            $diff = "Err";
        }

        $score = ($gamemode == GameInfos::GAMEMODE_TEAM) ? $player->matchScore : $player->score;
        if (empty($score)) {
            $score = 0;
        }
        $points = ($gamemode == GameInfos::GAMEMODE_LAPS) ? "lap " . $player->curLap : $score . " pts";

        return array(
            'color'  => $color,
            'index'  => ($player->position + 1) . ".",
            'nick'   => $this->widget->handleSpecialChars($nickname),
            'time'   => $this->widget->handleSpecialChars($time),
            'diff'   => $this->widget->handleSpecialChars($diff),
            'points' => $this->widget->handleSpecialChars($points),
        );
    }

    private function stripLeadingZeros($time)
    {
        if (substr($time, 0, 3) === "0:0") {
            return substr($time, 3);
        }
        if (substr($time, 0, 2) === "0:") {
            return substr($time, 2);
        }

        return $time;
    }

    private function getTeamScore($gamemode, $teamPoints, $cpCount)
    {
        if ($gamemode != GameInfos::GAMEMODE_TEAM) {
            return "";
        }

        if ($teamPoints[0] == $teamPoints[1] || $cpCount == 0) {
            return '$fffTeams Score is Draw';
        }
        if ($teamPoints[1] < $teamPoints[0]) {
            return '$00fTeam Blue is Winning  $fff' . $teamPoints[0] . " vs " . $teamPoints[1];
        }

        return '$f00Team Red is Winning $fff' . $teamPoints[1] . "vs" . $teamPoints[0];
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

        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->widget = null;
    }
}
