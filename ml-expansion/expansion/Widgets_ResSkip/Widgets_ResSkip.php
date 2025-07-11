<?php

namespace ManiaLivePlugins\eXpansion\Widgets_ResSkip;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Core\types\Bill;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Donate\Config as DonateConfig;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_ResSkip extends ExpPlugin
{

    private $msg_resOnProgress;
    private $msg_resUnused;
    private $msg_resMax;
    private $msg_skipUnused;
    private $msg_skipMax;
    private $msg_prestart;
    private $msg_pskip;

    private $config;

    private $donateConfig;

    private $lastMapUid = null;

    private $resCount = 0;

    private $resActive;

    private $skipCount = 0;

    private $skipActive;

    private $actions = array();

    private $widget;

    public function eXpOnLoad()
    {
        $this->msg_resOnProgress = eXpGetMessage("The restart of this track is in progress!");
        $this->msg_prestart = eXpGetMessage("#player#Player #variable# %s #player#pays and restarts the challenge!");
        $this->msg_pskip = eXpGetMessage('#player#Player#variable# %s #player#pays and skips the challenge!');
        $this->msg_resUnused = eXpGetMessage("#error#Player can't restart tracks on this server");
        $this->msg_resMax = eXpGetMessage("#error#The map has already been restarted. Limit reached!");
        $this->msg_skipUnused = eXpGetMessage("#error#You can't skip tracks on this server.");
        $this->msg_skipMax = eXpGetMessage("#error#You have skipped to many maps already!");

        $this->setPublicMethod('isPublicResIsActive');
        $this->setPublicMethod('isPublicSkipActive');
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->config = Config::getInstance();
        $this->donateConfig = DonateConfig::getInstance();

        $this->actions['skip'] = ActionHandler::getInstance()->createAction(array($this, "skipMap"));
        $this->actions['res'] = ActionHandler::getInstance()->createAction(array($this, "restartMap"));

        $this->showResSkip();
    }

    public function isPublicResIsActive()
    {
        return !(empty($this->config->publicResAmount) || $this->config->publicResAmount[0] == -1);
    }

    public function isPublicSkipActive()
    {
        return !(empty($this->config->publicSkipAmount) || $this->config->publicSkipAmount[0] == -1);
    }

    public function showResSkip()
    {
        if ($this->expStorage->isRelay) {
            return;
        }

        $skipAmount = null;
        $resAmount = null;
        if (isset($this->config->publicSkipAmount[$this->skipCount]) && $this->config->publicSkipAmount[$this->skipCount] > 0) {
            $skipAmount = $this->config->publicSkipAmount[$this->skipCount];
        }

        if (isset($this->config->publicResAmount[$this->resCount]) && $this->config->publicResAmount[$this->resCount] > 0) {
            $resAmount = $this->config->publicResAmount[$this->resCount];
        }


        $this->widget = new Widget("Widgets_ResSkip\Gui\Widgets\ResSkipButtons.xml");
        $this->widget->setName("Skip and Res Buttons");
        $this->widget->setLayer("normal");
        if ($this->expStorage->simpleEnviTitle == "SM") {
            $this->widget->setPosition($this->config->resSkipButtons_PosX_Shootmania, $this->config->resSkipButtons_PosY_Shootmania, 0);
        } else {
            $this->widget->setPosition($this->config->resSkipButtons_PosX, $this->config->resSkipButtons_PosY, 0);
        }
        $this->widget->setSize(20, 10);
        $this->widget->setParam("resAmount", $resAmount);
        $this->widget->setParam("skipAmount", $skipAmount);
        $this->widget->setParam("resAction", ($resAmount != null ? $this->actions['res'] : null));
        $this->widget->setParam("skipAction", ($skipAmount != null ? $this->actions['skip'] : null));
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
        $this->widget->show(null, true);
    }

    public function restartMap($login)
    {
        //Player restart cost Planets
        if ($this->resActive) {
            //Already restarted no need to do
            $this->eXpChatSendServerMessage($this->msg_resOnProgress, $login);
        } elseif (isset($this->config->publicResAmount[$this->resCount]) && $this->config->publicResAmount[$this->resCount] != -1 && $this->resCount < count($this->config->publicResAmount)) {
            $amount = $this->config->publicResAmount[$this->resCount];
            $this->resActive = true;

            if (!empty($this->donateConfig->toLogin)) {
                $toLogin = $this->donateConfig->toLogin;
            } else {
                $toLogin = $this->storage->serverLogin;
            }

            $bill = $this->eXpStartBill($login, $toLogin, $amount, __("Are you sure you want to restart this map", $login), array($this, 'publicRestartMap'));
            $bill->setSubject('map_restart');
            $bill->setErrorCallback(5, array($this, 'failRestartMap'));
            $bill->setErrorCallback(6, array($this, 'failRestartMap'));
        } else {
            if (empty($this->config->publicResAmount) || $this->config->publicResAmount[0] == -1) {
                $this->eXpChatSendServerMessage($this->msg_resUnused, $login);
            } else {
                $this->eXpChatSendServerMessage($this->msg_resMax, $login);
            }
        }
    }

    public function publicRestartMap(Bill $bill)
    {
        $player = $this->storage->getPlayerObject($bill->getSourceLogin());
        $this->eXpChatSendServerMessage($this->msg_prestart, null, array($player->nickName));

        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\Maps\\Maps')) {
            $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\Maps\\Maps", "replayMap", $bill->getSourceLogin());

            return;
        }
        $this->connection->restartMap($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_CUP);
    }

    public function failRestartMap(Bill $bill, $state, $stateName)
    {
        $this->resActive = false;
    }

    public function publicSkipMap(Bill $bill)
    {
        $this->skipActive = true;
        $this->connection->nextMap($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_CUP);
        $player = $this->storage->getPlayerObject($bill->getSourceLogin());
        $this->eXpChatSendServerMessage($this->msg_pskip, null, array($player->nickName));
    }

    public function skipMap($login)
    {
        if (isset($this->config->publicSkipAmount[$this->skipCount]) && $this->config->publicSkipAmount[$this->skipCount] != -1 && $this->skipCount < count($this->config->publicSkipAmount)) {
            $amount = $this->config->publicSkipAmount[$this->skipCount];

            if (!empty($this->donateConfig->toLogin)) {
                $toLogin = $this->donateConfig->toLogin;
            } else {
                $toLogin = $this->storage->serverLogin;
            }

            $bill = $this->eXpStartBill($login, $toLogin, $amount, __("Are you sure you want to skip this map", $login), array($this, 'publicSkipMap'));
            $bill->setSubject('map_skip');
        } else {
            if (empty($this->config->publicSkipAmount) || $this->config->publicSkipAmount[0] == -1) {
                $this->eXpChatSendServerMessage($this->msg_skipUnused, $login);
            } else {
                $this->eXpChatSendServerMessage($this->msg_skipMax, $login);
            }
        }
    }

    private function countMapRestart()
    {
        if ($this->storage->currentMap->uId == $this->lastMapUid) {
            $this->resCount++;
        } else {
            $this->lastMapUid = $this->storage->currentMap->uId;
            $this->resCount = 0;
        }
        $this->resActive = false;

        if (!$this->skipActive) {
            $this->skipCount = 0;
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        $this->widget->erase();
    }

    public function onBeginMatch()
    {
        $this->countMapRestart();
        $this->showResSkip();
    }

    public function eXpOnUnload()
    {
        $this->widget->erase();
        $this->widget = null;
    }
}
