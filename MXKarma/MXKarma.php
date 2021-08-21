<?php

namespace ManiaLivePlugins\eXpansion\MXKarma;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\MXKarma\Classes\Connection as mxConnection;
use ManiaLivePlugins\eXpansion\MXKarma\Events\MXKarmaEvent;
use ManiaLivePlugins\eXpansion\MXKarma\Events\MXKarmaEventListener;
use ManiaLivePlugins\eXpansion\MXKarma\Gui\Widgets\MXRatingsWidget;
use ManiaLivePlugins\eXpansion\MXKarma\Structures\MXRating;
use ManiaLivePlugins\eXpansion\MXKarma\Structures\MXVote;

class MXKarma extends ExpPlugin implements MXKarmaEventListener
{

    /** @var Connection */
    private $mxConnection;

    private $mxMapStart = -1;

    /** @var MXRating */
    private $mxRatings = null;

    /** @var String[][] */
    private $mx_votes = array();

    /** @var MXVote[] */
    private $mx_votesTemp = array();

    private $mx_msg_error;
    private $mx_msg_connected;

    /** @var Config */
    private $config;

    private $settingsChanged = array();

    public function eXpOnLoad()
    {
        parent::eXpOnLoad();
        $this->config = Config::getInstance();
        $this->mxConnection = new mxConnection();
        $this->mx_msg_error = eXpGetMessage('MXKarma error %1$s: %2$s');
        $this->mx_msg_connected = eXpGetMessage('MXKarma connection Success!');
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        \ManiaLive\Event\Dispatcher::register(MXKarmaEvent::getClass(), $this);

        $this->mxMapStart = time();
        $this->tryConnect();
    }

    private function tryConnect()
    {
        $admins = AdminGroups::getInstance();
        $this->config = Config::getInstance();
        if (!$this->mxConnection->isConnected()) {
            if (empty($this->config->mxKarmaServerLogin) || empty($this->config->mxKarmaApiKey)) {
                $admins->announceToPermission(Permission::EXPANSION_PLUGIN_SETTINGS, "#admin_error#Server login or/and Server code is empty in MXKarma Configuration");
                $this->console("Server code or/and login is not configured for MXKarma plugin!");
                return;
            }
            $this->mxConnection->connect($this->config->mxKarmaServerLogin, $this->config->mxKarmaApiKey);
        } else {
            $admins->announceToPermission(Permission::EXPANSION_PLUGIN_SETTINGS, "#admin_error#Tried to connect to MXKarma, but connection is already made.");
            $this->console("Tried to connect to MXKarma, but connection is already made.");
        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        $this->settingsChanged[$var->getName()] = true;
        if (array_key_exists("mxKarmaApiKey", $this->settingsChanged) && array_key_exists("mxKarmaServerLogin", $this->settingsChanged)) {
            $this->tryConnect();
            $this->settingsChanged = array();
        }
    }

    public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
    {
        if ($playerUid == 0) {
            return;
        }

        $player = $this->storage->getPlayerObject($login);

        switch ($text) {
            case "+++":
                $this->vote($player, 100);
                break;
            case "++":
                $this->vote($player, 80);
                break;
            case "+":
                $this->vote($player, 60);
                break;
            case "+-":
            case "-+":
                $this->vote($player, 50);
                break;
            case "-":
                $this->vote($player, 40);
                break;
            case "--":
                $this->vote($player, 20);
                break;
            case "---":
                $this->vote($player, 0);
                break;
        }

    }

    public function vote($player, $vote)
    {
        $oldVote = ArrayOfObj::getObjbyPropValue($this->mxRatings->votes, "login", $player->login);

        if ($oldVote) {
            if ($oldVote->vote == $vote) {
                $this->eXpChatSendServerMessage("Vote registered for MXKarma", $player->login);
                return;
            } else {
                if ($this->mxRatings->votecount != 1) {

                    $reAverage = ($this->mxRatings->voteaverage) - (($oldVote->vote - $this->mxRatings->voteaverage) / ($this->mxRatings->votecount-1));
                    $this->mxRatings->votecount -= 1;
                    $this->mxRatings->voteaverage = $reAverage;
                    unset($this->mxRatings->votes[array_search($player->login, $this->mxRatings->votes)]);

                } else {

                    $this->mxRatings->votecount = 0;
                    $this->mxRatings->voteaverage = 50;
                    unset($this->mxRatings->votes[array_search($player->login, $this->mxRatings->votes)]);

                }
            }
        }
        
        $this->mx_votesTemp[$player->login] = new MXVote($player, $vote);
        $this->eXpChatSendServerMessage("Vote registered for MXKarma", $player->login);

        $widget = MXRatingsWidget::Create();
        $x = 0;
        $avgTempVotes = 0;
        foreach ($this->mx_votesTemp as $vote) {
            $avgTempVotes += $vote->vote;
            $x++;
        }
        if ($x > 0) {
            $avgTempVotes = $avgTempVotes / $x;
        }
        $newAverage = (($this->mxRatings->voteaverage * $this->mxRatings->votecount) + ($avgTempVotes*$x)) / ($this->mxRatings->votecount+$x);
        $widget->setRating($newAverage, ($this->mxRatings->votecount+$x));
        $widget->show();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        parent::onBeginMatch();
        $this->mxRatings = null;
        $this->mx_votes = array();
        $this->mx_votesTemp = array();
        $this->mxMapStart = time();
        if ($this->mxConnection->isConnected()) {
            $this->mxConnection->getRatings($this->getPlayers(), false);
        }
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        $newVotes = array();

        foreach ($this->mx_votesTemp as $login => $vote) {
            $newVotes[] = $vote;
        }

        if (count($newVotes) > 0) {
            $outArray = array();
            foreach ($newVotes as $login => $vote) {
                $outArray[] = $vote;
            }

            $this->mxConnection->saveVotes($this->storage->currentMap, time() - $this->mxMapStart, $outArray);
        }

        MXRatingsWidget::EraseAll();
    }

    public function getPlayers()
    {
        $players = array();

        $players = array_keys($this->storage->players);
        array_merge($players, array_keys($this->storage->players));

        $spectators = array_keys($this->storage->spectators);
        array_merge($spectators, array_keys($this->storage->spectators));

        $total = array_merge($spectators, $players);
        return $total;
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if ($this->mxConnection->isConnected()) {
            $playerVote = ArrayOfObj::getObjbyPropValue($this->mxRatings->votes, "login", $login);
            if ($playerVote) {
                return;
            } else {
                if (array_key_exists($login, $this->mx_votesTemp)) {
                    return;
                } else {
                    $this->mxConnection->getRatings(array($login), true);
                }
            }
        }
    }

    public function MXKarma_onConnected()
    {
        $this->mxConnection->getRatings($this->getPlayers(), false);
    }

    public function MXKarma_onDisconnected()
    {

    }

    public function MXKarma_onError($state, $number, $reason)
    {
        $this->eXpChatSendServerMessage($this->mx_msg_error, null, array($state, $reason));
        $this->console("MXKarma error  " . $state . ": " . $reason);
    }

    public function MXKarma_onVotesRecieved(MXRating $votes)
    {
        if ($this->mxRatings === null) {
            $this->mxRatings = $votes;
            $this->mx_votes = array();
            foreach ($votes->votes as $vote) {
                $this->mx_votes[] = $vote;
            }

            $widget = MXRatingsWidget::Create();
            $widget->setRating($this->mxRatings->voteaverage, $this->mxRatings->votecount);
            $widget->show();

        } else {
            foreach ($votes->votes as $vote) {
                $this->mx_votes[] = $vote;
                $this->mxRatings->votes[] = $vote;
            }
        }
    }

    public function MXKarma_onVotesSave($isSuccess)
    {
        if ($isSuccess) {
            $this->console("MXKarma saved successfully!");
        } else {
            $this->console("Failed to save MXKarma!");
        }
    }

    public function eXpOnUnload()
    {
        \ManiaLive\Event\Dispatcher::unregister(MXKarmaEvent::getClass(), $this);
        MXRatingsWidget::EraseAll();
        unset($this->mxConnection);
        parent::eXpOnUnload();
    }
}
