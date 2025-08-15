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

use ManiaLib\Gui\Elements\Label;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\AutoQueue\AutoQueue;
use ManiaLivePlugins\eXpansion\AutoQueue\Structures\QueuePlayer;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetBackGround;
use ManiaLivePlugins\eXpansion\Gui\Elements\WidgetTitle;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;

/**
 * Description of EnterQueueWidget
 *
 * @author Reaby
 */
class QueueList extends Widget
{
    public $frame;

    /** @var QueuePlayer[] */
    public $queueplayers = array();
    protected $mainInstance = null;
    protected $bg;

    protected function eXpOnBeginConstruct()
    {
        $this->setName("Queue List");
    }

    protected function eXpOnEndConstruct()
    {
        $this->setSize(62, 40);
    }

    public function setPlayers($players, $instance)
    {
        $this->queueplayers = $players;
        $this->mainInstance = $instance;

        $this->bg = new WidgetBackGround(62, 40, AutoQueue::$showEnterQueueAction);
        foreach ($this->queueplayers as $player) {
            if ($player->login == $this->getRecipient()) {
                $this->bg = new WidgetBackGround(62, 40);
            }
        }
        $this->addComponent($this->bg);

        $header = new WidgetTitle(62, 40, eXpGetMessage("Waiting Queue"));
        $this->addComponent($header);

        $this->frame = new Frame(1, -2);
        $this->addComponent($this->frame);

        $this->frame->clearComponents();
        $x = 1;

        foreach ($this->queueplayers as $player) {
            $label = new Label(30, 6);
            $label->setAlign("left", "center2");
            $label->setPosition(0, -($x * 6));
            $label->setText($x . ".  " . $player->nickName);
            $this->frame->addComponent($label);

            if ($player->login == $this->getRecipient()) {
                $button = new \ManiaLive\Gui\Elements\Xml();
                $button->setContent('<frame posn="32 -' . ($x * 6) . ' 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Leave", $this->getRecipient()), null, null, null, null, null, $this->createAction(array($this->mainInstance, "leaveQueue")), null, null, null, null, null, null) . '</frame>');
                $this->frame->addComponent($button);
            } else if (AdminGroups::hasPermission($this->getRecipient(), Permission::SERVER_ADMIN)) {
                $button = new \ManiaLive\Gui\Elements\Xml();
                $button->setContent('<frame posn="32 -' . ($x * 6) . ' 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Remove", $this->getRecipient()), null, null, null, null, null, $this->createAction(array($this->mainInstance, "admRemoveQueue"), $player->login), null, null, null, null, null, null) . '</frame>');
                $this->frame->addComponent($button);
            }
            
            $x++;
            if ($x > 8) {
                break;
            }
        }
    }
}
