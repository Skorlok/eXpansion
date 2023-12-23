<?php

namespace ManiaLivePlugins\eXpansion\Widgets_PersonalBest;

use ManiaLive\Event\Dispatcher;
use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;
use ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record;
use ManiaLivePlugins\eXpansion\Widgets_PersonalBest\Gui\Widgets\PBPanel;

class Widgets_PersonalBest extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    public function eXpOnInit()
    {
        $this->addDependency(new Dependency('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords'));
    }

    public function eXpOnLoad()
    {
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
        if (\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        PBPanel::EraseAll();
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

        PBPanel::Erase($login);
        $info = PBPanel::Create($login);
        $info->setRecord($record, $rank, $rankTotal);
        $info->setSize(30, 13);
        $info->setPosition(112, -76);
        $info->show();
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_PERSONAL_BEST);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_FINISH);
        PBPanel::EraseAll();
    }
}