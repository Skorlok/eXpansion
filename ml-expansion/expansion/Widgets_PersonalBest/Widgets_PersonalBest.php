<?php

namespace ManiaLivePlugins\eXpansion\Widgets_PersonalBest;

use ManiaLive\Event\Dispatcher;
use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\eXpansion\Endurance\Endurance;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;
use ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;

class Widgets_PersonalBest extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $config;

    public function eXpOnInit()
    {
        $this->addDependency(new Dependency('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords'));
    }

    public function eXpOnLoad()
    {
        $this->config = Config::getInstance();
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_PERSONAL_BEST);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_FINISH);
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->redrawAll();
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->displayRecordWidget($login);
    }

    public function onBeginMatch()
    {
        $this->redrawWidget();
    }

    public function onRecordsLoaded($record)
    {
        foreach ($this->storage->players as $player) {
            $this->redrawWidget($player->login);
        }
        foreach ($this->storage->spectators as $player) {
            $this->redrawWidget($player->login);
        }
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if (Endurance::$enduro && Endurance::$last_round == false) {
            return;
        }
        $widget = new Widget("Widgets_PersonalBest\Gui\Widgets\PBPanel.xml");
        $widget->setName("Personal Best Widget");
        $widget->erase();
    }

    public function redrawAll()
    {
        foreach ($this->storage->players as $player) {
            $this->onPlayerConnect($player->login, false);
        }

        foreach ($this->storage->spectators as $spectator) {
            $this->onPlayerConnect($spectator->login, false);
        }
    }

    public function onPersonalBestRecord(Record $record)
    {
        $this->redrawWidget($record->login, $record);
    }

    public function onNewRecord($records, Record $record)
    {
        $this->redrawWidget($record->login);
    }

    public function onRecordPlayerFinished($login)
    {
        $this->redrawWidget($login);
    }

    public function redrawWidget($login = null)
    {
        $record = null;
        if ($login != null) {
            $record = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords', 'getCurrentChallangePlayerRecord', $login);
        }
        $this->displayRecordWidget($login, $record);
    }

    public function displayRecordWidget($login, $record = null)
    {
        if ($login == null) {
            return;
        }

        if ($record == null) {
            $record = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords', 'getCurrentChallangePlayerRecord', $login);
        }

        $rank = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords', 'getPlayerRank', $login);
        if ($rank == -1) {
            $rank = '--';
        }
        if ($rank == -2) {
            $rank = '';
        }
        $rankTotal = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords', 'getTotalRanked');

        if ($record == null) {
            $pbTime = '--';
            $avgTime = $pbTime;
            $nbFinish = 0;
        } else {
            $pbTime = \ManiaLive\Utilities\Time::fromTM($record->time);
            if (substr($pbTime, 0, 2) === "0:") {
                $pbTime = substr($pbTime, 2);
            }
            $avgTime = \ManiaLive\Utilities\Time::fromTM($record->avgScore);
            if (substr($avgTime, 0, 2) === "0:") {
                $avgTime = substr($avgTime, 2);
            }
            $nbFinish = $record->nbFinish;
        }

        $widget = new Widget("Widgets_PersonalBest\Gui\Widgets\PBPanel.xml");
        $widget->setName("Personal Best Widget");
        $widget->setLayer("normal");
        $widget->setPosition($this->config->personalBestWidget_PosX, $this->config->personalBestWidget_PosY, 0);
        $widget->setSize(30, 13);
        $widget->setParam('Personal_Best', __('Personal Best', $login));
        $widget->setParam('Average', __('Average', $login));
        $widget->setParam('Finishes', __('Finishes', $login));
        $widget->setParam('Server Rank', __('Server Rank', $login));
        $widget->setParam('pbTime', $pbTime);
        $widget->setParam('avgTime', $avgTime);
        $widget->setParam('rank', $rank);
        $widget->setParam('rankTotal', $rankTotal);
        $widget->setParam('nbFinish', $nbFinish);
        $widget->show($login);
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_PERSONAL_BEST);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_FINISH);

        $widget = new Widget("Widgets_PersonalBest\Gui\Widgets\PBPanel.xml");
        $widget->setName("Personal Best Widget");
        $widget->erase();
    }
}