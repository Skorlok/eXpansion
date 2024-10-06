<?php

namespace ManiaLivePlugins\eXpansion\Communication;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;

/**
 * Description of MetaData
 *
 * @author De Cramer Oliver
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName('Chat: Multi-tab personal messages');
        $this->setDescription('Provides nextgen commmunication platform for serverside personal messaging');
        $this->setGroups(array('Chat', 'Widgets'));

        $config = Config::getInstance();

        $var = new TypeFloat('messaging_PosX', 'Position of Messaging Widget X', $config, false, false);
        $var->setDefaultValue(115);
        $var->setGroup('Widgets');
        $this->registerVariable($var);

        $var = new TypeFloat('messaging_PosY', 'Position of Messaging Widget Y', $config, false, false);
        $var->setDefaultValue(-0.25);
        $var->setGroup('Widgets');
        $this->registerVariable($var);
    }
}
