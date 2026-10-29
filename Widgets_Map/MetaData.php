<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Map;

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
        $this->setName("Widget: Current map");
        $this->setDescription("Displays simple map infos widget at top right corner");
        $this->setGroups(array('Widgets', 'Maps'));

        $config = Config::getInstance();

        $var = new TypeFloat("mapWidget_PosX", "Position of Map Widget X", $config, false, false);
        $var->setDefaultValue(115);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("mapWidget_PosY", "Position of Map Widget Y", $config, false, false);
        $var->setDefaultValue(88);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
