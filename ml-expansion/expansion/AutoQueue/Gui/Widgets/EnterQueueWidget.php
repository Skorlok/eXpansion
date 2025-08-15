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

namespace ManiaLivePlugins\eXpansion\AutoQueue\Gui\Widgets;

use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\AutoQueue\AutoQueue;
use ManiaLivePlugins\eXpansion\Gui\Elements\DicoLabel;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;

/**
 * Description of EnterQueueWidget
 *
 * @author Reaby
 */
class EnterQueueWidget extends Widget
{
    protected $dicoLabel;
    protected $button;

    protected function eXpOnBeginConstruct()
    {
        $this->setName("Enter Queue");
        $login = $this->getRecipient();

        $bg = new WidgetBackGround(81, 18);
        $this->addComponent($bg);

        $header = new WidgetTitle(81, 4, eXpGetMessage("Join Queue"));
        $this->addComponent($header);

        $this->dicoLabel = new DicoLabel(50, 10);
        $this->dicoLabel->setPosition(2, -6);
        $this->dicoLabel->setText(eXpGetMessage("Click the button to \njoin the waiting queue!"));
        $this->dicoLabel->setTextColor("fff");
        $this->addComponent($this->dicoLabel);

        $frame = new Frame(50, -7);

        $this->button = new \ManiaLive\Gui\Elements\Xml();
        $this->button->setContent(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Join", $login), null, null, "0f0", null, null, AutoQueue::$enterAction, null, null, null, null, null, null));
        $frame->addComponent($this->button);

        $button = new \ManiaLive\Gui\Elements\Xml();
        $button->setContent('<frame posn="0 -6 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Hide", $login), array("Click waiting queue to show this window again."), null, null, null, null, $this->createAction(array($this, "hideWidget")), null, null, null, null, null, null) . '</frame>');
        $frame->addComponent($button);

        $this->addComponent($frame);

        $this->registerScript(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getScriptML());
    }

    protected function eXpOnEndConstruct()
    {
        $this->setSize(80, 18);
    }

    public function hideWidget($login)
    {
        $this->Erase($login);
    }
}
