<?php

namespace ManiaLivePlugins\eXpansion\Widgets_PersonalBest;

use ManiaLive\Event\Dispatcher;
use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\eXpansion\Endurance\Endurance;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;
use ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Widgets_PersonalBest extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $config;
    private $script;
    private $widgetWithScript;
    private $widget;

    public function eXpOnInit()
    {
        $this->addDependency(new Dependency('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords'));
    }

    public function eXpOnLoad()
    {
        $this->config = Config::getInstance();

        $this->script = new Script("Widgets_PersonalBest/Gui/Script");

        $this->widgetWithScript = new Widget("Widgets_PersonalBest\Gui\Widgets\PBPanel.xml");
        $this->widgetWithScript->setName("Personal Best Widget");
        $this->widgetWithScript->setLayer("normal");
        $this->widgetWithScript->setSize(30, 13);
        $this->widgetWithScript->registerScript($this->script);

        $this->widget = new Widget("Widgets_PersonalBest\Gui\Widgets\PBPanel.xml");
        $this->widget->setName("Personal Best Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(30, 13);

        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_PERSONAL_BEST);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_FINISH);
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->displayRecordWidget($login);
    }

    public function onRecordsLoaded($record)
    {
        $this->displayRecordWidgetToAll();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && Endurance::$last_round == false) {
            return;
        }
        $this->widget->erase();
    }

    public function onPersonalBestRecord(Record $record)
    {
        $this->displayRecordWidget($record->login, $record);
    }

    public function onNewRecord($records, Record $record)
    {
        $this->displayRecordWidget($record->login, $record);
    }

    public function onRecordPlayerFinished($record)
    {
        $this->displayRecordWidget($record->login, $record);
    }

    private function displayRecordWidgetToAll()
    {
        $rankTotal = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords', 'getTotalRanked');

        $playersPb = "";
        $playersAverage = "";
        $playersFinishes = "";
        $playersRank = "";

        $index = 1;
        foreach ($this->storage->players + $this->storage->spectators as $player) {
            if ($index > 1) {
                $playersPb .= ', ';
                $playersAverage .= ', ';
                $playersFinishes .= ', ';
                $playersRank .= ', ';
            }

            $record = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords', 'getCurrentChallangePlayerRecord', $player->login);
            $rank = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords', 'getPlayerRank', $player->login);

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

            if ($rank == -1) {
                $rank = '--';
            }
            if ($rank == -2) {
                $rank = '';
            }
            $playersPb .= '"' . $player->login . '"=>"' . $pbTime . '"';
            $playersAverage .= '"' . $player->login . '"=>"' . $avgTime . '"';
            $playersFinishes .= '"' . $player->login . '"=>"' . $nbFinish . '"';
            $playersRank .= '"' . $player->login . '"=>"' . $rank . '"';
            $index++;
        }

        if (empty($playersPb)) {
            $playersPb = 'Text[Text]';
            $playersAverage = 'Text[Text]';
            $playersFinishes = 'Text[Text]';
            $playersRank = 'Text[Text]';
        } else {
            $playersPb = '[' . $playersPb . ']';
            $playersAverage = '[' . $playersAverage . ']';
            $playersFinishes = '[' . $playersFinishes . ']';
            $playersRank = '[' . $playersRank . ']';
        }

        $this->script->setParam("playersPb", $playersPb);
        $this->script->setParam("playersAverage", $playersAverage);
        $this->script->setParam("playersFinishes", $playersFinishes);
        $this->script->setParam("playersRank", $playersRank);
        $this->script->setParam("rankTotal", $rankTotal);

        $this->widgetWithScript->setPosition($this->config->personalBestWidget_PosX, $this->config->personalBestWidget_PosY, 0);
        $this->widgetWithScript->setParam('pbTime', "");
        $this->widgetWithScript->setParam('avgTime', "");
        $this->widgetWithScript->setParam('rank', "");
        $this->widgetWithScript->setParam('rankTotal', "");
        $this->widgetWithScript->setParam('nbFinish', "");
        $this->widgetWithScript->show();
    }

    private function displayRecordWidget($login, $record = null)
    {
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
        
        $this->widget->setPosition($this->config->personalBestWidget_PosX, $this->config->personalBestWidget_PosY, 0);
        $this->widget->setParam('pbTime', $pbTime);
        $this->widget->setParam('avgTime', $avgTime);
        $this->widget->setParam('rank', $rank);
        $this->widget->setParam('rankTotal', $rankTotal);
        $this->widget->setParam('nbFinish', $nbFinish);
        $this->widget->show($login);
    }

    public function eXpOnUnload()
    {
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_PERSONAL_BEST);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_FINISH);

        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        if ($this->widgetWithScript instanceof Widget) {
            $this->widgetWithScript->erase();
        }
        $this->widget = null;
        $this->widgetWithScript = null;
        $this->script = null;
    }
}