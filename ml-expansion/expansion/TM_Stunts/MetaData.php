<?php

namespace ManiaLivePlugins\eXpansion\TM_Stunts;

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
        $this->setName("Widget: Stunts Figures");
        $this->setDescription("Displays the stunts you made for TM");
        $this->setGroups(array('Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $this->setRelaySupport(false);

        $config = Config::getInstance();

        $var = new TypeFloat("stuntWidget_PosX", "Position of Stunts Widget X", $config, false, false);
        $var->setDefaultValue(-30);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("stuntWidget_PosY", "Position of Stunts Widget Y", $config, false, false);
        $var->setDefaultValue(58);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
