<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Times;

/**
 * Description of MetaData
 *
 * @author Petri
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName('Widget: Checkpoint Times');
        $this->setDescription("Provides enhanced times tracking widget at center of screen");
        $this->setGroups(array('Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
    }
}
