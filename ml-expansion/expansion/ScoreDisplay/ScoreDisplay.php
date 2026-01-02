<?php

namespace ManiaLivePlugins\eXpansion\ScoreDisplay;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Helpers\Countries;
use ManiaLivePlugins\eXpansion\ScoreDisplay\Gui\Windows\ScoreSetup;

class ScoreDisplay extends ExpPlugin
{

    private $cmd_scores;
    private $config;
    private $widget;

    public static $actionStart;
    private $actionPlus0;
    private $actionMinus0;
    private $actionPlus1;
    private $actionMinus1;

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
        $this->config = Config::getInstance();
        $cmd = AdminGroups::addAdminCommand('scores', $this, 'scores', Permission::QUIZ_ADMIN);
        $cmd->setHelp('Setup the scores widget');
        $cmd->setHelpMore('$wSetup the scores widget');
        $this->cmd_scores = $cmd;

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        self::$actionStart = $aH->createAction(array($this, "displayWidget"));
        $this->actionPlus0 = $aH->createAction(array($this, "add"), 0);
        $this->actionMinus0 = $aH->createAction(array($this, "sub"), 0);
        $this->actionPlus1 = $aH->createAction(array($this, "add"), 1);
        $this->actionMinus1 = $aH->createAction(array($this, "sub"), 1);

        $this->widget = new Widget("ScoreDisplay\Gui\Widgets\Scores.xml");
        $this->widget->setName("ScoreWidget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(45, 7);
        $this->widget->setParam("actionPlus0", $this->actionPlus0);
        $this->widget->setParam("actionMinus0", $this->actionMinus0);
        $this->widget->setParam("actionPlus1", $this->actionPlus1);
        $this->widget->setParam("actionMinus1", $this->actionMinus1);
    }

    public function scores($login, $params = array())
    {
        $command = array_shift($params);

        if (!$command) {
            $this->eXpChatSendServerMessage("valid parameters: hide, setup", $login);
        }

        if ($command == "setup") {
            /** @var ScoreSetup $window */
            $window = ScoreSetup::Create($login);
            $window->setSize(40, 80);
            $window->setTitle("ScoreSetup");
            $window->show();
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
            ScoreSetup::Erase($login);
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
            $this->widget = null;
        }
        AdminGroups::removeAdminCommand($this->cmd_scores);

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        $aH->deleteAction($this->actionPlus0);
        $aH->deleteAction($this->actionMinus0);
        $aH->deleteAction($this->actionPlus1);
        $aH->deleteAction($this->actionMinus1);

        $this->actionPlus0 = null;
        $this->actionMinus0 = null;
        $this->actionPlus1 = null;
        $this->actionMinus1 = null;
        $this->config = null;

        parent::eXpOnUnload();
    }
}
