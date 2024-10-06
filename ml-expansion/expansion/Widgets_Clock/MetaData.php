<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Clock;

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
        $this->setName("Widget: Clock");
        $this->setDescription("Provides Local Time display");
        $this->setGroups(array('Widgets'));

        $config = Config::getInstance();

        $var = new TypeFloat("clock_PosX", "Position of Clock Widget X", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("clock_PosY", "Position of Clock Widget Y", $config, false, false);
        $var->setDefaultValue(74);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("clock_PosX_Shootmania", "Position of Clock Widget X (Shootmania)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("clock_PosY_Shootmania", "Position of Clock Widget Y (Shootmania)", $config, false, false);
        $var->setDefaultValue(-13.5);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
