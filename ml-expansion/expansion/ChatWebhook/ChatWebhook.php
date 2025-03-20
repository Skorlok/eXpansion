<?php

namespace ManiaLivePlugins\eXpansion\ChatWebhook;

use DateTime;
use Exception;
use ManiaLib\Utils\Formatting;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Core\DataAccess;

class ChatWebhook extends ExpPlugin
{

    private $dataAccess;
    private $config;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
    }

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $this->dataAccess = DataAccess::getInstance();

        foreach ($this->storage->players as $login => $player) {
            $this->onPlayerConnect($login, null);
        }

        foreach ($this->storage->spectators as $login => $player) {
            $this->onPlayerConnect($login, null);
        }
    }

    public function onSettingsChanged(Variable $var)
    {
        $this->config = Config::getInstance();
    }

    public function eXpOnUnload()
    {
        foreach ($this->storage->players as $login => $player) {
            $this->onPlayerDisconnect($login);
        }

        foreach ($this->storage->spectators as $login => $player) {
            $this->onPlayerDisconnect($login);
        }
    }

    public function sendWebhookMessage($content = "", $embeds = array())
    {
        if ($this->config->webhookUrl == "") {
            return;
        }
        $content = str_replace(array("@everyone", "@here"), array("\@everyone", "\@here"), $content);
        $postData = array("username" => Formatting::stripStyles($this->storage->server->name), "content" => $content);
        if (sizeof($embeds) > 0) {
            $postData["embeds"] = array($embeds);
        }

        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($postData), CURLOPT_HTTPHEADER => array("Content-Type: application/json; charset=utf-8"));
        $this->dataAccess->httpCurl($this->config->webhookUrl, array($this, "requestCallback"), null, $options);
    }

    public function requestCallback($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();

        if (!substr($code, 0, 1) == 2) {
            $this->console("Error while sending webhook message : " . $code);
        }
        if ($data == false) {
            return;
        }

        $json = json_decode($data, true);
        if ($json == false || !isset($json[0])) {
            return;
        }
        $this->console(print_r($json, true));
    }

    public function setJoinTime($login)
    {
        $this->storage->getPlayerObject($login)->sessionJoinTimeWebhook = new DateTime();
    }

    public function getSessionTime($login)
    {
        if (!$login) {
            return;
        }

        $player = $this->storage->getPlayerObject($login);
        $now = new DateTime();

        if (property_exists($player, "sessionJoinTimeWebhook")) {
            $diff = $now->getTimestamp() - $player->sessionJoinTimeWebhook->getTimestamp();

            $seconds = (int)($diff % 60);
            $diff /= 60;
            $minutes = (int)(intval($diff) % 60);
            $diff /= 60;
            $hours = (int)(intval($diff) % 24);
            $days = (int)($diff / 24);

            $timestring = '';
            if ($days) {
                $timestring .= sprintf("%d day%s", $days, ($days === 1 ? ' ' : 's '));
            }
            if ($hours) {
                $timestring .= sprintf("%d hour%s", $hours, ($hours === 1 ? ' ' : 's '));
            }
            if ($minutes) {
                $timestring .= sprintf("%d minute%s", $minutes, ($minutes === 1 ? ' ' : 's '));
            }
            if ($seconds) {
                $timestring .= sprintf("%d second%s", $seconds, ($seconds === 1 ? ' ' : 's'));
            }

            return $timestring;
        }
        return "";
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if (strstr($login, "*fakeplayer")) {
            return;
        }

        try {
            $player = $this->storage->getPlayerObject($login);
            if ($player === null) {
                return;
            }
            $this->setJoinTime($login);

            $this->sendWebhookMessage("", array(
                "description" => Formatting::stripStyles($player->nickName) . ' (' . $login . ') Connected from ' . $this->getCountry($player, false),
                "color" => hexdec("00cc00"),
                "timestamp" => date("c", strtotime("now"))
            ));

        } catch (Exception $e) {
            $this->console($e->getLine() . ":" . $e->getMessage());
        }
    }

    public function onPlayerDisconnect($login, $disconnectionReason = null)
    {
        if (strstr($login, "*fakeplayer")) {
            return;
        }

        try {
            $player = $this->storage->getPlayerObject($login);
            if ($player === null) {
                return;
            }
            
            $this->sendWebhookMessage("", array(
                "description" => Formatting::stripStyles($player->nickName) . ' (' . $login . ') from ' . $this->getCountry($player, true) . ' has left the game after ' . $this->getSessionTime($login),
                "color" => hexdec("cc0000"),
                "timestamp" => date("c", strtotime("now"))
            ));

        } catch (Exception $e) {
            $this->console("Error while disconnecting : $login");
        }
    }

    public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
    {
        if ($playerUid == 0) {
            return;
        }
        if (substr($text, 0, 1) == "/" && !$this->config->forwardChatCommands) {
            return;
        }

        $player = $this->storage->getPlayerObject($login);
        if ($player === null) {
            return;
        }

        $message = '**' . Formatting::stripStyles($player->nickName) .' :** ' . $text;

        if (strlen($message) < 2000) {
            $this->sendWebhookMessage($message);
        } else {
            $messages = str_split($message, 1500);
            foreach ($messages as $message) {
                $this->sendWebhookMessage($message);
            }
        }
    }

    private function getCountry($player, $short = true)
    {
        $path = str_replace("World|", "", $player->path);
        $country = explode("|", $path);
        
        if (sizeof($country) > 0 && isset($country[1])) {
            if ($short) {
                $country = $country[1];
            } else {
                array_shift($country);
                $country = implode(", ", $country);
            }
        } else {
            $country = "Unknown";
        }

        return $country;
    }
}
