<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LocalRecords;

use ManiaLive\Event\Dispatcher;
use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;
use ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Gui\Widgets\LocalPanel;
use ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Gui\Widgets\LocalPanel2;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_LocalRecords extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public static $me = null;
    public static $localrecords = array();
    public static $secondMap = false;
    private $widgetIds = array();
    public static $raceOn;
    public static $roundPoints;
    private $config;

    public function eXpOnInit()
    {
        $this->addDependency(new Dependency('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords'));
    }

    public function eXpOnLoad()
    {
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_UPDATE_RECORDS);
        Dispatcher::register(LocalEvent::getClass(), $this, LocalEvent::ON_RECORD_DELETED);
        $this->config = Config::getInstance();
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->lastUpdate = time();
        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords')) {
            self::$localrecords = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");
        }
        $this->updateLocalPanel();
        self::$me = $this;
    }

    public function updateLocalPanel($login = null)
    {
        $gui = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        //gamemode specific settings
        if (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS) {
            $posX = $this->config->localRecordsPanel_PosX_Laps;
            $posY = $this->config->localRecordsPanel_PosY_Laps;
            $nbF = $this->config->localRecordsPanel_nbFields_Laps;
            $nbFF = $this->config->localRecordsPanel_nbFirstFields_Laps;
        } elseif (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_ROUNDS) {
            $posX = $this->config->localRecordsPanel_PosX_Rounds;
            $posY = $this->config->localRecordsPanel_PosY_Rounds;
            $nbF = $this->config->localRecordsPanel_nbFields_Rounds;
            $nbFF = $this->config->localRecordsPanel_nbFirstFields_Rounds;
        } elseif (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TEAM) {
            $posX = $this->config->localRecordsPanel_PosX_Team;
            $posY = $this->config->localRecordsPanel_PosY_Team;
            $nbF = $this->config->localRecordsPanel_nbFields_Team;
            $nbFF = $this->config->localRecordsPanel_nbFirstFields_Team;
        } elseif (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_CUP) {
            $posX = $this->config->localRecordsPanel_PosX_Cup;
            $posY = $this->config->localRecordsPanel_PosY_Cup;
            $nbF = $this->config->localRecordsPanel_nbFields_Cup;
            $nbFF = $this->config->localRecordsPanel_nbFirstFields_Cup;
        } else {
            $posX = $this->config->localRecordsPanel_PosX_Default;
            $posY = $this->config->localRecordsPanel_PosY_Default;
            $nbF = $this->config->localRecordsPanel_nbFields_Default;
            $nbFF = $this->config->localRecordsPanel_nbFirstFields_Default;
        }

        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords')) {
            if ($login != null) {
                Gui\Widgets\LocalPanel::Erase($login);
                Gui\Widgets\LocalPanel2::Erase($login);
            } else {
                Gui\Widgets\LocalPanel::EraseAll();
                Gui\Widgets\LocalPanel2::EraseAll();
            }
            $panelMain = Gui\Widgets\LocalPanel::Create($login);
            $panelMain->setLayer(\ManiaLive\Gui\Window::LAYER_NORMAL);
            $panelMain->setPosition($posX, $posY);
            $panelMain->setNbFields($nbF);
            $panelMain->setNbFirstFields($nbFF);
            $this->widgetIds["LocalPanel"] = $panelMain;
            $this->widgetIds["LocalPanel"]->update();
            $this->widgetIds["LocalPanel"]->show();

            if (!$gui->disablePersonalHud) {
                $panelScore = Gui\Widgets\LocalPanel2::Create($login);
                $panelScore->setLayer(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);
                $panelScore->setVisibleLayer("scorestable");
                $panelScore->setPosition($posX, $posY);
                $panelScore->setNbFields($nbF);
                $panelScore->setNbFirstFields($nbFF);
                $this->widgetIds["LocalPanel2"] = $panelScore;
                $this->widgetIds["LocalPanel2"]->update();
                $this->widgetIds["LocalPanel2"]->show();
            }
        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->config = Config::getInstance();
            Gui\Widgets\LocalPanel::EraseAll();
            $this->updateLocalPanel();
        }
    }

    public function showLocalPanel($login)
    {
        $this->updateLocalPanel($login);
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if (\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        self::$raceOn = false;
        $this->widgetIds = array();
        Gui\Widgets\LocalPanel::EraseAll();
        Gui\Widgets\LocalPanel2::EraseAll();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($wasWarmUp) {
            self::$raceOn = false;
            $this->forceUpdate = true;
            $this->updateLocalPanel();
            self::$secondMap = true;
            self::$raceOn = true;
        } else {
            self::$localrecords = array(); //  reset
            $this->widgetIds = array();
            Gui\Widgets\LocalPanel::EraseAll();
            Gui\Widgets\LocalPanel2::EraseAll();
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        self::$raceOn = false;
        $this->forceUpdate = true;
        $this->widgetIds = array();
        Gui\Widgets\LocalPanel::EraseAll();
        Gui\Widgets\LocalPanel2::EraseAll();
        $this->updateLocalPanel();
        self::$secondMap = true;
        self::$raceOn = true;
    }

    public function onBeginMatch()
    {
        if (self::$raceOn == true) {
            return;
        }

        self::$raceOn = false;
        $this->forceUpdate = true;
        $this->widgetIds = array();
        Gui\Widgets\LocalPanel::EraseAll();
        Gui\Widgets\LocalPanel2::EraseAll();
        $this->updateLocalPanel();
        self::$secondMap = true;
        self::$raceOn = true;
    }

    public function onRecordsLoaded($data)
    {
        self::$localrecords = $data;
        $this->local = true;
        $this->needUpdate = self::$localrecords;
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        //$this->showLocalPanel($login);
    }

    public function onNewRecord($data)
    {
        self::$localrecords = $data;
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
        Gui\Widgets\LocalPanel::EraseAll();
        Gui\Widgets\LocalPanel2::EraseAll();
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_UPDATE_RECORDS);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORD_DELETED);
    }
}