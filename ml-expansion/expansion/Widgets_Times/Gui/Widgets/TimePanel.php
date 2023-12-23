<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Times\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Gui\Gui;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class TimePanel extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{
    protected $position;
    protected $top1;
    protected $totalCp = 0;
    protected $lapRace = false;
    protected $target = "";
    protected $reference = 1;

    public static $localrecords = array();
    public static $dedirecords = array();

    protected $checkValidCps = true;

    protected function eXpOnBeginConstruct()
    {
        $this->setName("Player Time Panel");
    }

    public function setTarget($login)
    {
        $this->target = Gui::fixString($login);
    }

    public function setReference($val)
    {
        $this->reference = $val;
    }

    public function setMapInfo($map, $gamemode, $ScriptSettings)
    {
        if (\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro) {
            $gamemode = GameInfos::GAMEMODE_LAPS;
        }
        if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP) {

            if ($map->lapRace) {

                $this->checkValidCps = false;
                $this->lapRace = 2;

                if (array_key_exists("S_ForceLapsNb", $ScriptSettings)) {
                    if ($ScriptSettings['S_ForceLapsNb'] > 0) {
                        $this->totalCp = $map->nbCheckpoints * $ScriptSettings['S_ForceLapsNb'];
                    } else {
                        $this->totalCp = $map->nbCheckpoints * $map->nbLaps;
                    }
                } else {
                    $this->totalCp = $map->nbCheckpoints * $map->nbLaps;
                }

            } else {
                $this->checkValidCps = true;
                $this->totalCp = $map->nbCheckpoints;
                $this->lapRace = 0;
            }

        } else {
            if ($map->lapRace) {
                $this->lapRace = 1;
            } else {
                $this->lapRace = 0;
            }
            $this->totalCp = $map->nbCheckpoints;
            $this->checkValidCps = true;
        }
    }

    protected function onDraw()
    {
        $widget = new \ManiaLive\Gui\Elements\Xml();
        $widget->setContent('<frame posn="20 -6 0">
            <frame posn="-40 3.5 0">
                <frame>
                    <label id="Cp" posn="20 0 0" sizen="22 4" halign="right" valign="center" style="TextStaticSmall" scriptevents="1" textcolor="fff" text=""/>
                    <label id="Label" posn="22 0 1.0E-5" sizen="50 4" halign="left" valign="center" style="TextRaceChrono" scriptevents="1" textsize="4" text=""/>
                    <label id="CpTop1" posn="72 0 2.0E-5" sizen="9 4" halign="left" valign="center" style="TextStaticSmall"/>
                    <label id="DediLabel" posn="81 0 3.0E-5" sizen="30 4" halign="left" valign="center" style="TextRaceChrono" textsize="4" text=""/>
                </frame>
            </frame>
            <audio posn="0 260 4.0E-5" looping="0"/>
        </frame>');
        $this->addComponent($widget);

        $script = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script('Widgets_Times/Gui/Scripts_Time');
        $this->registerScript($script);


        $playerRecord = \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::getObjbyPropValue(self::$localrecords, "login", $this->target);
        $drecord = array_search($this->target, array_column(self::$dedirecords, 'Login'));

        $record = false;

        // Now check for the PB in dedi and local records
        if ($drecord) {
            if ($playerRecord) {
                if ($playerRecord->time <= self::$dedirecords[$drecord]['Best']) {
                    $record = $playerRecord;
                } else {
                    $record = new \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record();
                    $record->place = self::$dedirecords[$drecord]['Rank'];
                    $record->time = self::$dedirecords[$drecord]['Best'];
                    $record->nickName = self::$dedirecords[$drecord]['NickName'];
                    $record->ScoreCheckpoints = explode(",", self::$dedirecords[$drecord]['Checks']);
                }
            } else {
                $record = new \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record();
                $record->place = self::$dedirecords[$drecord]['Rank'];
                $record->time = self::$dedirecords[$drecord]['Best'];
                $record->nickName = self::$dedirecords[$drecord]['NickName'];
                $record->ScoreCheckpoints = explode(",", self::$dedirecords[$drecord]['Checks']);
            }
        } else {
            $record = $playerRecord;
        }

        if ($this->checkValidCps) {

            $checkpoints = "[ -1 ]";
            $noRecs = true;

            // Add record information for MS usage.
            if ($record instanceof \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record) {
                // Normally all CP even last one should be in the object,
                // but not in databases imported from XAseco where last CP is missing.
                if (sizeof($record->ScoreCheckpoints) == $this->totalCp) {
                    // Normal DB entry with all CP's.
                    $checkpoints = "[" . implode(",", $record->ScoreCheckpoints) . "]";
                    $noRecs = false;
                    // XAseco entry missing last CP. Add the record time as it is the the same value.
                } elseif (sizeof($record->ScoreCheckpoints) == $this->totalCp - 1) {
                    $checkpoints = "[" . implode(",", $record->ScoreCheckpoints) . ", " . $record->time . "]";
                    $noRecs = false;
                }
            }

            // If CP in database don't match Map or no records send empty CP information.
            if ($noRecs) {
                $checkpoints = '[';
                for ($i = 0; $i < $this->totalCp; $i++) {
                    if ($i > 0) {
                        $checkpoints .= ', ';
                    }
                    $checkpoints .= -1;
                }
                $checkpoints .= ']';
            }

        } else {

            $checkpoints = "[ -1 ]";
            $noRecs = true;

            if ($record instanceof \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record) {
                if ($record->ScoreCheckpoints[count($record->ScoreCheckpoints) - 1] != $record->time) {
                    $checkpoints = "[" . implode(",", $record->ScoreCheckpoints) . ", " . $record->time . "]";
                    $noRecs = false;
                } else {
                    $checkpoints = "[" . implode(",", $record->ScoreCheckpoints) . "]";
                    $noRecs = false;
                }
            }

            if ($noRecs) {
                $checkpoints = '[';
                for ($i = 0; $i < $this->totalCp; $i++) {
                    if ($i > 0) {
                        $checkpoints .= ', ';
                    }
                    $checkpoints .= -1;
                }
                $checkpoints .= ']';
            }

        }

        // Send data for the dedimania records.
        $dediTime = "";
        $reference = $this->reference;
        if (sizeof(self::$dedirecords) > 0) {
            if (isset(self::$dedirecords[$reference - 1])) {
                $record = self::$dedirecords[$reference - 1];
            } else {
                $record = self::$dedirecords[0];
                $reference = 1;
            }
            $dediTime = '[' . $record['Checks'] . ']';
        } else {
            $dediTime = '[';
            for ($i = 0; $i < $this->totalCp; $i++) {
                if ($i > 0) {
                    $dediTime .= ', ';
                }
                $dediTime .= -1;
            }
            $dediTime .= ']';
        }

        $script->setParam('checkpoints', $checkpoints);
        $script->setParam('deditimes', $dediTime);
        $script->setParam('totalCp', $this->totalCp);
        $script->setParam('target', Gui::fixString($this->target));
        $script->setParam('lapRace', $this->lapRace);
        $script->setParam("playSound", 'True');
        $script->setParam("reference", $reference);

        parent::onDraw();
    }
}
