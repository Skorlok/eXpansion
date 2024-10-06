<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Netlost;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
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
        $this->setName("Widget: Netlost status");
        $this->setDescription("Provides netlost infos");
        $this->setGroups(array('Widgets', 'Tools'));

        $configInstance = Config::getInstance();

        $var = new Boolean("showOnlyAdmins", "show widget only to admins", $configInstance, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new TypeFloat("netlostWidget_PosX", "Position of NetLost Widget X", $configInstance, false, false);
        $var->setDefaultValue(-115);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("netlostWidget_PosY", "Position of NetLost Widget Y", $configInstance, false, false);
        $var->setDefaultValue(-50);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
