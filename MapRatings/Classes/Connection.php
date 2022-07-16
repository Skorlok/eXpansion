<?php

/*
 * Copyright (C) 2014 Reaby
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ManiaLivePlugins\eXpansion\MapRatings\Classes;

use ManiaLive\Data\Storage;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\DataAccess;
use ManiaLivePlugins\eXpansion\Helpers\Storage as Storage2;
use ManiaLivePlugins\eXpansion\MapRatings\Events\MXKarmaEvent;
use ManiaLivePlugins\eXpansion\MapRatings\Structures\MXRating;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

/**
 * Description of Connection
 *
 * @author Reaby, improved by Skorlok
 */
class Connection
{

    /** @var DataAccess */
    public $dataAccess;

    /** @var Storage */
    public $storage;

    /** @var Storage2 */
    public $expStorage;

    public $address = "http://karma.mania-exchange.com/api2/";

    private $connected = false;

    private $sessionKey = null;

    private $sessionSeed = null;

    private $apikey = "";

    /** @var MXRating */
    private $ratings = null;

    public function __construct()
    {
        $this->dataAccess = DataAccess::getInstance();
        $this->storage = Storage::getInstance();
        $this->expStorage = Storage2::getInstance();
    }

    public function connect($serverLogin, $apikey)
    {
        $this->apikey = $apikey;

        $params = array(
            "serverLogin" => $serverLogin,
            "applicationIdentifier" => "eXpansion " . Core::EXP_VERSION,
            "testMode" => "false"
        );

        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300);

        $this->dataAccess->httpCurl($this->build("startSession", $params), array($this, "xConnect"), null, $options);
    }

    public function xConnect($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];

        if ($code != 200) {
            $this->console("[MXKarma Error] MXKarma returned error code: " . $code);
            return;
        }

        $data = $this->getObject($job->getResponse(), "onConnect");

        if ($data === null) {
            $this->console("[MXKarma Error] Can't find Message from MXKarma reply");
            return;
        }

        $this->sessionKey = $data->sessionKey;
        $this->sessionSeed = $data->sessionSeed;

        $outHash = hash("sha512", ($this->apikey . $this->sessionSeed));

        $params = array("sessionKey" => $this->sessionKey, "activationHash" => $outHash);

        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300);

        $this->dataAccess->httpCurl($this->build("activateSession", $params), array($this, "xActivate"), null, $options);
    }

    public function xActivate($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];

        if ($code != 200) {
            $this->console("[MXKarma Error] MXKarma returned error code: " . $code);
            return;
        }

        $data = $this->getObject($job->getResponse(), "onActivate");

        if ($data === null) {
            $this->console("[MXKarma Error] Can't find Message from MXKarma reply");
            return;
        }

        if ($data->activated) {
            $this->connected = true;
            Dispatcher::dispatch(new MXKarmaEvent(MXKarmaEvent::ON_CONNECTED));
        }
    }

    public function getRatings($players = array(), $getVotesOnly = false)
    {
        if (!$this->connected) {
            return;
        }

        $params = array("sessionKey" => $this->sessionKey);
        $postData = array(
            "gamemode" => $this->getGameMode(),
            "titleid" => $this->expStorage->titleId,
            "mapuid" => $this->storage->currentMap->uId,
            "getvotesonly" => $getVotesOnly,
            "playerlogins" => $players
        );

        $headers = array('Accept: */*', 'Content-Type: application/json');
        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_POST => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => json_encode($postData));
        
        $this->dataAccess->httpCurl($this->build("getMapRating", $params), array($this, "xGetRatings"), null, $options);
    }

    public function saveVotes(\Maniaplanet\DedicatedServer\Structures\Map $map, $time, $votes)
    {
        if (!$this->connected) {
            return;
        }

        $params = array("sessionKey" => $this->sessionKey);
        $postData = array(
            "gamemode" => $this->getGameMode(),
            "titleid" => $this->expStorage->titleId,
            "mapuid" => $map->uId,
            "mapname" => $map->name,
            "mapauthor" => $map->author,
            "isimport" => false,
            "maptime" => $time,
            "votes" => $votes
        );

        $headers = array('Accept: */*', 'Content-Type: application/json');
        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_POST => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => json_encode($postData));

        $this->dataAccess->httpCurl($this->build("saveVotes", $params), array($this, "xSaveVotes"), null, $options);
    }

    public function xSaveVotes($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];

        if ($code != 200) {
            $this->console("[MXKarma Error] MXKarma returned error code: " . $code);
            return;
        }

        $data = $this->getObject($job->getResponse(), "getRatings");

        if ($data === null) {
            $this->console("[MXKarma Error] Can't find Message from MXKarma reply");
            return;
        }

        Dispatcher::dispatch(new MXKarmaEvent(MXKarmaEvent::ON_VOTE_SAVE, $data->updated));
    }

    public function xGetRatings($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];

        if ($code != 200) {
            $this->console("[MXKarma Error] MXKarma returned error code: " . $code);
            return;
        }

        $data = $this->getObject($job->getResponse(), "getRatings");

        if ($data === null) {
            $this->console("[MXKarma Error] Can't find Message from MXKarma reply");
            return;
        }

        $this->ratings = new MXRating();
        $this->ratings->append($data);
        Dispatcher::dispatch(new MXKarmaEvent(MXKarmaEvent::ON_VOTES_RECIEVED, $this->ratings));
    }

    public function getGameMode()
    {
        switch ($this->storage->gameInfos->gameMode) {
            case GameInfos::GAMEMODE_SCRIPT:
                $gamemode = strtolower($this->storage->gameInfos->scriptName);
                break;
            case GameInfos::GAMEMODE_ROUNDS:
                $gamemode = "Rounds";
                break;
            case GameInfos::GAMEMODE_TIMEATTACK:
                $gamemode = "TimeAttack";
                break;
            case GameInfos::GAMEMODE_TEAM:
                $gamemode = "Team";
                break;
            case GameInfos::GAMEMODE_LAPS:
                $gamemode = "Laps";
                break;
            case GameInfos::GAMEMODE_CUP:
                $gamemode = "Cup";
                break;
        }

        return $gamemode;
    }

    public function getObject($data, $origin = "onRecieve")
    {
        $obj = (object)json_decode($data);
        if ($obj->success == false) {
            $this->handleErrors($obj, $origin);

            return null;
        }

        return $obj->data;
    }

    public function handleErrors($obj, $origin = "onRecieve")
    {
        switch ($obj->data->code) {
            case 2:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
                $this->connected = false;
            default:
                break;
        }

        Dispatcher::dispatch(new MXKarmaEvent(MXKarmaEvent::ON_ERROR, $origin, $obj->data->code, $obj->data->message));
    }

    private function build($method, $params)
    {
        $url = $this->address . $method;
        $first = true;
        $buffer = "";
        foreach ($params as $key => $value) {
            $prefix = "&";
            if ($first) {
                $first = false;
                $prefix = "?";
            }
            $buffer .= $prefix . $key . "=" . rawurlencode($value);
        }

        return $url . $buffer;
    }

    public function isConnected()
    {
        return $this->connected;
    }
}
