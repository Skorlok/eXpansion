<?php

namespace ManiaLivePlugins\eXpansion\Votes;

use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\Votes\Gui\Windows\VoteSettingsWindow;
use ManiaLivePlugins\eXpansion\Votes\Gui\Widgets\VoteManagerWidget;
use ManiaLivePlugins\eXpansion\Votes\Structures\Vote;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\Events\GlobalEvent;

class Votes extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    /** @var Config */
    private $config;
    private $useQueue = false;
    private $counters = array();
    private $resCount = 0;
    private $lastMapUid = "";

    public $currentVote = null;
    public $currentVoteWidget = null;

    public function eXpOnInit()
    {
        $this->config = Config::getInstance();
        VoteManagerWidget::$parentPlugin = $this;
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

        $cmd = AdminGroups::addAdminCommand('cancel', $this, 'cancelVote', 'cancel_vote');
        $cmd->setHelp('Cancel current running vote');
        AdminGroups::addAlias($cmd, "can");

        $cmd = AdminGroups::addAdminCommand('passvote', $this, 'passVote', 'pass_vote');
        $cmd->setHelp('Pass current running vote');
        AdminGroups::addAlias($cmd, "passv");
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
            
            $this->currentVoteWidget->updateTimeleft(($this->currentVote->timestamp - time()) + $this->currentVote->votingTime);

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

    public function handlePlayerVote($login, $vote)
    {
        $this->currentVote->playerVotes[$login] = $vote;

        // Check if vote passes when we suppose that all players that didn't vote would vote NO.
        $playerCount = count($this->storage->players) + count($this->storage->spectators);
        if ($playerCount > 0 && ($this->currentVote->getYes() / $playerCount) > $this->currentVote->voteRatio) {
            $this->handleEndVote(true);
            return;
        }

        $this->currentVoteWidget->setDatas($this->currentVote, ($this->currentVote->timestamp - time()) + $this->currentVote->votingTime);
        $this->currentVoteWidget->RedrawAll();
    }

    public function handleEndVote($state)
    {
        VoteManagerWidget::EraseAll();
        $this->currentVoteWidget = null;

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

            $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "RestartMap", "", Core::$players[$login] . '$z$z want to replay this map, you too ?', $vote->voters, time());

            $this->debug("[exp\\Votes] Calling Restart (queue) vote..");

            VoteManagerWidget::EraseAll();
            $this->currentVoteWidget = VoteManagerWidget::Create(null);
            $this->currentVoteWidget->setSize(90, 20);
            $this->currentVoteWidget->setDatas($this->currentVote, ($this->currentVote->timestamp - time()) + $this->currentVote->votingTime);
            $this->currentVoteWidget->show();

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

            $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "NextMap", "", Core::$players[$login] . '$z$z want to skip this map, you too ?', $vote->voters, time());

            $this->debug("[exp\Votes] Calling Skip vote..");

            VoteManagerWidget::EraseAll();
            $this->currentVoteWidget = VoteManagerWidget::Create(null);
            $this->currentVoteWidget->setSize(90, 20);
            $this->currentVoteWidget->setDatas($this->currentVote, ($this->currentVote->timestamp - time()) + $this->currentVote->votingTime);
            $this->currentVoteWidget->show();

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

                    $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "Extend", "", Core::$players[$login] . '$z$z want to extend the time limit, you too ?', $vote->voters, time());

                    $this->debug("[exp\Votes] Calling extend vote..");

                    VoteManagerWidget::EraseAll();
                    $this->currentVoteWidget = VoteManagerWidget::Create(null);
                    $this->currentVoteWidget->setSize(90, 20);
                    $this->currentVoteWidget->setDatas($this->currentVote, ($this->currentVote->timestamp - time()) + $this->currentVote->votingTime);
                    $this->currentVoteWidget->show();

                    $player = $this->storage->getPlayerObject($login);
                    $msg = eXpGetMessage('#variable#%1$s #vote#initiated extend time vote..');
                    $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

                    if ($config->autoVoteStarter) {
                        $this->handlePlayerVote($login, "yes");
                    }

                } else {

                    $vote = $managedVotes['ExtendTime'];

                    $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "Extend", "", Core::$players[$login] . '$z$z want to extend the point limit, you too ?', $vote->voters, time());

                    $this->debug("[exp\Votes] Calling extend vote..");

                    VoteManagerWidget::EraseAll();
                    $this->currentVoteWidget = VoteManagerWidget::Create(null);
                    $this->currentVoteWidget->setSize(90, 20);
                    $this->currentVoteWidget->setDatas($this->currentVote, ($this->currentVote->timestamp - time()) + $this->currentVote->votingTime);
                    $this->currentVoteWidget->show();

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

                    $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "Add", "$params", Core::$players[$login] . "\$z\$z want to extend the time limit with $params minutes, you too ?", $vote->voters, time());

                    $this->debug("[exp\Votes] Calling extend vote..");

                    VoteManagerWidget::EraseAll();
                    $this->currentVoteWidget = VoteManagerWidget::Create(null);
                    $this->currentVoteWidget->setSize(90, 20);
                    $this->currentVoteWidget->setDatas($this->currentVote, ($this->currentVote->timestamp - time()) + $this->currentVote->votingTime);
                    $this->currentVoteWidget->show();

                    $player = $this->storage->getPlayerObject($login);
                    $msg = eXpGetMessage('#variable#%1$s #vote#initiated extend time vote..');
                    $this->eXpChatSendServerMessage($msg, null, array(\ManiaLib\Utils\Formatting::stripCodes($player->nickName, 'wosnm')));

                    if ($config->autoVoteStarter) {
                        $this->handlePlayerVote($login, "yes");
                    }

                } else {

                    $vote = $managedVotes['ExtendTime'];

                    $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "Add", "$params", Core::$players[$login] . "\$z\$z want to extend the time limit with $params points, you too ?", $vote->voters, time());

                    $this->debug("[exp\Votes] Calling extend vote..");

                    VoteManagerWidget::EraseAll();
                    $this->currentVoteWidget = VoteManagerWidget::Create(null);
                    $this->currentVoteWidget->setSize(90, 20);
                    $this->currentVoteWidget->setDatas($this->currentVote, ($this->currentVote->timestamp - time()) + $this->currentVote->votingTime);
                    $this->currentVoteWidget->show();

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

                $this->currentVote = new Vote($login, $vote->timeout, $vote->ratio, array(), "EndRound", "", Core::$players[$login] . '$z$z want to end the round, you too ?', $vote->voters, time());

                $this->debug("[exp\Votes] Calling EndRound vote..");

                VoteManagerWidget::EraseAll();
                $this->currentVoteWidget = VoteManagerWidget::Create(null);
                $this->currentVoteWidget->setSize(90, 20);
                $this->currentVoteWidget->setDatas($this->currentVote, ($this->currentVote->timestamp - time()) + $this->currentVote->votingTime);
                $this->currentVoteWidget->show();

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

    public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
    {
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
        VoteManagerWidget::EraseAll();
    }
}
