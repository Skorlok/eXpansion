<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LiveRankings\Gui\Widgets;

use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Data\Storage;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button as myButton;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;
use ManiaLivePlugins\eXpansion\LocalRecords\Config;
use ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Gui\Controls\Recorditem;
use ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Gui\Scripts\PlayerFinish;
use ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Widgets_LocalRecords;

use ManiaLib\Utils\Formatting;
use ManiaLivePlugins\eXpansion\Gui\Script_libraries\TextExt;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Widgets_LiveRankings\Gui\Scripts\CpPositions;
use ManiaLivePlugins\eXpansion\Widgets_LiveRankings\Widgets_LiveRankings;

class PlainLivePanel extends Widget
{
    public static $connection;
    /**
     * @var Control[]
     */
    protected $items = array();

    /**
     * @var Button
     */
    protected $layer;

    /** @var Storage */
    public $storage;
    public $timeScript;
    protected $nbFields;
    protected $firstNbFields;
    public $trayWidget;

    protected function eXpOnBeginConstruct()
    {
        $this->setScriptEvents();
        $this->storage = Storage::getInstance();
        $this->setName("Live Rankings Panel");
        $this->registerScript($this->getScript());
        $this->registerScript(new TextExt());
        parent::eXpOnBeginConstruct();
    }

    protected function autoSetPositions()
    {
        parent::autoSetPositions();
        $nbFields = $this->getParameter('nbFields');
        $nbFieldsFirst = $this->getParameter('nbFirstFields');
        if ($nbFields != null && $nbFieldsFirst != null) {
            $this->setNbFields($nbFields);
            $this->setNbFirstFields($nbFieldsFirst);
        }
    }

    public function setNbFields($nb)
    {
        if (Widgets_LocalRecords::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TEAM) {
            $this->timeScript->setParam("nbFields", $nb-1);
            $this->nbFields = $nb-1;
        } else {
            $this->timeScript->setParam("nbFields", $nb);
            $this->nbFields = $nb;
        }
    }

    public function setNbFirstFields($nb)
    {
        $this->timeScript->setParam("nbFirstFields", $nb);
        $this->firstNbFields = $nb;
    }

    protected function getScript()
    {
        $gm = Widgets_LiveRankings::eXpGetCurrentCompatibilityGameMode();
        if ($gm == GameInfos::GAMEMODE_ROUNDS || $gm == GameInfos::GAMEMODE_CUP || $gm == GameInfos::GAMEMODE_TEAM || Widgets_LocalRecords::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS) {

            $script = new CpPositions();
            $this->timeScript = $script;
            $this->timeScript->setParam("totalCp", $this->storage->currentMap->nbCheckpoints);
            $this->timeScript->setParam("nbFields", 20);
            $this->timeScript->setParam("nbFirstFields", 5);
            $this->timeScript->setParam('varName', 'LiveTime1');
            $this->timeScript->setParam("playerTimes", 'Integer[Text][Integer]');
            $this->timeScript->setParam("nickNames", 'Text[Text]');
            $this->timeScript->setParam("bestCps", 'Integer[Integer]');
            $this->timeScript->setParam("maxCp", -1);

            $this->timeScript->setParam("givePoints", "True");
            $this->timeScript->setParam("points", "Integer[Integer]");
            $this->timeScript->setParam("nbLaps", 1);
            $this->timeScript->setParam("isLaps", "False");
            $this->timeScript->setParam("isTeam", "False");
            $this->timeScript->setParam("team1Color", '$3AF');
            $this->timeScript->setParam("team2Color", '$D00');
            $this->timeScript->setParam("playerTeams", "Integer[Text]");


            $teamMaxPoint = 10;
            $ScriptSettings = self::$connection->getModeScriptSettings();
            if (array_key_exists("S_ForceLapsNb", $ScriptSettings)) {
                if ($ScriptSettings['S_ForceLapsNb'] != -1) {
                    $nbLaps = $ScriptSettings['S_ForceLapsNb'];
                } else {
                    $nbLaps = $this->storage->currentMap->nbLaps;
                }
            }
            if (isset($ScriptSettings['S_MaxPointsPerRound'])) {
                $teamMaxPoint = $ScriptSettings['S_MaxPointsPerRound'];
            }

            $this->timeScript->setParam("maxPoint", $teamMaxPoint);

            if (Widgets_LocalRecords::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS) {
                $this->timeScript->setParam("isLaps", "True");
                $this->timeScript->setParam("nbLaps", $nbLaps);
            } elseif (Widgets_LocalRecords::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_ROUNDS && $this->storage->currentMap->nbLaps > 1) {
                $this->timeScript->setParam("isLaps", "True");
                $this->timeScript->setParam("nbLaps", $nbLaps);
            } elseif (Widgets_LocalRecords::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TEAM && $this->storage->currentMap->nbLaps > 1) {
                $this->timeScript->setParam("isLaps", "True");
                $this->timeScript->setParam("nbLaps", $nbLaps);
            } elseif (Widgets_LocalRecords::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_CUP && $this->storage->currentMap->nbLaps > 1) {
                $this->timeScript->setParam("isLaps", "True");
                $this->timeScript->setParam("nbLaps", $nbLaps);
            }

            if (Widgets_LocalRecords::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS) {
                $this->timeScript->setParam("givePoints", "False");
            }

            if (Widgets_LocalRecords::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TEAM) {
                $this->timeScript->setParam("isTeam", "True");

                $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();

                if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
                    $this->timeScript->setParam("team1Color", '$' . $var["team1Color"]);
                    $this->timeScript->setParam("team2Color", '$' . $var["team2Color"]);
                } else {
                    $this->timeScript->setParam("team1Color", '$3AF');
                    $this->timeScript->setParam("team2Color", '$D00');
                }
            }

            if (!empty(Widgets_LiveRankings::$roundPoints)) {
                $this->timeScript->setParam("points", "[" . implode(",", Widgets_LiveRankings::$roundPoints) . "]");
            }

            return $script;
        } else {
            $script = new PlayerFinish();

            $recCount = Config::getInstance()->recordsCount;
            $this->timeScript = $script;
            $this->timeScript->setParam("playerTimes", "[]");
            $this->timeScript->setParam("nbRecord", $recCount);
            $this->timeScript->setParam("nbFields", 20);
            $this->timeScript->setParam("nbFirstFields", 5);
            $this->timeScript->setParam('varName', 'LocalTime1');
            $this->timeScript->setParam('getCurrentTimes', Widgets_LocalRecords::$secondMap ? "True" : "False");

            return $script;
        }
    }

    public function update()
    {
        $sizeX = 42;
        $sizeY = 51;

        $windowFrame = new Frame();
        $windowFrame->setAlign("left", "top");
        $windowFrame->setId("Frame");
        $windowFrame->setScriptEvents(true);
        $windowFrame->setSize($sizeX, $sizeY);
        $this->addComponent($windowFrame);

        $bg = new WidgetBackGround($sizeX, (3 + $this->nbFields * 4) + 1.5);
        $windowFrame->addComponent($bg);

        $bgTitle = new WidgetTitle($sizeX, $sizeY, eXpGetMessage("Live Rankings"), "minimizeButton");
        $windowFrame->addComponent($bgTitle);

        $bgFirst = new Quad($sizeX, $sizeY);
        $bgFirst->setBgcolor("aaa5");
        $bgFirst->setAlign("center", "top");
        $bgFirst->setPosX(($sizeX / 2) + 1);
        $bgFirst->setPosY((-4 * $this->firstNbFields) - 3);
        $bgFirst->setSize($this->sizeX / 1.5, 0.3);
        $windowFrame->addComponent($bgFirst);

        $frame = new Frame();
        $frame->setAlign("left", "top");
        $frame->setLayout(new Column(-1));
        $frame->setPosition(($sizeX / 2) + 1, -5.5);
        $windowFrame->addComponent($frame);

        $this->layer = new myButton(5, 5);
        $this->layer->setIcon("UIConstruction_Buttons", "Down");
        $this->layer->setId("toggleMicroMenu");
        $this->layer->setDescription("Switch from Race view to Score View(Visible on Tab)", 75);
        $this->layer->setPosY(-1.7);
        $this->addComponent($this->layer);

        $this->trayWidget = new Script("Gui/Scripts/NewTray");
        $this->registerScript($this->trayWidget);
        $this->trayWidget->setParam("sizeX", $sizeX);
        $this->trayWidget->setParam("sizeY", 3 + $this->nbFields * 4);

        $this->setSizeX($sizeX);
        $this->setSizeY(3 + $this->nbFields * 4);




        $index = 1;

        $gm = Widgets_LiveRankings::eXpGetCurrentCompatibilityGameMode();
        $short = false;
        if ($gm == GameInfos::GAMEMODE_ROUNDS || $gm == GameInfos::GAMEMODE_CUP || $gm == GameInfos::GAMEMODE_TEAM || $gm == GameInfos::GAMEMODE_LAPS) {
            $short = true;
        }

        for ($index = 1; $index <= $this->nbFields; $index++) {
            $this->items[$index - 1] = new Recorditem($index, false, $short);
            $frame->addComponent($this->items[$index - 1]);
        }

        if ($gm == GameInfos::GAMEMODE_TEAM) {
            $var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();

            if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
                $team1Name = $var["team1Name"] . ' : ';
                $team1Color = $var["team1Color"];
            } else {
                $team1Name = '$wBlue Team : ';
                $team1Color = '00f';
            }
    
            if (isset($var["team1Name"]) && isset($var["team2Name"]) && isset($var["team1ColorHSL"]) && isset($var["team2ColorHSL"]) && isset($var["team1Color"]) && isset($var["team2Color"])) {
                $team2Name = $var["team2Name"] . ' : ';
                $team2Color = $var["team2Color"];
            } else {
                $team2Name = '$wRed Team : ';
                $team2Color = 'f00';
            }

            $item = new \ManiaLive\Gui\Elements\Xml();
            $item->setContent('<frame posn="-20 -84 0.00189">
                <label posn="12 0 0" sizen="12 4" halign="right" valign="center" style="TextRaceChat" textsize="1" textcolor="' . $team1Color . '" text="' . $team1Name . '"/>
                <label id="bluePoints" posn="17 0 1.0E-5" sizen="4 4" halign="right" valign="center" style="TextRaceChat" scriptevents="1" textsize="1" textcolor="fff"/>
                <label posn="32 0 2.0E-5" sizen="12 4" halign="right" valign="center" style="TextRaceChat" textsize="1" textcolor="' . $team2Color . '" text="' . $team2Name . '"/>
                <label id="redPoints" posn="36 0 3.0E-5" sizen="4 4" halign="right" valign="center" style="TextRaceChat" scriptevents="1" textsize="1" textcolor="fff"/>
                </frame>');

            $this->items[$index - 1] = $item;
            $frame->addComponent($this->items[$index - 1]);
        }

        if ($gm == GameInfos::GAMEMODE_ROUNDS || $gm == GameInfos::GAMEMODE_CUP || $gm == GameInfos::GAMEMODE_TEAM || $gm == GameInfos::GAMEMODE_LAPS) {

            $guiConfig = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();
            $menu = new \ManiaLive\Gui\Elements\Xml();
            $menu->setContent('<frame id="MicroMenu">
                <frame scriptevents="1">
                    <quad id="mQuad_1" sizen="30 5" halign="left" valign="center" bgcolor="' . $guiConfig->style_widget_bgColorize . '" bgcolorfocus="' . $guiConfig->style_widget_title_bgColorize . '" scriptevents="1"/>
                    <label id="item_1" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Put On TAB View"/>
                </frame>
            
                <frame posn="0 -5 2.0E-5" scriptevents="1">
                    <quad id="mQuad_2" sizen="30 5" halign="left" valign="center" bgcolor="' . $guiConfig->style_widget_bgColorize . '" bgcolorfocus="' . $guiConfig->style_widget_title_bgColorize . '" scriptevents="1"/>
                    <label id="item_2" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Rectract Widget"/>
                </frame>
            </frame>');
            $this->addComponent($menu);

            $this->cpUpdate();
        } else {

            $guiConfig = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();
            $menu = new \ManiaLive\Gui\Elements\Xml();
            $menu->setContent('<frame id="MicroMenu">
                <frame scriptevents="1">
                    <quad id="mQuad_1" sizen="30 5" halign="left" valign="center" bgcolor="' . $guiConfig->style_widget_bgColorize . '" bgcolorfocus="' . $guiConfig->style_widget_title_bgColorize . '" scriptevents="1"/>
                    <label id="item_1" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Show Differences"/>
                </frame>

                <frame posn="0 -5 2.0E-5" scriptevents="1">
                    <quad id="mQuad_2" sizen="30 5" halign="left" valign="center" bgcolor="' . $guiConfig->style_widget_bgColorize . '" bgcolorfocus="' . $guiConfig->style_widget_title_bgColorize . '" scriptevents="1"/>
                    <label id="item_2" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Rectract Widget"/>
                </frame>
            
                <frame posn="0 -10 4.0E-5" scriptevents="1">
                    <quad id="mQuad_3" sizen="30 5" halign="left" valign="center" bgcolor="' . $guiConfig->style_widget_bgColorize . '" bgcolorfocus="' . $guiConfig->style_widget_title_bgColorize . '" scriptevents="1"/>
                    <label id="item_3" posn="2 0 1.0E-5" sizen="30 5" halign="left" valign="center" style="TextRaceChat" textsize="1" textcolor="fff" text="Put On TAB View"/>
                </frame>
            </frame>');
            $this->addComponent($menu);

            $this->taUpdate();
        }
    }

    protected function taUpdate()
    {
        $index = 1;

        $players = Core::$rankings;

        $recsData = "";
        $nickData = "";

        foreach ($players as $player) {
            if (!empty($player->bestTime) && $player->bestTime > 0) {
                if ($index > 1) {
                    $recsData .= ', ';
                    $nickData .= ', ';
                }
                $recsData .= '"' . Gui::fixString($player->login) . '"=>' . $player->bestTime;
                $nickData .= '"' . Gui::fixString($player->login) . '"=>"'
                    . Gui::fixString(Formatting::stripLinks($player->nickName)) . '"';
                $index++;
            }
        }

        $this->timeScript->setParam("totalCp", $this->storage->currentMap->nbCheckpoints);

        if (empty($recsData)) {
            $recsData = 'Integer[Text]';
            $nickData = 'Text[Text]';
        } else {
            $recsData = '[' . $recsData . ']';
            $nickData = '[' . $nickData . ']';
        }
        
        $this->timeScript->setParam("playerTimes", $recsData);
        $this->timeScript->setParam("playerNicks", $nickData);
    }

    protected function cpUpdate()
    {
        if (!Widgets_LiveRankings::$raceOn) {
            return;
        }


        $nbCheckpoints = array();
        $playerCps = array();
        $playerNickNames = array();
        $bestCps = array();
        $biggestCp = -1;

        foreach (Core::$playerInfo as $login => $player) {
            $lastCpIndex = count($player->checkpoints) - 1;
            $playerNickNames[$player->login] = $player->nickName;

            if ($player->isPlaying && $lastCpIndex >= 0
                && isset($player->checkpoints[$lastCpIndex]) && $player->checkpoints[$lastCpIndex] > 0
            ) {

                if ($lastCpIndex > $biggestCp) {
                    $biggestCp = $lastCpIndex;
                }

                $lastCpTime = $player->checkpoints[$lastCpIndex];
                $player = $this->storage->getPlayerObject($login);
                $playerCps[$lastCpIndex][$login] = $lastCpTime;
                $playerTeams[$login] = $player->teamId;
            }
        }

        $newPlayerCps = array();
        foreach ($playerCps as $coIndex => $cpsTimes) {
            arsort($cpsTimes);
            $newPlayerCps[$coIndex] = $cpsTimes;
        }

        $playerTimes = "[";
        $NickNames = "[";
        $teams = "[";

        $index = 1;
        foreach ($playerNickNames as $login => $nickname) {
            if ($index > 1) {
                $NickNames .= ', ';
            }
            $NickNames .= '"' . Gui::fixString($login) . '"=>"'
                . Gui::fixString(Formatting::stripLinks($nickname)) . '"';
            $index++;
        }
        $NickNames .= "]";

        $cpCount = 0;
        $teamCont = 0;
        foreach ($newPlayerCps as $cpIndex => $cpTimes) {
            if ($cpCount != 0) {
                $playerTimes .= ", ";
            }
            $playerTimes .= $cpIndex . "=>[";

            $cCount = 0;
            $nbCheckpoints[$cpIndex] = 0;
            foreach ($cpTimes as $login => $score) {
                if ($cCount != 0) {
                    $playerTimes .= ", ";
                }
                if ($teamCont != 0) {
                    $teams .= ", ";
                }
                $playerTimes .= '"' . $login . "\"=>" . $score;
                $teams .= '"' . $login . "\"=>" . ($playerTeams[$login] == 1 ? 0 : 1);
                $nbCheckpoints[$cpIndex]++;
                $cCount++;
                $teamCont++;

                if (!isset($bestCps[$cpIndex]) || $score < $bestCps[$cpIndex]) {
                    $bestCps[$cpIndex] = $score;
                }
            }

            $playerTimes .= "]";
            $cpCount++;

        }
        $playerTimes .= "]";
        $teams .= "]";

        $bestCpsText = '';
        foreach ($bestCps as $cpIndex => $time) {
            if ($bestCpsText != "") {
                $bestCpsText .= ', ';
            }
            $bestCpsText .= $cpIndex . '=>' . $time;
        }

        $bestCps = '[' . $bestCpsText . ']';


        if ($teamCont == 0) {
            $this->timeScript->setParam("playerTeams", "Integer[Text]");
        } else {
            $this->timeScript->setParam("playerTeams", $teams);
        }

        if (!empty($newPlayerCps)) {
            $this->timeScript->setParam("playerTimes", $playerTimes);
            $this->timeScript->setParam("nickNames", $NickNames);
            $this->timeScript->setParam("maxCp", $biggestCp + 1);
            $this->timeScript->setParam("bestCps", $bestCps);
        } else {
            $this->timeScript->setParam("playerTimes", 'Integer[Text][Integer]');
            $this->timeScript->setParam("nickNames", 'Text[Text]');
            $this->timeScript->setParam("maxCp", -1);
        }
    }
}
