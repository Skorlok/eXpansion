<?php

namespace ManiaLivePlugins\eXpansion\Widgets_DedimaniaRecords;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Dedimania\DedimaniaAbstract;
use ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Widgets_LocalRecords;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_DedimaniaRecords extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public static $dedirecords = array();
    public static $raceOn = true;
    private $config;
    private $script;
    private $trayScript;

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

        $libScript = new Script('Gui/Script_libraries/TimeToText');

        $this->trayScript = new Script("Gui/Scripts/NewTray");

        $this->script = new Script("Widgets_LocalRecords/Gui/Scripts/PlayerFinish");
        $this->script->setParam("playerTimes", "[]");
        $this->script->setParam("nbRecord", 100);
        $this->script->setParam('varName', 'DedimaniaRecords');

        $this->widget = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
        $this->widget->setName("Dedimania Panel");
        $this->widget->setLayer("normal");
        $this->widget->registerScript($libScript);
        $this->widget->registerScript($this->script);
        $this->widget->registerScript($this->trayScript);

        $this->widget2 = new Widget("Widgets_LocalRecords\Gui\Widgets\LocalRecords.xml");
        $this->widget2->setName("Dedimania Panel");
        $this->widget2->setLayer("scorestable");
        $this->widget2->registerScript($libScript);
        $this->widget2->registerScript($this->script);
        $this->widget2->registerScript($this->trayScript);

        $this->updateDediPanel();
    }

    public function updateDediPanel($login = null, $update = false)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup") {
            return;
        }

        $data = $this->getWidgetData();

        if ($update) {
            $xml = '<manialink id="dedimaniarecords_updater" version="2" name="dedimaniarecords_updater">';
            $xml .= '<script><!--';
            $xml .= 'main () {';
            $xml .= 'declare Integer[Text] playerTimesDedimaniaRecords for UI = Integer[Text];';
            $xml .= 'playerTimesDedimaniaRecords = ' . $data[0] . ';';
            $xml .= 'declare Text[Text] playerNickNameDedimaniaRecords for UI = Text[Text];';
            $xml .= 'playerNickNameDedimaniaRecords = ' . $data[1] . ';';
            $xml .= 'declare Boolean needUpdateDedimaniaRecords for UI = True;';
            $xml .= 'needUpdateDedimaniaRecords = True;';
            $xml .= '}';
            $xml .= '--></script>';
            $xml .= '</manialink>';
            $this->connection->sendDisplayManialinkPage($login, $xml);
            return;
        }

        if (!$this->callPublicMethod('\ManiaLivePlugins\\eXpansion\\Dedimania\\Dedimania', 'isRunning')) {
            return;
        }

        /** @var \ManiaLivePlugins\eXpansion\Gui\Config $gui */
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

        $sizeX = 42;
        $sizeY = 3 + $nbF * 4;

        $this->trayScript->setParam("sizeX", $this->trayScript->getNumber($sizeX));
        $this->trayScript->setParam("sizeY", $this->trayScript->getNumber(3 + $nbF * 4));
        $this->script->setParam("nbFields", $nbF);
        $this->script->setParam("nbFirstFields", $nbFF);
        $this->script->setParam("playerTimes", $data[0]);
        $this->script->setParam("playerNicks", $data[1]);

        $this->widget->setPosition($posX, $posY, 0);
        $this->widget->setSize($sizeX, $sizeY);
        Widgets_LocalRecords::setPanelParams($this->widget, $sizeX, $nbF, "Dedimania Records", DedimaniaAbstract::$actionOpenRecs);
        $this->widget->show($login);

        if (!$gui->disablePersonalHud) {
            $this->widget2->setPosition($posX, $posY, 0);
            $this->widget2->setSize($sizeX, $sizeY);
            Widgets_LocalRecords::setPanelParams($this->widget2, $sizeX, $nbF, "Dedimania Records", DedimaniaAbstract::$actionOpenRecs);
            $this->widget2->show($login);
        }
    }

    private function getWidgetData()
    {
        $recsData = "";
        $nickData = "";

        $index = 1;
        foreach (self::$dedirecords as $record) {
            if ($index > 1) {
                $recsData .= ', ';
                $nickData .= ', ';
            }
            $recsData .= '"' . $record['Login'] . '"=> ' . $record['Best'];
            $nickData .= '"' . $record['Login'] . '"=>"' . Gui::fixString2($record['NickName']) . '"';
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
            $this->updateDediPanel();
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        self::$raceOn = false;
        $this->hideWidgets();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        self::$dedirecords = array();
        $this->hideWidgets();
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

    public function onDedimaniaGetRecords($data)
    {
        self::$dedirecords = $data['Records'];
        $this->updateDediPanel();
    }

    public function onDedimaniaUpdateRecords($data)
    {
        self::$dedirecords = $data['Records'];
        $this->updateDediPanel(null, true);
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if (self::$raceOn == true) {
            $this->updateDediPanel($login);
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
        self::$dedirecords = array();
        $this->config = null;
        $this->script = null;
        $this->trayScript = null;
        $this->widget = null;
        $this->widget2 = null;

        Dispatcher::unregister(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
    }

    public function onDedimaniaOpenSession()
    {
    }

    public function onDedimaniaNewRecord($data)
    {
    }

    public function onDedimaniaPlayerConnect($data)
    {
    }

    public function onDedimaniaPlayerDisconnect()
    {
    }

    public function onDedimaniaRecord($record, $oldrecord)
    {
    }
}