<?php

namespace ManiaLivePlugins\eXpansion\Widgets_PlainLocalRecords;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Listener;
use ManiaLivePlugins\eXpansion\Widgets_PlainLocalRecords\Gui\Widgets\LocalPanel;

class Widgets_PlainLocalRecords extends ExpPlugin implements Listener
{
    public static $localrecords = array();

    /** @var Config */
    private $config;

    public function eXpOnLoad()
    {
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_UPDATE_RECORDS);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_RECORD_DELETED);
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->config = Config::getInstance();

        $this->lastUpdate = time();

        if ($this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\SM_ObstaclesScores\\SM_ObstaclesScores')) {
            self::$localrecords = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\SM_ObstaclesScores\\SM_ObstaclesScores", "getRecords" );
        }
        $this->updateLocalPanel();
    }

    public function updateLocalPanel($login = null)
    {
        LocalPanel::EraseAll();
        $widget = LocalPanel::Create($login);
        $widget->setPosition($this->config->localRecordsPanel_PosX, $this->config->localRecordsPanel_PosY);
        $widget->update();
        $widget->show();
    }

    public function showLocalPanel($login)
    {
        $this->updateLocalPanel($login);
    }

    public function onBeginMatch()
    {
        $this->updateLocalPanel();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        LocalPanel::EraseAll();
    }

    public function onRecordsLoaded($records)
    {
        self::$localrecords = $records;
        $this->updateLocalPanel();
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->showLocalPanel($login);
    }

    public function onPlayerDisconnect($login, $reason = null)
    {

    }

    public function onUpdateRecords($data)
    {
        self::$localrecords = $data;
        $this->updateLocalPanel();
    }

    public function onRecordDeleted($removedRecord, $records)
    {
        self::$localrecords = $records;
        $this->updateLocalPanel();
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_UPDATE_RECORDS);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORD_DELETED);
        LocalPanel::EraseAll();
    }

    public function onPersonalBestRecord($record)
    {

    }

    public function onRecordPlayerFinished($record)
    {

    }

    public function onNewRecord($record, $oldRecord)
    {
        self::$localrecords = $record;
        $this->updateLocalPanel();
    }
}
