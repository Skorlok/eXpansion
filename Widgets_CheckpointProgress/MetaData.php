<?php

namespace ManiaLivePlugins\eXpansion\Widgets_CheckpointProgress;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;

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
        $this->setName("Widget: Checkpoints Progressbar");
        $this->setDescription("Provides Checkpoint progress widget");
        $this->setGroups(array('Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $config = Config::getInstance();

        $var = new TypeFloat("checkpointProgressWidget_PosX", "Position of CheckpointsProgress Widget X", $config, false, false);
        $var->setDefaultValue(-80);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("checkpointProgressWidget_PosY", "Position of CheckpointsProgress Widget Y", $config, false, false);
        $var->setDefaultValue(-56);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
