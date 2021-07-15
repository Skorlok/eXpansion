<?php

namespace ManiaLivePlugins\eXpansion\ResetTime;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

/**
 * Description of MetaData
 *
 * @author Skorlok
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("ResetTime");

        $this->addGameModeCompability(GameInfos::GAMEMODE_TIMEATTACK);
        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $this->setDescription("Reset the timelimit after each map");
        $this->setGroups(array('Tools'));

        $config = Config::getInstance();
        $var = new TypeInt("timelimit", "Default timelimit to set", $config, false, false);
        $var->setDefaultValue(300);
        $this->registerVariable($var);
    }
}
