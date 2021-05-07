<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ManiaLivePlugins\eXpansion\Dedimania;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
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
        $this->setName("Records: Dedimania");
        $this->setDescription("Dedimania, Global world records system integration");
        $this->setGroups(array('Records'));

        $this->addGameModeCompability(GameInfos::GAMEMODE_TIMEATTACK);
        $this->addGameModeCompability(GameInfos::GAMEMODE_ROUNDS);
        $this->addGameModeCompability(GameInfos::GAMEMODE_TEAM);
        $this->addGameModeCompability(GameInfos::GAMEMODE_LAPS);
        $this->addGameModeCompability(GameInfos::GAMEMODE_CUP);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_SCRIPT,"Doppler.Script.txt");
        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
        $this->setEnviAsTitle(true);

        $config = Config::getInstance();

        $var = new TypeString("login", "Dedimania server login (use this server current login)", $config, false, false);
        $var->setDefaultValue("");
        $this->registerVariable($var);

        $var = new TypeString(
            "code",
            'Dedimania server code, $l[http://dedimania.net/tm2stats/?do=register]click this text to register$l',
            $config,
            false,
            false
        );
        $var->setDescription('For server code: click the header or visit http://dedimania.net');
        $var->setDefaultValue("");
        $this->registerVariable($var);

        $var = new Boolean("allowBannedPlayersToJoin", "Allow dedimania gloabal banned player to join server ?", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new TypeInt("noRedirectTreshold", "If you use notifications plugin, show normal chat message for top records", $config, false, false);
        $var->setDefaultValue(30);
        $this->registerVariable($var);

        $this->setRelaySupport(false);
    }
}
