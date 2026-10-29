<?php

namespace ManiaLivePlugins\eXpansion\Widgets_PlainLocalRecords;

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

        $this->setName("Widget: Plain Local Records");
        $this->setDescription("LocalRecords without maniascript");
        $this->setGroups(array('Widgets', 'Records'));

        $config = Config::getInstance();

        $var = new TypeFloat("localRecordsPanel_PosX", "Position of LocalRecords Panel X", $config, false, false);
        $var->setDefaultValue(114);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeFloat("localRecordsPanel_PosY", "Position of LocalRecords Panel Y", $config, false, false);
        $var->setDefaultValue(64);
        $var->setGroup("Widgets");
        $this->registerVariable($var);

        $var = new TypeInt("localRecordsPanel_nbFields", "Number of fields in LocalRecords Panel", $config, false, false);
        $var->setDefaultValue(23);
        $var->setGroup("Widgets");
        $this->registerVariable($var);
    }
}
