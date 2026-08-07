<?php

namespace ManiaLivePlugins\eXpansion\ScoreDisplay;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Helpers\Countries;

class ScoreDisplay extends ExpPlugin
{

    private $cmd_scores;
    private $config;
    private $widget;
    private $scoreSetupWindow;

    private $widgetData = array(
        "teamName0" => "",
        "teamName1" => "",
        "flag0" => "",
        "flag1" => "",
        "score0" => 0,
        "score1" => 0,
    );

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents(\ManiaLive\DedicatedApi\Callback\Event::ON_PLAYER_MANIALINK_PAGE_ANSWER);
        $this->registerManialinkCallback('add', false, true);
        $this->registerManialinkCallback('sub', false, true);
        $this->registerManialinkCallback('displayWidget', true);
        
        $this->config = Config::getInstance();
        $cmd = AdminGroups::addAdminCommand('scores', $this, 'scores', Permission::QUIZ_ADMIN);
        $cmd->setHelp('Setup the scores widget');
        $cmd->setHelpMore('$wSetup the scores widget');
        $this->cmd_scores = $cmd;

        $this->widget = new Widget("ScoreDisplay\Gui\Widgets\Scores.xml");
        $this->widget->setName("ScoreWidget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(45, 7);

        $this->scoreSetupWindow = new Window("ScoreDisplay\Gui\Windows\ScoreSetup.xml");
        $this->scoreSetupWindow->setName("ScoreSetup");
        $this->scoreSetupWindow->setSize(40, 80);
        $this->scoreSetupWindow->setTitle('ScoreSetup');
    }

    public function scores($login, $params = array())
    {
        $command = array_shift($params);

        if (!$command) {
            $this->eXpChatSendServerMessage("valid parameters: hide, setup", $login);
        }

        if ($command == "setup") {
            $this->scoreSetupWindow->show($login);
            return;
        }

        if ($command == "hide") {
            if ($this->widget instanceof Widget) {
                $this->widget->erase();
            }
            $this->widgetData = array(
                "teamName0" => "",
                "teamName1" => "",
                "flag0" => "",
                "flag1" => "",
                "score0" => 0,
                "score1" => 0,
            );
        }
    }

    public function displayWidget($login, $data)
    {
        if ($data) {
            $this->scoreSetupWindow->erase($login);
            $this->widgetData = array(
                "teamName0" => $data['team1Name'],
                "teamName1" => $data['team2Name'],
                "flag0" => "http://reaby.kapsi.fi/ml/flags/" . Countries::getCountryFromCode($data['team1Country']) . ".dds",
                "flag1" => "http://reaby.kapsi.fi/ml/flags/" . Countries::getCountryFromCode($data['team2Country']) . ".dds",
                "score0" => 0,
                "score1" => 0,
            );
        } else if (!$this->widget instanceof Widget) {
            return;
        }

        $this->widget->setParam("data", $this->widgetData);
        $this->widget->setPosition($this->config->scoreWidget_PosX, $this->config->scoreWidget_PosY, 0);
        $this->widget->show(null, true);
    }

    public function add($login, $team)
    {
        if (!AdminGroups::hasPermission($login, Permission::QUIZ_ADMIN)) {
            $this->eXpChatSendServerMessage("You don't have permission to do that.", $login);
            return;
        }
        $this->widgetData["score" . $team]++;
        $this->displayWidget(null, null);
    }

    public function sub($login, $team)
    {
        if (!AdminGroups::hasPermission($login, Permission::QUIZ_ADMIN)) {
            $this->eXpChatSendServerMessage("You don't have permission to do that.", $login);
            return;
        }
        $this->widgetData["score" . $team]--;
        $this->displayWidget(null, null);
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->widget = null;

        if ($this->scoreSetupWindow instanceof Window) {
            $this->scoreSetupWindow->erase();
        }
        $this->scoreSetupWindow = null;
        
        AdminGroups::removeAdminCommand($this->cmd_scores);

        $this->config = null;

        parent::eXpOnUnload();
    }
}
