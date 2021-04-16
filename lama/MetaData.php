<?php

namespace ManiaLivePlugins\eXpansion\lama;

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
        $this->setName("a_lama");
        $this->setDescription("affiche des lamas");
        $this->setGroups(array('Widgets'));

        $config = Config::getInstance();
        $var = new \ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString(
            "texture",
            "texture url",
            $config,
            false,
            false
        );
        $var->setDefaultValue("https://cdn.skorlok.com/file/pngegg.png");
        $this->registerVariable($var);

        $var = new \ManiaLivePlugins\eXpansion\Core\types\config\types\BoundedTypeInt(
            "particleCount",
            "Particles count",
            $config,
            false,
            false
        );
        $var->setDefaultValue(25);
        $var->setMin(1);
        $var->setMax(200);
        $this->registerVariable($var);
    }
}
