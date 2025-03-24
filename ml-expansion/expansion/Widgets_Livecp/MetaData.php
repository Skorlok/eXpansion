<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Livecp;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;

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
        $this->setName("Widget: Live CP Progress");
        $this->setDescription("Shows Checkpoint progress for players");
        $this->setGroups(array('Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $config = Config::getInstance();

        $var = new TypeFloat("livecpPanel_PosX", "Position of LiveCP Panel X", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("livecpPanel_PosY", "Position of LiveCP Panel Y", $config, false, false);
        $var->setDefaultValue(-1);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("livecpPanel_nbFields", "Number of fields in LiveCP Panel", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("livecpPanel_nbFirstFields", "Number of first fields in LiveCP Panel", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
