<?php

namespace ManiaLivePlugins\eXpansion\Widgets_PlainLocalRecords;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Listener;

class Widgets_PlainLocalRecords extends ExpPlugin implements Listener
{
    public static $localrecords = array();

    /** @var Config */
    private $config;
    private $widget;

    public function eXpOnLoad()
    {
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_UPDATE_RECORDS);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_RECORD_DELETED);

        $this->config = Config::getInstance();

        $this->widget = new Widget("Widgets_PlainLocalRecords\Gui\Widgets\RecordsPanel.xml");
        $this->widget->setName("Local Scores Panel");
        $this->widget->setLayer("normal");
        $this->widget->setParam("title", "Best Scores");
        $this->widget->setParam("isTime", true);
        if ($this->expStorage->simpleEnviTitle == "TM") {
            $this->widget->registerScript(new Script("Gui/Scripts/EdgeWidget"));
        }
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        if ($this->isPluginLoaded('\\ManiaLivePlugins\\eXpansion\\SM_ObstaclesScores\\SM_ObstaclesScores')) {
            self::$localrecords = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\SM_ObstaclesScores\\SM_ObstaclesScores", "getRecords" );
        }
        $this->updateLocalPanel();
    }

    public function updateLocalPanel()
    {
        $this->widget->setSize(46, ($this->config->localRecordsPanel_nbFields * 4) + 3.25);
        $this->widget->setPosition($this->config->localRecordsPanel_PosX, $this->config->localRecordsPanel_PosY, 0);
        $this->widget->setParam("nbFields", $this->config->localRecordsPanel_nbFields);
        $this->widget->setParam("records", self::$localrecords);
        $this->widget->show(null, true);
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

        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
    }

    public function onRecordsLoaded($records)
    {
        self::$localrecords = $records;
        $this->updateLocalPanel();
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

        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            $this->widget = null;
        }
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
