<?php

namespace ManiaLivePlugins\eXpansion\Widgets_PersonalBest;

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
        $this->setName("Widget: Personal best");
        $this->setDescription("Provides personal best widget");
        $this->setGroups(array('Widgets', 'Records'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_ROUNDS);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TIMEATTACK);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TEAM);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_LAPS);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);
        $this->addGameModeCompability(
            \Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_SCRIPT,
            'TeamAttack.Script.txt'
        );

        $config = Config::getInstance();

        $var = new TypeFloat("personalBestWidget_PosX", "Position of PersonalBest Widget X", $config, false, false);
        $var->setDefaultValue(112);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("personalBestWidget_PosY", "Position of PersonalBest Widget Y", $config, false, false);
        $var->setDefaultValue(-76);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
