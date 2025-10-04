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
use ManiaLivePlugins\eXpansion\Menu\Menu;
use ManiaLivePlugins\eXpansion\Votes\Gui\Windows\VoteSettingsWindow;
use ManiaLivePlugins\eXpansion\Votes\Structures\Vote;

class Votes extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    /** @var Config */
    private $config;
    private $useQueue = false;
    private $counters = array();
    private $resCount = 0;
    private $lastMapUid = "";

    private $widget;
    private $script;
    private $actionYes;
    private $actionNo;

    public $currentVote = null;

    public function eXpOnInit()
    {
        $this->config = Config::getInstance();
    }

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

        $this->counters = array();
        $this->setPublicMethod("vote_restart");
        $this->setPublicMethod("vote_skip");
        $this->setPublicMethod("vote_extend");
        $this->setPublicMethod("vote_endround");
        $this->setPublicMethod("vote_balance");
        $this->setPublicMethod("showVotesConfig");
        $this->setPublicMethod("cancelVote");

        $cmd = AdminGroups::addAdminCommand('votes', $this, 'showVotesConfig', 'server_votes');
        $cmd->setHelp('shows config window for managing votes');
        $cmd->setMinParam(0);


        $this->lastMapUid = $this->storage->currentMap->uId;

        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\Maps\\Maps') && $this->config->restartVote_useQueue) {
            $this->useQueue = true;
            $this->debug("[exp\Votes] Restart votes set to queue");
        } else {
            $this->debug("[exp\Votes] Restart vote set to normal");
        }

        $this->syncSettings();

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();

        $this->actionYes = $aH->createAction(array($this, "handlePlayerVote"), "yes");
        $this->actionNo = $aH->createAction(array($this, "handlePlayerVote"), "no");

        $this->script = new Script("Votes/Gui/Script");
        $this->script->setParam("actionYes", $this->actionYes);
        $this->script->setParam("actionNo", $this->actionNo);
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
        $this->currentVote->playerVotes[$login] = $vote;

        // Check if vote passes when we suppose that all players that didn't vote would vote NO.
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
            /*$this->handleEndVote(true);
            return;*/
        }

        $script = "main () {
            declare Text[Text] votes_playerVotes for UI = Text[Text];
            votes_playerVotes = " . $this->currentVote->getManiaScriptVotes() . ";
        }";

        $xml = '<manialink id="votes_updater" version="2" name="votes_updater">';
        $xml .= '<script><!--';
        $xml .= $script;
        $xml .= '--></script>';
        $xml .= '</manialink>';

        $this->connection->sendDisplayManialinkPage(null, $xml);
    }

    public function handleEndVote($state)
    {
        $this->widget->erase();

        if ($state) {

            $msg = eXpGetMessage('#vote_success# $iVote passed!');
            $this->eXpChatSendServerMessage($msg, null);

            if ($this->currentVote->action == "RestartMap") {
                if (sizeof($this->storage->players) == 1) {
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

            if ($this->currentVote->action == "Extend") {
                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', null);
            }

            if ($this->currentVote->action == "Add") {
                if (Core::$isTimeExtendable) {
                    $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', intval($this->currentVote->actionParams * 60));
                }
                if (Core::$isPointExtendable) {
                    $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', intval($this->currentVote->actionParams));
                }
            }

            if ($this->currentVote->action == "EndRound") {
                $this->connection->triggerModeScriptEventArray('Trackmania.ForceEndRound', array());
                $this->connection->triggerModeScriptEvent('Rounds_ForceEndRound');
            }

            if ($this->currentVote->action == "AutoTeamBalance") {
                $this->connection->autoTeamBalance();
            }

        } else {
            $msg = eXpGetMessage('#vote_failure# $iVote failed!');
            $this->eXpChatSendServerMessage($msg, null);
        }

        $this->currentVote = null;
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($this->currentVote) {
            $this->handleEndVote(false);
        }
    }

    public function vote_Restart($login)
    {
        try {
            $managedVotes = $this->getVotes();

            // if vote is not managed...
            if (!array_key_exists('RestartMap', $managedVotes)) {
                return;
            }

            // if vote is not managed...
            if ($managedVotes['RestartMap']->managed == false) {
                return;
            }

            if ($managedVotes['RestartMap']->ratio == -1.) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#Restart vote is disabled!"), $login);
                return;
            }

            if ($this->currentVote) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#There is already a vote in progress!"), $login);
                return;
            }

            /** @var Config */
            $config = Config::getInstance();

            if ($config->restartLimit != 0 && $config->restartLimit <= $this->resCount) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#Map limit for voting restart reached."), $login, array($this->config->restartLimit));
                return;
            }

            if (!isset($this->counters["RestartMap"])) {
                $this->counters["RestartMap"] = 0;
            }

            $this->counters["RestartMap"]++;

            if ($config->limit_votes > 0) {
                if ($this->counters["RestartMap"] > $config->limit_votes) {
                    $msg = eXpGetMessage("Vote limit reached.");
                    $this->eXpChatSendServerMessage($msg);
                    return;
                }
            }


            $vote = $managedVotes['RestartMap'];

            $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "RestartMap", "", 'Replay This Map ?', $vote->voters, time());

            $this->debug("[exp\\Votes] Calling Restart (queue) vote..");

            $this->displayWidget($this->currentVote);

            $player = $this->storage->getPlayerObject($login);
            $msg = eXpGetMessage('#variable#%s #vote#initiated restart map vote..');
            $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

            if ($config->autoVoteStarter) {
                $this->handlePlayerVote($login, "yes");
            }
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage("[Notice] " . $e->getMessage(), $login);
        }
    }

    public function vote_Skip($login)
    {
        try {
            $managedVotes = $this->getVotes();

            // if vote is not managed...
            if (!array_key_exists('NextMap', $managedVotes)) {
                return;
            }

            // if vote is not managed...
            if ($managedVotes['NextMap']->managed == false) {
                return;
            }

            if ($managedVotes['NextMap']->ratio == -1.) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#Skip vote is disabled!"), $login);
                return;
            }

            if ($this->currentVote) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#There is already a vote in progress!"), $login);
                return;
            }

            /** @var Config */
            $config = Config::getInstance();

            if (!isset($this->counters["NextMap"])) {
                $this->counters["NextMap"] = 0;
            }

            $this->counters["NextMap"]++;

            if ($config->limit_votes > 0) {
                if ($this->counters["NextMap"] > $config->limit_votes) {
                    $msg = eXpGetMessage("Vote limit reached.");
                    $this->eXpChatSendServerMessage($msg);
                    return;
                }
            }


            $vote = $managedVotes['NextMap'];

            $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "NextMap", "", 'Skip This Map ?', $vote->voters, time());

            $this->debug("[exp\Votes] Calling Skip vote..");

            $this->displayWidget($this->currentVote);

            $player = $this->storage->getPlayerObject($login);
            $msg = eXpGetMessage('#variable#%1$s #vote#initiated skip map vote..');
            $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

            if ($config->autoVoteStarter) {
                $this->handlePlayerVote($login, "yes");
            }
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage("[Notice] " . $e->getMessage(), $login);
        }
    }

    public function vote_Extend($login)
    {
        if (Core::$isTimeExtendable || Core::$isPointExtendable) {
            try {
                $managedVotes = $this->getVotes();

                //if vote is not managed...
                if (!array_key_exists('ExtendTime', $managedVotes)) {
                    return;
                }

                // if vote is not managed...
                if ($managedVotes['ExtendTime']->managed == false) {
                    return;
                }

                if ($managedVotes['ExtendTime']->ratio == -1.) {
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#Extend vote is disabled!"), $login);
                    return;
                }

                if ($this->currentVote) {
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#There is already a vote in progress!"), $login);
                    return;
                }

                /** @var Config */
                $config = Config::getInstance();

                if (!isset($this->counters["Extend"])) {
                    $this->counters["Extend"] = 0;
                }

                $this->counters["Extend"]++;

                if ($config->limit_votes > 0) {
                    if ($this->counters["Extend"] > $config->limit_votes) {
                        $msg = eXpGetMessage("Vote limit reached.");
                        $this->eXpChatSendServerMessage($msg);
                        return;
                    }
                }

                if (Core::$isTimeExtendable) {

                    $vote = $managedVotes['ExtendTime'];

                    $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "Extend", "", 'Extend The Time Limit ?', $vote->voters, time());

                    $this->debug("[exp\Votes] Calling extend vote..");

                    $this->displayWidget($this->currentVote);

                    $player = $this->storage->getPlayerObject($login);
                    $msg = eXpGetMessage('#variable#%1$s #vote#initiated extend time vote..');
                    $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

                    if ($config->autoVoteStarter) {
                        $this->handlePlayerVote($login, "yes");
                    }

                } else {

                    $vote = $managedVotes['ExtendTime'];

                    $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "Extend", "", 'Extend The Point Limit ?', $vote->voters, time());

                    $this->debug("[exp\Votes] Calling extend vote..");

                    $this->displayWidget($this->currentVote);

                    $player = $this->storage->getPlayerObject($login);
                    $msg = eXpGetMessage('#variable#%1$s #vote#initiated extend point vote..');
                    $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

                    if ($config->autoVoteStarter) {
                        $this->handlePlayerVote($login, "yes");
                    }
                }

            } catch (\Exception $e) {
                $this->connection->chatSendServerMessage("[Notice] " . $e->getMessage(), $login);
            }
        } else {
            $this->connection->chatSendServerMessage("Not in TimeAttack or Rounds mode", $login);
        }
    }

    public function vote_Extend_Custom($login, $params)
    {
        if (Core::$isTimeExtendable || Core::$isPointExtendable) {
            try {
                $managedVotes = $this->getVotes();

                //if vote is not managed...
                if (!array_key_exists('ExtendTime', $managedVotes)) {
                    return;
                }

                // if vote is not managed...
                if ($managedVotes['ExtendTime']->managed == false) {
                    return;
                }

                if ($managedVotes['ExtendTime']->ratio == -1.) {
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#Extend vote is disabled!"), $login);
                    return;
                }

                if ($this->currentVote) {
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#There is already a vote in progress!"), $login);
                    return;
                }

                if (!is_numeric($params[0])) {
                    $this->eXpChatSendServerMessage(eXpGetMessage('#admin_error#You need to provide a correct number'), $login);
                    return;
                }

                /** @var Config */
                $config = Config::getInstance();

                if ($config->extendTimeLimit != -1 && $params > $config->extendTimeLimit) {
                    $this->eXpChatSendServerMessage(eXpGetMessage("#admin_error#You are trying to add too much time, the max time is $config->extendTimeLimit"), $login);
                    return;
                }

                if (!isset($this->counters["Extend"])) {
                    $this->counters["Extend"] = 0;
                }

                $this->counters["Extend"]++;

                if ($config->limit_votes > 0) {
                    if ($this->counters["Extend"] > $config->limit_votes) {
                        $msg = eXpGetMessage("Vote limit reached.");
                        $this->eXpChatSendServerMessage($msg);
                        return;
                    }
                }

                if (Core::$isTimeExtendable) {

                    $vote = $managedVotes['ExtendTime'];

                    $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "Add", "$params", "Extend The Time Limit With $params Minutes ?", $vote->voters, time());

                    $this->debug("[exp\Votes] Calling extend vote..");

                    $this->displayWidget($this->currentVote);

                    $player = $this->storage->getPlayerObject($login);
                    $msg = eXpGetMessage('#variable#%1$s #vote#initiated extend time vote..');
                    $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

                    if ($config->autoVoteStarter) {
                        $this->handlePlayerVote($login, "yes");
                    }

                } else {

                    $vote = $managedVotes['ExtendTime'];

                    $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "Add", "$params", "Extend The Time Limit With $params Points ?", $vote->voters, time());

                    $this->debug("[exp\Votes] Calling extend vote..");

                    $this->displayWidget($this->currentVote);

                    $player = $this->storage->getPlayerObject($login);
                    $msg = eXpGetMessage('#variable#%1$s #vote#initiated extend point vote..');
                    $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

                    if ($config->autoVoteStarter) {
                        $this->handlePlayerVote($login, "yes");
                    }
                }

            } catch (\Exception $e) {
                $this->connection->chatSendServerMessage("[Notice] " . $e->getMessage(), $login);
            }
        } else {
            $this->connection->chatSendServerMessage("Not in TimeAttack or Rounds mode", $login);
        }
    }

    public function vote_EndRound($login)
    {
        if ($this->eXpGetCurrentCompatibilityGameMode()== GameInfos::GAMEMODE_ROUNDS || $this->eXpGetCurrentCompatibilityGameMode()== GameInfos::GAMEMODE_CUP || $this->eXpGetCurrentCompatibilityGameMode()== GameInfos::GAMEMODE_TEAM) {
            try {
                $managedVotes = $this->getVotes();

                // if vote is not managed...
                if (!array_key_exists('EndRound', $managedVotes)) {
                    return;
                }

                // if vote is not managed...
                if ($managedVotes['EndRound']->managed == false) {
                    return;
                }

                if ($managedVotes['EndRound']->ratio == -1.) {
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#End round vote is disabled!"), $login);
                    return;
                }

                if ($this->currentVote) {
                    $this->eXpChatSendServerMessage(eXpGetMessage("#error#There is already a vote in progress!"), $login);
                    return;
                }

                /** @var Config */
                $config = Config::getInstance();

                if (!isset($this->counters["EndRound"])) {
                    $this->counters["EndRound"] = 0;
                }

                $this->counters["EndRound"]++;

                if ($config->limit_votes > 0) {
                    if ($this->counters["EndRound"] > $config->limit_votes) {
                        $msg = eXpGetMessage("Vote limit reached.");
                        $this->eXpChatSendServerMessage($msg);
                        return;
                    }
                }


                $vote = $managedVotes['EndRound'];

                $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "EndRound", "", 'End The Round ?', $vote->voters, time());

                $this->debug("[exp\Votes] Calling EndRound vote..");

                $this->displayWidget($this->currentVote);

                $player = $this->storage->getPlayerObject($login);
                $msg = eXpGetMessage('#variable#%1$s #vote#initiated endround vote..');
                $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

                if ($config->autoVoteStarter) {
                    $this->handlePlayerVote($login, "yes");
                }
            } catch (\Exception $e) {
                $this->connection->chatSendServerMessage("[Notice] " . $e->getMessage(), $login);
            }
        } else {
            $this->connection->chatSendServerMessage("Not in Rounds, Cup or Team gamemode", $login);
        }
    }

    public function vote_balance($login)
    {
        try {
            $managedVotes = $this->getVotes();

            // if vote is not managed...
            if (!array_key_exists('AutoTeamBalance', $managedVotes)) {
                return;
            }

            // if vote is not managed...
            if ($managedVotes['AutoTeamBalance']->managed == false) {
                return;
            }

            if ($managedVotes['AutoTeamBalance']->ratio == -1.) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#AutoTeamBalance vote is disabled!"), $login);
                return;
            }

            if ($this->currentVote) {
                $this->eXpChatSendServerMessage(eXpGetMessage("#error#There is already a vote in progress!"), $login);
                return;
            }

            /** @var Config */
            $config = Config::getInstance();

            if (!isset($this->counters["AutoTeamBalance"])) {
                $this->counters["AutoTeamBalance"] = 0;
            }

            $this->counters["AutoTeamBalance"]++;

            if ($config->limit_votes > 0) {
                if ($this->counters["AutoTeamBalance"] > $config->limit_votes) {
                    $msg = eXpGetMessage("Vote limit reached.");
                    $this->eXpChatSendServerMessage($msg);
                    return;
                }
            }


            $vote = $managedVotes['AutoTeamBalance'];

            $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "AutoTeamBalance", "", 'Balance Teams ?', $vote->voters, time());

            $this->debug("[exp\Votes] Calling AutoTeamBalance vote..");

            $this->displayWidget($this->currentVote);

            $player = $this->storage->getPlayerObject($login);
            $msg = eXpGetMessage('#variable#%1$s #vote#initiated AutoTeamBalance vote..');
            $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

            if ($config->autoVoteStarter) {
                $this->handlePlayerVote($login, "yes");
            }
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage("[Notice] " . $e->getMessage(), $login);
        }
    }

    public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
    {
        /** @var Config */
        $config = Config::getInstance();

        // check for our stuff...
        if ($stateName == "NewVote") {

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

            if ($config->limit_votes > 0) {
                if ($this->counters[$cmdName] > $config->limit_votes) {
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
        if ($this->currentVote) {
            $this->handleEndVote(true);
            $msg = eXpGetMessage('#admin_action#Admin #variable#%1$s #admin_action# pass the vote!');
            $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->getPlayerObject($login)->nickName, 'wosnm'), $login));
        } else {
            $this->connection->chatSendServerMessage('Notice: Can\'t pass a vote, no vote in progress!', $login);
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
    }
}
