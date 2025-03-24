<?php

namespace ManiaLivePlugins\eXpansion\Widgets_LiveRankings;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;

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
        $this->setName("Widget: Live Rankings");
        $this->setDescription("Provides live rankings for all Trackmania game modes.");
        $this->setGroups(array('Records', 'Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_ROUNDS);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TIMEATTACK);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TEAM);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_LAPS);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_SCRIPT, 'teamattack');

        $config = Config::getInstance();

        $var = new TypeFloat("liveRankingPanel_PosX_Default", "Position of liveRanking Panel X (Default)", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("liveRankingPanel_PosY_Default", "Position of liveRanking Panel Y (Default)", $config, false, false);
        $var->setDefaultValue(-1);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFields_Default", "Number of fields in liveRanking Panel (Default)", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFirstFields_Default", "Number of first fields in liveRanking Panel (Default)", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("liveRankingPanel_PosX_Rounds", "Position of liveRanking Panel X (Rounds)", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("liveRankingPanel_PosY_Rounds", "Position of liveRanking Panel Y (Rounds)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFields_Rounds", "Number of fields in liveRanking Panel (Rounds)", $config, false, false);
        $var->setDefaultValue(22);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFirstFields_Rounds", "Number of first fields in liveRanking Panel (Rounds)", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("liveRankingPanel_PosX_Cup", "Position of liveRanking Panel X (Cup)", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("liveRankingPanel_PosY_Cup", "Position of liveRanking Panel Y (Cup)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFields_Cup", "Number of fields in liveRanking Panel (Cup)", $config, false, false);
        $var->setDefaultValue(22);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFirstFields_Cup", "Number of first fields in liveRanking Panel (Cup)", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("liveRankingPanel_PosX_Laps", "Position of liveRanking Panel X (Laps)", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("liveRankingPanel_PosY_Laps", "Position of liveRanking Panel Y (Laps)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFields_Laps", "Number of fields in liveRanking Panel (Laps)", $config, false, false);
        $var->setDefaultValue(22);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFirstFields_Laps", "Number of first fields in liveRanking Panel (Laps)", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("liveRankingPanel_PosX_Team", "Position of liveRanking Panel X (Team)", $config, false, false);
        $var->setDefaultValue(120);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("liveRankingPanel_PosY_Team", "Position of liveRanking Panel Y (Team)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFields_Team", "Number of fields in liveRanking Panel (Team)", $config, false, false);
        $var->setDefaultValue(21);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFirstFields_Team", "Number of first fields in liveRanking Panel (Team)", $config, false, false);
        $var->setDefaultValue(10);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        

        $var = new TypeFloat("liveRankingPanel_PosX_Endurance", "Position of EnduroPoints Panel X", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("liveRankingPanel_PosY_Endurance", "Position of EnduroPoints Panel Y", $config, false, false);
        $var->setDefaultValue(67);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFields_Endurance", "Number of fields in EnduroPoints Panel", $config, false, false);
        $var->setDefaultValue(13);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("liveRankingPanel_nbFirstFields_Endurance", "Number of first fields in EnduroPoints Panel", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
