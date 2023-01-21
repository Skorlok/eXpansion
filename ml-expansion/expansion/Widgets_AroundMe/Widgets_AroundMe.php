<?php

namespace ManiaLivePlugins\eXpansion\Widgets_AroundMe;

use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Widgets_AroundMe extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{


    public static $me = null;

    public static $secondMap = false;
    private $forceUpdate = false;
    private $needUpdate = false;

    private $widgetIds = array();
    public static $raceOn;
    public static $roundPoints;

    /** @var Config */
    private $config;

    public function eXpOnLoad()
    {

        $this->config = Config::getInstance();
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->updateAroundMe();
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


    public function updateAroundMe($login = null)
    {
        Gui\Widgets\AroundMe::$connection = $this->connection;

        $localRecs = Gui\Widgets\AroundMe::GetAll();
        if ($login == null) {
            $panelMain = Gui\Widgets\AroundMe::Create($login);
            $panelMain->setLayer(\ManiaLive\Gui\Window::LAYER_NORMAL);
            $panelMain->setSizeX(40);
            $this->widgetIds["AroundMe"] = $panelMain;
            $this->widgetIds["AroundMe"]->update();
            $this->widgetIds["AroundMe"]->show();
        } elseif (isset($localRecs[0])) {
            $localRecs[0]->update();
            $localRecs[0]->show($login);
        }

    }

    public function showAroundMe($login)
    {
        $this->updateAroundMe($login);
    }

    public function hideAroundMe()
    {
        $this->widgetIds = array();
        Gui\Widgets\AroundMe::EraseAll();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        self::$raceOn = false;
        $this->hideAroundMe();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($wasWarmUp) {
            self::$raceOn = false;
            $this->forceUpdate = true;
            $this->updateAroundMe();
            self::$secondMap = true;
            self::$raceOn = true;
        } else {
            $this->hideAroundMe();
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
        $this->getRoundsPoints();
        self::$raceOn = false;
        $this->forceUpdate = true;
        $this->hideAroundMe();
        self::$secondMap = true;
        self::$raceOn = true;
    }

    public function onBeginMatch()
    {
        self::$raceOn = false;
        $this->forceUpdate = true;
        $this->hideAroundMe();
        $this->updateAroundMe();
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
        $this->hideAroundMe();
        $this->updateAroundMe();
        self::$raceOn = true;
    }


    public function onPlayerConnect($login, $isSpectator)
    {

        $this->showAroundMe($login);
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        Gui\Widgets\AroundMe::Erase($login);
    }


    public function eXpOnUnload()
    {
        Gui\Widgets\AroundMe::EraseAll();
        self::$me = null;
    }
}
