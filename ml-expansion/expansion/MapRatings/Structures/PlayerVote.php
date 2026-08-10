<?php

namespace ManiaLivePlugins\eXpansion\MapRatings\Structures;

class PlayerVote extends \Maniaplanet\DedicatedServer\Structures\AbstractStructure
{

    public $login;
    public $rating;

    public function __construct($login = null, $rating = null)
    {
        $this->login = $login;
        $this->rating = $rating;
    }
}
