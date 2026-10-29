<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Times;

use ManiaLive\Event\Dispatcher;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;
use ManiaLivePlugins\eXpansion\Widgets_Times\Structures\TimeRecord;

class Widgets_Times extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    /** @var Config */
    private $config;
    /** @var Script */
    private $script;
    /** @var Widget */
    private $widget;

    private $totalCp = 0;
    private $lapRace = false;

    /** @var TimeRecord[] */
    private $localrecords = array();
    /** @var TimeRecord[] */
    private $dedirecords = array();
    /** @var string[] */
    private $localReferences = array();
    /** @var string[] */
    private $dediReferences = array();

    /** @var array */
    private $references = array();
    /** @var string */
    private $emptyCheckpoints = '[]';

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();

        $this->script = new Script('Widgets_Times/Gui/Scripts_Time');
        $this->script->setParam("playSound", 'True');

        $this->widget = new Widget("Widgets_Times\Gui\Widgets\TimePanel.xml");
        $this->widget->setName("Player Time Panel");
        $this->widget->setLayer("normal");
        $this->widget->setSize(30, 6);
        $this->widget->registerScript($this->script);
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
        $this->registerChatCommand("cpt", "chat_cpt_help", 0, true);
        $this->registerChatCommand("cpt", "chat_cpt_help", 1, true);
        $this->registerChatCommand("cpt", "chat_cpt", 2, true);
        $this->showToAll();
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        $this->config = Config::getInstance();

        if ($var->getName() == 'enableTarget') {
            $this->showToAll();
        }
    }

    public function chat_cpt_help($login, $value = null)
    {
        if (!$this->config->enableTarget) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#error#/cpt is disabled because target checkpoints are disabled'), $login);
            return;
        }

        $this->eXpChatSendServerMessage(eXpGetMessage('#info#usage: "/cpt X {local/dedi}"'), $login);
    }

    public function chat_cpt($login, $value, $target)
    {
        if (!$this->config->enableTarget) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#error#/cpt is disabled because target checkpoints are disabled'), $login);
            return;
        }

        if (!is_numeric($value)) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#error#"%s" is not a numeric value!'), $login, array($value));
            return;
        }

        if ($value < 1) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#error#"%s" is less than 1!'), $login, array($value));
            return;
        }

        if ($target != "dedi" && $target != "local") {
            $this->eXpChatSendServerMessage(eXpGetMessage('#error#"%s" target should be "local" or "dedi"'), $login, array($target));
            return;
        }

        $this->eXpChatSendServerMessage(eXpGetMessage('#info#New time reference point set to %s'), $login, array($target . " " . $value));
        $this->references[$login] = array($target, (int)$value);
        $this->showPanel($login, $this->storage->getPlayerObject($login));
    }

    public function onPlayerInfoChanged($playerInfo)
    {
        $player = \Maniaplanet\DedicatedServer\Structures\PlayerInfo::fromArray($playerInfo);
        if ($player) {
            $this->showPanel($player->login, $player);
        }
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        $this->widget->erase();
        $this->dediReferences = array();
        $this->localReferences = array();
        $this->dedirecords = array();
        $this->localrecords = array();
    }

    public function getRecords()
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
            if (empty($this->localrecords)) {
                try {
                    $localrecs = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");

                    $this->localrecords = array();
                    foreach ($localrecs as $record) {
                        $this->localrecords[$record->login] = new TimeRecord($record->login, $record->time, $record->place, $record->ScoreCheckpoints);
                        $this->localReferences[$record->place] = $record->login;
                    }
                } catch (\Exception $e) {
                    $this->localrecords = array();
                    $this->localReferences = array();
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

        $this->emptyCheckpoints = '[';
        for ($i = 0; $i < $this->totalCp; $i++) {
            if ($i > 0) {
                $this->emptyCheckpoints .= ', ';
            }
            $this->emptyCheckpoints .= -1;
        }
        $this->emptyCheckpoints .= ']';

        $this->script->setParam('totalCp', $this->totalCp);
        $this->script->setParam('lapRace', $this->lapRace);
    }

    private function getCheckpointsData($target)
    {
        $checkpoints = "[ -1 ]";

        if (isset($this->dedirecords[$target]) && isset($this->localrecords[$target])) {
            if ($this->dedirecords[$target]->time < $this->localrecords[$target]->time) {
                $checkpoints = $this->dedirecords[$target]->checkpoints;
            } else {
                $checkpoints = $this->localrecords[$target]->checkpoints;
            }
        } else if (isset($this->dedirecords[$target])) {
            $checkpoints = $this->dedirecords[$target]->checkpoints;
        } else if (isset($this->localrecords[$target])) {
            $checkpoints = $this->localrecords[$target]->checkpoints;
        } else {
            $checkpoints = $this->emptyCheckpoints;
        }

        return $checkpoints;
    }

    private function getCheckpointsTargetData($login)
    {
        if (isset($this->references[$login])) {
            return $this->getReferenceTargetData($this->references[$login]);
        }

        return $this->getReferenceTargetData($this->getDefaultReference());
    }

    private function getDefaultReference()
    {
        if (count($this->dedirecords) > 0) {
            return array("dedi", 1);
        }
        return array("local", 1);
    }

    private function getReferenceTargetData($reference)
    {
        if ($reference[0] == "dedi") {
            $target = $this->resolveTarget($reference[1], $this->dedirecords, $this->dediReferences);
        } else {
            $target = $this->resolveTarget($reference[1], $this->localrecords, $this->localReferences);
        }
        $reference[1] = $target[1];

        return(array($target[0], $reference));
    }

    private function resolveTarget($rank, $records, $references)
    {
        if (isset($references[$rank])) {
            return array($records[$references[$rank]]->checkpoints, $rank);
        }
        if (isset($references[1])) {
            return array($records[$references[1]]->checkpoints, 1);
        }

        return array($this->emptyCheckpoints, $rank);
    }

    private function showToAll()
    {
        $this->sendPanel($this->storage->players + $this->storage->spectators, null);
    }

    private function showPanel($login, $playerObject)
    {
        $this->sendPanel(array($login => $playerObject), $login);
    }

    private function sendPanel($players, $recipient)
    {
        $targetCheckpoints = '[]';
        $reference = '';
        if ($this->config->enableTarget) {
            $defaultTarget = $this->getReferenceTargetData($this->getDefaultReference());
            $targetCheckpoints = $defaultTarget[0];
            $reference = $defaultTarget[1][0] . ' ' . $defaultTarget[1][1];
        }

        $pbGroups = array();
        $targetGroups = array();
        $spectatorGroups = array();
        $playerIds = null;

        foreach ($players as $login => $playerObject) {
            if (!$this->storage->isRelay) {
                $target = $login;
                if (isset($playerObject->currentTargetId) && $playerObject->currentTargetId) {
                    if ($playerIds === null) {
                        $playerIds = array();
                        foreach ($this->storage->players + $this->storage->spectators as $playerLogin => $player) {
                            $playerIds[$player->playerId] = $playerLogin;
                        }
                    }
                    if (isset($playerIds[$playerObject->currentTargetId])) {
                        $target = $playerIds[$playerObject->currentTargetId];
                        $spectatorGroups['target = "' . $target . '";'][] = $login;
                    }
                }

                $pbCheckpoints = $this->getCheckpointsData($target);
                if ($pbCheckpoints !== $this->emptyCheckpoints) {
                    $pbGroups['pbCheckpoints = ' . $this->toManiaScriptArray($pbCheckpoints) . ';'][] = $login;
                }
            }

            if ($this->config->enableTarget && isset($this->references[$login])) {
                $targetData = $this->getCheckpointsTargetData($login);
                $targetReference = $targetData[1][0] . ' ' . $targetData[1][1];
                if ($targetData[0] !== $targetCheckpoints || $targetReference !== $reference) {
                    $targetGroups['targetCheckpoints = ' . $this->toManiaScriptArray($targetData[0]) . '; reference = "' . $targetReference . '";'][] = $login;
                }
            }
        }

        $playersScript = $this->storage->isRelay ? '' : 'target = LocalUser.Login;';
        $playersScript .= $this->getGroupsScript('spectatorGroups', $spectatorGroups);
        $playersScript .= $this->getGroupsScript('pbGroups', $pbGroups);
        $playersScript .= $this->getGroupsScript('targetGroups', $targetGroups);

        $this->script->setParam('checkpoints', $this->toManiaScriptArray($this->emptyCheckpoints));
        $this->script->setParam('targetCheckpoints', $this->toManiaScriptArray($targetCheckpoints));
        $this->script->setParam('reference', $reference);
        $this->script->setParam('players', $playersScript);
        $this->widget->setPosition($this->config->timePanel_PosX, $this->config->timePanel_PosY, 0);
        $this->widget->show($recipient);
    }

    private function getGroupsScript($name, $groups)
    {
        if (empty($groups)) {
            return '';
        }

        $logins = array();
        $cases = '';
        $index = 0;
        foreach ($groups as $assignments => $groupLogins) {
            foreach ($groupLogins as $login) {
                $logins[] = '"' . $login . '" => ' . $index;
            }
            $cases .= 'case ' . $index . ': { ' . $assignments . ' }';
            $index++;
        }

        return 'declare Integer[Text] ' . $name . ' = [' . implode(', ', $logins) . '];'
            . 'if (' . $name . '.existskey(LocalUser.Login)) { switch (' . $name . '[LocalUser.Login]) { ' . $cases . ' } }';
    }

    private function toManiaScriptArray($checkpoints)
    {
        return $checkpoints == '[]' ? 'Integer[]' : $checkpoints;
    }

    private function updatePanel($login)
    {
        $pbCheckpoints = $this->getCheckpointsData($login);

        $xml  = '<manialink id="playertimepanel_updater" version="2" name="playertimepanel_updater">';
        $xml .= '<script><!--';
        $xml .= '    main () {';
        $xml .= '        declare Integer[] pbCheckpoints for UI = Integer[];';
        $xml .= '        pbCheckpoints = ' . $this->toManiaScriptArray($pbCheckpoints) . ';';
        if ($this->config->enableTarget) {
            $targetCheckpoints = $this->getCheckpointsTargetData($login);
            $xml .= '        declare Integer[] targetCheckpoints for UI = Integer[];';
            $xml .= '        targetCheckpoints = ' . $this->toManiaScriptArray($targetCheckpoints[0]) . ';';
            $xml .= '        declare Text reference for UI = Text;';
            $xml .= '        reference = "' . $targetCheckpoints[1][0] . ' ' . $targetCheckpoints[1][1] . '";';
        }
        $xml .= '    }';
        $xml .= '--></script>';
        $xml .= '</manialink>';
        $this->connection->sendDisplayManialinkPage($login, $xml);
    }

    private function updateTargetsPanel($arrayOfCheckpoints)
    {
        $groups = array();
        foreach ($arrayOfCheckpoints as $checkpoints => $references) {
            foreach ($references as $reference => $logins) {
                $groups['targetCheckpoints = ' . $this->toManiaScriptArray($checkpoints) . '; reference = "' . $reference . '";'] = $logins;
            }
        }

        $xml  = '<manialink id="playertimepanel_targets_updater" version="2" name="playertimepanel_targets_updater">';
        $xml .= '<script><!--';
        $xml .= '    main () {';
        $xml .= '        declare Integer[] targetCheckpoints for UI = Integer[];';
        $xml .= '        declare Text reference for UI = Text;';
        $xml .= '        if (LocalUser != Null) {';
        $xml .= $this->getGroupsScript('targetGroups', $groups);
        $xml .= '        }';
        $xml .= '    }';
        $xml .= '--></script>';
        $xml .= '</manialink>';
        $this->connection->sendDisplayManialinkPage(null, $xml);
    }

    private function getChangedTargets($type, $newRecords, $newReferences)
    {
        $old = array(
            "local" => array($this->localrecords, $this->localReferences),
            "dedi" => array($this->dedirecords, $this->dediReferences),
        );
        $new = $old;
        $new[$type] = array($newRecords, $newReferences);

        $connected = $this->storage->players + $this->storage->spectators;
        $changed = array();

        $oldDefault = count($old["dedi"][0]) > 0 ? "dedi" : "local";
        $newDefault = count($new["dedi"][0]) > 0 ? "dedi" : "local";
        $oldTarget = $this->resolveTarget(1, $old[$oldDefault][0], $old[$oldDefault][1]);
        $newTarget = $this->resolveTarget(1, $new[$newDefault][0], $new[$newDefault][1]);
        if ($oldTarget[0] !== $newTarget[0] || $oldDefault !== $newDefault) {
            $logins = array_keys(array_diff_key($connected, $this->references));
            if (!empty($logins)) {
                $changed[$newTarget[0]][$newDefault . ' ' . $newTarget[1]] = $logins;
            }
        }

        foreach ($this->references as $login => $reference) {
            if (($reference[0] == "dedi" ? "dedi" : "local") != $type || !isset($connected[$login])) {
                continue;
            }
            $oldTarget = $this->resolveTarget($reference[1], $old[$type][0], $old[$type][1]);
            $newTarget = $this->resolveTarget($reference[1], $new[$type][0], $new[$type][1]);
            if ($oldTarget[0] !== $newTarget[0] || $oldTarget[1] !== $newTarget[1]) {
                $changed[$newTarget[0]][$reference[0] . ' ' . $newTarget[1]][] = $login;
            }
        }

        return $changed;
    }

    private function updateLocalRecordsList($records, $updateTargets)
    {
        $localrecords = array();
        $localReferences = array();
        foreach ($records as $record) {
            $localrecords[$record->login] = new TimeRecord($record->login, $record->time, $record->place, $record->ScoreCheckpoints);
            $localReferences[$record->place] = $record->login;
        }

        $changedTargets = false;
        if ($updateTargets && $this->config->enableTarget) {
            $changedTargets = $this->getChangedTargets("local", $localrecords, $localReferences);
        }

        $this->localrecords = $localrecords;
        $this->localReferences = $localReferences;

        if ($changedTargets && !empty($changedTargets)) {
            $this->updateTargetsPanel($changedTargets);
        }
    }

    private function updateDedimaniaRecordsList($records, $updateTargets)
    {
        $dedirecords = array();
        $dediReferences = array();
        foreach ($records as $record) {
            $dedirecords[$record["Login"]] = new TimeRecord($record["Login"], $record["Best"], $record["Rank"], $record["Checks"]);
            $dediReferences[$record["Rank"]] = $record["Login"];
        }

        $changedTargets = false;
        if ($updateTargets && $this->config->enableTarget) {
            $changedTargets = $this->getChangedTargets("dedi", $dedirecords, $dediReferences);
        }

        $this->dedirecords = $dedirecords;
        $this->dediReferences = $dediReferences;

        if ($changedTargets && !empty($changedTargets)) {
            $this->updateTargetsPanel($changedTargets);
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->showPanel($login, $this->storage->getPlayerObject($login));
    }

    public function onPlayerDisconnect($login, $disconnectionReason = null)
    {
        if (array_key_exists($login, $this->references)) {
            unset($this->references[$login]);
        }
    }

    public function onRecordsLoaded($data)
    {
        $this->updateLocalRecordsList($data, false);
        $this->setMapInfo();
        $this->showToAll();
    }

    public function onUpdateRecords($data)
    {
        $this->updateLocalRecordsList($data, true);
    }

    public function onNewRecord($data)
    {
        $this->updateLocalRecordsList($data, true);
    }

    public function onDedimaniaUpdateRecords($data)
    {
        $this->updateDedimaniaRecordsList($data['Records'], true);
    }

    public function onDedimaniaGetRecords($data)
    {
        $this->updateDedimaniaRecordsList($data['Records'], false);
        $this->setMapInfo();
        $this->showToAll();
    }

    public function onRecordDeleted($removedRecord, $records)
    {
        $this->updateLocalRecordsList($records, true);

        $player = $this->storage->getPlayerObject($removedRecord->login);
        if ($player) {
            $this->showPanel($removedRecord->login, $player);
        }
    }

    public function onPersonalBestRecord($data)
    {
        $this->updatePanel($data->login);
    }

    public function onDedimaniaRecord($record, $oldrecord)
    {
        $this->updatePanel($record->login);
    }

    public function onDedimaniaNewRecord($record)
    {
        $this->updatePanel($record->login);
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
        Dispatcher::unregister(LocalEvent::getClass(), $this);
        
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->widget = null;
        $this->script = null;

        $this->dediReferences = array();
        $this->localReferences = array();
        $this->dedirecords = array();
        $this->localrecords = array();
        $this->references = array();
    }

    public function onRecordPlayerFinished($data)
    {
    }

    public function onDedimaniaOpenSession()
    {
    }

    public function onDedimaniaPlayerConnect($data)
    {
    }

    public function onDedimaniaPlayerDisconnect($login)
    {
    }
}
