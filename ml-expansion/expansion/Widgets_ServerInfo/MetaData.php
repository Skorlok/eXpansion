<?php

namespace ManiaLivePlugins\eXpansion\Widgets_ServerInfo;

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
        $this->setName("Widget: Server infos");
        $this->setDescription("Provides server infos widget");
        $this->setGroups(array('Widgets'));

        $config = Config::getInstance();

        $var = new TypeFloat("serverInfosWidget_PosX", "Position of ServerInfo Widget X", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("serverInfosWidget_PosY", "Position of ServerInfo Widget Y", $config, false, false);
        $var->setDefaultValue(88);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
