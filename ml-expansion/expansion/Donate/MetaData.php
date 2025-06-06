<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ManiaLivePlugins\eXpansion\Donate;

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
        $this->setName("Tools: Donates plugin");
        $this->setDescription("Donates for players to send for server or eachother");
        $this->setGroups(array('Tools'));
        $config = Config::getInstance();
        $var = new \ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt("donateAmountForGlobalMsg", "Treshold to show public message on donation", $config, false, true);
        $var->setGroup("Planets");
        $var->setDefaultValue(20);
        $this->registerVariable($var);
    }
}
