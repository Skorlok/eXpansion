<?php

namespace ManiaLivePlugins\eXpansion\ScoreDisplay;

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
        $this->setName("Games: ScoreDisplay");
        $this->setDescription("Show scores for a match");
        $this->setGroups(array('Games'));

        $config = Config::getInstance();

        $var = new TypeFloat("scoreWidget_PosX", "Position of ScoreDisplay Widget X", $config, false, false);
        $var->setDefaultValue(-56);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("scoreWidget_PosY", "Position of ScoreDisplay Widget Y", $config, false, false);
        $var->setDefaultValue(80);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
