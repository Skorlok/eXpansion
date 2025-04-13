<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LocalRecordsHidden;

use ManiaLive\Event\Dispatcher;
use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\eXpansion\Core\ColorParser;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Config as guiConfig;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\LocalRecords\LocalBase;
use ManiaLivePlugins\eXpansion\LocalRecords\Config as LocalRecordsConfig;
use ManiaLivePlugins\eXpansion\LocalRecords\Events\Event as LocalEvent;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_LocalRecordsHidden extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public static $localrecords = array();
    public static $raceOn = true;
    private $config;

    private $widget;
    private $widget2;

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

        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords')) {
            self::$localrecords = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");
        }
        $this->updateLocalPanel();
    }

    public function updateLocalPanel($login = null, $update = false)
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords')) {

            if ($update) {
                $xml = '<manialink id="localrecordshidden_updater" version="2" name="localrecordshidden_updater">';
                $xml .= '<script><!--';
                $xml .= $this->getWidgetScript(null, null, true);
                $xml .= '--></script>';
                $xml .= '</manialink>';
                $this->connection->sendDisplayManialinkPage($login, $xml);
                return;
            }

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

            $sizeX = 42;
            $sizeY = 3 + $nbF * 4;
            $widgetScript = $this->getWidgetScript($nbF, $nbFF);
            $trayScript = $this->getTrayScript($sizeX, $nbF);

            $this->widget = new Widget("Widgets_LocalRecordsHidden\Gui\Widgets\LocalRecords.xml");
            $this->widget->setName("LocalRecords Hidden Panel");
            $this->widget->setLayer("normal");
            $this->widget->setPosition($posX, $posY, 0);
            $this->widget->setSize($sizeX, $sizeY);
            $this->widget->setParam("sizeX", $sizeX);
            $this->widget->setParam("nbFields", $nbF);
            $this->widget->setParam("title", "Local Records");
            $this->widget->setParam("action", LocalBase::$openRecordsAction);
            $this->widget->setParam("guiConfig", guiConfig::getInstance());
            $this->widget->setParam("colorParser", ColorParser::getInstance());
            $this->widget->registerScript(new Script('Gui/Script_libraries/TimeToText'));
            $this->widget->registerScript($widgetScript);
            $this->widget->registerScript($trayScript);
            $this->widget->show($login);

            /** @var ManiaLivePlugins\eXpansion\Gui\Gui $gui */
            if (!$gui->disablePersonalHud) {
                $this->widget2 = new Widget("Widgets_LocalRecordsHidden\Gui\Widgets\LocalRecords.xml");
                $this->widget2->setName("LocalRecords Hidden Panel");
                $this->widget2->setLayer("scorestable");
                $this->widget2->setPosition($posX, $posY, 0);
                $this->widget2->setSize($sizeX, $sizeY);
                $this->widget2->setParam("sizeX", $sizeX);
                $this->widget2->setParam("nbFields", $nbF);
                $this->widget2->setParam("title", "Local Records");
                $this->widget2->setParam("action", LocalBase::$openRecordsAction);
                $this->widget2->setParam("guiConfig", guiConfig::getInstance());
                $this->widget2->setParam("colorParser", ColorParser::getInstance());
                $this->widget2->registerScript(new Script('Gui/Script_libraries/TimeToText'));
                $this->widget2->registerScript($widgetScript);
                $this->widget2->registerScript($trayScript);
                $this->widget2->show($login);
            }
        }
    }

    public function getWidgetScript($nbField, $nbFirstField, $update = false)
    {
        if (!$update) {
            $script = new Script("Widgets_LocalRecordsHidden/Gui/Scripts/PlayerFinish");
            $script->setParam("playerTimes", "[]");
            $script->setParam("nbRecord", LocalRecordsConfig::getInstance()->recordsCount);
            $script->setParam("nbFields", $nbField);
            $script->setParam("nbFirstFields", $nbFirstField);
            $script->setParam('varName', 'LocalRecords');
        }

        $recsData = "";
        $nickData = "";

        $index = 1;
        foreach (self::$localrecords as $record) {
            if ($index > 1) {
                $recsData .= ', ';
                $nickData .= ', ';
            }
            $recsData .= '"' . $record->login . '"=>' . $record->time;
            $nickData .= '"' . $record->login . '"=>"' . Gui::fixString2($record->nickName) . '"';
            $index++;
        }

        if (empty($recsData)) {
            $recsData = 'Integer[Text]';
            $nickData = 'Text[Text]';
        } else {
            $recsData = '[' . $recsData . ']';
            $nickData = '[' . $nickData . ']';
        }

        if (!$update) {
            $script->setParam("playerTimes", $recsData);
            $script->setParam("playerNicks", $nickData);
        } else {
            return "main () {
                declare Integer[Text] playerTimesLocalRecords for UI = Integer[Text];
                playerTimesLocalRecords.clear();
                playerTimesLocalRecords = $recsData;

                declare Text[Text] playerNickNameLocalRecords for UI = Text[Text];
                playerNickNameLocalRecords.clear();
                playerNickNameLocalRecords = $nickData;

                declare Boolean needUpdateLocalRecords for UI = True;
                needUpdateLocalRecords = True;
            }";
        }

        return $script;
    }

    public function getTrayScript($sizeX, $nbField)
    {
        $script = new Script("Gui/Scripts/NewTray");
        $script->setParam("sizeX", $sizeX);
        $script->setParam("sizeY", 3 + $nbField * 4);
        return $script;
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->config = Config::getInstance();
            $this->updateLocalPanel();
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        self::$raceOn = false;
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            if ($this->widget2 instanceof Widget) {
                $this->widget2->erase();
            }
        }
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($wasWarmUp) {
            self::$raceOn = false;
            $this->updateLocalPanel();
            self::$raceOn = true;
        } else {
            self::$localrecords = array();
            if ($this->widget instanceof Widget) {
                $this->widget->erase();
                if ($this->widget2 instanceof Widget) {
                    $this->widget2->erase();
                }
            }
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        self::$raceOn = false;
        $this->updateLocalPanel();
        self::$raceOn = true;
    }

    public function onBeginMatch()
    {
        if (self::$raceOn == true) {
            return;
        }

        self::$raceOn = false;
        $this->updateLocalPanel();
        self::$raceOn = true;
    }

    public function onRecordsLoaded($data)
    {
        self::$localrecords = $data;
    }

    public function onNewRecord($data)
    {
        self::$localrecords = $data;
        $this->updateLocalPanel(null, true);
    }

    public function onUpdateRecords($data)
    {
        self::$localrecords = $data;
        $this->updateLocalPanel(null, true);
    }

    public function onRecordDeleted($removedRecord, $records)
    {
        self::$localrecords = $records;
        $this->updateLocalPanel(null, true);
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if (self::$raceOn == true) {
            $this->updateLocalPanel($login);
        }
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            if ($this->widget2 instanceof Widget) {
                $this->widget2->erase();
            }
        }
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_UPDATE_RECORDS);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORD_DELETED);
    }
}