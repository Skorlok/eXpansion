<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Livecp;

use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Gui\Config as guiConfig;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Widgets_Livecp extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    private $config;
    private $widget;
    private $widget2;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
        if (strtolower($this->connection->getScriptName()['CurrentValue']) != "endurocup.script.txt") {
            $this->displayWidget();
        }
    }

    private function displayWidget()
    {
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
        $widgetScript = $this->getWidgetScript($this->config->livecpPanel_nbFields, $this->config->livecpPanel_nbFirstFields, $totalCp);
        $trayScript = $this->getTrayScript($sizeX, $this->config->livecpPanel_nbFields);

        $this->widget = new Widget("Widgets_Livecp\Gui\Widgets\LiveCP.xml");
        $this->widget->setName("CpLive Widget");
        $this->widget->setLayer("normal");
        $this->widget->setPosition($this->config->livecpPanel_PosX, $this->config->livecpPanel_PosY, 0);
        $this->widget->setSize($sizeX, $sizeY);
        $this->widget->setParam("sizeX", $sizeX);
        $this->widget->setParam("nbFields", $this->config->livecpPanel_nbFields);
        $this->widget->setParam("title", "LiveCP - Total CP: " . $totalCp);
        $this->widget->setParam("guiConfig", guiConfig::getInstance());
        $this->widget->registerScript(new Script('Gui/Script_libraries/TimeToText'));
        $this->widget->registerScript($widgetScript);
        $this->widget->registerScript($trayScript);
        $this->widget->show();

        /** @var ManiaLivePlugins\eXpansion\Gui\Gui $gui */
        if (!$gui->disablePersonalHud) {
            $this->widget2 = new Widget("Widgets_Livecp\Gui\Widgets\LiveCP.xml");
            $this->widget2->setName("CpLive Widget");
            $this->widget2->setLayer("scorestable");
            $this->widget2->setPosition($this->config->livecpPanel_PosX, $this->config->livecpPanel_PosY, 0);
            $this->widget2->setSize($sizeX, $sizeY);
            $this->widget2->setParam("sizeX", $sizeX);
            $this->widget2->setParam("nbFields", $this->config->livecpPanel_nbFields);
            $this->widget2->setParam("title", "LiveCP - Total CP: " . $totalCp);
            $this->widget2->setParam("guiConfig", guiConfig::getInstance());
            $this->widget2->registerScript(new Script('Gui/Script_libraries/TimeToText'));
            $this->widget2->registerScript($widgetScript);
            $this->widget2->registerScript($trayScript);
            $this->widget2->show();
        }
    }

    public function getWidgetScript($nbField, $nbFirstField, $totalCp = 0)
    {
        $script = new Script("Widgets_Livecp/Gui/Scripts/PlayerFinish");
        $script->setParam("playerTimes", "[]");
        $script->setParam("nbRecord", 510);
        $script->setParam("nbFields", $nbField);
        $script->setParam("nbFirstFields", $nbFirstField);
        $script->setParam('varName', 'LocalTime1');
        $script->setParam("totalCp", $totalCp);
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
            $this->displayWidget();
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->displayWidget();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        if (strtolower($this->connection->getScriptName()['CurrentValue']) != "endurocup.script.txt") {
            $this->displayWidget();
        }
    }

    public function onBeginMatch()
    {
        if ($this->storage->getCleanGamemodeName() != "endurocup") {
            $this->displayWidget();
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            if ($this->widget2 instanceof Widget) {
                $this->widget2->erase();
            }
        }
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            $this->widget = null;
            if ($this->widget2 instanceof Widget) {
                $this->widget2->erase();
                $this->widget2 = null;
            }
        }
        $this->disableDedicatedEvents();
    }
}
