<?php

namespace ManiaLivePlugins\eXpansion\Widgets_DedimaniaRecords;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\ColorParser;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Config as guiConfig;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Dedimania\DedimaniaAbstract;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_DedimaniaRecords extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public static $dedirecords = array();
    public static $raceOn;
    private $config;

    private $widget;
    private $widget2;

    public function eXpOnLoad()
    {
        if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\Dedimania\\Dedimania')) {
            Dispatcher::register(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
        }
        $this->config = Config::getInstance();
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->updateDediPanel();
    }

    public function updateDediPanel($login = null)
    {
        if (strtolower($this->connection->getScriptName()['CurrentValue']) == "endurocup.script.txt") {
            return;
        }

        $dedi = '\ManiaLivePlugins\\eXpansion\\Dedimania\\Dedimania';
        $gui = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        //gamemode specific settings
        if (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS) {
            $posX = $this->config->dedimaniaRecordsPanel_PosX_Laps;
            $posY = $this->config->dedimaniaRecordsPanel_PosY_Laps;
            $nbF = $this->config->dedimaniaRecordsPanel_nbFields_Laps;
            $nbFF = $this->config->dedimaniaRecordsPanel_nbFirstFields_Laps;
        } elseif (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_ROUNDS) {
            $posX = $this->config->dedimaniaRecordsPanel_PosX_Rounds;
            $posY = $this->config->dedimaniaRecordsPanel_PosY_Rounds;
            $nbF = $this->config->dedimaniaRecordsPanel_nbFields_Rounds;
            $nbFF = $this->config->dedimaniaRecordsPanel_nbFirstFields_Rounds;
        } elseif (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TEAM) {
            $posX = $this->config->dedimaniaRecordsPanel_PosX_Team;
            $posY = $this->config->dedimaniaRecordsPanel_PosY_Team;
            $nbF = $this->config->dedimaniaRecordsPanel_nbFields_Team;
            $nbFF = $this->config->dedimaniaRecordsPanel_nbFirstFields_Team;
        } elseif (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_CUP) {
            $posX = $this->config->dedimaniaRecordsPanel_PosX_Cup;
            $posY = $this->config->dedimaniaRecordsPanel_PosY_Cup;
            $nbF = $this->config->dedimaniaRecordsPanel_nbFields_Cup;
            $nbFF = $this->config->dedimaniaRecordsPanel_nbFirstFields_Cup;
        } else {
            $posX = $this->config->dedimaniaRecordsPanel_PosX_Default;
            $posY = $this->config->dedimaniaRecordsPanel_PosY_Default;
            $nbF = $this->config->dedimaniaRecordsPanel_nbFields_Default;
            $nbFF = $this->config->dedimaniaRecordsPanel_nbFirstFields_Default;
        }

        try {
            if (($this->isPluginLoaded($dedi) && $this->callPublicMethod($dedi, 'isRunning'))) {
                if ($this->widget instanceof Widget) {
                    $this->widget->erase();
                    if ($this->widget2 instanceof Widget) {
                        $this->widget2->erase();
                    }
                }

                $sizeX = 42;
                $sizeY = 3 + $nbF * 4;
                $widgetScript = $this->getWidgetScript($nbF, $nbFF);
                $trayScript = $this->getTrayScript($sizeX, $nbF);

                $this->widget = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
                $this->widget->setName("Dedimania Panel");
                $this->widget->setLayer("normal");
                $this->widget->setPosition($posX, $posY, 0);
                $this->widget->setSize($sizeX, $sizeY);
                $this->widget->setParam("sizeX", $sizeX);
                $this->widget->setParam("nbFields", $nbF);
                $this->widget->setParam("title", "Dedimania Records");
                $this->widget->setParam("action", DedimaniaAbstract::$actionOpenRecs);
                $this->widget->setParam("guiConfig", guiConfig::getInstance());
                $this->widget->setParam("colorParser", ColorParser::getInstance());
                $this->widget->registerScript(new Script('Gui/Script_libraries/TimeToText'));
                $this->widget->registerScript($widgetScript);
                $this->widget->registerScript($trayScript);
                if ($login != null) {
                    $this->widget->show($login, false);
                } else {
                    $this->widget->show(null, true);
                }

                /** @var ManiaLivePlugins\eXpansion\Gui\Gui $gui */
                if (!$gui->disablePersonalHud) {
                    $this->widget2 = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
                    $this->widget2->setName("Dedimania Panel");
                    $this->widget2->setLayer("scorestable");
                    $this->widget2->setPosition($posX, $posY, 0);
                    $this->widget2->setSize($sizeX, $sizeY);
                    $this->widget2->setParam("sizeX", $sizeX);
                    $this->widget2->setParam("nbFields", $nbF);
                    $this->widget2->setParam("title", "Dedimania Records");
                    $this->widget2->setParam("action", DedimaniaAbstract::$actionOpenRecs);
                    $this->widget2->setParam("guiConfig", guiConfig::getInstance());
                    $this->widget2->setParam("colorParser", ColorParser::getInstance());
                    $this->widget2->registerScript(new Script('Gui/Script_libraries/TimeToText'));
                    $this->widget2->registerScript($widgetScript);
                    $this->widget2->registerScript($trayScript);
                    if ($login != null) {
                        $this->widget2->show($login, false);
                    } else {
                        $this->widget2->show(null, true);
                    }
                }
            }
        } catch (\Exception $ex) {

        }
    }

    public function getWidgetScript($nbField, $nbFirstField)
    {
        $script = new Script("Widgets_LocalRecords/Gui/Scripts/PlayerFinish");
        $script->setParam("playerTimes", "[]");
        $script->setParam("nbRecord", 100);
        $script->setParam("nbFields", $nbField);
        $script->setParam("nbFirstFields", $nbFirstField);
        $script->setParam('varName', 'LocalTime1');

        $recsData = "";
        $nickData = "";

        $index = 1;
        foreach (self::$dedirecords as $record) {
            if ($index > 1) {
                $recsData .= ', ';
                $nickData .= ', ';
            }
            $recsData .= '"' . Gui::fixString($record['Login']) . '"=> ' . $record['Best'];
            $nickData .= '"' . Gui::fixString($record['Login']) . '"=>"' . Gui::fixString($record['NickName']) . '"';
            $index++;
        }

        if (empty($recsData)) {
            $recsData = 'Integer[Text]';
            $nickData = 'Text[Text]';
        } else {
            $recsData = '[' . $recsData . ']';
            $nickData = '[' . $nickData . ']';
        }

        $script->setParam("playerTimes", $recsData);
        $script->setParam("playerNicks", $nickData);

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
            $this->updateDediPanel();
        }
    }

    public function showDediPanel($login)
    {
        $this->updateDediPanel($login);
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
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
            $this->updateDediPanel();
            self::$raceOn = true;
        } else {
            self::$dedirecords = array();
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
        $this->updateDediPanel();
        self::$raceOn = true;
    }

    public function onBeginMatch()
    {
        if (self::$raceOn == true) {
            return;
        }

        self::$raceOn = false;
        $this->updateDediPanel();
        self::$raceOn = true;
    }

    public function onEndRound()
    {

    }

    public function onDedimaniaGetRecords($data)
    {
        self::$dedirecords = $data['Records'];
        $this->updateDediPanel();
    }

    public function onDedimaniaOpenSession()
    {

    }

    public function onDedimaniaUpdateRecords($data)
    {
        self::$dedirecords = $data['Records'];
        $this->updateDediPanel();
    }

    public function onDedimaniaNewRecord($data)
    {
        //self::$dedirecords = $data['Records'];
    }

    /**
     * @param $data DediPlayer
     */
    public function onDedimaniaPlayerConnect($data)
    {
        if (self::$raceOn == true) {
            $this->updateDediPanel();
        }
    }

    public function onDedimaniaPlayerDisconnect()
    {

    }

    public function onDedimaniaRecord($record, $oldrecord)
    {

    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            if ($this->widget2 instanceof Widget) {
                $this->widget2->erase();
            }
        }

        Dispatcher::unregister(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
    }
}