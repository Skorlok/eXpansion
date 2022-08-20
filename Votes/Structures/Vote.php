<?php

namespace ManiaLivePlugins\eXpansion\Votes\Structures;

class Vote extends \Maniaplanet\DedicatedServer\Structures\AbstractStructure
{
    /** @var string voteAuthor login */
    public $voteAuthor = "";

    /** @var int votingTime */
    public $votingTime = 30;

    /** @var float voteRatio */
    public $voteRatio = 0.5;

    /** @var mixed[] */
    public $playerVotes = array();

    /** @var string voteCommand name */
    public $action = "";

    /** @var string voteParams name */
    public $actionParams = "";

    /** @var string vote name */
    public $voteText = "";

    /** @var int voters */
    public $voters = 1;

    /** @var int timestamp */
    public $timestamp = 0;

    public function __construct($voteAuthor, $votingTime, $voteRatio, $playerVotes, $action, $actionParams, $voteText, $voters, $timestamp)
    {
        $this->voteAuthor = $voteAuthor;
        $this->votingTime = $votingTime;
        $this->voteRatio = $voteRatio;
        $this->playerVotes = $playerVotes;
        $this->action = $action;
        $this->actionParams = $actionParams;
        $this->voteText = $voteText;
        $this->voters = $voters;
        $this->timestamp = $timestamp;
    }

    public function getYes()
    {
        $yes = 0;
        foreach ($this->playerVotes as $vote) {
            if ($vote == "yes") {
                $yes++;
            }
        }
        return $yes;
    }

    public function getNo()
    {
        $no = 0;
        foreach ($this->playerVotes as $vote) {
            if ($vote == "no") {
                $no++;
            }
        }
        return $no;
    }
}
