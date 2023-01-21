<?php
namespace ManiaLivePlugins\eXpansion\AdminGroups;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;

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
        $this->setName("Admin Groups");
        $this->setDescription("Provides Admin Groups");
        $this->setGroups(array('Core'));

        $var = new TypeString('fileName', 'Admin Groups data file', Config::getInstance());
        $var->setDescription("If left empty the name will be generated using the server login.");
        $var->setGroup("Config Files");
        $var->setCanBeNull(true)
            ->setDefaultValue(null);
        $this->registerVariable($var);
    }
}
