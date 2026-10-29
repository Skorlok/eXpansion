<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Livecp;

use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Widgets_LocalRecords;

class Widgets_Livecp extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    private $config;
    private $script;
    private $trayScript;
    private $widget;
    private $widget2;
    private $widgetShown;
    private $widget2Shown;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
        $this->widgetShown = false;
        $this->widget2Shown = false;

        $libScript = new Script('Gui/Script_libraries/TimeToText');

        $this->trayScript = new Script("Gui/Scripts/NewTray");

        $this->script = new Script("Widgets_Livecp/Gui/Scripts/PlayerFinish");
        $this->script->setParam("playerTimes", "[]");
        $this->script->setParam("nbRecord", 510);
        $this->script->setParam('varName', 'LocalTime1');

        $this->widget = new Widget("Widgets_Livecp\Gui\Widgets\LiveCP.xml");
        $this->widget->setName("CpLive Widget");
        $this->widget->setLayer("normal");
        $this->widget->registerScript($libScript);
        $this->widget->registerScript($this->script);
        $this->widget->registerScript($this->trayScript);

        $this->widget2 = new Widget("Widgets_Livecp\Gui\Widgets\LiveCP.xml");
        $this->widget2->setName("CpLive Widget");
        $this->widget2->setLayer("scorestable");
        $this->widget2->registerScript($libScript);
        $this->widget2->registerScript($this->script);
        $this->widget2->registerScript($this->trayScript);

        if ($this->storage->getCleanGamemodeName() != "endurocup") {
            $this->displayWidget();
        }
    }

    private function displayWidget()
    {
        /** @var \ManiaLivePlugins\eXpansion\Gui\Config $gui */
        $gui = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        $totalCp = 0;
        $gamemode = self::eXpGetCurrentCompatibilityGameMode();
        $scriptSettings = $this->connection->getModeScriptSettings();

        if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP) {
            if ($this->storage->currentMap->lapRace) {
                if (array_key_exists("S_ForceLapsNb", $scriptSettings)) {
                    if ($scriptSettings['S_ForceLapsNb'] > 0) {
                        $totalCp = $this->storage->currentMap->nbCheckpoints * $scriptSettings['S_ForceLapsNb'];
                    } else {
                        $totalCp = $this->storage->currentMap->nbCheckpoints * $this->storage->currentMap->nbLaps;
                    }
                } else {
                    $totalCp = $this->storage->currentMap->nbCheckpoints * $this->storage->currentMap->nbLaps;
                }
            } else {
                $totalCp = $this->storage->currentMap->nbCheckpoints;
            }
        } else {
            $totalCp = $this->storage->currentMap->nbCheckpoints;
        }

        $sizeX = 42;
        $sizeY = 3 + $this->config->livecpPanel_nbFields * 4;

        $this->trayScript->setParam("sizeX", $this->trayScript->getNumber($sizeX));
        $this->trayScript->setParam("sizeY", $this->trayScript->getNumber(3 + $this->config->livecpPanel_nbFields * 4));
        $this->script->setParam("nbFields", $this->config->livecpPanel_nbFields);
        $this->script->setParam("nbFirstFields", $this->config->livecpPanel_nbFirstFields);
        $this->script->setParam("totalCp", $totalCp);
        
        $this->widget->setPosition($this->config->livecpPanel_PosX, $this->config->livecpPanel_PosY, 0);
        $this->widget->setSize($sizeX, $sizeY);
        Widgets_LocalRecords::setPanelParams($this->widget, $sizeX, $this->config->livecpPanel_nbFields, "LiveCP - Total CP: " . $totalCp, null);
        $this->widget->show();
        $this->widgetShown = true;
        
        if (!$gui->disablePersonalHud) {
            $this->widget2->setPosition($this->config->livecpPanel_PosX, $this->config->livecpPanel_PosY, 0);
            $this->widget2->setSize($sizeX, $sizeY);
            Widgets_LocalRecords::setPanelParams($this->widget2, $sizeX, $this->config->livecpPanel_nbFields, "LiveCP - Total CP: " . $totalCp, null);
            $this->widget2->show();
            $this->widget2Shown = true;
        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->config = Config::getInstance();
            if ($this->widgetShown) {
                $this->displayWidget();
            }
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if ($this->widgetShown) {
            $this->widget->show($login);
            if ($this->widget2Shown) {
                $this->widget2->show($login);
            }
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        if ($this->storage->getCleanGamemodeName() != "endurocup") {
            $this->displayWidget();
        }
    }

    public function onBeginMatch()
    {
        if ($this->storage->getCleanGamemodeName() != "endurocup" && !$this->widgetShown) {
            $this->displayWidget();
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        if ($this->widget2 instanceof Widget) {
            $this->widget2->erase();
        }
        $this->widgetShown = false;
        $this->widget2Shown = false;
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        if ($this->widget2 instanceof Widget) {
            $this->widget2->erase();
        }
        $this->widget = null;
        $this->widget2 = null;

        $this->config = null;
        $this->script = null;
        $this->trayScript = null;

        $this->widgetShown = false;
        $this->widget2Shown = false;
    }
}
