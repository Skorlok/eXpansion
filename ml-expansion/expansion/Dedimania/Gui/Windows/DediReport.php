<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ManiaLivePlugins\eXpansion\Dedimania\Gui\Windows;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Data\Storage;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

/**
 * Description of DediReport
 *
 * @author Petri Järvisalo <petri.jarvisalo@gmail.com>
 */
class DediReport extends Window
{
    protected $report;
    protected $infolabel;
    protected $frame;

    protected function onConstruct()
    {
        parent::onConstruct();

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setLayout(new Column());
        $this->addComponent($this->frame);

        $info = new Label(100, 16);
        $info->setTextSize(2);
        $info->setText(
            "To report invalid records for dedimania, \n please copy the following info at clipboard (ctrl-a + ctrl-c)"
        );
        $this->frame->addComponent($info);

        $this->report = new \ManiaLive\Gui\Elements\Xml();
        $this->frame->addComponent($this->report);

        $info = new Label(100, 32);
        $info->setPosY(-50);
        $info->setTextSize(2);
        $info->setText(
            '$fffThen go click following link:$3af' . "\n" . '$lhttp://dedimania.net/SITE/forum/viewtopic.php?id=384$l '
            . "\n" . '$fffand post this information there.'
        );
        $this->frame->addComponent($info);
    }

    public function setLogin($login)
    {
        $text = "Login to check: " . $login . "&#10;";
        $text .= "Map: " . Storage::getInstance()->currentMap->uId . "&#10;";
        $text .= "Reason: *edit your reason here*" . "&#10;";
        $text .= "Reportee: " . $this->getRecipient() . "&#10;";
        $this->report->setContent('<textedit posn="0 -16 1.0E-5" sizen="100 40" default="' . $text . '" textformat="default" name="" showlinenumbers="0" autonewline="0"/>');
    }
}
