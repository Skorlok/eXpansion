<?php

namespace ManiaLivePlugins\eXpansion\Dedimania_Script;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
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
        $this->setName("Dedimania (Deprecated !!!)");
        $this->setDescription("Dedimania, Global world records system integration");
        $this->setGroups(array('Records'));

        $this->addGameModeCompability(GameInfos::GAMEMODE_ROUNDS);
        $this->addGameModeCompability(GameInfos::GAMEMODE_TEAM);
        $this->addGameModeCompability(GameInfos::GAMEMODE_CUP);
        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
        $this->setEnviAsTitle(true);

        $config = \ManiaLivePlugins\eXpansion\Dedimania\Config::getInstance();

        $var = new TypeString("login", "Dedimania server login (use this server login)", $config, false, false);
        $var->setDefaultValue("");
        $this->registerVariable($var);

        $var = new TypeString(
            "code",
            'Dedimania $l[http://dedimania.net/tm2stats/?do=register]server code$l',
            $config,
            false,
            false
        );
        $var->setDefaultValue("");
        $this->registerVariable($var);

        $this->setRelaySupport(false);
    }
}
