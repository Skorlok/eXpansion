<?php

namespace ManiaLivePlugins\eXpansion\Core\Structures\JsonCallbacks;

class Pole extends \Maniaplanet\DedicatedServer\Structures\AbstractStructure
{

    /** @var string */
    public $tag = "";

    /** @var integer */
    public $order;

    /** @var boolean */
    public $captured = false;
}
