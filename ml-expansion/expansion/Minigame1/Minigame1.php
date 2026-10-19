<?php

namespace ManiaLivePlugins\eXpansion\Minigame1;

use Exception;
use ManiaLive\DedicatedApi\Callback\Event;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Helpers\TimeConversion;

class Minigame1 extends ExpPlugin
{
    /** @var Config */
    private $config;

    private $timerGap = 30;

    private $tick = 0;

    /** @var StaticWidget */
    private $widget;

    public function eXpOnReady()
    {
        $this->enableTickerEvent();
        $this->enableDedicatedEvents(Event::ON_PLAYER_MANIALINK_PAGE_ANSWER);

        $this->config = Config::getInstance();
        $this->timerGap = $this->getTimerGap();

        $this->registerManialinkCallback('onClick');

        Gui::preloadImage($this->config->mg1_imageUrl);
        Gui::preloadImage($this->config->mg1_imageFocusUrl);
        Gui::preloadUpdate();

        $this->widget = new StaticWidget('Minigame1\Gui\Widgets\Minigame.xml');
        $this->widget->setName("Minigame1");
        $this->widget->setLayer("normal");
        $this->widget->setPosition(0, 0, 50);

        $this->colorParser->registerCode("game1", $this->config, "mg1_messageColor");
    }

    public function onClick($login)
    {
        $this->widget->erase();
        try {
            $player = $this->storage->getPlayerObject($login);
            $amount = intval(rand($this->config->mg1_giftMin, $this->config->mg1_giftMax));
            $message = 'You won ' . $amount . 'p from '
                . $this->storage->server->name . '$z$s minigame!' . "\n" . ' Congratulations! ';

            $this->connection->pay($login, $amount, $message);
            $this->eXpChatSendServerMessage(
                '%1$s $z$s#game1# wins #variable#%2$s #game1#planets',
                null,
                array($player->cleanNickName, $amount)
            );
        } catch (Exception $e) {
            $ac = AdminGroups::getInstance();
            $ac->announceToPermission(Permission::SERVER_ADMIN, "Minigame1 Error: " . $e->getMessage());
            $this->console("[Minigame1] Error:" . $e->getMessage());
        }
    }

    public function onTick()
    {
        $this->tick++;

        if ($this->tick % 15 == 0) {
            $this->config = Config::getInstance();
        }

        if ($this->tick > $this->timerGap) {
            if ($this->connection->getServerPlanets() < $this->config->mg1_serverPlanetsMin) {
                $msg = eXpGetMessage(
                    "#game1#The server has not enough #variable#Planets #game1#to run minigame, please #variable#Donate #game1#!"
                );
                $this->eXpChatSendServerMessage($msg);
            } else {
                $this->showWidget();
                $this->timerGap = $this->getTimerGap();
            }
            $this->tick = 0;
        }
    }

    private function showWidget()
    {
        $size = $this->config->mg1_imageSize;

        $this->widget->setParam("posX", rand(-160 + intval($size / 2), 160 - intval($size / 2)));
        $this->widget->setParam("posY", rand(-90 + intval($size / 2), 90 - intval($size / 2)));
        $this->widget->setParam("size", $size);
        $this->widget->setParam("imageUrl", $this->config->mg1_imageUrl);
        $this->widget->setParam("imageFocusUrl", $this->config->mg1_imageFocusUrl);
        $this->widget->show(null, true, $this->getDuration());
    }

    private function getTimerGap()
    {
        $min = TimeConversion::MStoTM($this->config->mg1_displayIntervalMin);
        $max = TimeConversion::MStoTM($this->config->mg1_displayIntervalMax);

        return intval(rand($min, $max) / 1000);
    }

    private function getDuration()
    {
        $min = $this->config->mg1_displayDurationMin;
        $max = $this->config->mg1_displayDurationMax;

        // 0 means "no timeout", so never let it through
        return max(1, intval(rand($min, $max)));
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof StaticWidget) {
            $this->widget->erase();
        }
        $this->widget = null;

        Gui::preloadRemove($this->config->mg1_imageUrl);
        Gui::preloadRemove($this->config->mg1_imageFocusUrl);
        Gui::preloadUpdate();

        parent::eXpOnUnload();
    }
}
