<?php

namespace ManiaLivePlugins\eXpansion\Widgets_ResSkip;

class Config extends \ManiaLib\Utils\Singleton
{

    //To deactivate put empty array or -1 in array
    public $publicResAmount = array(500);
    public $publicSkipAmount = array(750);

    public $resSkipButtons_PosX = 106.5;
    public $resSkipButtons_PosY = 75;
    public $resSkipButtons_PosX_Shootmania = -70;
    public $resSkipButtons_PosY_Shootmania = 90;
}
