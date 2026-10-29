<?php

namespace ManiaLivePlugins\eXpansion\SM_CheckpointCount;

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

        $this->setName("Widget: Checkpoint Counter");
        $this->setDescription("Checkpoint counter");
        $this->setGroups(array('Widgets', 'Records'));

        $config = Config::getInstance();

        $var = new TypeFloat("checkpointCounter_PosX", "Position of CheckpointCounter Widget X", $config, false, false);
        $var->setDefaultValue(-17.5);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("checkpointCounter_PosY", "Position of CheckpointCounter Widget Y", $config, false, false);
        $var->setDefaultValue(-63);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
