<?php

namespace ManiaLivePlugins\eXpansion\ChatWebhook;

use DateTime;
use Exception;
use ManiaLivePlugins\eXpansion\Helpers\Countries;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLivePlugins\eXpansion\ChatWebhook\Classes\Connection as ChatWebhookConnection;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;

class ChatWebhook extends ExpPlugin
{

    /** @var ChatWebhookConnection */
    private $webConnection;

    /** @var Config */
    private $config;

    /** @var array country => flag image, only where Countries::mapCountry() doesn't give an existing image */
    private static $flags = array(
        'Cameroon' => 'CMR',
        'Chinese Taipei' => 'ROC',
        'Congo' => 'COG',
        'Germany' => 'GER',
        'Niger' => 'NER',
        'Romania' => 'ROU',
        'Serbia' => 'SRB',
        'Taiwan' => 'ROC',
        'Trinidad and Tobago' => 'TTO',
        'Aruba' => 'WOR',
        'Bermuda' => 'WOR',
        'Guam' => 'WOR',
        'Puerto Rico' => 'WOR',
    );

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
    }

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $this->webConnection = new ChatWebhookConnection($this->config->webhookUrl);

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
        $this->webConnection->url = $this->config->webhookUrl;
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

            $this->webConnection->sendWebhookMessage("", null, array(
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
            
            $this->webConnection->sendWebhookMessage("", null, array(
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

        if (strlen($text) < 2000) {
            $this->webConnection->sendWebhookMessage($text, array("name" => Formatting::stripStyles($player->nickName), "flag" => $this->getFlag($player)));
        } else {
            $messages = str_split($text, 1500);
            foreach ($messages as $message) {
                $this->webConnection->sendWebhookMessage($message, array("name" => Formatting::stripStyles($player->nickName), "flag" => $this->getFlag($player)));
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

    private function getFlag($player)
    {
        $country = $this->getCountry($player, true);
        if (isset(self::$flags[$country])) {
            return self::$flags[$country];
        }

        $code = Countries::mapCountry($country);
        return $code == 'OTH' ? 'WOR' : $code;
    }
}
