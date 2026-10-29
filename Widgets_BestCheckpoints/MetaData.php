<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestCheckpoints;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
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
        $this->setName("Widget: Best Checkpoints");
        $this->setDescription("Provides Best checkpoints widget at the top of the screen.");
        $this->setGroups(array('Records', 'Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $config = Config::getInstance();

        $var = new TypeInt("CPNumber", "Number of checkpoints to show", $config, false, false);
        $var->setDefaultValue(12);
        $this->registerVariable($var);

        $var = new TypeFloat("bestCpWidget_PosX", "Position of BestCp Widget X", $config, false, false);
        $var->setDefaultValue(-112);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("bestCpWidget_PosY", "Position of BestCp Widget Y", $config, false, false);
        $var->setDefaultValue(90);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
