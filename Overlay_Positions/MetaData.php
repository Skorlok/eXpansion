<?php

namespace ManiaLivePlugins\eXpansion\Overlay_Positions;

class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Deprecated: Overlay Positions");
        $this->setDescription("This plugin is deprecated and will not be updated anymore.");
        $this->setGroups(array('Deprecated'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
    }
}
