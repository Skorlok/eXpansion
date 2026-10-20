<?php
/*
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ManiaLivePlugins\eXpansion\AutoQueue;

use ManiaLive\DedicatedApi\Callback\Event;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\AutoQueue\Classes\Queue;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Core\ColorParser;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

/**
 * Description of AutoQueue
 *
 * @author Reaby, Skorlok
 */
class AutoQueue extends ExpPlugin
{
    /** @var Queue */
    private $queue;
    /** @var Config */
    private $config;
    private $canUnqueue = true;
    private $checkQueue = true;
    protected $fullMatchPlayers = array();

    /** @var Widget */
    private $queueWidget;

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();

        $this->enableDedicatedEvents();
        $this->enableStorageEvents();
        $this->enableTickerEvent();
        $this->disableDedicatedEvents(Event::ON_BEGIN_MAP);
        Dispatcher::register(Event::getClass(), $this, Event::ON_BEGIN_MAP, 20); // Ensure players can join queue before the match starts, avoid staring with seconds late

        $this->queue = new Queue();

        $this->registerManialinkCallback('enterQueue');
        $this->registerManialinkCallback('leaveQueue');
        $this->registerManialinkCallback('admRemoveQueue', false, true);

        $this->queueWidget = new Widget("AutoQueue\Gui\Widgets\AutoQueueWidget.xml");
        $this->queueWidget->setName("AutoQueue");
        $this->queueWidget->setLayer("normal");
        $this->queueWidget->setSize(35, 44.5);
        $this->queueWidget->setPosition($this->config->autoQueue_PosX, $this->config->autoQueue_PosY, 0);

        foreach ($this->storage->spectators as $login => $player) {
            $this->connection->forceSpectator($login, 1);
        }
        foreach ($this->storage->players as $login => $player) {
            $this->fullMatchPlayers[$login] = $player;
        }
        $this->widgetSyncList();

        $this->enableScriptEvents("Maniaplanet.StartRound_Start");
    }

    public function onTick()
    {
        if ($this->checkQueue && $this->canUnqueue && $this->storage->server->currentMaxPlayers > count($this->storage->players)) {

            $nbPlayers = count($this->storage->players);

            while ($this->storage->server->currentMaxPlayers > $nbPlayers && $this->queue->getNbPlayers() > 0) {
                $player = $this->queue->getNextPlayer();
                if ($player) {
                    $this->connection->forceSpectator($player->login, 2);
                    $this->connection->forceSpectator($player->login, 0);
                    $nbPlayers++;
                    $msg = eXpGetMessage('You got free spot, good luck and have fun!');
                    $this->eXpChatSendServerMessage($msg, $player->login);
                    
                    // for debug purposes
                    $msg = eXpGetMessage('$z$s$fffPlayer %s $z$s$fff have been released from queue');
                    $this->eXpChatSendServerMessage($msg, null, array($player->cleanNickName));
                }
                $this->widgetSyncList();
            }

            $this->checkQueue = false;
        }
    }

    public function eXpOnModeScriptCallback($callback, $array)
    {
        switch ($callback) {
            case "Maniaplanet.StartRound_Start":
                $this->onBeginRound();
                break;
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if ($isSpectator) {
            $this->connection->forceSpectator($login, 1);
            $this->widgetSyncList();
        }
    }

    public function onPlayerDisconnect($login, $disconnectionReason = null)
    {
        if (in_array($login, $this->queue->getLogins())) {
            $this->queue->remove($login);
            $this->widgetSyncList();
        }
        $this->checkQueue = true;
    }

    public function onPlayerChangeSide($player, $oldSide)
    {
        if (in_array($player->login, $this->queue->getLogins())) {
            return;
        }

        if ($player->spectator) {
            $this->widgetSyncList();

            try {
                $this->connection->forceSpectator($player->login, 1);
            } catch (\Exception $ex) {}

            if ($player->hasPlayerSlot) {
                try {
                    $this->connection->spectatorReleasePlayerSlot($player->login);
                } catch (\Exception $e) {}
            }

            $this->checkQueue = true;
        } else {
            $this->widgetSyncList();
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->canUnqueue = true;
        $this->onTick(); // Force check queue on map start to avoid players starting later than the match
    }

    public function onBeginMatch()
    {
        $this->canUnqueue = true;
        $this->checkQueue = true;
        foreach ($this->storage->players as $login => $player) {
            $this->fullMatchPlayers[$login] = $player;
        }
    }

    public function onBeginRound()
    {
        $this->checkQueue = true;
    }

    public function admRemoveQueue($login, $target)
    {
        $target = str_replace('–', '-', $target);

        if (AdminGroups::hasPermission($login, Permission::SERVER_ADMIN)) {
            if (in_array($target, $this->queue->getLogins())) {
                $this->queue->remove($target);
                $this->eXpChatSendServerMessage(eXpGetMessage("Admin has removed you from queue!"), $target);
                $this->eXpChatSendServerMessage(eXpGetMessage('Removed player %s $z$ffffrom queue'), $login,  array($this->storage->getPlayerObject($target)->cleanNickName));
                $this->widgetSyncList();
            }
        }
    }

    public function enterQueue($login)
    {
        $player = $this->storage->getPlayerObject($login);
        if ($player->ladderScore < $this->storage->server->ladderServerLimitMin) {
            $msg = eXpGetMessage('You can not join queue, your ladder score is too low!');
            $this->eXpChatSendServerMessage($msg, $login);
            return;
        }
        $this->queue->add($login);

        $this->checkQueue = true;

        $this->widgetSyncList();
    }

    public function leaveQueue($login)
    {
        if (!in_array($login, $this->queue->getLogins())) {
            return;
        }
        $this->queue->remove($login);
        $this->widgetSyncList();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        $this->canUnqueue = false;
        $this->rotatePlayers();
        $this->fullMatchPlayers = array();
    }

    public function rotatePlayers()
    {
        $this->config = Config::getInstance();

        if ($this->config->rotateCount <= 0 || $this->storage->server->currentMaxPlayers > count($this->storage->players)) {
            return;
        }

        $ranking = Core::$rankings;
        if ($this->eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TIMEATTACK) {
            ArrayOfObj::asortDesc($ranking, "bestTime");
        } else {
            ArrayOfObj::asortAsc($ranking, "map_points");
        }

        if ($ranking[0]->bestTime <= 0) {
            return;
        }

        $players = array();
        $i = 0;
        foreach ($ranking as $player) {
            if (!isset($this->storage->player[$player->login]) && !isset($this->fullMatchPlayers[$player->login])) {
                continue;
            }
            if ($i >= $this->config->rotateCount || $this->queue->getNbPlayers() == count($players)) {
                break;
            }
            if (isset($this->fullMatchPlayers[$player->login])) {
                $players[] = $player->login;
            }
            $i++;
        }

        $nickNames = array();
        foreach ($players as $login) {
            $this->connection->forceSpectator($login, 1);
            $nickNames[] = $this->storage->getPlayerObject($login)->cleanNickName;
        }

        if (count($nickNames) > 0) {
            $this->eXpChatSendServerMessage(eXpGetMessage('Rotating players$z %s $z$s$fffto spectator'), null, array(implode('$z, ', $nickNames)));
            $this->checkQueue = true;
        }
    }

    public function widgetSyncList()
    {
        $this->queue->syncPlayers(array_keys($this->storage->players));

        /** @var ColorParser $colorParser */
        $colorParser = ColorParser::getInstance();
        $rankColor   = $colorParser->parseColors("#rank#");

        $queued = $this->queue->getQueuedPlayers();
        $logins = array_keys($queued);

        $items = array();
        $data  = array();
        $i     = 0;
        foreach ($queued as $player) {
            $items[$i] = array($rankColor . ($i + 1) . '.', $player->nickName);
            $data[$i]  = array(-1, -1, 'exp:eXpansion.AutoQueue:admRemoveQueue:' . $player->login);
            $i++;
        }
        $this->queueWidget->setParam("queueItems", $items);
        $this->queueWidget->setParam("queueData", $data);

        // failsafe, players should not be able to see the widget
        $players = array_keys($this->storage->players);
        if (!empty($players)) {
            $this->queueWidget->erase($players);
        }

        foreach ($this->storage->spectators as $login => $player) {
            $inQueue = in_array($login, $logins);

            $this->queueWidget->setParam("hideJoin",   $inQueue);
            $this->queueWidget->setParam("hideLeave",  !$inQueue);
            $this->queueWidget->setParam("hideRemove", !AdminGroups::hasPermission($login, Permission::SERVER_ADMIN));
            $this->queueWidget->show($login);
        }
    }

    public function eXpOnUnload()
    {
        if ($this->queueWidget instanceof Widget) {
            $this->queueWidget->erase();
        }
        $this->queueWidget = null;

        $this->queue = null;
        $this->fullMatchPlayers = array();
    }
}
