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
}
