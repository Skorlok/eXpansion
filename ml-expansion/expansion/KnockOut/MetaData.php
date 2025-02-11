<?php

namespace ManiaLivePlugins\eXpansion\KnockOut;

use ManiaLivePlugins\eXpansion\Core\types\config\types\ColorCode;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
use ManiaLivePlugins\eXpansion\Core\types\config\types\HashList;

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
        $this->setName("GameMode: KnockOut!");
        $this->setDescription("Provides Knockout Virtual Game mode");
        $this->setGroups(array('Games', 'Tools'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");
        /*$this->setScriptCompatibilityMode(false);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TIMEATTACK);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_ROUNDS);
        $this->addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);
        $this->addGameModeCompability("Pursuit.Script.txt");*/

        $configInstance = Config::getInstance();

        $var = new HashList("nbKicks", "Number of knockouted players", $configInstance, false, false);
        $var->setDescription("If the number of players is lower than the key, the value is used as the number of players to knock out.");
        $var->setKeyType(new TypeInt(""));
        $var->setType(new TypeString(""));
        $var->setDefaultValue(array(8 => 1, 16 => 2, 32 => 4, 64 => 8));
        $this->registerVariable($var);

        $var = new ColorCode("koColor", "Color for knockout", $configInstance, false, false);
        $var->setDefaultValue('$0d0');
        $this->registerVariable($var);

        $this->setRelaySupport(false);
    }
}
