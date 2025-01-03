<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Times;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;
use ManiaLivePlugins\eXpansion\Widgets_Times\Gui\Widgets\TimePanel;

class Widgets_Times extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    protected $references = array();

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
    }

    public function eXpOnReady()
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\Dedimania\\Dedimania')) {
            Dispatcher::register(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
        }
        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            Dispatcher::register(LocalEvent::getClass(), $this);
            try {
                TimePanel::$localrecords = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");
            } catch (\Exception $e) {
                TimePanel::$localrecords = array();
            }
        }

        $this->registerChatCommand("cpt", "chat_cpt", 1, true);
        $this->showToAll();
    }

    public function chat_cpt($login, $value)
    {
        if (!is_numeric($value)) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#error#"%s" is not a numeric value!'), $login, array($value));
            return;
        }

        if ($value < 1) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#error#"%s" is less than 1!'), $login, array($value));
            return;
        }

        $this->eXpChatSendServerMessage(eXpGetMessage('#info#New time reference point set to %s'), $login, array($value));
        $this->references[$login] = (int)$value;
        $this->showPanel($login, $this->storage->getPlayerObject($login));
    }

    public function onPlayerInfoChanged($playerInfo)
    {
        $player = \Maniaplanet\DedicatedServer\Structures\PlayerInfo::fromArray($playerInfo);
        if ($player) {
            $this->showPanel($player->login, $player);
        }
    }

    public function onBeginMatch()
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            if (empty(TimePanel::$localrecords)) {
                try {
                    TimePanel::$localrecords = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");
                } catch (\Exception $e) {
                    TimePanel::$localrecords = array();
                }
            }
        }
        $this->showToAll();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if (\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        TimePanel::$dedirecords = array();
        TimePanel::$localrecords = array();
    }

    public function showToAll()
    {
        TimePanel::EraseAll();
        foreach ($this->storage->players as $player) {
            $this->showPanel($player->login, $player);
        }

        foreach ($this->storage->spectators as $player) {
            $this->showPanel($player->login, $player);
        }
    }

    public function showPanel($login, $playerObject)
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            TimePanel::$localrecords = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");
        }

        $spectatorTarget = $login;

        if (isset($playerObject->currentTargetId)) {
            if ($playerObject->currentTargetId) {
                $spec = $this->getPlayerObjectById($playerObject->currentTargetId);
                if ($spec->login) {
                    $spectatorTarget = $spec->login;
                }
            }
        }

        TimePanel::Erase($login);
        $info = TimePanel::Create($login);
        $info->setSize(30, 6);
        $info->setPosition(-16, 46);
        if (!$this->expStorage->isRelay) {
            $info->setTarget($spectatorTarget);
        } else {
            $info->setTarget("");
        }
        if (array_key_exists($login, $this->references)) {
            $info->setReference($this->references[$login]);
        }

        $info->setMapInfo($this->storage->currentMap, $this->eXpGetCurrentCompatibilityGameMode(), $this->connection->getModeScriptSettings());
        $info->show();
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->showPanel($login, $this->storage->getPlayerObject($login));
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        TimePanel::Erase($login);
    }

    public function onRecordsLoaded($data)
    {
        TimePanel::$localrecords = $data;
        $this->showToAll();
    }

    public function onUpdateRecords($data)
    {
        TimePanel::$localrecords = $data;
    }

    public function onDedimaniaUpdateRecords($data)
    {
        TimePanel::$dedirecords = $data['Records'];
    }

    public function onDedimaniaGetRecords($data)
    {
        TimePanel::$dedirecords = $data['Records'];
        $this->showToAll();
    }

    public function onRecordPlayerFinished($login)
    {

    }

    public function onDedimaniaOpenSession()
    {

    }

    public function onNewRecord($data)
    {

    }

    public function onRecordDeleted($removedRecord, $records)
    {
        $this->showPanel($removedRecord->login, false);
    }

    public function onPersonalBestRecord($data)
    {
        $this->showPanel($data->login, false);
    }

    public function onDedimaniaPlayerConnect($data)
    {

    }

    public function onDedimaniaPlayerDisconnect($login)
    {

    }

    public function onDedimaniaRecord($record, $oldrecord)
    {
        foreach (TimePanel::$dedirecords as $index => $data) {
            if (TimePanel::$dedirecords[$index]['Login'] == $record->login) {
                TimePanel::$dedirecords[$index] = array(
                    "Login" => $record->login,
                    "NickName" => $record->nickname,
                    "Rank" => $record->place,
                    "Best" => $record->time,
                    "Checks" => $record->checkpoints
                );
            }
        }
        $this->showPanel($record->login, false);
    }

    public function onDedimaniaNewRecord($record)
    {
        foreach (TimePanel::$dedirecords as $index => $data) {
            if (TimePanel::$dedirecords[$index]['Login'] == $record->login) {
                TimePanel::$dedirecords[$index] = array(
                    "Login" => $record->login,
                    "NickName" => $record->nickname,
                    "Rank" => $record->place,
                    "Best" => $record->time,
                    "Checks" => $record->checkpoints
                );
            }
        }
        $this->showPanel($record->login, false);
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
        Dispatcher::unregister(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
        Dispatcher::unregister(LocalEvent::getClass(), $this);
        TimePanel::EraseAll();
    }
}
