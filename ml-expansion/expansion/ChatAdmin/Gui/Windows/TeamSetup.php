<?php

namespace ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows;

use ManiaLib\Gui\Layouts\Column;
use ManiaLive\Gui\Controls\Frame;
use ManiaLib\Gui\Elements\Label;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Gui\Elements\ColorChooser;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

class TeamSetup extends Window
{
    public static $mainPlugin;
    protected $frame;
    
    public function onConstruct()
    {
        parent::onConstruct();

        $this->frame = new Frame(0, -8, new Column());
        $this->addComponent($this->frame);

        $input = new Inputbox("team1Name");
        $input->setLabel("Team1 Name");
        $this->frame->addComponent($input);

        $lbl = new Label(100, 30);
        $lbl->setPosition(0, 6);
        $lbl->setText("Team1 Color (DO NOT ENTER $$)");
        $lbl->setSize(35, 12);
        $this->frame->addComponent($lbl);

        $input = new ColorChooser("team1Color", 35, 3, false);
        $input->setPosX(0);
        $input->setPosY(-20);
        $this->addComponent($input);
        
        $input = new Inputbox("team2Name");
        $input->setLabel("Team2 Name");
        $this->frame->addComponent($input);

        $lbl = new Label(100, 30);
        $lbl->setPosition(0, 6);
        $lbl->setText("Team2 Color (DO NOT ENTER $$)");
        $lbl->setSize(35, 12);
        $this->frame->addComponent($lbl);

        $input = new ColorChooser("team2Color", 35, 3, false);
        $input->setPosX(0);
        $input->setPosY(-44);
        $this->addComponent($input);

        $button = new Button();
        $button->setText("Ok");
        $button->setAction($this->createAction(array($this, "ok")));
        $button->setPosX(5);
        $button->setPosY(-3);
        $this->frame->addComponent($button);

    }

    public function Ok($login, $data)
    {
        $this->EraseAll();

        $r1 = hexdec($data["team1Color"][0].$data["team1Color"][0]);
		$g1 = hexdec($data["team1Color"][1].$data["team1Color"][1]);
		$b1 = hexdec($data["team1Color"][2].$data["team1Color"][2]);

        $r2 = hexdec($data["team2Color"][0].$data["team2Color"][0]);
		$g2 = hexdec($data["team2Color"][1].$data["team2Color"][1]);
		$b2 = hexdec($data["team2Color"][2].$data["team2Color"][2]);

        $data["team1Color"] = ($this->RGBToHSL($r1, $g1, $b1)/360);
        $data["team2Color"] = ($this->RGBToHSL($r2, $g2, $b2)/360);
        
        self::$mainPlugin->setTeamDisplayAfterWindow($login, $data);
    }

    private function RGBToHSL($r, $g, $b) {
        $r = ($r / 255.0);
        $g = ($g / 255.0);
        $b = ($b / 255.0);
    
        $min = min(min($r, $g), $b);
        $max = max(max($r, $g), $b);
        $delta = $max - $min;
    
        if ($delta == 0) {
            return (float)0.0;
        } else {
            if ($r == $max) {
                $hue = (($g - $b) / 6) / $delta;
            } else if ($g == $max) {
                $hue = (1.0 / 3) + (($b - $r) / 6) / $delta;
            } else {
                $hue = (2.0 / 3) + (($r - $g) / 6) / $delta;
            }
    
            if ($hue < 0)
                $hue += 1;
            if ($hue > 1)
                $hue -= 1;
    
            return (float)($hue * 360);
        }
    }
}
