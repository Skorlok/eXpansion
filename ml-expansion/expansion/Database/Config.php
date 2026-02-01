<?php

namespace ManiaLivePlugins\eXpansion\Database;

class Config extends \ManiaLib\Utils\Singleton
{
    public $showWins = true;
    public $enableBackup = true;
    public $backupInterval = 24; //hours
    public $backupRetention = 15; //days
    public $autoBackupFiles = array();
}
