<?php

namespace ManiaLivePlugins\eXpansion\PersonalMessages;

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
        $this->setName("Chat: Personal messages");
        $this->setDescription("Provides personal messaging");
        $this->setGroups(array('Chat', 'Widgets'));

        $config = Config::getInstance();

        $var = new TypeFloat("messagingWidget_PosX", "Position of Messaging Widget X", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("messagingWidget_PosY", "Position of Messaging Widget Y", $config, false, false);
        $var->setDefaultValue(-57);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
