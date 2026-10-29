<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Speedometer;

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
        $this->setName("Widget: Speedometer");
        $this->setDescription("Provides speedometer");
        $this->setGroups(array('Widgets'));

        $config = Config::getInstance();

        $var = new TypeFloat("speedometerWidget_PosX", "Position of Speedometer Widget X", $config, false, false);
        $var->setDefaultValue(-14);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("speedometerWidget_PosY", "Position of Speedometer Widget Y", $config, false, false);
        $var->setDefaultValue(-74);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
