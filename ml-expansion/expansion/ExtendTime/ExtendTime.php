<?php

namespace ManiaLivePlugins\eXpansion\ExtendTime;

use ManiaLive\Gui\Window;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\ExtendTime\Gui\Widgets\TimeExtendVote;
use ManiaLivePlugins\eXpansion\Votes\Votes;

class ExtendTime extends ExpPlugin
{

    protected $votes = ["yes" => 0, "no" => 0];
    protected $voters = [];
    protected $config;
    protected $voteCount = 0;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        /** @var Config $config */
        $this->config = Config::getInstance();
        TimeExtendVote::$parentPlugin = $this;
        $this->showWidget();
    }

    function onBeginMatch()
    {
        $this->votes = ["yes" => 0, "no" => 0];
        $this->voters = [];
        $this->voteCount = 0;
        $this->showWidget();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        TimeExtendVote::EraseAll();
    }

    public function calcVotes()
    {
        $total = $this->votes['yes'] + $this->votes['no'];
        if ($total > 0) {
            if ( ($this->votes['yes'] / $total) > $this->config->ratio) {
                $this->eXpChatSendServerMessage("#vote#Vote to extend time: #vote_success# Success.");
                $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Core\Core', 'extendTime', null);
            } else {
                $this->eXpChatSendServerMessage("#vote#Vote to extend time: #vote_failure# Fail.");
            }

            $this->voteCount++;

            if ($this->voteCount >= Config::getInstance()->limit_votes && Config::getInstance()->limit_votes != -1) {
                TimeExtendVote::EraseAll();
            }
        }

        $this->votes = ["yes" => 0, "no" => 0];
        $this->voters = [];
    }

    public function vote($login, $vote)
    {
        if (!array_key_exists($login, $this->voters)) {
            $this->voters[$login] = true;
            $this->votes[$vote] += 1;
        }
    }


    public function showWidget()
    {
        TimeExtendVote::EraseAll();
        $widget = TimeExtendVote::Create(null);
        $widget->setPosition($this->config->extendWidget_PosX, $this->config->extendWidget_PosY);
        $widget->show();
    }

    public function eXpOnUnload()
    {
        TimeExtendVote::EraseAll();
    }
}
