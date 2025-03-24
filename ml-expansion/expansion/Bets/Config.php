<?php

namespace ManiaLivePlugins\eXpansion\Bets;

class Config extends \ManiaLib\Utils\Singleton
{
    public $timeoutSetBet = 45;
    public $betAmounts = array(25, 50, 100, 250, 500, 1000);

    public $betWidget_PosX = 20;
    public $betWidget_PosY = -65;
}
