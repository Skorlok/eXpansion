<?php

namespace ManiaLivePlugins\eXpansion\Gears;

class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Widget: Gears Indicator");
        $this->setDescription("Displays message on gears");
        $this->setGroups(array('Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $this->setRelaySupport(false);
    }
}
