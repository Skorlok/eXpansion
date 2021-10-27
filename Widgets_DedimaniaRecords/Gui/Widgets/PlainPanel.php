<?php

namespace ManiaLivePlugins\eXpansion\Widgets_DedimaniaRecords\Gui\Widgets;

use ManiaLivePlugins\eXpansion\Dedimania\Classes\Connection;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Widgets_DedimaniaRecords\Widgets_DedimaniaRecords;
use ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Gui\Controls\Recorditem;

class PlainPanel extends \ManiaLivePlugins\eXpansion\Widgets_LocalRecords\Gui\Widgets\PlainPanel
{

    public function eXpOnBeginConstruct()
    {
        parent::eXpOnBeginConstruct();
        $this->setName("Dedimania Panel");
        $this->timeScript->setParam('varName', 'DediTime1');
        $this->bg->setAction(\ManiaLivePlugins\eXpansion\Dedimania\DedimaniaAbstract::$actionOpenRecs);
    }

    public function update()
    {
        $login = $this->getRecipient();

        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->items = array();
        $this->frame->clearComponents();

        $index = 1;

        $this->bgTitle->setText(eXpGetMessage('Dedimania Records'));


        $recsData = "";
        $nickData = "";

        for ($index = 1; $index <= $this->nbFields; $index++) {
            $this->items[$index - 1] = new Recorditem($index, false);
            $this->frame->addComponent($this->items[$index - 1]);
        }

        $index = 1;
        foreach (Widgets_DedimaniaRecords::$dedirecords as $record) {
            if ($index > 1) {
                $recsData .= ', ';
                $nickData .= ', ';
            }
            $recsData .= '"' . Gui::fixString($record['Login']) . '"=> ' . $record['Best'];
            $nickData .= '"' . Gui::fixString($record['Login']) . '"=>"' . Gui::fixString($record['NickName']) . '"';
            $index++;
        }

        if (empty($recsData)) {
            $recsData = 'Integer[Text]';
            $nickData = 'Text[Text]';
        } else {
            $recsData = '[' . $recsData . ']';
            $nickData = '[' . $nickData . ']';
        }

        $this->timeScript->setParam("nbRecord", 100);
        $this->timeScript->setParam("playerTimes", $recsData);
        $this->timeScript->setParam("playerNicks", $nickData);

        $playersOnServer = "";
        $index = 1;
        foreach ($this->storage->players as $player) {
            if ($index > 1) {
                $playersOnServer .= ', ';
            }
            $playersOnServer .= '"' . Gui::fixString($player->login) . '"=>"' . Gui::fixString($player->nickName) . '"';
            $index++;
        }

        foreach ($this->storage->spectators as $player) {
            if ($index > 1) {
                $playersOnServer .= ', ';
            }
            $playersOnServer .= '"' . Gui::fixString($player->login) . '"=>"' . Gui::fixString($player->nickName) . '"';
            $index++;
        }

        if (empty($playersOnServer)) {
            $playersOnServer = 'Text[Text]';
        } else {
            $playersOnServer = '[' . $playersOnServer . ']';
        }

        $this->timeScript->setParam("playersOnline", $playersOnServer);
    }

    public function fixDashes($string)
    {
        $out = str_replace('--', '––', $string);

        return $out;
    }

    public function fixHyphens($string)
    {
        $out = str_replace('"', "'", $string);
        $out = str_replace('\\', '\\\\', $out);
        $out = str_replace('-', '–', $out);

        return $out;
    }
}
