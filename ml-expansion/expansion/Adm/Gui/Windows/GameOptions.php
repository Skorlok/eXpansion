<?php

namespace ManiaLivePlugins\eXpansion\Adm\Gui\Windows;

use ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted as Checkbox;
use ManiaLivePlugins\eXpansion\Gui\Elements\Ratiobutton;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class GameOptions extends Window
{
    /** @var  \Maniaplanet\DedicatedServer\Connection */
    private $connection;

    /** @var \ManiaLive\Data\Storage */
    private $storage;

    /** @var GameInfos */
    private $nextGameInfo;

    protected $actionOK;
    protected $actionTA;
    protected $actionRounds;
    protected $actionLaps;
    protected $actionCup;
    protected $actionTeam;

    protected $buttonTA;
    protected $buttonRounds;
    protected $buttonCup;
    protected $buttonTeams;
    protected $buttonLaps;
    protected $buttonOK;

    protected $frameGameMode;

    private $e = array();
    private $nextMode = null;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->actionOK = $this->createAction(array($this, "ok"));
        $this->actionTA = $this->createAction(array($this, "setGamemode"), GameInfos::GAMEMODE_TIMEATTACK);
        $this->actionRounds = $this->createAction(array($this, "setGamemode"), GameInfos::GAMEMODE_ROUNDS);
        $this->actionLaps = $this->createAction(array($this, "setGamemode"), GameInfos::GAMEMODE_LAPS);
        $this->actionCup = $this->createAction(array($this, "setGamemode"), GameInfos::GAMEMODE_CUP);
        $this->actionTeam = $this->createAction(array($this, "setGamemode"), GameInfos::GAMEMODE_TEAM);

        $this->setTitle(__('Game Options', $this->getRecipient()));

        $this->nextGameInfo = $this->connection->getNextGameInfo();
        $this->nextMode = $this->nextGameInfo->gameMode;

        $this->buttonOK = new \ManiaLive\Gui\Elements\Xml();
        $this->buttonOK->setContent('<frame posn="131 -83 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Apply", $this->getRecipient()), null, null, null, null, null, $this->actionOK, null, null, null, null, null, null) . '</frame>');
        $this->addComponent($this->buttonOK);

        $this->genGameModes();
        $this->genGeneral();
    }

    public function handleSpecialChars($string)
    {
        if ($string == null) {
            return "";
        }
        return str_replace(array('&', '"', "'", '>', '<', "\n", "\t", "\r"), array('&amp;', '&quot;', '&apos;', '&gt;', '&lt;', '&#10;', '&#9;', '&#13;'), $string);
    }

    private function genGeneral()
    {
        $login = $this->getRecipient();

        $content = '<frame posn="0 -5 0">';

        $content .= '<frame>';
        $content .= '<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("ChatTime", 35, true, $this->handleSpecialChars(__("Podium: Chat Time", $login)), \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::TMtoMS($this->nextGameInfo->chatTime), null, null) . '</frame>';
        $content .= '<frame posn="39 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("AllWarmupDuration", 35, true, $this->handleSpecialChars(__("All: Warmup Duration", $login)), $this->nextGameInfo->allWarmUpDuration, null, null) . '</frame>';
        $content .= '<frame posn="78 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("finishTimeOut", 35, true, $this->handleSpecialChars(__("All: Finish Timeout", $login)), \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::TMtoMS($this->nextGameInfo->finishTimeout), null, null) . '</frame>';
        $content .= '</frame>';

        $content .= '<frame posn="0 -10 0">';
        $content .= '<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("timeAttackLimit", 35, true, $this->handleSpecialChars(__("TimeAttack: Time Limit", $login)), \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::TMtoMS($this->nextGameInfo->timeAttackLimit), null, null) . '</frame>';
        $content .= '<frame posn="39 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("timeAttackSynchStartPeriod", 35, true, $this->handleSpecialChars(__("TimeAttack: Synch Start Period", $login)), $this->nextGameInfo->timeAttackSynchStartPeriod, null, null) . '</frame>';
        $content .= '</frame>';

        $content .= '<frame posn="0 -20 0">';
        $content .= '<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("roundsPointsLimit", 35, true, $this->handleSpecialChars(__("Rounds: Points Limit", $login)), $this->nextGameInfo->roundsPointsLimit, null, null) . '</frame>';
        $content .= '<frame posn="39 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("roundsForcedLaps", 35, true, $this->handleSpecialChars(__("Rounds: Set Number of Forced Laps", $login)), $this->nextGameInfo->roundsForcedLaps, null, null) . '</frame>';
        $content .= '<frame posn="78 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("roundsPointsLimitNewRules", 35, true, $this->handleSpecialChars(__("Rounds: Points Limit (NewRules)", $login)), $this->nextGameInfo->roundsPointsLimitNewRules, null, null) . '</frame>';
        $content .= '</frame>';

        $content .= '<frame posn="0 -30 0">';
        $content .= '<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("teamPointsLimit", 35, true, $this->handleSpecialChars(__("Team: Points Limit", $login)), $this->nextGameInfo->teamPointsLimit, null, null) . '</frame>';
        $content .= '<frame posn="39 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("teamMaxPoints", 35, true, $this->handleSpecialChars(__("Team: Max Points For a Round", $login)), $this->nextGameInfo->teamMaxPoints, null, null) . '</frame>';
        $content .= '<frame posn="78 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("teamPointsLimitNewRules", 35, true, $this->handleSpecialChars(__("Team: Points Limit (NewRules)", $login)), $this->nextGameInfo->teamPointsLimitNewRules, null, null) . '</frame>';
        $content .= '</frame>';

        $content .= '<frame posn="0 -40 0">';
        $content .= '<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("cupPointsLimit", 35, true, $this->handleSpecialChars(__("Cup: Points Limit", $login)), $this->nextGameInfo->cupPointsLimit, null, null) . '</frame>';
        $content .= '<frame posn="39 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("cupNbWinners", 35, true, $this->handleSpecialChars(__("Cup: Number of Winners", $login)), $this->nextGameInfo->cupNbWinners, null, null) . '</frame>';
        $content .= '<frame posn="78 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("cupRoundsPerMap", 35, true, $this->handleSpecialChars(__("cup Rounds Per Map", $login)), $this->nextGameInfo->cupRoundsPerMap, null, null) . '</frame>';
        $content .= '</frame>';

        $content .= '<frame posn="0 -50 0">';
        $content .= '<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("lapsNbLaps", 35, true, $this->handleSpecialChars(__("Laps: Number of Laps", $login)), $this->nextGameInfo->lapsNbLaps, null, null) . '</frame>';
        $content .= '<frame posn="39 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("lapsTimeLimit", 35, true, $this->handleSpecialChars(__("Laps: Time Limit", $login)), \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::TMtoMS($this->nextGameInfo->lapsTimeLimit), null, null) . '</frame>';
        $content .= '</frame>';

        $content .= '</frame>';

        $input = new \ManiaLive\Gui\Elements\Xml();
        $input->setContent($content);
        $this->mainFrame->addComponent($input);
    }

    // Generate all inputboxes
    private function genGameModes()
    {
        $login = $this->getRecipient();
        $this->frameGameMode = new \ManiaLive\Gui\Controls\Frame($this->sizeX - 40, 0);
        $this->frameGameMode->setAlign("left", "top");
        $this->frameGameMode->setLayout(new \ManiaLib\Gui\Layouts\Column());
        $this->frameGameMode->setSize(100, 11);

        $lbl = new \ManiaLib\Gui\Elements\Label(25, 6);
        $lbl->setText(__("Choose Gamemode:", $login));
        $lbl->setTextSize(1);
        $this->frameGameMode->addComponent($lbl);

        $this->buttonTA = new Ratiobutton();
        $this->buttonTA->setText(__("Time Attack", $login));
        $this->buttonTA->setAction($this->actionTA);
        if ($this->nextMode == GameInfos::GAMEMODE_TIMEATTACK) {
            $this->buttonTA->setStatus(true);
        }
        $this->frameGameMode->addComponent($this->buttonTA);

        $this->buttonRounds = new Ratiobutton();
        $this->buttonRounds->setText(__("Rounds", $login));
        $this->buttonRounds->setAction($this->actionRounds);
        if ($this->nextMode == GameInfos::GAMEMODE_ROUNDS) {
            $this->buttonRounds->setStatus(true);
        }
        $this->frameGameMode->addComponent($this->buttonRounds);

        $this->buttonCup = new Ratiobutton();
        $this->buttonCup->setText(__("Cup", $login));
        $this->buttonCup->setAction($this->actionCup);
        if ($this->nextMode == GameInfos::GAMEMODE_CUP) {
            $this->buttonCup->setStatus(true);
        }
        $this->frameGameMode->addComponent($this->buttonCup);

        $this->buttonLaps = new Ratiobutton();
        $this->buttonLaps->setText(__("Laps", $login));
        $this->buttonLaps->setAction($this->actionLaps);
        if ($this->nextMode == GameInfos::GAMEMODE_LAPS) {
            $this->buttonLaps->setStatus(true);
        }
        $this->frameGameMode->addComponent($this->buttonLaps);

        $this->buttonTeams = new Ratiobutton();
        $this->buttonTeams->setText(__("Team", $login));
        $this->buttonTeams->setAction($this->actionTeam);
        if ($this->nextMode == GameInfos::GAMEMODE_TEAM) {
            $this->buttonTeams->setStatus(true);
        }
        $this->frameGameMode->addComponent($this->buttonTeams);

        $lbl = new \ManiaLib\Gui\Elements\Label(25, 8);
        $lbl->setText(__("Additional Options:", $login));
        $lbl->setTextSize(1);
        $lbl->setPosY(-1);
        $this->frameGameMode->addComponent($lbl);

        $this->e['roundsUseNewRules'] = new Checkbox(4, 4, 30);
        $this->e['roundsUseNewRules']->setPosX(-1);
        if ($this->nextGameInfo->roundsUseNewRules) {
            $this->e['roundsUseNewRules']->setStatus(true);
        }
        $this->e['roundsUseNewRules']->setText(__("Rounds: use new rules", $login));
        $this->frameGameMode->addComponent($this->e['roundsUseNewRules']);

        $this->e['teamUseNewRules'] = new Checkbox(4, 4, 30);
        $this->e['teamUseNewRules']->setPosX(-1);
        if ($this->nextGameInfo->teamUseNewRules) {
            $this->e['teamUseNewRules']->setStatus(true);
        }
        $this->e['teamUseNewRules']->setText(__("Team: use new rules", $login));
        $this->frameGameMode->addComponent($this->e['teamUseNewRules']);

        $this->e['DisableRespawn'] = new Checkbox(4, 4, 30);
        $this->e['DisableRespawn']->setPosX(-1);
        if ($this->nextGameInfo->disableRespawn) {
            $this->e['DisableRespawn']->setStatus(true);
        }
        $this->e['DisableRespawn']->setText(__("Disable Respawn", $login));
        $this->frameGameMode->addComponent($this->e['DisableRespawn']);

        $this->e['ForceShowAllOpponents'] = new Checkbox(4, 4, 30);
        $this->e['ForceShowAllOpponents']->setPosX(-1);
        if ($this->nextGameInfo->forceShowAllOpponents) {
            $this->e['ForceShowAllOpponents']->setStatus(true);
        }

        $this->e['ForceShowAllOpponents']->setText(__("Force Show All Opponents", $login));
        $this->frameGameMode->addComponent($this->e['ForceShowAllOpponents']);

        $this->mainFrame->addComponent($this->frameGameMode);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frameGameMode->setPosition($this->sizeX - 36, 0);
    }

    public function setGameMode($login, $gameMode)
    {
        $this->nextMode = $gameMode;
        $this->frameGameMode->clearComponents();
        $this->mainFrame->removeComponent($this->frameGameMode);
        $this->genGameModes();
        $this->frameGameMode->setPosition($this->sizeX - 36, 0);
        $this->RedrawAll();
    }

    public function ok($login, $options)
    {
        $gameInfos = $this->nextGameInfo;

        foreach ($this->e as $component) {
            if ($component instanceof Checkbox) {
                $component->setArgs($options);
            }
        }

        //general
        $gameInfos->allWarmUpDuration = intval($options['AllWarmupDuration']);
        $gameInfos->cupWarmUpDuration = intval($options['AllWarmupDuration']);
        $gameInfos->finishTimeout = intval(
            \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::MStoTM($options['finishTimeOut'])
        );

        $gameInfos->chatTime = \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::MStoTM($options['ChatTime']);

        $gameInfos->disableRespawn = $this->e['DisableRespawn']->getStatus();
        $gameInfos->forceShowAllOpponents = $this->e['ForceShowAllOpponents']->getStatus();

        $gameInfos->roundsUseNewRules = $this->e['roundsUseNewRules']->getStatus();
        $gameInfos->teamUseNewRules = $this->e['teamUseNewRules']->getStatus();

        $gameInfos->gameMode = $this->nextMode;

        //ta
        $gameInfos->timeAttackLimit = \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::MStoTM(
            $options['timeAttackLimit']
        );
        $gameInfos->timeAttackSynchStartPeriod = intval($options['timeAttackSynchStartPeriod']);

        //rounds
        $gameInfos->roundsForcedLaps = intval($options['roundsForcedLaps']);
        $gameInfos->roundsPointsLimit = intval($options['roundsPointsLimit']);
        $gameInfos->roundsPointsLimitNewRules = intval($options['roundsPointsLimitNewRules']);

        //team
        $gameInfos->teamPointsLimit = intval($options['teamPointsLimit']);
        $gameInfos->teamPointsLimitNewRules = intval($options['teamPointsLimitNewRules']);
        $gameInfos->teamMaxPoints = intval($options['teamMaxPoints']);

        //cup
        $gameInfos->cupNbWinners = intval($options['cupNbWinners']);
        $gameInfos->cupPointsLimit = intval($options['cupPointsLimit']);
        $gameInfos->cupRoundsPerMap = intval($options['cupRoundsPerMap']);

        //laps
        $gameInfos->lapsNbLaps = intval($options['lapsNbLaps']);
        $gameInfos->lapsTimeLimit = \ManiaLivePlugins\eXpansion\Helpers\TimeConversion::MStoTM(
            $options['lapsTimeLimit']
        );
        try {
            $this->connection->setGameInfos($gameInfos);
        } catch (\Exception $e) {
            $this->connection->chatSendServerMessage('$f00Dedicated error: ' . $e->getMessage(), $login);
            \ManiaLib\Utils\Logger::error("Error while setGameInfos: " . $e->getMessage());
        }

        $this->Erase($login);
    }

    public function destroy()
    {
        $this->connection = null;
        $this->storage = null;

        $this->buttonCup->destroy();
        $this->buttonLaps->destroy();
        $this->buttonRounds->destroy();
        $this->buttonTA->destroy();
        $this->buttonTeams->destroy();

        $this->e = array();

        $this->frameGameMode->clearComponents();
        $this->frameGameMode->destroy();

        $this->destroyComponents();
        parent::destroy();
    }
}
