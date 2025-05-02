<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Times;

use ManiaLive\Event\Dispatcher;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Endurance\Endurance;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;

class Widgets_Times extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    protected $references = array();
    private $config;

    // widget
    protected $totalCp = 0;
    protected $lapRace = false;
    protected $localrecords = array();
    protected $dedirecords = array();

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
    }

    public function eXpOnReady()
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\Dedimania\\Dedimania')) {
            Dispatcher::register(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
        }
        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            Dispatcher::register(LocalEvent::getClass(), $this);
            $this->getRecords();
        }

        $this->setMapInfo();
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
        $this->setMapInfo();
        $this->getRecords();
        $this->showToAll();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && Endurance::$last_round == false) {
            return;
        }
        $this->dedirecords = array();
        $this->localrecords = array();
    }

    public function getRecords()
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            if (empty($this->localrecords)) {
                try {
                    $this->localrecords = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");
                } catch (\Exception $e) {
                    $this->localrecords = array();
                }
            }
        }
    }

    public function setMapInfo()
    {
        $map = $this->storage->currentMap;
        $gamemode = $this->eXpGetCurrentCompatibilityGameMode();

        if ($this->storage->getCleanGamemodeName() == "endurocup") {
            $gamemode = GameInfos::GAMEMODE_LAPS;
        }
        if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP) {

            if ($map->lapRace) {
                $scriptSettings = $this->connection->getModeScriptSettings();

                $this->lapRace = 2;

                if (array_key_exists("S_ForceLapsNb", $scriptSettings)) {
                    if ($scriptSettings['S_ForceLapsNb'] > 0) {
                        $this->totalCp = $map->nbCheckpoints * $scriptSettings['S_ForceLapsNb'];
                    } else {
                        $this->totalCp = $map->nbCheckpoints * $map->nbLaps;
                    }
                } else {
                    $this->totalCp = $map->nbCheckpoints * $map->nbLaps;
                }

            } else {
                $this->totalCp = $map->nbCheckpoints;
                $this->lapRace = 0;
            }

        } else {
            if ($map->lapRace) {
                $this->lapRace = 1;
            } else {
                $this->lapRace = 0;
            }
            $this->totalCp = $map->nbCheckpoints;
        }
    }

    public function showToAll()
    {
        foreach ($this->storage->players as $player) {
            $this->showPanel($player->login, $player);
        }

        foreach ($this->storage->spectators as $player) {
            $this->showPanel($player->login, $player);
        }
    }

    public function getScript($reference, $target, $update = false)
    {
        $playerRecord = \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::getObjbyPropValue($this->localrecords, "login", $target);
        $drecord = array_search($target, array_column($this->dedirecords, 'Login'));

        $record = false;

        // Now check for the PB in dedi and local records
        if ($drecord) {
            if ($playerRecord) {
                if ($playerRecord->time <= $this->dedirecords[$drecord]['Best']) {
                    $record = $playerRecord;
                } else {
                    $record = new \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record();
                    $record->place = $this->dedirecords[$drecord]['Rank'];
                    $record->time = $this->dedirecords[$drecord]['Best'];
                    $record->nickName = $this->dedirecords[$drecord]['NickName'];
                    $record->ScoreCheckpoints = explode(",", $this->dedirecords[$drecord]['Checks']);
                }
            } else {
                $record = new \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record();
                $record->place = $this->dedirecords[$drecord]['Rank'];
                $record->time = $this->dedirecords[$drecord]['Best'];
                $record->nickName = $this->dedirecords[$drecord]['NickName'];
                $record->ScoreCheckpoints = explode(",", $this->dedirecords[$drecord]['Checks']);
            }
        } else {
            $record = $playerRecord;
        }

        $checkpoints = "[ -1 ]";

        if ($record instanceof \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record) {
            // Normally all CP even last one should be in the object,
            // but not in databases imported from XAseco where last CP is missing.
            if ($record->ScoreCheckpoints[count($record->ScoreCheckpoints) - 1] != $record->time) {
                $checkpoints = "[" . implode(",", $record->ScoreCheckpoints) . ", " . $record->time . "]";
            } else {
                $checkpoints = "[" . implode(",", $record->ScoreCheckpoints) . "]";
            }
        } else {
            $checkpoints = '[';
            for ($i = 0; $i < $this->totalCp; $i++) {
                if ($i > 0) {
                    $checkpoints .= ', ';
                }
                $checkpoints .= -1;
            }
            $checkpoints .= ']';
        }

        // Send data for the dedimania records.
        $dediTime = "";
        if (sizeof($this->dedirecords) > 0) {
            if (isset($this->dedirecords[$reference - 1])) {
                $record = $this->dedirecords[$reference - 1];
            } else {
                $record = $this->dedirecords[0];
                $reference = 1;
            }
            $dediTime = '[' . $record['Checks'] . ']';
        } else {
            $dediTime = '[';
            for ($i = 0; $i < $this->totalCp; $i++) {
                if ($i > 0) {
                    $dediTime .= ', ';
                }
                $dediTime .= -1;
            }
            $dediTime .= ']';
        }

        if ($update) {
            return "main () {
                declare Integer[] pbCheckpoints for UI = Integer[];
                pbCheckpoints.clear();
                pbCheckpoints = $checkpoints;

                declare Integer[] Deditimes for UI = Integer[];
                Deditimes.clear();
                Deditimes = $dediTime;
            }";
        } else {
            $script = new Script('Widgets_Times/Gui/Scripts_Time');
            $script->setParam('checkpoints', $checkpoints);
            $script->setParam('deditimes', $dediTime);
            $script->setParam('totalCp', $this->totalCp);
            $script->setParam('target', $target);
            $script->setParam('lapRace', $this->lapRace);
            $script->setParam("playSound", 'True');
            $script->setParam("reference", $reference);

            return $script;
        }
    }

    public function showPanel($login, $playerObject, $update = false)
    {
        $reference = 1;
        $target = "";
        $spectatorTarget = $login;

        if (isset($playerObject->currentTargetId)) {
            if ($playerObject->currentTargetId) {
                $spec = $this->getPlayerObjectById($playerObject->currentTargetId);
                if ($spec->login) {
                    $spectatorTarget = $spec->login;
                }
            }
        }

        if (!$this->expStorage->isRelay) {
            $target = $spectatorTarget;
        }
        
        if (array_key_exists($login, $this->references)) {
            $reference = $this->references[$login];
        }

        if ($update) {
            $xml = '<manialink id="playertimepanel_updater" version="2" name="playertimepanel_updater">';
            $xml .= '<script><!--';
            $xml .= $this->getScript($reference, $target, true);
            $xml .= '--></script>';
            $xml .= '</manialink>';
            $this->connection->sendDisplayManialinkPage($login, $xml);
        } else {
            $widget = new Widget("Widgets_Times\Gui\Widgets\TimePanel.xml");
            $widget->setName("Player Time Panel");
            $widget->setLayer("normal");
            $widget->setPosition($this->config->timePanel_PosX, $this->config->timePanel_PosY, 0);
            $widget->setSize(30, 6);
            $widget->registerScript($this->getScript($reference, $target, false));
            $widget->show($login);
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->showPanel($login, $this->storage->getPlayerObject($login));
    }

    public function onRecordsLoaded($data)
    {
        $this->localrecords = $data;
        $this->showToAll();
    }

    public function onUpdateRecords($data)
    {
        $this->localrecords = $data;
    }

    public function onNewRecord($data)
    {
        $this->localrecords = $data;
    }

    public function onDedimaniaUpdateRecords($data)
    {
        $this->dedirecords = $data['Records'];
    }

    public function onDedimaniaGetRecords($data)
    {
        $this->dedirecords = $data['Records'];
        $this->showToAll();
    }

    public function onRecordPlayerFinished($login)
    {
    }

    public function onDedimaniaOpenSession()
    {
    }

    public function onRecordDeleted($removedRecord, $records)
    {
        $this->showPanel($removedRecord->login, false);
    }

    public function onPersonalBestRecord($data)
    {
        $this->showPanel($data->login, false, true);
    }

    public function onDedimaniaPlayerConnect($data)
    {
    }

    public function onDedimaniaPlayerDisconnect($login)
    {
    }

    public function onDedimaniaRecord($record, $oldrecord)
    {
        foreach ($this->dedirecords as $index => $data) {
            if ($this->dedirecords[$index]['Login'] == $record->login) {
                $this->dedirecords[$index] = array(
                    "Login" => $record->login,
                    "NickName" => $record->nickname,
                    "Rank" => $record->place,
                    "Best" => $record->time,
                    "Checks" => $record->checkpoints
                );
            }
        }
        $this->showPanel($record->login, false, true);
    }

    public function onDedimaniaNewRecord($record)
    {
        foreach ($this->dedirecords as $index => $data) {
            if ($this->dedirecords[$index]['Login'] == $record->login) {
                $this->dedirecords[$index] = array(
                    "Login" => $record->login,
                    "NickName" => $record->nickname,
                    "Rank" => $record->place,
                    "Best" => $record->time,
                    "Checks" => $record->checkpoints
                );
            }
        }
        $this->showPanel($record->login, false, true);
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
        Dispatcher::unregister(LocalEvent::getClass(), $this);
        
        $widget = new Widget("Widgets_Times\Gui\Widgets\TimePanel.xml");
        $widget->setName("Player Time Panel");
        $widget->erase();
    }
}
