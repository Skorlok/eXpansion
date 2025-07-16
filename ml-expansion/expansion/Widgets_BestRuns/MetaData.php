<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestRuns;

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
        $this->setName("Widget: Best runs");
        $this->setDescription("Provides Best runs widget");
        $this->setGroups(array('Records', 'Widgets'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $config = Config::getInstance();

        $var = new TypeFloat("bestRunsWidget_PosX", "Position of BestRuns Widget X", $config, false, false);
        $var->setDefaultValue(-116);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("bestRunsWidget_PosY", "Position of BestRuns Widget Y", $config, false, false);
        $var->setDefaultValue(87.5);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("bestRunsWidget_nbDisplay", "Number of player to show in BestRuns Widget", $config, false, false);
        $var->setDefaultValue(2);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
