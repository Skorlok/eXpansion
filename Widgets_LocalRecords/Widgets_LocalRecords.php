<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LocalRecords;

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

class Widgets_LocalRecords extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public static $localrecords = array();
    public static $raceOn = true;
    private $config;
    private $script;
    private $trayScript;

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

        $libScript = new Script('Gui/Script_libraries/TimeToText');

        $this->trayScript = new Script("Gui/Scripts/NewTray");

        $this->script = new Script("Widgets_LocalRecords/Gui/Scripts/PlayerFinish");
        $this->script->setParam("playerTimes", "[]");
        $this->script->setParam('varName', 'LocalRecords');

        $this->widget = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
        $this->widget->setName("LocalRecords Panel");
        $this->widget->setLayer("normal");
        $this->widget->registerScript($libScript);
        $this->widget->registerScript($this->script);
        $this->widget->registerScript($this->trayScript);

        $this->widget2 = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
        $this->widget2->setName("LocalRecords Panel");
        $this->widget2->setLayer("scorestable");
        $this->widget2->registerScript($libScript);
        $this->widget2->registerScript($this->script);
        $this->widget2->registerScript($this->trayScript);

        self::$localrecords = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");
        $this->updateLocalPanel();
    }

    public function updateLocalPanel($login = null, $update = false)
    {
        $data = $this->getWidgetData();

        if ($update) {
            $xml = '<manialink id="localrecords_updater" version="2" name="localrecords_updater">';
            $xml .= '<script><!--';
            $xml .= 'main () {';
            $xml .= 'declare Integer[Text] playerTimesLocalRecords for UI = Integer[Text];';
            $xml .= 'playerTimesLocalRecords = ' . $data[0] . ';';
            $xml .= 'declare Text[Text] playerNickNameLocalRecords for UI = Text[Text];';
            $xml .= 'playerNickNameLocalRecords = ' . $data[1] . ';';
            $xml .= 'declare Boolean needUpdateLocalRecords for UI = True;';
            $xml .= 'needUpdateLocalRecords = True;';
            $xml .= '}';
            $xml .= '--></script>';
            $xml .= '</manialink>';
            $this->connection->sendDisplayManialinkPage($login, $xml);
            return;
        }

        /** @var \ManiaLivePlugins\eXpansion\Gui\Config $gui */
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

        $this->trayScript->setParam("sizeX", $this->trayScript->getNumber($sizeX));
        $this->trayScript->setParam("sizeY", $this->trayScript->getNumber(3 + $nbF * 4));
        $this->script->setParam("nbRecord", LocalRecordsConfig::getInstance()->recordsCount);
        $this->script->setParam("nbFields", $nbF);
        $this->script->setParam("nbFirstFields", $nbFF);
        $this->script->setParam("playerTimes", $data[0]);
        $this->script->setParam("playerNicks", $data[1]);

        
        $this->widget->setPosition($posX, $posY, 0);
        $this->widget->setSize($sizeX, $sizeY);
        self::setPanelParams($this->widget, $sizeX, $nbF, "Local Records", LocalBase::$openRecordsAction);
        $this->widget->show($login);

        if (!$gui->disablePersonalHud) {
            $this->widget2->setPosition($posX, $posY, 0);
            $this->widget2->setSize($sizeX, $sizeY);
            self::setPanelParams($this->widget2, $sizeX, $nbF, "Local Records", LocalBase::$openRecordsAction);
            $this->widget2->show($login);
        }
    }

    /**
     * Fill the params of the templates sharing the records panel layout:
     * - Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml (also used by Widgets_DedimaniaRecords and Widgets_LiveRankings)
     * - Widgets_LocalRecordsHidden\Gui\Widgets\LocalRecords.xml
     * - Widgets_LiveRankings\Gui\Widgets\LiveRanking.xml (which adds its team params)
     * - Widgets_Livecp\Gui\Widgets\LiveCP.xml
     */
    public static function setPanelParams(Widget $widget, $sizeX, $nbFields, $title, $action)
    {
        /** @var guiConfig $guiConfig */
        $guiConfig   = guiConfig::getInstance();
        /** @var ColorParser $colorParser */
        $colorParser = ColorParser::getInstance();

        $fields = array();
        for ($index = 1; $index <= $nbFields; $index++) {
            $fields[] = array('index' => $index, 'posY' => -4 * ($index - 1));
        }

        $widget->setParam("sizeX",        $sizeX);
        $widget->setParam("bgSizeY",      (3 + $nbFields * 4) + 1.5);
        $widget->setParam("titleSizeY",   3 + $nbFields * 4);
        $widget->setParam("centerX",      ($sizeX / 2) + 1);
        $widget->setParam("title",        $title);
        $widget->setParam("action",       $action);
        $widget->setParam("fields",       $fields);
        $widget->setParam("rankColor",    $widget->handleSpecialChars(str_replace('$', "", $colorParser->getColor("#rank#"))));
        $widget->setParam("timeColor",    $widget->handleSpecialChars(str_replace('$', "", $colorParser->getColor("#time#"))));
        $widget->setParam("bgColor",      $widget->handleSpecialChars($guiConfig->style_widget_bgColorize));
        $widget->setParam("bgColorFocus", $widget->handleSpecialChars($guiConfig->style_widget_title_bgColorize));
    }

    private function getWidgetData()
    {
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

        return array($recsData, $nickData);
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
        $this->hideWidgets();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        self::$localrecords = array();
        $this->hideWidgets();
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

    private function hideWidgets()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        if ($this->widget2 instanceof Widget) {
            $this->widget2->erase();
        }
    }

    public function eXpOnUnload()
    {
        $this->hideWidgets();
        self::$localrecords = array();
        $this->config = null;
        $this->script = null;
        $this->trayScript = null;
        $this->widget = null;
        $this->widget2 = null;

        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORDS_LOADED);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_NEW_RECORD);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_UPDATE_RECORDS);
        Dispatcher::unregister(LocalEvent::getClass(), $this, LocalEvent::ON_RECORD_DELETED);
    }
}