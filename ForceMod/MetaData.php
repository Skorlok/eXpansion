<?php

namespace ManiaLivePlugins\eXpansion\ForceMod;

use ManiaLivePlugins\eXpansion\Core\types\config\types\HashList;
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
        $this->setName("Tools: Force Mod");
        $this->setDescription("Forces a Mod for a server");
        $this->setGroups(array('Tools'));
        $config = Config::getInstance();

        $var = new HashList("mods", "Mods to be loaded", $config, false, false);
        $var->setDescription("Key = Mod url ending with .zip, Value = Stadium, Canyon or Valley");
        $var->setKeyType(new TypeString(""));
        $var->setType(new TypeString(""));
        $var->setDefaultValue(array());
        $this->registerVariable($var);

        $this->setRelaySupport(false);
    }
}
