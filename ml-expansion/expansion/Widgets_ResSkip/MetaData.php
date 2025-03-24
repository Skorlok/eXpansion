<?php

namespace ManiaLivePlugins\eXpansion\Widgets_ResSkip;

use ManiaLivePlugins\eXpansion\Core\types\config\types\SortedList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;

/**
 * Description of MetaData
 *
 * @author De Cramer Oliver
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();

        $this->setName("Widget: Pay for Res/Skip buttons");
        $this->setDescription("Widget buttons");
        $this->setGroups(array('Widgets'));

        $config = Config::getInstance();

        $var = new SortedList('publicResAmount', 'Amount needed to restart a map', $config, false, false);
        $var->setDescription("If you use a negative value it will disable this feature.");
        $var->setType(new TypeInt("", "", null));
        $var->setDefaultValue(array(0 => 500));
        $this->registerVariable($var);

        $var = new SortedList('publicSkipAmount', 'Amount needed to skip a map', $config, false, false);
        $var->setDescription("If you use a negative value it will disable this feature.");
        $var->setType(new TypeInt("", "", null));
        $var->setDefaultValue(array(0 => 750));
        $this->registerVariable($var);

        $var = new TypeFloat("resSkipButtons_PosX", "Position of Skip and Res Buttons X", $config, false, false);
        $var->setDefaultValue(106.5);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("resSkipButtons_PosY", "Position of Skip and Res Buttons Y", $config, false, false);
        $var->setDefaultValue(75);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("resSkipButtons_PosX_Shootmania", "Position of Skip and Res Buttons X (Shootmania)", $config, false, false);
        $var->setDefaultValue(-70);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("resSkipButtons_PosY_Shootmania", "Position of Skip and Res Buttons Y (Shootmania)", $config, false, false);
        $var->setDefaultValue(90);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
