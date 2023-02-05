<?php

namespace ManiaLivePlugins\eXpansion\AutoUpdate;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;

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
        $this->setName("Core: Auto Update");
        $this->setDescription("Provides auto update service requests and ingame updates");
        $this->setGroups(array('Core'));

        $config = Config::getInstance();
        $var = new Boolean("useGit", "Use git to update server", $config);
        $var->setDescription("!! You need to have git installed for this to work !!");
        $var->setDefaultValue(true);
        $var->setGroup("Auto Update");
        $this->registerVariable($var);
    }
}
