<?php

namespace ManiaLivePlugins\eXpansion\Widgets_DedimaniaRecords;

use ManiaLive\PluginHandler\PluginHandler;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
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
        $this->setName("Widget: Dedimania Records");
        $this->setDescription("Provides dedimania records widget");
        $this->setGroups(array('Widgets', 'Records'));

        $this->addGameModeCompability(GameInfos::GAMEMODE_TIMEATTACK);
        $this->addGameModeCompability(GameInfos::GAMEMODE_ROUNDS);
        $this->addGameModeCompability(GameInfos::GAMEMODE_TEAM);
        $this->addGameModeCompability(GameInfos::GAMEMODE_LAPS);
        $this->addGameModeCompability(GameInfos::GAMEMODE_CUP);
        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $config = Config::getInstance();

        $var = new TypeFloat("dedimaniaRecordsPanel_PosX_Default", "Position of DediMania Records Panel X (Default)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("dedimaniaRecordsPanel_PosY_Default", "Position of DediMania Records Panel Y (Default)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFields_Default", "Number of fields in DediMania Records Panel (Default)", $config, false, false);
        $var->setDefaultValue(20);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFirstFields_Default", "Number of first fields in DediMania Records Panel (Default)", $config, false, false);
        $var->setDefaultValue(5);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("dedimaniaRecordsPanel_PosX_Rounds", "Position of DediMania Records Panel X (Rounds)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("dedimaniaRecordsPanel_PosY_Rounds", "Position of DediMania Records Panel Y (Rounds)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFields_Rounds", "Number of fields in DediMania Records Panel (Rounds)", $config, false, false);
        $var->setDefaultValue(12);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFirstFields_Rounds", "Number of first fields in DediMania Records Panel (Rounds)", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("dedimaniaRecordsPanel_PosX_Cup", "Position of DediMania Records Panel X (Cup)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("dedimaniaRecordsPanel_PosY_Cup", "Position of DediMania Records Panel Y (Cup)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFields_Cup", "Number of fields in DediMania Records Panel (Cup)", $config, false, false);
        $var->setDefaultValue(12);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFirstFields_Cup", "Number of first fields in DediMania Records Panel (Cup)", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("dedimaniaRecordsPanel_PosX_Laps", "Position of DediMania Records Panel X (Laps)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("dedimaniaRecordsPanel_PosY_Laps", "Position of DediMania Records Panel Y (Laps)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFields_Laps", "Number of fields in DediMania Records Panel (Laps)", $config, false, false);
        $var->setDefaultValue(12);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFirstFields_Laps", "Number of first fields in DediMania Records Panel (Laps)", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);



        $var = new TypeFloat("dedimaniaRecordsPanel_PosX_Team", "Position of DediMania Records Panel X (Team)", $config, false, false);
        $var->setDefaultValue(-160);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("dedimaniaRecordsPanel_PosY_Team", "Position of DediMania Records Panel Y (Team)", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFields_Team", "Number of fields in DediMania Records Panel (Team)", $config, false, false);
        $var->setDefaultValue(12);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("dedimaniaRecordsPanel_nbFirstFields_Team", "Number of first fields in DediMania Records Panel (Team)", $config, false, false);
        $var->setDefaultValue(3);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }


    public function checkOtherCompatibility()
    {
        $errors = parent::checkOtherCompatibility();

        /** @var PluginHandler $phandler */
        $phandler = PluginHandler::getInstance();

        if ($phandler->isLoaded('\ManiaLivePlugins\\eXpansion\\Dedimania\\Dedimania')) {
            return $errors;
        }

        $errors[] = 'Dedimania Records Panel needs a running Dedimania plugin!!';

        return $errors;
    }
}
