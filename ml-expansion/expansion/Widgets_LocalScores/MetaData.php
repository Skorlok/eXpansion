<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LocalScores;

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

        $this->setName("Widget: Local Scores");
        $this->setDescription("Local scores widget, can be used when local records are in points instead of time");
        $this->setGroups(array('Widgets', 'Records'));

        $config = Config::getInstance();

        $var = new TypeFloat("localScoresPanel_PosX", "Position of LocalScores Panel X", $config, false, false);
        $var->setDefaultValue(114);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("localScoresPanel_PosY", "Position of LocalScores Panel Y", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
