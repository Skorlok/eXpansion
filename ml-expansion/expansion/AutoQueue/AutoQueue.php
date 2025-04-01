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

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\AutoQueue\Classes\Queue;
use ManiaLivePlugins\eXpansion\AutoQueue\Gui\Widgets\EnterQueueWidget;
use ManiaLivePlugins\eXpansion\AutoQueue\Gui\Widgets\QueueList;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
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
    public static $enterAction;
    public static $leaveAction;
    public static $showEnterQueueAction;
    protected $fullMatchPlayers = array();

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();

        $this->enableDedicatedEvents();
        $this->enableStorageEvents();
        $this->enableTickerEvent();

        $this->queue = new Queue();

        /** @var ActionHandler $ah */
        $ah = ActionHandler::getInstance();
        self::$enterAction = $ah->createAction(array($this, "enterQueue"));
        self::$leaveAction = $ah->createAction(array($this, "leaveQueue"));
        self::$showEnterQueueAction = $ah->createAction(array($this, "showEnterQueue"));

        foreach ($this->storage->spectators as $login => $player) {
            $this->connection->forceSpectator($login, 1);
            $this->showEnterQueue($login);
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
                $this->onBeginRound(0);
                break;
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if ($isSpectator) {
            $this->connection->forceSpectator($login, 1);
            $this->showEnterQueue($login);
            $this->widgetSyncList();
        }
    }

    public function onPlayerDisconnect($login, $disconnectionReason = null)
    {
        if (in_array($login, $this->queue->getLogins())) {
            $this->queue->remove($login);
        }
        $this->widgetSyncList();
        $this->checkQueue = true;
    }

    public function onPlayerChangeSide($player, $oldSide)
    {
        if (in_array($player->login, $this->queue->getLogins())) {
            return;
        }

        if ($player->spectator) {
            $this->showEnterQueue($player->login);
            $this->widgetSyncList();

            try {
                $this->connection->forceSpectator($player->login, 1);
            } catch (\Exception $ex) {

            }
            if ($player->hasPlayerSlot) {
                try {
                    $this->connection->spectatorReleasePlayerSlot($player->login);
                } catch (\Exception $e) {

                }
            }

            $this->checkQueue = true;
        } else {
            $this->widgetSyncList();
            EnterQueueWidget::Erase($player->login);
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->canUnqueue = true;
    }

    public function onBeginMatch()
    {
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
        if (AdminGroups::hasPermission($login, Permission::SERVER_ADMIN)) {
            if (in_array($target, $this->queue->getLogins())) {
                $this->queue->remove($target);
                $this->eXpChatSendServerMessage(eXpGetMessage("Admin has removed you from queue!", $target));
                $this->eXpChatSendServerMessage(eXpGetMessage('Removed player %s $z$ffffrom queue'), $login,  array($this->storage->getPlayerObject($target)->nickName));
            }
        }
        EnterQueueWidget::Erase($login);
        $this->widgetSyncList();
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

        EnterQueueWidget::Erase($login);
        $this->widgetSyncList();
    }

    public function leaveQueue($login)
    {
        $this->queue->remove($login);
        $this->showEnterQueue($login);
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
            $nickNames[] = $this->storage->getPlayerObject($login)->nickName;
        }

        if (count($nickNames) > 0) {
            $this->eXpChatSendServerMessage(eXpGetMessage('Rotating players$z %s $z$s$fffto spectator'), null, array(implode('$z, ', $nickNames)));
        }
    }

    public function widgetSyncList()
    {
        $this->queue->syncPlayers(array_keys($this->storage->players));

        QueueList::EraseAll();

        foreach ($this->storage->spectators as $login => $player) {
            /** @var QueueList $widget */
            $widget = QueueList::Create($login);
            $widget->setPosition($this->config->queueList_PosX, $this->config->queueList_PosY);
            $widget->setPlayers($this->queue->getQueuedPlayers(), $this);
            $widget->show();
        }
    }

    public function showEnterQueue($login)
    {
        $player = $this->storage->getPlayerObject($login);
        if ($player && $player->ladderScore < $this->storage->server->ladderServerLimitMin) {
            return;
        }
        $widget = EnterQueueWidget::Create($login);
        $widget->setPosition($this->config->enterQueueList_PosX, $this->config->enterQueueList_PosY);
        $widget->show($login);
    }

    public function eXpOnUnload()
    {
        /** @var ActionHandler $ah */
        $ah = ActionHandler::getInstance();
        $ah->deleteAction(self::$enterAction);
        $ah->deleteAction(self::$leaveAction);
        $ah->deleteAction(self::$showEnterQueueAction);

        self::$enterAction = null;
        self::$leaveAction = null;
        EnterQueueWidget::EraseAll();
        QueueList::EraseAll();
        $this->queue = null;
        $this->fullMatchPlayers = array();
    }
}
