<?php

namespace ManiaLivePlugins\eXpansion\Discord;

use DateTime;
use Exception;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Core\DataAccess;
use ManiaLivePlugins\eXpansion\Discord\Classes\Connection as DiscordConnection;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\Helpers\GBXChallMapFetcher;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use oliverde8\AsynchronousJobs\Job\Curl;

class Discord extends ExpPlugin
{
    /** @var DataAccess */
    private $dataAccess;
    /** @var Config */
    private $config;
    /** @var DiscordConnection */
    private $discord;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();
    }

    public function eXpOnReady()
    {
        $this->config = Config::getInstance();
        $this->dataAccess = DataAccess::getInstance();

        $this->discord = new DiscordConnection($this->config->token, $this->config->channelId);

        foreach ($this->storage->players as $login => $player) {
            $this->onPlayerConnect($login, null);
        }

        foreach ($this->storage->spectators as $login => $player) {
            $this->onPlayerConnect($login, null);
        }

        $this->updateChannelTopic();
    }

    public function onSettingsChanged(Variable $var)
	{
		$this->config = Config::getInstance();

        $this->discord->token = $this->config->token;
        $this->discord->channelId = $this->config->channelId;
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

    public function onTick()
    {
        $this->discord->processMessageQueue();
        $this->discord->processChannelTopic();
    }

    /**
     * @param string $login
     */
    public function setJoinTime($login)
    {
        $this->storage->getPlayerObject($login)->sessionJoinTimeDiscord = new DateTime();
    }

    /**
     * @param string $login
     */
    public function getSessionTime($login)
    {
        if (!$login) {
            return;
        }

        $player = $this->storage->getPlayerObject($login);
        $now = new DateTime();

        if (property_exists($player, "sessionJoinTimeDiscord")) {
            $diff = $now->getTimestamp() - $player->sessionJoinTimeDiscord->getTimestamp();

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

            if ($this->config->enableToDiscord) {
                    $this->discord->sendDiscordMessage("", array(
                    "description" => Formatting::stripStyles($player->nickName) . ' (' . $login . ') Connected from ' . $this->getCountry($player, false),
                    "color" => hexdec("00cc00"),
                    "timestamp" => date("c", strtotime("now"))
                ));
            }

            $this->updateChannelTopic();

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
            
            if ($this->config->enableToDiscord) {
                $this->discord->sendDiscordMessage("", array(
                    "description" => Formatting::stripStyles($player->nickName) . ' (' . $login . ') from ' . $this->getCountry($player, true) . ' has left the game after ' . $this->getSessionTime($login),
                    "color" => hexdec("cc0000"),
                    "timestamp" => date("c", strtotime("now"))
                ));
            }

            $this->updateChannelTopic();

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
        if (!$this->config->enableToDiscord) {
            return;
        }

        $player = $this->storage->getPlayerObject($login);
        if ($player === null) {
            return;
        }

        $message = '**' . Formatting::stripStyles($player->nickName) .' :** ' . $text;

        if (strlen($message) < 2000) {
            $this->discord->sendDiscordMessage($message);
        } else {
            $messages = str_split($message, 1500);
            foreach ($messages as $message) {
                $this->discord->sendDiscordMessage($message);
            }
        }
    }

    /**
     * @param \ManiaLive\Data\Player $player
     */
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

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->updateChannelTopic();
    }

    private function updateChannelTopic()
    {
        $currentMap = $this->storage->currentMap;
        if (!$currentMap) {
            return;
        }

        $this->discord->setChannelTopic('Players/Spectators on server ' . $this->countConnected($this->storage->players) . '/' . $this->countConnected($this->storage->spectators) . ', current map: ' . Formatting::stripStyles($currentMap->name) . ' by ' . Formatting::stripStyles($currentMap->author));
    }

    /**
     * @param array $players
     */
    private function countConnected($players)
    {
        $count = 0;
        foreach ($players as $login => $player) {
            if ($player->isConnected && !strstr($login, "*fakeplayer")) {
                $count++;
            }
        }
        return $count;
    }

    public function onEcho($internal, $public)
	{
        if ($internal == "SasecoDiscordBot") {
            $data = json_decode($public, true);
            if ($data) {
                $this->onDiscordMessage($public);
            }
        }
	}

    /**
     * Handle incoming Discord messages.
     *
     * @param string $message
     */
    public function onDiscordMessage($message)
    {
        $data = json_decode($message, true);

        if (!isset($data['channel_id']) || !isset($data['author']['username'])) {
            return;
        }

        if (!isset($data['member'])) { // webhook messages
            return;
        }

        if ($data['channel_id'] != $this->config->channelId) {
            return;
        }

        if (isset($data['author']['bot']) && $data['author']['bot']) {
            return;
        }

        if (isset($data['content']) && $data['content']) {
            if (substr($data['content'], 0, 1) == "!") {
                $this->handleAdminCommand($data);
            } else {
                $this->eXpChatSendServerMessage('$z$s$ff0[' . $this->getDiscordUserName($data) . '$z$ff0$s]$fff ' . $data['content']);
            }
        }

        if (isset($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                if (isset($attachment['url']) && isset($attachment['filename'])) {
                    $this->addMapsFromDiscord($data, $attachment);
                }
            }
        }
    }

    /**
     * Add maps from discord to the server.
     *
     * @param array $message
     * @param array $attachments
     */
    public function addMapsFromDiscord($message, $attachments)
    {
        $check_file_type = explode(".", $attachments['filename']);

        if (count($check_file_type) >= 3 && strtolower($check_file_type[count($check_file_type)-1]) == "gbx") {
            $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300);
            $this->dataAccess->httpCurl($attachments['url'], array($this, "xaddMapFromDiscord"), array("discordMessage" => $message), $options);
        }
    }

    /**
     * Add a map from discord to the server.
     *
     * @param Curl $job
     * @param array $jobData
     */
    public function xaddMapFromDiscord($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];

        $additionalData = $job->__additionalData;

        $discordMessage = $additionalData['discordMessage'];

        $data = $job->getResponse();


        if ($code !== 200) {
            $this->discord->sendDiscordMessage("Error while downloading map from discord: " . $discordMessage['attachments'][0]['filename'] . " (HTTP code: $code)");
            return;
        }

        $dir = Helper::getPaths()->getDownloadMapsPath();

        try {
            $gbxReader = new GBXChallMapFetcher(true, false, false);
            $gbxReader->processData($data);

            if (!$gbxReader->validated) {
                $this->discord->sendDiscordMessage("Map is not validated: **" . Formatting::stripStyles($gbxReader->name) . "** (validate the map and compute shadows before adding)");
                return;
            }

            $mapFileName = ArrayOfObj::getObjbyPropValue($this->storage->maps, "uId", $gbxReader->uid);
            if ($mapFileName){
                $this->discord->sendDiscordMessage("Map already exists on the server: **" . Formatting::stripStyles($gbxReader->name) . "** by **" . Formatting::stripStyles($gbxReader->authorLogin) . "**");
                return;
            }

            $file = $dir . '/' . $this->getDownloadedMapFilePath($gbxReader);
            $dir = dirname($file);

            $this->saveMapLocally($file, $dir, $data, $discordMessage, $gbxReader);
        } catch (\Exception $ex) {
            $this->discord->sendDiscordMessage("Error while adding map from discord: " . $discordMessage['attachments'][0]['filename'] . " (" . $ex->getMessage() . ")");
        }
    }

    /**
     * Save the map locally and add it to the server.
     *
     * @param string $file
     * @param string $dir
     * @param string $data
     * @param array $discordMessage
     * @param GBXChallMapFetcher $gbxReader
     */
    public function saveMapLocally($file, $dir, $data, $discordMessage, $gbxReader)
    {
        try {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0775, true)) {
                    $this->discord->sendDiscordMessage("Error while creating directory for map: " . $dir);
                    return;
                }
            }

            if ($this->dataAccess->save($file, $data)) {
                try {
                    try {
                        $this->connection->checkMapForCurrentServerParams($file);
                    } catch (\Exception $e) {
                        $this->discord->sendDiscordMessage("Error while checking map from discord: **" . Formatting::stripStyles($gbxReader->name) . "** (" . $e->getMessage() . ")");
                        return;
                    }

                    $this->connection->addMap($file);

                    $map = $this->connection->getMapInfo($file);

                    $this->eXpChatSendServerMessage('$z$s$i$78D Discord: Map $z' . $map->name . ' $z$s$i$78Dadded to the list by $z$s$fff' . $this->getDiscordUserName($discordMessage));
                    $this->discord->sendDiscordMessage("Map added to the server: **" . Formatting::stripStyles($map->name) . "** by **" . Formatting::stripStyles($map->author) . "**");
                } catch (\Exception $e) {
                    $this->discord->sendDiscordMessage("Error while adding map from discord: **" . Formatting::stripStyles($gbxReader->name) . "** (" . $e->getMessage() . ")");
                }
            } else {
                $this->discord->sendDiscordMessage("Error while saving map from discord: **" . Formatting::stripStyles($gbxReader->name) . "** (Could not save file)");
            }
        } catch (\Exception $ex) {
            $this->discord->sendDiscordMessage("Error while saving map from discord: **" . Formatting::stripStyles($gbxReader->name) . "** (" . $ex->getMessage() . ")");
        }
    }

    /**
     * Get Name for the downloaded map.
     *
     * @param GBXChallMapFetcher $gbxReader
     *
     * @return string
     */
    public function getDownloadedMapFilePath(GBXChallMapFetcher $gbxReader)
    {
        $authorName = $this->cleanMapName($gbxReader->authorLogin);
        $mapName = $this->cleanMapName(trim(mb_convert_encoding(substr(Formatting::stripStyles($gbxReader->name), 0, 40), "7bit", "UTF-8")));

        $replacements = array(
            '{map_author}' => $authorName,
            '{map_name}' => $mapName,
            '{map_environment}' => $gbxReader->envir,
            '{map_vehicle}' => $gbxReader->vehicle,
            '{map_type}' => $gbxReader->mapType,
            '{map_style}' => $gbxReader->mapStyle,
            '{server_title}' => $this->expStorage->titleId,
            '{server_login}' => $this->storage->serverLogin
        );

        return str_replace(array_keys($replacements), array_values($replacements), $this->config->file_name);
    }

    /**
     * Remove special characters from map name
     *
     * @param string $string
     * @return mixed
     */
    protected function cleanMapName($string)
    {
        return str_replace(array("/", "\\", ":", ".", "?", "*", '"', "|", "<", ">", "'"), "", $string);
    }

    /**
     * Get the discord user name from the message data.
     *
     * @param array $message
     * @return string
     */
    private function getDiscordUserName($message)
    {
        if (isset($message['member']) && isset($message['member']['nick']) && $message['member']['nick']) {
            return $message['member']['nick'];
        } else {
            if (isset($message['author']['global_name']) && $message['author']['global_name']) {
                return $message['author']['global_name'];
            } else {
                return $message['author']['username'];
            }
        }
    }

    /**
     * @param array $message
     */
    public function handleAdminCommand($message)
    {
        if (!isset($message['author']['id'])) {
            return;
        }
        if (!in_array($message['author']['id'], $this->config->allowedIds)) {
            $this->discord->sendDiscordMessage("<@" . $message['author']['id'] . "> You are not allowed to use this interface. go away.");
            return;
        }
        $params = explode(" ", substr($message['content'], 1));
        $command = array_shift($params);
        $authorId = $message['author']['id'];
        $authorName = $this->getDiscordUserName($message);

        switch ($command) {
            case "help":
                $this->discord->sendDiscordMessage("<@" . $authorId . "> Available commands: skip, res, players, kick [login], ignore [login], unignore [login]");
                break;
            case "kick":
                $this->kick($authorId, $params);
                break;
            case "ignore":
                $this->ignore($authorId, $params);
                break;
            case "unignore":
                $this->unignore($authorId, $params);
                break;
            case "skip":
                $this->skip($authorId, $authorName);
                break;
            case "res":
                $this->res($authorId, $authorName);
                break;
            case "players":
                $this->players($authorId);
                break;
            default:
                break;
        }
    }

    /**
     * @param string $authorId
     * @param array $params
     */
    private function kick($authorId, $params)
    {
        try {
            if (!isset($params[0])) {
                $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to kick: player login is missing");
                return;
            }
            $player = $this->storage->getPlayerObject($params[0]);
            if (!$player) {
                $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to kick: player is not on the server");
                return;
            }

            $this->connection->kick($params[0]);
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Kicked " . Formatting::stripStyles($player->cleanNickName));
        } catch (\Exception $e) {
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to kick:" . $e->getMessage());
        }
    }

    /**
     * @param string $authorId
     * @param array $params
     */
    private function ignore($authorId, $params)
    {
        try {
            if (!isset($params[0])) {
                $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to ignore: player login is missing");
                return;
            }
            $player = $this->storage->getPlayerObject($params[0]);
            if (!$player) {
                $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to ignore: player is not on the server");
                return;
            }

            $this->connection->ignore($params[0]);
            $this->connection->chatSendServerMessage($player->cleanNickName . ' $z$fffis now ignored from the chat!');
            $this->connection->chatSendServerMessage("You are not allowed to chat anymore!", $params[0]);
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Ignored: " . Formatting::stripStyles($player->cleanNickName));
        } catch (\Exception $e) {
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to ignore: " . $e->getMessage());
        }
    }

    /**
     * @param string $authorId
     * @param array $params
     */
    private function unignore($authorId, $params)
    {
        try {
            if (!isset($params[0])) {
                $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to unignore: player login is missing");
                return;
            }
            $player = $this->storage->getPlayerObject($params[0]);
            if (!$player) {
                $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to unignore: player is not on the server");
                return;
            }

            $this->connection->unIgnore($params[0]);
            $this->connection->chatSendServerMessage($player->cleanNickName . ' $z$fffis unignored from the chat!');
            $this->connection->chatSendServerMessage("You are allowed to chat again!", $params[0]);
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Unignored: " . Formatting::stripStyles($player->cleanNickName));
        } catch (\Exception $e) {
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to unignore:" . $e->getMessage());
        }
    }

    /**
     * @param string $authorId
     * @param string $authorName
     */
    private function skip($authorId, $authorName)
    {
        try {
            $this->connection->nextMap($this->storage->gameInfos->gameMode == \Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Skipped.");
            $this->eXpChatSendServerMessage('$z$s$i$78D Discord:$fff %s has skipped the map!', null, array($authorName));
        } catch (\Exception $e) {
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to skip: " . $e->getMessage());
        }
    }

    /**
     * @param string $authorId
     * @param string $authorName
     */
    private function res($authorId, $authorName)
    {
        try {
            $this->connection->restartMap($this->storage->gameInfos->gameMode == \Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Restarted.");
            $this->eXpChatSendServerMessage('$z$s$i$78D Discord:$fff %s has restarted the map!', null, array($authorName));
        } catch (\Exception $e) {
            $this->discord->sendDiscordMessage("<@" . $authorId . "> Failed to restart: " . $e->getMessage());
        }
    }

    /**
     * @param string $authorId
     */
    private function players($authorId)
    {
        $pla = "Players:  ";
        $placount = 0;
        $speccount = 0;
        foreach ($this->storage->players as $login => $player) {
            $placount++;
            $pla .= Formatting::stripStyles($player->nickName) . " ($login)\n";
        }
        $spec = "Spectators:  ";
        foreach ($this->storage->spectators as $login => $player) {
            $speccount++;
            $spec .= Formatting::stripStyles($player->nickName) . " ($login)\n";
        }
        $this->discord->sendDiscordMessage("<@" . $authorId . "> Players at server: " . $placount . " (spec: $speccount)\n" . $pla . "\n" . $spec);
    }
}
