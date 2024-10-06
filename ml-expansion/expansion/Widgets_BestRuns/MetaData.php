<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestRuns;

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
        $this->setName("Widget: Best runs");
        $this->setDescription("Provides Best runs widget");
        $this->setGroups(array('Records', 'Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_ROUNDS);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TIMEATTACK);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TEAM);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_LAPS);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);

        $config = Config::getInstance();

        $var = new TypeFloat("bestRunsWidget_PosX", "Position of BestRuns Widget X", $config, false, false);
        $var->setDefaultValue(0);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("bestRunsWidget_PosY", "Position of BestRuns Widget Y", $config, false, false);
        $var->setDefaultValue(86);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
