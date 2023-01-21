<?php

namespace ManiaLivePlugins\eXpansion\Autotime;

use ManiaLivePlugins\eXpansion\Core\types\config\types\BoundedTypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
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
        $this->setName("Tools: Auto TimeLimit");

        $this->addGameModeCompability(GameInfos::GAMEMODE_TIMEATTACK);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_SCRIPT,"Doppler.Script.txt");
        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $this->setDescription("Provides auto timelimit on a map");
        $this->setGroups(array('Tools'));

        $config = Config::getInstance();

        $var = new TypeInt("timelimit_multiplier", "Timelimit multiplier", $config, false, false);
        $var->setDefaultValue(8);
        $this->registerVariable($var);

        $var = new TypeString("min_timelimit", "Minimum timelimit to set", $config, false, false);
        $var->setDefaultValue('2:00');
        $this->registerVariable($var);

        $var = new TypeString("max_timelimit", "Maximum timelimit to set", $config, false, false);
        $var->setDefaultValue('15:00');
        $this->registerVariable($var);

        $var = new TypeString("timelimit", "Default timelimit to set", $config, false, false);
        $var->setDefaultValue('5:00');
        $this->registerVariable($var);

        $var = new TypeString("medal", "Medal multiplicator", $config, false, false);
        $var->setDefaultValue('silver');
        $this->registerVariable($var);

        $var = new Boolean("message", "display message at mapstart ?", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);
    }
}
