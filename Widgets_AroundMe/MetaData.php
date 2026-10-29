<?php

namespace ManiaLivePlugins\eXpansion\Widgets_AroundMe;

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
        $this->setName("Widget: Around Me");
        $this->setDescription("Provides Around Me time display widget");
        $this->setGroups(array('Records', 'Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_ROUNDS);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_LAPS);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);

        $config = Config::getInstance();

        $var = new TypeFloat("aroundmeWidget_PosX", "Position of AroundMe Widget X", $config, false, false);
        $var->setDefaultValue(-15);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("aroundmeWidget_PosY", "Position of AroundMe Widget Y", $config, false, false);
        $var->setDefaultValue(-70);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
