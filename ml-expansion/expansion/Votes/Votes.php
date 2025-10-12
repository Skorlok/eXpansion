<?php

namespace ManiaLivePlugins\eXpansion\Votes;

use ManiaLive\Gui\ActionHandler;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\Events\GlobalEvent;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Windows\PlayerSelection;
use ManiaLivePlugins\eXpansion\Menu\Menu;
use ManiaLivePlugins\eXpansion\Votes\Gui\Windows\VoteSettingsWindow;
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
    private $actionYes;
    private $actionNo;
    private $actionPass;
    private $actionCancel;

    public $currentVote = null;

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

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        Menu::addMenuItem("Votes",
            array("Vote" => array(null, array(
                "Skip" => array(null, $aH->createAction(array($this, "vote_Skip"))),
                "Res" => array(null, $aH->createAction(array($this, "vote_Restart"))),
                "Extend Time" => array(null, $aH->createAction(array($this, "vote_Extend"))),
                "End Round" => array(null, $aH->createAction(array($this, "vote_EndRound"))),
                "Balance Teams" => array(null, $aH->createAction(array($this, "vote_balance"))),
                "Config..." => array(Permission::SERVER_VOTES, $aH->createAction(array($this, "showVotesConfig"))),
                '$f00Cancel' => array(Permission::SERVER_VOTES, $aH->createAction(array($this, "cancelVote"))),
                '$0c0Pass' => array(Permission::SERVER_VOTES, $aH->createAction(array($this, "passVote")))
            )))
        );
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();

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

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();

        $this->actionYes = $aH->createAction(array($this, "handlePlayerVote"), "yes");
        $this->actionNo = $aH->createAction(array($this, "handlePlayerVote"), "no");
        $this->actionPass = $aH->createAction(array($this, "passVote"));
        $this->actionCancel = $aH->createAction(array($this, "cancelVote"));

        $this->script = new Script("Votes/Gui/Script");
        $this->script->setParam("actionYes", $this->actionYes);
        $this->script->setParam("actionNo", $this->actionNo);
        $this->script->setParam("actionPass", $this->actionPass);
        $this->script->setParam("actionCancel", $this->actionCancel);
        $this->script->setParam("isTrackmania", ($this->expStorage->simpleEnviTitle == "TM"));
        

        $this->widget = new Widget("Votes\Gui\Widgets\VoteManagerWidget.xml");
        $this->widget->setName("Vote Manager Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(90, 27);
        $this->widget->setParam("actionYes", $this->actionYes);
        $this->widget->setParam("actionNo", $this->actionNo);
        $this->widget->registerScript($this->script);
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
        $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

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
        $this->startNewVote($login, 'Kick', 'Kick ' . $this->widget->handleSpecialChars($player->nickName) . ' $z$z?', $target);
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
        $this->startNewVote($login, 'Ban', 'Ban ' . $this->widget->handleSpecialChars($player->nickName) . ' $z$z?', $target);
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
            $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->getPlayerObject($login)->nickName, 'wosnm'), $login));
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
            $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->getPlayerObject($login)->nickName, 'wosnm'), $login));
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
        /** @var Gui\Windows\VoteSettingsWindow */
        $window = Gui\Windows\VoteSettingsWindow::Create($login);
        $window->setSize(120, 96);
        $window->setTitle(__("Configure Votes", $login));
        $window->addLimits();
        $window->populateList($this->getVotes(), $this->metaData);
        $window->addMxVotes();
        $window->show($login);
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->syncSettings();
        }
    }

    public function eXpOnUnload()
    {
        VoteSettingsWindow::EraseAll();
        
        $this->widget->erase();
        $this->widget = null;
        $this->script = null;

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
