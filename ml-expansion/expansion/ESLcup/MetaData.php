<?php

namespace ManiaLivePlugins\eXpansion\ESLcup;

class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Deprecated: ESLcup");
        $this->setDescription("This plugin is deprecated and will not be updated anymore.");
        $this->setGroups(array('Deprecated'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);
    }
}
