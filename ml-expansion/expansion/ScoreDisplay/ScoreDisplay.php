<?php

namespace ManiaLivePlugins\eXpansion\ScoreDisplay;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\ScoreDisplay\Gui\Widgets\Scores;
use ManiaLivePlugins\eXpansion\ScoreDisplay\Gui\Windows\ScoreSetup;

class ScoreDisplay extends ExpPlugin
{

    private $cmd_scores;
    private $config;

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $cmd = AdminGroups::addAdminCommand('scores', $this, 'scores', Permission::QUIZ_ADMIN);
        $cmd->setHelp('Setup the scores widget');
        $cmd->setHelpMore('$wSetup the scores widget');
        $this->cmd_scores = $cmd;
    }


    public function scores($login, $params = array())
    {
        if (!AdminGroups::hasPermission($login, Permission::QUIZ_ADMIN)) {
            $this->eXpChatSendServerMessage("No Permission.", $login);
            return;
        }
        
        $command = array_shift($params);

        if (!$command) {
            $this->eXpChatSendServerMessage("valid parameters: hide, setup", $login);
        }

        if ($command == "setup") {
            $window = ScoreSetup::Create($login);
            //$window->setPosition($this->config->scoreWidget_PosX, $this->config->scoreWidget_PosY);
            $window->setSize(40, 80);
            $window->setTitle("ScoreSetup");
            $window->show();
            return;
        }

        if ($command == "hide") {
            Scores::EraseAll();
        }
    }

    public function eXpOnUnload()
    {
        AdminGroups::removeAdminCommand($this->cmd_scores);
    }
}
