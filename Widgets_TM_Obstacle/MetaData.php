<?php

namespace ManiaLivePlugins\eXpansion\Widgets_TM_Obstacle;

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
        $this->setName("Widget: Obstacle Progress");
        $this->setDescription("Shows Checkpoint progress for 10 players in a widget");
        $this->setGroups(array('Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $config = Config::getInstance();

        $var = new TypeFloat("obstaclePanel_PosX", "Position of Obstacle Panel X", $config, false, false);
        $var->setDefaultValue(55);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("obstaclePanel_PosY", "Position of Obstacle Panel Y", $config, false, false);
        $var->setDefaultValue(0);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
