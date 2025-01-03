<?php

namespace ManiaLivePlugins\eXpansion\LocalRecords\Structures;

class Record
{
    public $place;
    public $login;
    public $nickName;
    public $time;
    public $nbFinish;
    public $nbWins;
    public $avgScore;

    /** @var int[] */
    public $ScoreCheckpoints = array();
    public $date;
    public $nation;
    public $uId;
}
