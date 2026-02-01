<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ManiaLivePlugins\eXpansion\Database;

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
        $this->setName("Core: Database");
        $this->setDescription("Handles eXpansion database tables versions, backups, restores and repairs etc");
        $this->setGroups(array('Core'));

        $config = Config::getInstance();

        $var = new \ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean("showWins", "Show player win statistics at podium ?", $config);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue(true)->setCanBeNull(false);
        $this->registerVariable($var);

        $var = new \ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean("enableBackup", "Enable the automatic database backup ?", $config);
        $var->setGroup("Database");
        $var->setDefaultValue(true)->setCanBeNull(false);
        $this->registerVariable($var);

        $var = new \ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt("backupInterval", "Interval of time between backups (in hours)", $config);
        $var->setGroup("Database");
        $var->setDefaultValue(24);
        $this->registerVariable($var);

        $var = new \ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt("backupRetention", "Number of days to keep backups", $config);
        $var->setGroup("Database");
        $var->setDefaultValue(15);
        $this->registerVariable($var);

        $var = new \ManiaLivePlugins\eXpansion\Core\types\config\types\HashList("autoBackupFiles", "List of files to automatically backup (do not edit)", $config);
        $var->setGroup("Database");
        $var->setDefaultValue(array());
        $var->setVisible(true);
        $this->registerVariable($var);
    }
}
