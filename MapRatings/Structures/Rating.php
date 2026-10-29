<?php
namespace ManiaLivePlugins\eXpansion\MapRatings\Structures;

class Rating extends \Maniaplanet\DedicatedServer\Structures\AbstractStructure
{

    public $rating;
    public $totalvotes;
    public $uid;
    public $playerVotes;

    public function __construct($rating, $total, $uid, $playerVotes = array())
    {
        $this->rating = $rating;
        $this->totalvotes = $total;
        $this->uid = $uid;
        $this->playerVotes = $playerVotes;
    }
}
