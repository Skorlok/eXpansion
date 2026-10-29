<?php

namespace ManiaLivePlugins\eXpansion\Bets;

use ManiaLivePlugins\eXpansion\Core\types\config\types\BoundedTypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;

/**
 * Description of MetaData
 *
 * @author Petri
 *
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{
    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Games: Bet Planets for winner");
        $this->setDescription('Enables the famous bets for playres to compete for planets');
        $this->setGroups(array("Games"));
        $configInstance = Config::getInstance();

        $var = new BoundedTypeInt("timeoutSetBet", "Bet Accept timeout (in seconds)", $configInstance, false, false);
        $var->setMin(20);
        $var->setDefaultValue(45);
        $this->registerVariable($var);

        $var = new TypeFloat("betWidget_PosX", "Position of Betting Widget X", $configInstance, false, false);
        $var->setDefaultValue(20);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("betWidget_PosY", "Position of Betting Widget Y", $configInstance, false, false);
        $var->setDefaultValue(-65);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
