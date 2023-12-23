<?php

namespace ManiaLivePlugins\eXpansion\Widgets_DedimaniaRecords;

use ManiaLive\PluginHandler\PluginHandler;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
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
