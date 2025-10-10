<?php

namespace ManiaLivePlugins\eXpansion\ExtendTime;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class ExtendTime extends ExpPlugin
{

    protected $votes = ["yes" => 0, "no" => 0];
    protected $voters = [];
    protected $config;
    protected $voteCount = 0;
    protected $widget;
    protected $actionYes;
    protected $actionNo;
    protected $actionCalcVotes;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        /** @var Config $config */
        $this->config = Config::getInstance();

        /** @var ActionHandler @aH */
        $ah = ActionHandler::getInstance();
        $this->actionYes = $ah->createAction(array($this, "vote"), "yes");
        $this->actionNo = $ah->createAction(array($this, "vote"), "no");
        $this->actionCalcVotes = $ah->createAction(array($this, "calcVotes"));

        
        $script = new Script("ExtendTime/Gui/Script");
        $script->setParam("actionYes", $this->actionYes);
        $script->setParam("actionNo", $this->actionNo);
        $script->setParam("calcVotes", $this->actionCalcVotes);

        $this->widget = new Widget("ExtendTime\Gui\Widgets\TimeExtendVote.xml");
        $this->widget->setName("Extend Timelimit");
        $this->widget->setLayer("normal");
        $this->widget->setSize(90, 25);
        $this->widget->registerScript($script);


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
        $this->widget->erase();
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
                $this->widget->erase();
            }
        }

        $this->votes = ["yes" => 0, "no" => 0];
        $this->voters = [];
    }

    public function vote($login, $vote)
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\Votes\Votes')) {
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Votes\Votes', 'cancelAutoExtend');
        }
        if (!array_key_exists($login, $this->voters)) {
            $this->voters[$login] = true;
            $this->votes[$vote] += 1;
            $this->eXpChatSendServerMessage('%s$z#vote# voted #variable#%s #vote#for extending time.', null, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->getPlayerObject($login)->nickName, 'wosnm'), $vote));
            $this->eXpChatSendServerMessage("#vote#The vote will end at 15 seconds before the end of the map.", $login);
        }
    }

    public function showWidget()
    {
        $this->widget->setPosition($this->config->extendWidget_PosX, $this->config->extendWidget_PosY, 0);
        $this->widget->show(null, true);
    }

    public function eXpOnUnload()
    {
        $this->widget->erase();
        /** @var ActionHandler @aH */
        $ah = ActionHandler::getInstance();
        $ah->deleteAction($this->actionYes);
        $ah->deleteAction($this->actionNo);
        $ah->deleteAction($this->actionCalcVotes);
        $this->votes = [];
        $this->voters = [];
        $this->voteCount = 0;
        $this->config = null;
    }
}
