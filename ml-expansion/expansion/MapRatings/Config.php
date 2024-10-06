<?php

namespace ManiaLivePlugins\eXpansion\MapRatings;

class Config extends \ManiaLib\Utils\Singleton
{

    public $sendBeginMapNotices = true;    // Sends chat message of  current rating at map start
    public $showPodiumWindow = true;        // enable showing maprating window at podium
    public $minVotes = 10;                // minimum votes for auto removal
    public $removeTresholdPercentage = 30;    // map rating value for removal
    public $karmaRequireFinishes = 0;

    public $mxKarmaEnabled = false;
    public $mxKarmaApiKey = "";
    public $mxKarmaServerLogin = "";

    public $mapRating_PosX = 128;
    public $mapRating_PosY = 75;
    public $mapRating_PosX_Shootmania = 38;
    public $mapRating_PosY_Shootmania = 90;

    public $endMapRating_PosX = -45;
    public $endMapRating_PosY = -42;
}
