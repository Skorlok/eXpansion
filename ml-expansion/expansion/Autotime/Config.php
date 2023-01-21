<?php

namespace ManiaLivePlugins\eXpansion\Autotime;

class Config extends \ManiaLib\Utils\Singleton
{
    public $timelimit_multiplier = 8;
    public $min_timelimit = '2:00';
    public $max_timelimit = '15:00';
    public $timelimit = '5:00';
    public $medal = 'silver';
    public $message = true;
}
