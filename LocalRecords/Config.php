<?php

namespace ManiaLivePlugins\eXpansion\LocalRecords;

class Config extends \ManiaLib\Utils\Singleton
{

    public $sendBeginMapNotices = true;  // show messages on beginmap
    public $sendRankingNotices = true; // show personal rank messages on beginmap
    public $recordsCount = 1000; // number of records to save
    public $recordPublicMsgTreshold = 100; // records rank number to show public message
    public $lapsModeCount1lap = true;
    public $nbMap_rankProcess = 500;
    public $ranking = true;
    public $resetRanks = false;
    public $saveRecFrequency = 0;
    public $noRedirectTreshold = 30;
}
