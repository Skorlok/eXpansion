<?php

namespace ManiaLivePlugins\eXpansion\Widgets_DedimaniaRecords;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Widgets_DedimaniaRecords\Gui\Widgets\DediPanel;
use ManiaLivePlugins\eXpansion\Widgets_DedimaniaRecords\Gui\Widgets\DediPanel2;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_DedimaniaRecords extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    const NONE = 0x0;
    const DEDIMANIA = 0x2;
    const DEDIMANIA_FORCE = 0x8;
    const All = 0x31;

    public static $dedirecords = array();
    public static $secondMap = false;
    private $widgetIds = array();
    public static $raceOn;
    public static $roundPoints;
    private $config;

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
                if ($login != null) {
                    Gui\Widgets\DediPanel::Erase($login);
                    Gui\Widgets\DediPanel2::Erase($login);
                } else {
                    Gui\Widgets\DediPanel::EraseAll();
                    Gui\Widgets\DediPanel2::EraseAll();
                }
                $localRecs = DediPanel::GetAll();
                if ($login == null) {
                    $panelMain = Gui\Widgets\DediPanel::Create($login);
                    $panelMain->setLayer(\ManiaLive\Gui\Window::LAYER_NORMAL);
                    $panelMain->setPosition($posX, $posY);
                    $panelMain->setNbFields($nbF);
			        $panelMain->setNbFirstFields($nbFF);
                    $this->widgetIds["DediPanel"] = $panelMain;
                    $this->widgetIds["DediPanel"]->update();
                    $this->widgetIds["DediPanel"]->show();
                } elseif (isset($localRecs[0])) {
                    $localRecs[0]->update();
                    $localRecs[0]->show($login);
                }
                if (!$gui->disablePersonalHud) {
                    $localRecs = DediPanel2::GetAll();
                    if ($login == null) {
                        $panelScore = Gui\Widgets\DediPanel2::Create($login);
                        $panelScore->setLayer(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);
                        $panelScore->setVisibleLayer("scorestable");
                        $panelScore->setPosition($posX, $posY);
                        $panelScore->setNbFields($nbF);
                        $panelScore->setNbFirstFields($nbFF);
                        $this->widgetIds["DediPanel2"] = $panelScore;
                        $this->widgetIds["DediPanel2"]->update();
                        $this->widgetIds["DediPanel2"]->show();
                    } elseif (isset($localRecs[0])) {
                        $localRecs[0]->update();
                        $localRecs[0]->show($login);
                    }
                }
            }
        } catch (\Exception $ex) {

        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof Config) {
            $this->config = Config::getInstance();
            Gui\Widgets\DediPanel::EraseAll();
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
        $this->widgetIds = array();
        Gui\Widgets\DediPanel::EraseAll();
        Gui\Widgets\DediPanel2::EraseAll();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($restartMap) {
            self::$secondMap = true;
        }
        if ($wasWarmUp) {
            self::$raceOn = false;
            $this->updateDediPanel();
            self::$secondMap = true;
            self::$raceOn = true;
        } else {
            self::$secondMap = false;
            self::$dedirecords = array(); // reset
            $this->widgetIds = array();
            Gui\Widgets\DediPanel::EraseAll();
            Gui\Widgets\DediPanel2::EraseAll();
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        self::$raceOn = false;
        $this->widgetIds = array();
        Gui\Widgets\DediPanel::EraseAll();
        Gui\Widgets\DediPanel2::EraseAll();
        $this->updateDediPanel();
        self::$secondMap = true;
        self::$raceOn = true;
    }

    public function onBeginMatch()
    {
        if (self::$raceOn == true) {
            return;
        }

        self::$raceOn = false;
        $this->widgetIds = array();
        Gui\Widgets\DediPanel::EraseAll();
        Gui\Widgets\DediPanel2::EraseAll();
        $this->updateDediPanel();
        self::$secondMap = true;
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

    public function onPlayerDisconnect($login, $reason = null)
    {
        Gui\Widgets\DediPanel::Erase($login);
        Gui\Widgets\DediPanel2::Erase($login);
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
        Gui\Widgets\DediPanel::EraseAll();
        Gui\Widgets\DediPanel2::EraseAll();

        Dispatcher::unregister(\ManiaLivePlugins\eXpansion\Dedimania\Events\Event::getClass(), $this);
    }
}