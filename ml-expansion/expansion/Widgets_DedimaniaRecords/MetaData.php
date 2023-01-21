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

        $config = Config::getInstance();
        $var = new Boolean("isHorizontal", "Use horizontal (old) widget style", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);
    }


    public function checkOtherCompatibility()
    {
        $errors = parent::checkOtherCompatibility();

        $dedi1 = '\ManiaLivePlugins\\eXpansion\\Dedimania\\Dedimania';
        $dedi2 = '\ManiaLivePlugins\\eXpansion\\Dedimania_Script\\Dedimania_Script';

        /** @var PluginHandler $phandler */
        $phandler = PluginHandler::getInstance();

        if ($phandler->isLoaded($dedi1)) {
            return $errors;
        } elseif ($phandler->isLoaded($dedi2)) {
            return $errors;
        }

        $errors[] = 'Dedimania Records Panel needs a running Dedimania plugin!!';

        return $errors;
    }
}
