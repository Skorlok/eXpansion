<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Times\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Gui\Gui;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;

class TimePanel extends \ManiaLivePlugins\eXpansion\Gui\Widgets\Widget
{
    protected $checkpoint;
    protected $time;
    protected $audio;
    protected $frame;
    protected $position;
    protected $top1;
    protected $totalCp = 0;
    protected $lapRace = false;
    protected $nScript;
    protected $target = "";
    protected $reference = 1;


    public static $localrecords = array();
    public static $dedirecords = array();

    protected $checkValidCps = true;

    protected function eXpOnBeginConstruct()
    {
        $login = $this->getRecipient();

        $frame = new \ManiaLive\Gui\Controls\Frame();
        $frame->setPosition(20, -6);
        $this->addComponent($frame);

        $this->setAlign("center", "center");
        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setAlign("center", "center");
        $this->frame->setSize(80, 7);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $frame->addComponent($this->frame);

        $this->checkpoint = new \ManiaLib\Gui\Elements\Label(22, 4);
        $this->checkpoint->setTextColor("fff");
        $this->checkpoint->setAlign("right", "center");
        $this->checkpoint->setText('');
        $this->checkpoint->setId("Cp");
        $this->checkpoint->setPosX(20);
        $this->checkpoint->setScriptEvents();
        $this->frame->addComponent($this->checkpoint);

        $this->time = new \ManiaLib\Gui\Elements\Label(50, 4);
        $this->time->setAlign("left", "center");
        $this->time->setStyle("TextRaceChrono");
        $this->time->setText('');
        $this->time->setId("Label");
        $this->time->setScriptEvents();
        $this->time->setTextSize(4);
        $this->frame->addComponent($this->time);

        $this->position = new \ManiaLib\Gui\Elements\Label(9, 4);
        $this->position->setId("CpTop1");
        $this->position->setAlign("left", "center");
        $this->frame->addComponent($this->position);

        $this->top1 = new \ManiaLib\Gui\Elements\Label(30, 4);
        $this->top1->setId("DediLabel");
        $this->top1->setStyle("TextRaceChrono");
        $this->top1->setTextSize(4);
        $this->top1->setText('');
        $this->top1->setAlign("left", "center");
        $this->frame->addComponent($this->top1);

        $this->audio = new \ManiaLib\Gui\Elements\Audio();
        $this->audio->setPosY(260);
        $frame->addComponent($this->audio);

        $this->nScript = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script('Widgets_Times/Gui/Scripts_Time');
        $this->registerScript($this->nScript);

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
                    $record->nickName = self::$dedirecords[$drecord]['NickName'];
                    $record->ScoreCheckpoints = explode(",", self::$dedirecords[$drecord]['Checks']);
                }
            } else {
                $record = new \ManiaLivePlugins\eXpansion\LocalRecords\Structures\Record();
                $record->place = self::$dedirecords[$drecord]['Rank'];
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

        $this->nScript->setParam('checkpoints', $checkpoints);
        $this->nScript->setParam('deditimes', $dediTime);
        $this->nScript->setParam('totalCp', $this->totalCp);
        $this->nScript->setParam('target', Gui::fixString($this->target));
        $this->nScript->setParam('lapRace', $this->lapRace);
        $this->nScript->setParam("playSound", 'True');
        $this->nScript->setParam("reference", $reference);

        parent::onDraw();
    }
}
