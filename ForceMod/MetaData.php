<?php

namespace ManiaLivePlugins\eXpansion\ForceMod;

use ManiaLivePlugins\eXpansion\Core\types\config\types\HashList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;

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

        $this->setRelaySupport(false);

        $config = Config::getInstance();

        $var = new HashList("mods", "Mods to be loaded", $config, false, false);
        $var->setDescription("Key = Mod url ending with .zip, Value = Stadium, Canyon or Valley");
        $var->setKeyType(new TypeString(""));
        $var->setType(new TypeString(""));
        $var->setDefaultValue(array());
        $this->registerVariable($var);

        $var = new Boolean("override", "Override all mods on server, even if map has defined custom one ?", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);
    }
}
