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

        $var = new TypeFloat("enterQueueList_PosX", "Position of EnterQueueList Widget X", $config, false, false);
        $var->setDefaultValue(-30);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("enterQueueList_PosY", "Position of EnterQueueList Widget Y", $config, false, false);
        $var->setDefaultValue(60);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("queueList_PosX", "Position of QueueList Widget X", $config, false, false);
        $var->setDefaultValue(80);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("queueList_PosY", "Position of QueueList Widget Y", $config, false, false);
        $var->setDefaultValue(-30);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("rotateCount", "How many times to rotate the queue (-1 to disable)", $config, false, false);
        $var->setDefaultValue(2);
        $this->registerVariable($var);
    }
}
