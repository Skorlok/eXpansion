<?php

namespace ManiaLivePlugins\eXpansion\AutoQueue;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;

/**
 * Description of MetaData
 *
 * @author Petri
 *
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Tools: AutoQueue");
        $this->setDescription('AutoQueue for servers which has lot of players');
        $this->setGroups(array('Tools'));

        $config = Config::getInstance();

        $var = new TypeInt("rotateCount", "How many times to rotate the queue (-1 to disable)", $config, false, false);
        $var->setDefaultValue(2);
        $this->registerVariable($var);

        $var = new TypeFloat("autoQueue_PosX", "Position of AutoQueue Widget X", $config, false, false);
        $var->setDefaultValue(84);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("autoQueue_PosY", "Position of AutoQueue Widget Y", $config, false, false);
        $var->setDefaultValue(-18);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
