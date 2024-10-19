<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LiveRankings;

use ManiaLivePlugins\eXpansion\Widgets_LiveRankings\Gui\Widgets\LivePanel;
use ManiaLivePlugins\eXpansion\Widgets_LiveRankings\Gui\Widgets\LivePanel2;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_LiveRankings extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    public static $me = null;
    public static $secondMap = false;
    private $forceUpdate = false;
    private $needUpdate = false;
    private $widgetIds = array();
    public static $raceOn = true;
    public static $roundPoints;
    private $config;

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $this->enableDedicatedEvents();
        $this->updateLivePanel();
        self::$me = $this;

        $this->getRoundsPoints();
    
        $this->enableScriptEvents("Maniaplanet.StartRound_Start");
    }
	
	public function eXpOnModeScriptCallback($callback, $array)
    {
        switch ($callback) {
            case "Maniaplanet.StartRound_Start":
                $this->onBeginRound(0);
                break;
        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->config = Config::getInstance();
            Gui\Widgets\LivePanel::EraseAll();
            $this->updateLivePanel();
        }
    }

    public function updateLivePanel($login = null)
    {
        if (strtolower($this->connection->getScriptName()['CurrentValue']) == "endurocup.script.txt") {
            return;
        }
        Gui\Widgets\LivePanel::$connection = $this->connection;
        $gui = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

        //gamemode specific settings
        if (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS) {
            $posX = $this->config->liveRankingPanel_PosX_Laps;
            $posY = $this->config->liveRankingPanel_PosY_Laps;
            $nbF = $this->config->liveRankingPanel_nbFields_Laps;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Laps;
        } elseif (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_ROUNDS) {
            $posX = $this->config->liveRankingPanel_PosX_Rounds;
            $posY = $this->config->liveRankingPanel_PosY_Rounds;
            $nbF = $this->config->liveRankingPanel_nbFields_Rounds;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Rounds;
        } elseif (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TEAM) {
            $posX = $this->config->liveRankingPanel_PosX_Team;
            $posY = $this->config->liveRankingPanel_PosY_Team;
            $nbF = $this->config->liveRankingPanel_nbFields_Team;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Team;
        } elseif (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_CUP) {
            $posX = $this->config->liveRankingPanel_PosX_Cup;
            $posY = $this->config->liveRankingPanel_PosY_Cup;
            $nbF = $this->config->liveRankingPanel_nbFields_Cup;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Cup;
        } else {
            $posX = $this->config->liveRankingPanel_PosX_Default;
            $posY = $this->config->liveRankingPanel_PosY_Default;
            $nbF = $this->config->liveRankingPanel_nbFields_Default;
            $nbFF = $this->config->liveRankingPanel_nbFirstFields_Default;
        }

        if ($login != null) {
            Gui\Widgets\LivePanel::Erase($login);
            Gui\Widgets\LivePanel2::Erase($login);
        } else {
            Gui\Widgets\LivePanel::EraseAll();
            Gui\Widgets\LivePanel2::EraseAll();
        }

        $localRecs = LivePanel::GetAll();

        if ($login == null) {
            $panelMain = Gui\Widgets\LivePanel::Create($login);
            $panelMain->setLayer(\ManiaLive\Gui\Window::LAYER_NORMAL);
            $panelMain->setPosition($posX, $posY);
            $panelMain->setNbFields($nbF);
            $panelMain->setNbFirstFields($nbFF);
            $this->widgetIds["LivePanel"] = $panelMain;
            $this->widgetIds["LivePanel"]->update();
            $this->widgetIds["LivePanel"]->show();
        } else {
            if (isset($localRecs[0])) {
                $localRecs[0]->update();
                $localRecs[0]->show($login);
            }
        }

        if (!$gui->disablePersonalHud) {
            $localRecs = LivePanel2::GetAll();
            if ($login == null) {
                $panelScore = Gui\Widgets\LivePanel2::Create($login);
                $panelScore->setLayer(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);
                $panelScore->setVisibleLayer("scorestable");
                $panelMain->setPosition($posX, $posY);
                $panelMain->setNbFields($nbF);
                $panelMain->setNbFirstFields($nbFF);
                $this->widgetIds["LivePanel2"] = $panelScore;
                $this->widgetIds["LivePanel2"]->update();
                $this->widgetIds["LivePanel2"]->show();
            } else {
                if (isset($localRecs[0])) {
                    $localRecs[0]->update();
                    $localRecs[0]->show($login);
                }
            }
        }

        $gamemode = self::eXpGetCurrentCompatibilityGameMode();
        if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP) {
            if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_SCRIPT) {
                $this->connection->triggerModeScriptEvent("UI_DisplaySmallScoresTable", "False");
            } else {
                \ManiaLive\Gui\CustomUI::HideForAll(\ManiaLive\Gui\CustomUI::ROUND_SCORES);
            }
        }
    }

    public function showLivePanel($login)
    {
        $this->updateLivePanel($login);
    }

    public function hideLivePanel()
    {
        Gui\Widgets\LivePanel::EraseAll();
        Gui\Widgets\LivePanel2::EraseAll();
        $this->widgetIds = array();

    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        self::$raceOn = false;
        $this->hideLivePanel();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($wasWarmUp) {
            self::$raceOn = false;
            $this->forceUpdate = true;
            $this->updateLivePanel();
            self::$secondMap = true;
            self::$raceOn = true;
        } else {
            $this->hideLivePanel();
        }
    }

    public function onScoresCalculated($scores)
    {
        $gamemode = self::eXpGetCurrentCompatibilityGameMode();
        if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP || $gamemode == GameInfos::GAMEMODE_LAPS) {
            return;
        }

        if (self::$raceOn == true) {
            $this->updateLivePanel();
        }
    }

    public function getRoundsPoints()
    {
        $points = $this->connection->getRoundCustomPoints();
        if (empty($points)) {
            self::$roundPoints = array(10, 6, 4, 3, 2, 1);
        } else {
            self::$roundPoints = $points;
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        if (self::$raceOn == true) {
            return;
        }

        $this->getRoundsPoints();
        self::$raceOn = false;
        $this->forceUpdate = true;
        $this->hideLivePanel();
        $this->updateLivePanel();
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
        $this->hideLivePanel();
        $this->updateLivePanel();
        self::$secondMap = true;
        self::$raceOn = true;
    }

    public function onEndRound()
    {

    }

    public function onBeginRound()
    {
        //We need to reset the panel for next Round
        self::$raceOn = false;
        $this->getRoundsPoints();
        $this->hideLivePanel();
        $this->updateLivePanel();
        self::$raceOn = true;
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->showLivePanel($login);
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        Gui\Widgets\LivePanel::Erase($login);
        Gui\Widgets\LivePanel2::Erase($login);
    }

    public function eXpOnUnload()
    {
        Gui\Widgets\LivePanel::EraseAll();
        Gui\Widgets\LivePanel2::EraseAll();
    }
}