<?php

namespace ManiaLivePlugins\eXpansion\Votes;

use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\Events\GlobalEvent;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Windows\PlayerSelection;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLivePlugins\eXpansion\Menu\Menu;
use ManiaLivePlugins\eXpansion\Votes\Structures\Vote;

class Votes extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    /** @var Config */
    private $config;
    private $counters = array();
    private $resCount = 0;
    private $lastMapUid = "";

    private $widget;
    private $script;

    public $currentVote = null;

    /** @var Window */
    private $voteSettingsWindow;

    /**
     * returns managedvote with key of command name
     *
     * @return \ManiaLivePlugins\eXpansion\Votes\Structures\ManagedVote[]
     */
    private function getVotes()
    {
        $out = array();
        for ($x = 0; $x < count($this->config->managedVote_commands); $x++) {
            $vote = new Structures\ManagedVote();
            $vote->managed = $this->config->managedVote_enable[$this->config->managedVote_commands[$x]];
            $vote->command = $this->config->managedVote_commands[$x];
            $vote->ratio = $this->config->managedVote_ratios[$this->config->managedVote_commands[$x]];
            $vote->timeout = $this->config->managedVote_timeouts[$this->config->managedVote_commands[$x]];
            $vote->voters = $this->config->managedVote_voters[$this->config->managedVote_commands[$x]];
            $out[$vote->command] = $vote;
        }

        return $out;
    }

    public function eXpOnLoad()
    {
        $cmd = $this->registerChatCommand("replay", "vote_Restart", 0, true);
        $cmd->help = 'Start a vote to restart a map';
        $cmd = $this->registerChatCommand("restart", "vote_Restart", 0, true);
        $cmd->help = 'Start a vote to restart a map';
        $cmd = $this->registerChatCommand("res", "vote_Restart", 0, true);
        $cmd->help = 'Start a vote to restart a map';

        $cmd = $this->registerChatCommand("skip", "vote_Skip", 0, true);
        $cmd->help = 'Start a vote to skip a map';

        $cmd = $this->registerChatCommand("er", "vote_EndRound", 0, true);
        $cmd->help = 'Start a vote to endround';
        $cmd = $this->registerChatCommand("endround", "vote_EndRound", 0, true);
        $cmd->help = 'Start a vote to endround';

        $cmd = $this->registerChatCommand("ext", "vote_Extend", 0, true);
        $cmd->help = 'Start a vote to extend timelimit';
        $cmd = $this->registerChatCommand("extend", "vote_Extend", 0, true);
        $cmd->help = 'Start a vote to extend timelimit';

        $cmd = $this->registerChatCommand("ext", "vote_Extend_Custom", 1, true);
        $cmd->help = 'Start a vote to extend timelimit';
        $cmd = $this->registerChatCommand("extend", "vote_Extend_Custom", 1, true);
        $cmd->help = 'Start a vote to extend timelimit';

        $cmd = $this->registerChatCommand("balance", "vote_balance", 0, true);
        $cmd->help = 'Start a vote to balance teams';
        $cmd = $this->registerChatCommand("bal", "vote_balance", 0, true);
        $cmd->help = 'Start a vote to balance teams';

        $cmd = $this->registerChatCommand("kick", "vote_kick", 0, true);
        $cmd->help = 'Start a vote to kick a player';
        $cmd = $this->registerChatCommand("kick", "vote_kick", 1, true);
        $cmd->help = 'Start a vote to kick a player';

        $cmd = $this->registerChatCommand("ban", "vote_ban", 0, true);
        $cmd->help = 'Start a vote to ban a player';
        $cmd = $this->registerChatCommand("ban", "vote_ban", 1, true);
        $cmd->help = 'Start a vote to ban a player';

        $cmd = AdminGroups::addAdminCommand('cancel', $this, 'cancelVote', 'cancel_vote');
        $cmd->setHelp('Cancel current running vote');
        AdminGroups::addAlias($cmd, "can");

        $cmd = AdminGroups::addAdminCommand('passvote', $this, 'passVote', 'pass_vote');
        $cmd->setHelp('Pass current running vote');
        AdminGroups::addAlias($cmd, "passv");

        Menu::addMenuItem("Votes",
            array("Vote" => array(null, array(
                "Skip" => array(null, 'exp:eXpansion.Votes:vote_Skip'),
                "Res" => array(null, 'exp:eXpansion.Votes:vote_Restart'),
                "Extend Time" => array(null, 'exp:eXpansion.Votes:vote_Extend'),
                "End Round" => array(null, 'exp:eXpansion.Votes:vote_EndRound'),
                "Balance Teams" => array(null, 'exp:eXpansion.Votes:vote_balance'),
                "Config..." => array(Permission::SERVER_VOTES, 'exp:eXpansion.Votes:showVotesConfig'),
                '$f00Cancel' => array(Permission::SERVER_VOTES, 'exp:eXpansion.Votes:cancelVote'),
                '$0c0Pass' => array(Permission::SERVER_VOTES, 'exp:eXpansion.Votes:passVote')
            )))
        );
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();

        $this->registerManialinkCallback('handlePlayerVote', false, true);
        $this->registerManialinkCallback('applyVoteSettings', true);
        $this->registerManialinkCallback('vote_Skip');
        $this->registerManialinkCallback('vote_Restart');
        $this->registerManialinkCallback('vote_Extend');
        $this->registerManialinkCallback('vote_EndRound');
        $this->registerManialinkCallback('vote_balance');
        $this->registerManialinkCallback('showVotesConfig');
        $this->registerManialinkCallback('cancelVote');
        $this->registerManialinkCallback('passVote');

        $this->config = Config::getInstance();

        $this->counters = array();
        $this->setPublicMethod("vote_restart");
        $this->setPublicMethod("vote_skip");
        $this->setPublicMethod("vote_extend");
        $this->setPublicMethod("vote_endround");
        $this->setPublicMethod("vote_balance");
        $this->setPublicMethod("showVotesConfig");
        $this->setPublicMethod("cancelVote");
        $this->setPublicMethod("cancelAutoExtend");

        $cmd = AdminGroups::addAdminCommand('votes', $this, 'showVotesConfig', 'server_votes');
        $cmd->setHelp('shows config window for managing votes');
        $cmd->setMinParam(0);


        $this->lastMapUid = $this->storage->currentMap->uId;

        $this->syncSettings();

        $this->script = new Script("Votes/Gui/Script");
        $this->script->setParam("disableNotTM_1", ($this->expStorage->simpleEnviTitle != "TM" ? "/*" : " "));
        $this->script->setParam("disableNotTM_2", ($this->expStorage->simpleEnviTitle != "TM" ? "*/" : " "));
        

        $this->widget = new Widget("Votes\Gui\Widgets\VoteManagerWidget.xml");
        $this->widget->setName("Vote Manager Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(90, 27);
        $this->widget->registerScript($this->script);

        $this->script->setParam("actionYes", 'exp:eXpansion.Votes:handlePlayerVote:yes');
        $this->script->setParam("actionNo", 'exp:eXpansion.Votes:handlePlayerVote:no');
        $this->script->setParam("actionPass", 'exp:eXpansion.Votes:passVote');
        $this->script->setParam("actionCancel", 'exp:eXpansion.Votes:cancelVote');

        $this->voteSettingsWindow = new Window("Votes\Gui\Windows\VoteSettingsWindow.xml");
        $this->voteSettingsWindow->setName("VoteSettings");
        $this->voteSettingsWindow->setSize(120, 96);
        $this->voteSettingsWindow->setTitle("Configure Votes");
        $this->voteSettingsWindow->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Pager::getScriptML(10, 88));
        $this->voteSettingsWindow->setParam("voterOptions", array("Select", "Active Players", "Players", "Everybody"));
    }

    public function syncSettings()
    {
        $this->config = Config::getInstance();
        
        $managedVotes = $this->getVotes();

        foreach ($managedVotes as $cmd => $vote) {
            $ratios[] = new \Maniaplanet\DedicatedServer\Structures\VoteRatio($vote->command, $vote->ratio);
        }
        $this->connection->setCallVoteRatios($ratios, true);
        if ($this->config->use_callvotes == false) {
            $this->connection->setCallVoteTimeOut(0);
        } else {
            $this->connection->setCallVoteTimeOut(($this->config->global_timeout * 1000));
        }
    }

    public function onBeginMatch()
    {
        $this->counters = array();

        if ($this->storage->currentMap->uId == $this->lastMapUid) {
            $this->resCount++;
        } else {
            $this->lastMapUid = $this->storage->currentMap->uId;
            $this->resCount = 0;
        }
    }

    public function onTick()
    {
        if ($this->currentVote) {
            if (($this->currentVote->timestamp + $this->currentVote->votingTime) < time()) {

                $totalVotes = count($this->currentVote->playerVotes);

                if ($totalVotes >= 1 && ($this->currentVote->getYes() / $totalVotes) > $this->currentVote->voteRatio) {
                    $this->handleEndVote(true);
                } else {
                    $this->handleEndVote(false);
                }
            }
        }
    }

    public function displayWidget($vote)
    {
        $this->script->setParam("countdown", $vote->votingTime);
        $this->script->setParam("votes", $vote->getManiaScriptVotes());
        $this->script->setParam("voters", $vote->voters);

        if ($this->expStorage->simpleEnviTitle == "SM") {
            $this->widget->setPosition($this->config->voteWidget_PosX_Shootmania, $this->config->voteWidget_PosY_Shootmania, 0);
        } else {
            $this->widget->setPosition($this->config->voteWidget_PosX, $this->config->voteWidget_PosY, 0);
        }
        $this->widget->setParam("voteText", $vote->voteText);
        $this->widget->setParam("ratioPos", sprintf("%0.1f", ($vote->voteRatio * 58) + 16));
        $this->widget->show(null, true);
    }

    public function handlePlayerVote($login, $vote)
    {
        if (!$this->currentVote) {
            return;
        }
        
        if ($vote) {
            $this->currentVote->playerVotes[$login] = $vote;
        } else {
            if (isset($this->currentVote->playerVotes[$login])) {
                unset($this->currentVote->playerVotes[$login]);
            } else {
                return;
            }
        }

        if ($this->checkVoteAutoPass()) {
            $this->handleEndVote(true);
            return;
        }

        $xml  = '<manialink id="votes_updater" version="2" name="votes_updater">';
        $xml .= '<script><!--';

        $xml .= 'main () {';
        $xml .= '   declare Text[Text] votes_playerVotes for UI = Text[Text];';
        $xml .= '   votes_playerVotes = ' . $this->currentVote->getManiaScriptVotes() . ';';
        $xml .= '}';

        $xml .= '--></script>';
        $xml .= '</manialink>';

        $this->connection->sendDisplayManialinkPage(null, $xml);
    }

    /**
     * Check if vote passes when we suppose that all players that didn't vote would vote NO.
     */
    public function checkVoteAutoPass()
    {
        $playerCount = count($this->storage->players) + count($this->storage->spectators);
        if ($this->currentVote->voters == 0) {
            $playerCount = count($this->storage->players);
        } else if ($this->currentVote->voters == 1) {
            $playerCount = count($this->storage->players);
            foreach ($this->storage->spectators as $login => $player) {
                if (isset($this->expStorage->playerTimes[$login]) && $this->expStorage->playerTimes[$login] > 0) {
                    $playerCount++;
                }
            }
        }

        if ($playerCount > 0 && ($this->currentVote->getYes() / $playerCount) > $this->currentVote->voteRatio) {
            return true;
        }

        return false;
    }

    public function handleEndVote($state)
    {
        $this->widget->erase();

        if ($state) {

            $msg = eXpGetMessage('#vote_success# $iVote passed!');
            $this->eXpChatSendServerMessage($msg, null);

            if ($this->currentVote->action == "RestartMap") {
                if (sizeof($this->storage->players) == 1 || !$this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\Maps\\Maps') || !$this->config->restartVote_useQueue) {
                    \ManiaLive\Event\Dispatcher::dispatch(new GlobalEvent(GlobalEvent::ON_ADMIN_RESTART));
                    $this->callPublicMethod('\ManiaLivePlugins\\eXpansion\\Maps\\Maps', 'replayMapInstant', $this->currentVote->voteAuthor);
                } else {
                    $this->callPublicMethod('\ManiaLivePlugins\\eXpansion\\Maps\\Maps', 'replayMap', $this->currentVote->voteAuthor);
                }
            }

            if ($this->currentVote->action == "NextMap") {
                \ManiaLive\Event\Dispatcher::dispatch(new GlobalEvent(GlobalEvent::ON_ADMIN_SKIP));
                $this->connection->nextMap();
            }

            if ($this->currentVote->action == "ExtendTime") {
                if ($this->currentVote->actionParams != "") {
                    if (Core::$isTimeExtendable)
                        $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', intval($this->currentVote->actionParams * 60));
                    if (Core::$isPointExtendable)
                        $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', intval($this->currentVote->actionParams));
                } else {
                    $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', null);
                }
            }

            if ($this->currentVote->action == "EndRound") {
                $this->connection->triggerModeScriptEventArray('Trackmania.ForceEndRound', array());
                $this->connection->triggerModeScriptEvent('Rounds_ForceEndRound');
            }

            if ($this->currentVote->action == "AutoTeamBalance") {
                $this->connection->autoTeamBalance();
            }

            if ($this->currentVote->action == "Kick") {
                $target = $this->currentVote->actionParams;
                $player = $this->storage->getPlayerObject($target);
                if ($player != null) {
                    try {
                        $this->connection->kick($player->login, "Kicked by vote");
                    } catch (\Exception $e) {
                        $this->eXpChatSendServerMessage(eXpGetMessage("#error#Could not kick player"), $this->currentVote->voteAuthor);
                    }
                } else {
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#Player not found"), $this->currentVote->voteAuthor);
                }
            }

            if ($this->currentVote->action == "Ban") {
                $target = $this->currentVote->actionParams;
                $player = $this->storage->getPlayerObject($target);
                if ($player != null) {
                    try {
                        $this->connection->ban($player->login, "Banned by vote");
                    } catch (\Exception $e) {
                        $this->eXpChatSendServerMessage(eXpGetMessage("#error#Could not ban player"), $this->currentVote->voteAuthor);
                    }
                } else {
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#Player not found"), $this->currentVote->voteAuthor);
                }
            }

        } else {
            $msg = eXpGetMessage('#vote_failure# $iVote failed!');
            $this->eXpChatSendServerMessage($msg, null);
        }

        $this->currentVote = null;
    }

    public function onPlayerDisconnect($login, $disconnectionReason)
    {
        if ($this->currentVote) {
            if ($this->currentVote->action == "Kick" || $this->currentVote->action == "Ban") {
                if ($this->currentVote->actionParams == $login) {
                    // target of kick/ban left, cancel vote.
                    $this->handleEndVote(false);
                    return;
                }
            }
            $this->handlePlayerVote($login, null);
        }
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($this->currentVote) {
            $this->handleEndVote(false);
        }
    }

    public function startNewVote($login, $caseName, $voteText, $actionParams = "")
    {
        $managedVotes = $this->getVotes();

        // if vote is not managed...
        if (!array_key_exists($caseName, $managedVotes)) {
            return;
        }

        // if vote is not managed...
        if ($managedVotes[$caseName]->managed == false) {
            return;
        }

        if ($managedVotes[$caseName]->ratio == -1.) {
            switch ($caseName) {
                case "RestartMap":
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#Restart vote is disabled!"), $login);
                    break;
                case "NextMap":
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#Skip vote is disabled!"), $login);
                    break;
                case "ExtendTime":
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#Extend vote is disabled!"), $login);
                    break;
                case "EndRound":
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#End round vote is disabled!"), $login);
                    break;
                case "AutoTeamBalance":
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#AutoTeamBalance vote is disabled!"), $login);
                    break;
                case "Kick":
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#Kick vote is disabled!"), $login);
                    break;
                case "Ban":
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#Ban vote is disabled!"), $login);
                    break;
                default:
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#This vote is disabled!"), $login);
                    break;
            }
            return;
        }

        if ($this->currentVote) {
            $this->eXpChatSendServerMessage(eXpGetMessage("#error#There is already a vote in progress!"), $login);
            return;
        }

        if ($caseName == "RestartMap") {
            if ($this->config->restartLimit != 0 && $this->config->restartLimit <= $this->resCount) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#Map limit for voting restart reached."), $login, array($this->config->restartLimit));
                return;
            }
        }

        if (!isset($this->counters[$caseName])) {
            $this->counters[$caseName] = 0;
        }

        $this->counters[$caseName]++;

        if ($this->config->limit_votes > 0 && $this->counters[$caseName] > $this->config->limit_votes) {
            $msg = eXpGetMessage("Vote limit reached.");
            $this->eXpChatSendServerMessage($msg);
            return;
        }


        $vote = $managedVotes[$caseName];

        $votes = array();
        if ($this->config->autoVoteStarter) {
            $votes[$login] = "yes";
        }

        $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, $votes, $caseName, $actionParams, $voteText, $vote->voters, time());

        $player = $this->storage->getPlayerObject($login);
        switch ($caseName) {
            case "RestartMap":
                $msg = eXpGetMessage('#variable#%s #vote#initiated restart map vote..');
                break;
            case "NextMap":
                $msg = eXpGetMessage('#variable#%1$s #vote#initiated skip map vote..');
                break;
            case "ExtendTime":
                if (Core::$isTimeExtendable)
                    $msg = eXpGetMessage('#variable#%1$s #vote#initiated extend time vote..');
                else
                    $msg = eXpGetMessage('#variable#%1$s #vote#initiated extend point vote..');
                break;
            case "EndRound":
                $msg = eXpGetMessage('#variable#%1$s #vote#initiated endround vote..');
                break;
            case "AutoTeamBalance":
                $msg = eXpGetMessage('#variable#%1$s #vote#initiated AutoTeamBalance vote..');
                break;
            case "Kick":
                $msg = eXpGetMessage('#variable#%1$s #vote#initiated kick vote..');
                break;
            case "Ban":
                $msg = eXpGetMessage('#variable#%1$s #vote#initiated ban vote..');
                break;
            default:
                $msg = eXpGetMessage('#variable#%1$s #vote#initiated a vote..');
                break;
        }
        $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($player->cleanNickName, 'wosnm')));

        if ($this->checkVoteAutoPass()) {
            $this->handleEndVote(true);
        } else {
            $this->displayWidget($this->currentVote);
        }
    }

    public function vote_Restart($login)
    {
        $this->startNewVote($login, 'RestartMap', 'Replay This Map ?');
    }

    public function vote_Skip($login)
    {
        $this->startNewVote($login, 'NextMap', 'Skip This Map ?');
    }

    public function vote_Extend($login)
    {
        if (Core::$isTimeExtendable) {
            $this->startNewVote($login, 'ExtendTime', 'Extend The Time Limit ?');
        } else if (Core::$isPointExtendable) {
            $this->startNewVote($login, 'ExtendTime', 'Extend The Point Limit ?');
        } else {
            $this->connection->chatSendServerMessage("Not in TimeAttack or Rounds mode", $login);
        }
    }

    public function vote_Extend_Custom($login, $params)
    {
        if (!is_numeric($params) || $params < 0) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide a correct number'), $login);
            return;
        }

        if (Core::$isTimeExtendable) {

            if ($this->config->extendTimeLimit != -1 && $params > $this->config->extendTimeLimit) {
                $limit = $this->config->extendTimeLimit;
                $this->eXpChatSendServerMessage(eXpGetMessage("#admin_error#You are trying to add too much time, the max time is $limit"), $login);
                return;
            }

            $this->startNewVote($login, 'ExtendTime', 'Extend The Time Limit With ' . $params . ' Minutes ?', $params);

        } else if (Core::$isPointExtendable) {

            if ($this->config->extendPointLimit != -1 && $params > $this->config->extendPointLimit) {
                $limit = $this->config->extendPointLimit;
                $this->eXpChatSendServerMessage(eXpGetMessage("#admin_error#You are trying to add too much points, the max points is $limit"), $login);
                return;
            }

            $this->startNewVote($login, 'ExtendTime', 'Extend The Point Limit With ' . $params . ' Points ?', $params);

        } else {
            $this->connection->chatSendServerMessage("Not in TimeAttack or Rounds mode", $login);
        }
    }

    public function vote_EndRound($login)
    {
        if ($this->eXpGetCurrentCompatibilityGameMode()== GameInfos::GAMEMODE_ROUNDS || $this->eXpGetCurrentCompatibilityGameMode()== GameInfos::GAMEMODE_CUP || $this->eXpGetCurrentCompatibilityGameMode()== GameInfos::GAMEMODE_TEAM) {
            $this->startNewVote($login, 'EndRound', 'End The Round ?');
        } else {
            $this->connection->chatSendServerMessage("Not in Rounds, Cup or Team gamemode", $login);
        }
    }

    public function vote_balance($login)
    {
        $this->startNewVote($login, 'AutoTeamBalance', 'Balance Teams ?');
    }

    public function vote_kick($login, $target = null)
    {
        if (!$target || !$player = $this->storage->getPlayerObject($target)) {
            if ($target) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#Player not found"), $login);
            }
            $this->selectPlayers($login, "vote_kick");
            return;
        }
        PlayerSelection::Erase($login);
        $this->startNewVote($login, 'Kick', 'Kick ' . $this->widget->handleSpecialChars($player->cleanNickName) . ' $z$z?', $target);
    }

    public function vote_ban($login, $target = null)
    {
        if (!$target || !$player = $this->storage->getPlayerObject($target)) {
            if ($target) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#Player not found"), $login);
            }
            $this->selectPlayers($login, "vote_ban");
            return;
        }
        PlayerSelection::Erase($login);
        $this->startNewVote($login, 'Ban', 'Ban ' . $this->widget->handleSpecialChars($player->cleanNickName) . ' $z$z?', $target);
    }

    public function selectPlayers($login, $callback)
    {
        /** @var PlayerSelection */
        $win = PlayerSelection::Create($login);
        $win->setTitle('Select Player');
        $win->setSize(85, 100);
        $win->populateList(array($this, $callback), 'select');
        $win->centerOnScreen();
        $win->show();
    }

    public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
    {
        // check for our stuff...
        if ($stateName == "NewVote") {

            if ($cmdName == "RestartMap") {
                $this->connection->cancelVote();
                $this->vote_Restart($login);
                return;
            }
            if ($cmdName == "NextMap") {
                $this->connection->cancelVote();
                $this->vote_Skip($login);
                return;
            }
            if ($cmdName == "Kick") {
                $this->connection->cancelVote();
                $this->vote_kick($login, $cmdParam);
                return;
            }
            if ($cmdName == "Ban") {
                $this->connection->cancelVote();
                $this->vote_ban($login, $cmdParam);
                return;
            }

            foreach ($this->getVotes() as $cmd => $vote) {
                if ($cmdName == $cmd) {
                    if ($vote->ratio == -1.) {
                        $this->connection->cancelVote();
                    }
                }
            }

            if (!isset($this->counters[$cmdName])) {
                $this->counters[$cmdName] = 0;
            }

            $this->counters[$cmdName]++;

            if ($this->config->limit_votes > 0) {
                if ($this->counters[$cmdName] > $this->config->limit_votes) {
                    $this->connection->cancelVote();
                    $msg = eXpGetMessage("Vote limit reached.");
                    $this->eXpChatSendServerMessage($msg);
                    return;
                }
            }
        }
    }

    public function cancelVote($login)
    {
        if (!AdminGroups::hasPermission($login, 'cancel_vote')) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You don\'t have the permission to cancel a vote!'), $login);
            return;
        }

        $cancelled = false;

        if ($this->currentVote) {
            $this->handleEndVote(false);
            $cancelled = true;
        }

        $vote = $this->connection->getCurrentCallVote();
        if (!empty($vote->cmdName)) {
            $this->connection->cancelVote();
            $cancelled = true;
        }

        if ($cancelled) {
            $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action# cancelled the vote!');
            $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($this->storage->getPlayerObject($login)->cleanNickName, 'wosnm'), $login));
        } else {
            $this->connection->chatSendServerMessage('Notice: Can\'t cancel a vote, no vote in progress!', $login);
        }
    }

    public function passVote($login)
    {
        if (!AdminGroups::hasPermission($login, 'pass_vote')) {
            $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You don\'t have the permission to pass a vote!'), $login);
            return;
        }

        if ($this->currentVote) {
            $this->handleEndVote(true);
            $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action# pass the vote!');
            $this->eXpChatSendServerMessage($msg, null, array(Formatting::stripCodes($this->storage->getPlayerObject($login)->cleanNickName, 'wosnm'), $login));
        } else {
            $this->connection->chatSendServerMessage('Notice: Can\'t pass a vote, no vote in progress!', $login);
        }
    }

    public function cancelAutoExtend()
    {
        if ($this->currentVote && $this->currentVote->action == "ExtendTime") {
            $this->handleEndVote(false);
            $this->eXpChatSendServerMessage(eXpGetMessage("#vote#The extend time vote was cancelled as the auto extend vote is active."));
        }
    }

    public function showVotesConfig($login)
    {
        $config   = Config::getInstance();
        /** @var \ManiaLivePlugins\eXpansion\ManiaExchange\Config $mxConfig */
        $mxConfig = \ManiaLivePlugins\eXpansion\ManiaExchange\Config::getInstance();

        $limitsData = array(
            array('label' => "Max votes per map (0 = disabled)",     'name' => '!_voteLimit',    'value' => $config->limit_votes),
            array('label' => "Max restarts of a map (0 = disabled)", 'name' => '!_restartLimit', 'value' => $config->restartLimit),
        );

        $votesData    = array();
        $managedVotes = $this->getVotes();
        foreach ($managedVotes as $cmd => $vote) {
            $selectedIdx = min(3, max(0, $vote->voters + 1));
            $votesData[] = array(
                'label'          => $vote->command,
                'timeoutName'    => $vote->command . '_timeouts',
                'timeoutValue'   => $vote->timeout,
                'ratioName'      => $vote->command . '_ratios',
                'ratioValue'     => $vote->ratio,
                'votersName'     => $vote->command . '_voters',
                'votersSelected' => $selectedIdx,
            );
        }

        $mxSelectedIdx = min(3, max(0, $mxConfig->mxVote_voters + 1));
        $votesData[] = array(
            'label'          => "mxVote",
            'timeoutName'    => "mxVote_timeouts",
            'timeoutValue'   => $mxConfig->mxVote_timeouts,
            'ratioName'      => "mxVote_ratios",
            'ratioValue'     => $mxConfig->mxVote_ratios,
            'votersName'     => "mxVote_voters",
            'votersSelected' => $mxSelectedIdx,
        );

        $this->voteSettingsWindow->setParam("limits",      $limitsData);
        $this->voteSettingsWindow->setParam("votes",       $votesData);
        $this->voteSettingsWindow->setParam("sizeX",       120);
        $this->voteSettingsWindow->setParam("sizeY",       100);
        $this->voteSettingsWindow->show($login);
    }

    public function applyVoteSettings($login, $params = array())
    {
        foreach ($params as $key => $value) {
            $exploded = explode("_", $key);

            if ($exploded[0] == "!") {
                switch ($exploded[1]) {
                    case "voteLimit":
                        $var = $this->metaData->getVariable("limit_votes");
                        $var->setRawValue(intval($value));
                        break;
                    case "restartLimit":
                        $var = $this->metaData->getVariable("restartLimit");
                        $var->setRawValue(intval($value));
                        break;
                }
            } else {
                if ($exploded[1] == "voters") {
                    $value = intval($value) - 1;
                }

                if ($exploded[1] == "ratios") {
                    $value = floatval($value);
                } else {
                    $value = intval($value);
                }

                if ($exploded[0] == "mxVote") {
                    $meta = \ManiaLivePlugins\eXpansion\ManiaExchange\MetaData::getInstance();
                    $var  = $meta->getVariable($key);
                    $var->setRawValue($value);
                }

                if ($key == "mxVote_ratios") {
                    $meta = \ManiaLivePlugins\eXpansion\ManiaExchange\MetaData::getInstance();
                    $var  = $meta->getVariable('mxVote_enable');
                    if ($value == -1.) {
                        $var->setRawValue(false);
                    } else {
                        $var->setRawValue(true);
                    }
                }

                $varName  = 'managedVote_' . array_pop($exploded);
                $voteName = implode('_', $exploded);

                $var = $this->metaData->getVariable($varName);
                if ($var instanceof \ManiaLivePlugins\eXpansion\Core\types\config\types\HashList) {
                    $var->setValue($voteName, $value);
                }
            }
        }

        \ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();
        $this->voteSettingsWindow->erase($login);
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->syncSettings();
        }
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->widget = null;
        $this->script = null;

        if ($this->voteSettingsWindow instanceof Window) {
            $this->voteSettingsWindow->erase();
        }
        $this->voteSettingsWindow = null;

        $this->currentVote = null;
        $this->counters = array();
        $this->resCount = 0;
        $this->lastMapUid = "";
        $this->config = null;
        
        $this->actionYes = null;
        $this->actionNo = null;
        $this->actionPass = null;
        $this->actionCancel = null;
    }
}
