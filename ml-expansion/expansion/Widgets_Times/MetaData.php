<?php

namespace ManiaLivePlugins\eXpansion\Widgets_Times;

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
        $this->setName('Widget: Checkpoint Times');
        $this->setDescription("Provides enhanced times tracking widget at center of screen");
        $this->setGroups(array('Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $config = Config::getInstance();

        $var = new TypeFloat('timePanel_PosX', 'Position of Time Panel X', $config, false, false);
        $var->setDefaultValue(-16);
        $var->setGroup('Widgets');
        $this->registerVariable($var);

        $var = new TypeFloat('timePanel_PosY', 'Position of Time Panel Y', $config, false, false);
        $var->setDefaultValue(46);
        $var->setGroup('Widgets');
        $this->registerVariable($var);
    }
}
