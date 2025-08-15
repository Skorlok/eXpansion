<?php

namespace ManiaLivePlugins\eXpansion\Chat\Gui\Widgets;

use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\Chat\Chat;
use ManiaLivePlugins\eXpansion\Gui\Elements\Dropdown;
use ManiaLivePlugins\eXpansion\Gui\Widgets\Widget;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;
use Maniaplanet\DedicatedServer\Connection;

class ChatSelect extends Widget
{
    /** @var  Dropdown */
    protected $channelSelect;
    protected $button;
    protected $frame;
    protected $edgeWidget;

    public function eXpOnBeginConstruct()
    {
        parent::eXpOnBeginConstruct();
        $this->setName("chat channel select");
        $this->frame = new Frame(0, 0, new Line());
        $this->channelSelect = new Dropdown("channel");
        $this->channelSelect->addItems(Chat::$channels);
        $this->frame->addComponent($this->channelSelect);
        $this->button = new \ManiaLive\Gui\Elements\Xml();
        $this->button->setContent('<frame posn="35 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, "Change", null, null, null, null, null, $this->createAction(array($this, "ok")), null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->button);
        $this->addComponent($this->frame);
    }

    public function eXpOnEndConstruct()
    {
        parent::eXpOnEndConstruct();
        $this->setSize(30, 6);
        $this->setScale(0.9);

        if (\ManiaLivePlugins\eXpansion\Helpers\Storage::getInstance()->simpleEnviTitle == "TM") {
            $this->edgeWidget = new \ManiaLivePlugins\eXpansion\Gui\Structures\Script("Gui/Scripts/EdgeWidget");
            $this->registerScript($this->edgeWidget);
        }
    }

    public function ok($login, $entries)
    {
        $channel = $this->channelSelect->getValueByIndex($entries['channel']);
        if (Chat::$playerChannels[$login] == $channel) return;

        Chat::$playerChannels[$login] = $channel;
        /** @var Connection $connection */
        $connection = Singletons::getInstance()->getDediConnection();
        $connection->chatSendServerMessage('Your chat channel is set to: $0d0' . $channel, $login);
    }

    public function sync($login = null)
    {
        if ($login == null) {
            $login = $this->getRecipient();
        }

        $items = $this->channelSelect->getDropdownItems();
        $this->channelSelect->setSelected(0);
        foreach ($items as $key => $value) {
            if (Chat::$playerChannels[$login] == $value) {
                $this->channelSelect->setSelected($key);
            }
        }
    }
}