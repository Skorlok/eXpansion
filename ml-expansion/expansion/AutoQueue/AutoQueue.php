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
use Maniaplanet\DedicatedServer\Structures\PlayerInfo;
use Maniaplanet\DedicatedServer\Structures\Status;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

/**
 * Description of AutoQueue
 *
 * @author Reaby
 */
class AutoQueue extends ExpPlugin
{
    /** @var Queue */
    private $queue;
    public static $enterAction;
    public static $leaveAction;
    private $config;
    protected $fullMatchPlayers = array();

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->queue = new Queue();

        $ah = ActionHandler::getInstance();
        self::$enterAction = $ah->createAction(array($this, "enterQueue"));
        self::$leaveAction = $ah->createAction(array($this, "leaveQueue"));

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

    public function onPlayerInfoChanged($info)
    {
        if ($this->storage->serverStatus->code != Status::PLAY) {
            return;
        }

        $player = PlayerInfo::fromArray($info);
        $login = $player->login;

        if (in_array($login, $this->queue->getLogins())) {
            return;
        }

        if ($player->spectator) {
            $this->showEnterQueue($login);
            $this->widgetSyncList();

            try {
                $this->connection->forceSpectator($login, 1);
            } catch (\Exception $ex) {

            }
            if ($player->hasPlayerSlot) {
                try {
                    $this->connection->spectatorReleasePlayerSlot($login);
                } catch (\Exception $e) {

                }
            }

            if ($this->storage->server->currentMaxPlayers > count($this->storage->players)) {
                $this->queueReleaseNext();
            }
        } else {
            $this->widgetSyncList();
            EnterQueueWidget::Erase($login);
        }
    }

    public function onPlayerDisconnect($login, $disconnectionReason = null)
    {
        if (in_array($login, $this->queue->getLogins())) {
            $this->queue->remove($login);
        }
        $this->queueReleaseNext();
    }

    public function onBeginMatch()
    {
        $this->queRealeseAvailable();
        foreach ($this->storage->players as $login => $player) {
            $this->fullMatchPlayers[$login] = $player;
        }
    }

    public function onBeginRound()
    {
        $this->queRealeseAvailable();
    }

    public function queRealeseAvailable()
    {
        for ($i = 0; $i < $this->storage->server->currentMaxPlayers; $i++) {
            $this->queueReleaseNext();
        }
    }

    public function queueReleaseNext()
    {
        if (count($this->storage->players) < $this->storage->server->currentMaxPlayers) {
            $player = $this->queue->getNextPlayer();
            if ($player) {
                $this->connection->forceSpectator($player->login, 2);
                $this->connection->forceSpectator($player->login, 0);
                $msg = eXpGetMessage('You got free spot, good luck and have fun!');
                $this->eXpChatSendServerMessage($msg, $player->login);
            }
        }
        $this->widgetSyncList();
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
        $this->queue->add($login);

        if ($this->storage->server->currentMaxPlayers > count($this->storage->players)) {
            $this->queueReleaseNext();
        }

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
        $this->rotatePlayers();
        $this->fullMatchPlayers = array();
    }

    public function rotatePlayers()
    {
        $this->config = Config::getInstance();

        if ($this->config->rotateCount <= 0 || $this->storage->server->currentMaxPlayers > count($this->storage->players)) {
            if (isset($this->storage->players['skorlok']) || isset($this->storage->spectators['skorlok'])) {
                $this->eXpChatSendServerMessage('Server not full, no need to rotate players', 'skorlok');
            }
            return;
        }

        $ranking = Core::$rankings;
        $ranking = $this->removeSpecs($ranking);
        $this->sortDesc($ranking);

        $players = array();
        $i = 0;
        if (isset($this->storage->players['skorlok']) || isset($this->storage->spectators['skorlok'])) {
            $this->eXpChatSendServerMessage('number of players in queue: ' . $this->queue->getNbPlayers(), 'skorlok');
        }
        foreach ($ranking as $player) {
            if ($i >= $this->config->rotateCount || $this->queue->getNbPlayers() == count($players)) {
                break;
            }
            if (isset($this->storage->players['skorlok']) || isset($this->storage->spectators['skorlok'])) {
                $this->eXpChatSendServerMessage('Checking if login: ' . $player->login . ' is rotatable', 'skorlok');
            }
            if (isset($this->fullMatchPlayers[$player->login])) {
                if (isset($this->storage->players['skorlok']) || isset($this->storage->spectators['skorlok'])) {
                    $this->eXpChatSendServerMessage($player->login . ' is rotatable, adding to list', 'skorlok');
                }
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

    public function sortDesc(&$array)
    {
        if ($this->eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TIMEATTACK) {
            ArrayOfObj::asortDesc($array, "best_race_time");
        } else {
            ArrayOfObj::asortAsc($array, "map_points");
        }
    }

    public function removeSpecs($array)
    {
        $arr = array();
        foreach ($array as $player) {
            if (isset($this->storage->players[$player->login])) {
                $arr[] = $player;
            }
        }
        return $arr;
    }

    public function eXpOnUnload()
    {
        $ah = ActionHandler::getInstance();
        $ah->deleteAction(self::$enterAction);
        $ah->deleteAction(self::$leaveAction);
        self::$enterAction = null;
        self::$leaveAction = null;
        EnterQueueWidget::EraseAll();
        QueueList::EraseAll();
        $this->queue = null;
        $this->fullMatchPlayers = array();
    }

    public function widgetSyncList()
    {
        $this->queue->syncPlayers(array_keys($this->storage->players));

        QueueList::EraseAll();

        foreach ($this->storage->spectators as $login => $player) {
            $widget = QueueList::Create($login);
            $widget->setPlayers($this->queue->getQueuedPlayers(), $this);
            $widget->show();
        }
    }

    public function showEnterQueue($login)
    {
        $widget = EnterQueueWidget::Create($login);
        $widget->show($login);
    }
}
