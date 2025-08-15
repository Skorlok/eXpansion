<?php
/*
 * Copyright (C) 2014 Reaby
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ManiaLivePlugins\eXpansion\Bets\Gui\Widgets;

use ManiaLib\Gui\Layouts\Flow;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Bets\Bets;
use ManiaLivePlugins\eXpansion\Bets\Config;
use ManiaLivePlugins\eXpansion\Gui\Elements\DicoLabel;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;

/**
 * Description of BetWidget
 *
 * @author Reaby
 */
class BetWidget extends Widget
{
    public static $action_acceptBet;
    public static $action_setAmount;
    public static $action_setAmount25;
    public static $action_setAmount50;
    public static $action_setAmount100;
    public static $action_setAmount250;
    public static $action_setAmount500;
    protected $frame;
    protected $labelAccept;
    protected $bg;
    protected $header;
    protected $buttonAccept;
    protected $script;

    protected function eXpOnBeginConstruct()
    {
        $sX = 42;
        $this->setName("Bet widget");

        $this->frame = new Frame(1, -8);
        $this->addComponent($this->frame);

        $this->script = new Script("Bets/Gui/Scripts");
        $this->script->setParam("hideFor", "Text[]");
        $this->registerScript($this->script);

        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="52 -15 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Close", null, null, null, null, null, null, null, null, null, "closeButton", null, null) . '</frame>');
        $this->addComponent($button);
    }

    public function onDraw()
    {
        if (Bets::$state == Bets::SET) {
            $this->setBets();
        }
        if (Bets::$state == Bets::ACCEPT) {
            $this->acceptBets();
        }
        parent::onDraw();
    }

    public function acceptBets()
    {
        $this->frame->clearComponents();

        $this->bg = new WidgetBackGround(80, 19.75);
        $this->addComponent($this->bg);

        $this->header = new WidgetTitle($this->sizeX, 4, eXpGetMessage("Accept Bet"));
        $this->addComponent($this->header);

        $line = new Frame();
        $line->setLayout(new Flow());
        $line->setSize(80, 12);

        $this->labelAccept = new DicoLabel(50);
        $this->labelAccept->setPosition(5, -2);
        $this->labelAccept->setText(eXpGetMessage('Accept bet for %1$s planets ?'), array("" . Bets::$betAmount));
        $line->addComponent($this->labelAccept);

        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="52 -8 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Accept", null, null, "0f0", null, null, self::$action_acceptBet, null, null, null, null, null, null) . '</frame>');
        $this->addComponent($button);
        $this->frame->addComponent($line);
    }

    public function setBets()
    {
        $this->frame->clearComponents();

        $this->script->setParam("action", self::$action_setAmount);

        $this->bg = new WidgetBackGround($this->sizeX, $this->sizeY);
        $this->addComponent($this->bg);

        $this->header = new WidgetTitle($this->sizeX, 4, eXpGetMessage("Start Bet"));
        $this->addComponent($this->header);

        $line = new Frame();
        $line->setLayout(new Flow());
        $line->setSize(60, 6);

        $x = 0;
        foreach (array(25, 50, 100, 250, 500) as $value) {
            $var = "action_setAmount" . $value;
            $button = new \ManiaLive\Gui\Elements\Xml();
            $button->setContent('<frame posn="' . (16.5 * ($x % 3)) . ' -' . ((6*(floor($x/3)))) . ' 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(20, 6, $value, null, null, "3af", null, null, self::$$var, null, null, null, null, null, null) . '</frame>');
            $line->addComponent($button);
            $x++;
        }

        $inputbox = new Inputbox("betAmount", 18);
        $inputbox->setPosition(33, -6);
        $line->addComponent($inputbox);

        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="52 -8 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Accept", null, null, "0d0", null, null, self::$action_setAmount, null, null, null, null, null, null) . '</frame>');
        $this->addComponent($button);

        $this->frame->addComponent($line);
    }

    /**
     * set logins to maniascritp to hide the widget...
     *
     * @param string[] $players
     */
    public function setToHide($players)
    {
        $out = \ManiaLivePlugins\eXpansion\Helpers\Maniascript::stringifyAsStringList($players);
        if (count($players) == 0) {
            $out = "Text[]";
        }
        $this->script->setParam("hideFor", $out);
    }
}
