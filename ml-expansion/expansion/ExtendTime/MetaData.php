<?php

namespace ManiaLivePlugins\eXpansion\ExtendTime;

use ManiaLivePlugins\eXpansion\Core\types\config\types\BoundedTypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

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
        $this->setName("Tools: Extend Time");

        $this->addGameModeCompability(GameInfos::GAMEMODE_TIMEATTACK);
        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $this->setDescription("Provides Votes to Extend timelimit on  a map");
        $this->setGroups(array('Tools'));

        $config = Config::getInstance();

        $var = new BoundedTypeFloat("ratio", "voteRatio", $config, false, false);
        $var->setMax(1.0);
        $var->setMax(0.0);
        $var->setDefaultValue(0.51);
        $this->registerVariable($var);

        $var = new TypeInt("limit_votes", "Limit voting for a player on map", $config, false, false);
        $var->setDescription("-1 to disable, othervice number of vote start");
        $var->setDefaultValue(1);
        $this->registerVariable($var);

        $var = new TypeFloat("extendWidget_PosX", "Position of Extend Widget X", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("extendWidget_PosY", "Position of Extend Widget Y", $config, false, false);
        $var->setDefaultValue(88);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
