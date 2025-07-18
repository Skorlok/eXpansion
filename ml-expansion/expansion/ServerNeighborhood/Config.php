<?php

namespace ManiaLivePlugins\eXpansion\ServerNeighborhood;

class Config extends \ManiaLib\Utils\Singleton
{

    public $refresh_interval = 20;

    public $nbElement = 5;

    public $style = 'Small';

    public $storing_path = "../";

    public $servers = array();

    public $snwidget_isDockable = true;

    public $serverPanel_PosX = -160;
    public $serverPanel_PosY = 80;
}
