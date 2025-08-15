<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Helpers\GBXChallMapFetcher;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Windows\MxSearch;
use ManiaLivePlugins\eXpansion\ManiaExchange\Structures\MxMap;
use ManiaLivePlugins\eXpansion\Maps\Maps;
use ManiaLivePlugins\eXpansion\Menu\Menu;
use oliverde8\AsynchronousJobs\Job\Curl;
use ManiaLive\Utilities\Time;

class ManiaExchange extends ExpPlugin
{
    /** @var Config * */
    private $config;

    /** @var \Maniaplanet\DedicatedServer\Structures\Vote */
    private $vote;

    /** @var string */
    private $titleId;

    /** @var \ManiaLivePlugins\eXpansion\Core\I18n\Message */
    private $msg_add;
    private $msg_not_found;
    private $msg_worldRec;

    /** @var \ManiaLivePlugins\eXpansion\Core\DataAccess */
    private $dataAccess;
    private $cmd_add;
    private $cmd_update;
    private $cmd_random;
    private $cmd_pack;

    /** @var StdClass $mxInfo */
    public static $mxInfo = null;
    public static $mxReplays = null;
    public static $openInfosAction = null;

    public function eXpOnInit()
    {
        $this->config = Config::getInstance();
    }

    public function eXpOnLoad()
    {
        $this->msg_add = eXpGetMessage('#mx#Map $fff%s $z$s#mx# added from MX Succesfully');
        $this->msg_worldRec = eXpGetMessage('#mx#MX World Record: #time#%s#mx# by #variable#%s');
        $this->msg_not_found = eXpGetMessage('#error#Map not found on ManiaExchange');

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        Menu::addMenuItem("ManiaExchange",
            array("Maps" => array(null, array(
                "ManiaExchange" => array(Permission::MAP_ADD_MX, $aH->createAction(array($this, "mxSearch")))
            )))
        );
    }

    public function eXpOnReady()
    {
        $this->dataAccess = \ManiaLivePlugins\eXpansion\Core\DataAccess::getInstance();
        $this->registerChatCommand("mx", "chatMX", 2, true);
        $this->registerChatCommand("mx", "chatMX", 1, true);
        $this->registerChatCommand("mx", "chatMX", 0, true);
        $this->setPublicMethod("mxSearch");

        $cmd = AdminGroups::addAdminCommand('add', $this, 'addMap', Permission::MAP_ADD_MX);
        $cmd->setHelp('Adds a map from ManiaExchange');
        $cmd->setHelpMore('$w//add #id$z$w will add a map with id from ManiaExchange');
        $cmd->setMinParam(1);
        AdminGroups::addAlias($cmd, "addmx");
        AdminGroups::addAlias($cmd, "mxadd");
        $this->cmd_add = $cmd;

        $cmd = AdminGroups::addAdminCommand('mxupdate', $this, 'mxUpdate', 'server_maps');
        $cmd->setHelp('show updated maps from ManiaExchange');
        $cmd->setHelpMore('$wShow a window with maps updated on ManiaExchange');
        AdminGroups::addAlias($cmd, "updatemx");
        $this->cmd_update = $cmd;

        $cmd = AdminGroups::addAdminCommand('mxrandom', $this, 'mxRandom', Permission::MAP_ADD_MX);
        $cmd->setHelp('Adds a random map from ManiaExchange');
        $cmd->setHelpMore('$w//mxrandom will add a random map from ManiaExchange');
        AdminGroups::addAlias($cmd, "randommx");
        AdminGroups::addAlias($cmd, "rmx");
        $this->cmd_random = $cmd;

        $cmd = AdminGroups::addAdminCommand('addpack', $this, 'mxPack', Permission::MAP_ADD_MX);
        $cmd->setHelp('Adds a pack of maps from ManiaExchange');
        $cmd->setHelpMore('$w//addpack will add a pack of maps from ManiaExchange');
        $cmd->setMinParam(1);
        $this->cmd_pack = $cmd;

        if ($this->isPluginLoaded('eXpansion\Menu')) {
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Menu', 'addSeparator', __('ManiaExchange'), false);
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Menu', 'addItem', __('Search Maps'), null, array($this, 'mxSearch'), false);
        }

        $this->enableDedicatedEvents();

        ManiaExchange::$openInfosAction = array($this, 'showMxInfos');

        $this->onBeginMap(null, null, null);
    }

    public function chatMX($login, $arg = "", $param = "")
    {
        switch ($arg) {
            case "search":
                $this->mxSearch($login, $param, "");
                break;
            case "author":
                $this->mxSearch($login, "", $param);
                break;
            case "queue":
                $this->mxVote($login, $param);
                break;
            case "infos":
                $this->showMxInfos($login);
                break;
            default:
                $msg = eXpGetMessage('usage /mx queue [id], /mx search "terms here"  "authorname", /mx author "name", /mx infos');
                $this->eXpChatSendServerMessage($msg, $login);
                break;
        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        $this->config = Config::getInstance();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        self::$mxInfo = null;
        self::$mxReplays = null;

        $fields = "fields=MapId,MapUid,Name,GbxMapName,UploadedAt,UpdatedAt,Uploader.Name,Tags,Images,MapType,MoodFull,Routes,Difficulty,Length,AwardCount,TitlePack,ReplayCount,Feature.Comment";

        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/maps?' . $fields . "&uid=" . $this->storage->currentMap->uId;

        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_HTTPHEADER => array("X-ManiaPlanet-ServerLogin" => $this->storage->serverLogin));
        $this->dataAccess->httpCurl($query, array($this, "xGetMapInfo"), null, $options);
    }

    public function xGetMapInfo($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();

        if ($data === false || $code !== 200) {
            return;
        }

        $json = json_decode($data, true);
        if ($json == false || !array_key_exists("Results", $json)) {
            return;
        }

        self::$mxInfo = MxMap::fromArray($json['Results'][0]);

        if ($this->expStorage->simpleEnviTitle == "TM" && self::$mxInfo->replayCount > 0) {
            $query = "https://tm.mania.exchange/api/replays/?count=25&best=1&mapId=" . self::$mxInfo->mapId;

            $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_HTTPHEADER => array("X-ManiaPlanet-ServerLogin" => $this->storage->serverLogin));
            $this->dataAccess->httpCurl($query, array($this, "xGetReplaysInfo"), null, $options);
        }
    }

    public function xGetReplaysInfo($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();

        if ($data === false || $code !== 200) {
            return;
        }

        $jsonReplay = json_decode($data);
        if ($jsonReplay === false || !isset($jsonReplay->Results)) {
            return;
        }

        self::$mxReplays = $jsonReplay->Results;

        foreach (self::$mxReplays as $replay) {
            $replay->Username = $replay->User->Name;
        }

        ArrayOfObj::sortAsc(self::$mxReplays, "ReplayTime");

        if ($this->config->announceMxRecord) {
            $this->eXpChatSendServerMessage($this->msg_worldRec, null, array(Time::fromTM(self::$mxReplays[0]->ReplayTime), self::$mxReplays[0]->Username));
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if ($this->expStorage->simpleEnviTitle == "TM" && count(self::$mxReplays) > 0 && $this->config->announceMxRecord) {
            $this->eXpChatSendServerMessage($this->msg_worldRec, $login, array(Time::fromTM(self::$mxReplays[0]->ReplayTime), self::$mxReplays[0]->Username));
        }
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        Gui\Windows\MxSearch::Erase($login);
    }

    public function showMxInfos($login)
    {
        if (!self::$mxInfo) {
            $this->eXpChatSendServerMessage($this->msg_not_found, $login);
            return;
        }
        $window = Gui\Windows\MxInfos::Create($login);
        $window->setTitle('ManiaExchange Map Infos');
        $window->setSize(220, 100);
        $window->centerOnScreen();
        $window->show();
    }

    public function mxPack($login, $packId)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_ADD_MX)) {
            $this->eXpChatSendServerMessage("#error#You don't have permission to run this command.", $login);
            return;
        }

        if (is_array($packId)) {
            $packId = $packId[0];
        }

        if (!is_numeric($packId)) {
            $this->connection->chatSendServerMessage(__('"%s" is not a numeric value.', $login, $packId), $login);
            return false;
        }

        $this->eXpChatSendServerMessage("#mx#Download starting for map pack: %s", $login, array($packId));
        $this->mxDownloadPack($login, $packId);
    }

    public function mxDownloadPack($login, $packId, $startAfter = null)
    {
        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/maps?fields=MapId&mappackid=' . $packId . '&count=50' . ($startAfter ? '&after=' . $startAfter : '');

        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_HTTPHEADER => array("X-ManiaPlanet-ServerLogin" => $this->storage->serverLogin));
        $this->dataAccess->httpCurl($query, array($this, "xAddMxPackAdmin"), array("login" => $login, "packId" => $packId, "firstLoop" => $startAfter == null), $options);
    }

    public function xAddMxPackAdmin($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();
        $additionalData = $job->__additionalData;

        $login = $additionalData['login'];
        $packId = $additionalData['packId'];
        $firstLoop = $additionalData['firstLoop'];

        if ($data === false || $code !== 200) {
            $this->eXpChatSendServerMessage("#error#MX returned error code $code", $login);
            return;
        }

        $json = json_decode($data);
        if (!$json) {
            if ($firstLoop) {
                $this->eXpChatSendServerMessage("#error#No maps found in mappack !", $login);
            } else {
                $this->eXpChatSendServerMessage("#mx#Map pack added succesfully", $login);
            }
            return;
        }
        if (count($json->Results) <= 0) {
            if ($firstLoop) {
                $this->eXpChatSendServerMessage("#error#No maps found in mappack !", $login);
            } else {
                $this->eXpChatSendServerMessage("#mx#Map pack added succesfully", $login);
            }
            return;
        }

        foreach ($json->Results as $map) {
            $this->addMap($login, $map->MapId);
        }

        if (count($json->Results) == 50) {
            $this->mxDownloadPack($login, $packId, $json->Results[count($json->Results) - 1]->MapId);
        } else {
            $this->eXpChatSendServerMessage("#mx#Map pack added succesfully", $login);
        }
    }

    public function mxRandom($login)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_ADD_MX)) {
            $this->eXpChatSendServerMessage("#error#You don't have permission to run this command.", $login);
            return;
        }

        $titlePack = $this->expStorage->titleId;
        $pack = explode("@", $titlePack);

        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/maps?fields=MapId&random=1&count=1&titlepack=' . $pack[0];

        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_HTTPHEADER => array("X-ManiaPlanet-ServerLogin" => $this->storage->serverLogin));
        $this->dataAccess->httpCurl($query, array($this, "xAddRandomMapAdmin"), array("login" => $login), $options);
    }

    public function xAddRandomMapAdmin($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();
        $additionalData = $job->__additionalData;

        $login = $additionalData['login'];

        if ($data === false || $code !== 200) {
            $this->eXpChatSendServerMessage("#error#MX returned error code $code", $login);
            return;
        }

        $json = json_decode($data);
        if (!$json || $json->Results == 0) {
            $this->eXpChatSendServerMessage("#error#No maps found !", $login);
            return;
        }

        $this->addMap($login, $json->Results[0]->MapId);
    }

    public function mxUpdate($login)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_ADD_MX)) {
            $this->eXpChatSendServerMessage("#error#You don't have permission to run this command.", $login);
            return;
        }
        // clear mx maps cache
        Maps::$dbMapsByUid = array();

        $window = Gui\Windows\MxUpdate::Create($login);
        $window->setMain($this);
        $window->setTitle('Update Maps');
        $window->setSize(210, 100);
        $window->show();
    }

    public function mxSearch($login, $search = "", $author = "")
    {
        $window = Gui\Windows\MxSearch::Create($login);
        $window->setPlugin($this);
        $window->search($login, $search, $author);
        $window->setSize(210, 100);
        $window->centerOnScreen();
        $window->show();
    }

    public function addMap($login, $mxId)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_ADD_MX)) {
            $this->eXpChatSendServerMessage("#error#You don't have permission to run this command.", $login);
            return;
        }

        if (is_array($mxId)) {
            $mxId = $mxId[0];
        }

        if ($mxId == 'this') {
            try {
                $this->connection->addMap($this->storage->currentMap->fileName);
                $this->eXpChatSendServerMessage($this->msg_add, null, array($this->storage->currentMap->name));
            } catch (\Exception $e) {
                $this->connection->chatSendServerMessage(__("Error: %s", $login, $e->getMessage()), $login);
            }
            return;
        }
        $this->download($mxId, $login, "xAddMapAdmin");
    }

    /**
     *
     * @param string $mxId
     * @param string $login
     * @param        $redirect
     *
     * @return string
     */
    public function download($mxId, $login, $redirect)
    {
        if (!is_numeric($mxId)) {
            $this->connection->chatSendServerMessage(__('"%s" is not a numeric value.', $login, $mxId), $login);
            return false;
        }

        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/mapgbx/' . $mxId;

        $this->eXpChatSendServerMessage("#mx#Download starting for: %s", $login, array($mxId));
        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_HTTPHEADER => array("X-ManiaPlanet-ServerLogin" => $this->storage->serverLogin));
        $this->dataAccess->httpCurl($query, array($this, $redirect), array("login" => $login, "mxId" => $mxId), $options);
    }

    /**
     * @param Curl $job
     * @param      $jobData
     */
    public function xAddMapAdmin($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];

        $additionalData = $job->__additionalData;

        $mxId = $additionalData['mxId'];
        $login = $additionalData['login'];

        $data = $job->getResponse();

        if ($code !== 200) {
            if ($code == 302) {
                $this->eXpChatSendServerMessage("#admin_error#Map author has declined the permission to download this map!", $login);
                return;
            }
            $this->eXpChatSendServerMessage("#admin_error#MX returned error code $code", $login);
            return;
        }
        /** @var \Maniaplanet\DedicatedServer\Structures\Version */
        $dir = Helper::getPaths()->getDownloadMapsPath();
        if ($this->expStorage->isRemoteControlled) {
            $dir = "Downloaded";
        }

        try {
            $gbxReader = new GBXChallMapFetcher(true, false, false);
            $gbxReader->processData($data);

            $mapFileName = ArrayOfObj::getObjbyPropValue($this->storage->maps, "uId", $gbxReader->uid);
            if ($mapFileName){
                $this->eXpChatSendServerMessage("#mx#Map already in playlist! Update? remove it first or use //mxupdate", $login);
                return;
            }

            $file = $dir . '/' . $this->getDownloadedMapFilePath($gbxReader, $mxId);
            $dir = dirname($file);

            if ($this->expStorage->isRemoteControlled) {
                $this->saveMapRemotelly($file, $dir, $data, $login);
            } else {
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $this->saveMapLocally($file, $dir, $data, $login);
            }
        } catch (\Exception $ex) {
            $this->console('Error while adding map from mx:' . $ex->getMessage());
        }
    }

    /**
     * Get Name for the downloaded map.
     *
     * @param GBXChallMapFetcher $gbxReader
     * @param int $mxId
     *
     * @return string
     */
    public function getDownloadedMapFilePath(GBXChallMapFetcher $gbxReader, $mxId)
    {
        $authorName = $this->cleanMapName($gbxReader->authorLogin);
        $mapName = $this->cleanMapName(trim(mb_convert_encoding(substr(\ManiaLib\Utils\Formatting::stripStyles($gbxReader->name), 0, 40), "7bit", "UTF-8")));

        $replacements = array(
            '{map_author}' => $authorName,
            '{map_name}' => $mapName,
            '{map_environment}' => $gbxReader->envir,
            '{map_vehicle}' => $gbxReader->vehicle,
            '{map_type}' => $gbxReader->mapType,
            '{map_style}' => $gbxReader->mapStyle,
            '{mx_id}' => $mxId,
            '{server_title}' => $this->expStorage->titleId,
            '{server_login}' => $this->storage->serverLogin
        );

        return str_replace(array_keys($replacements), array_values($replacements), $this->config->file_name);
    }

    /**
     * Remove special characters from map name
     *
     * @param $string
     * @return mixed
     */
    protected function cleanMapName($string)
    {
        return str_replace(array("/", "\\", ":", ".", "?", "*", '"', "|", "<", ">", "'"), "", $string);
    }

    public function saveMapRemotelly($file, $dir, $data, $login)
    {
        try {
            if ($this->connection->writeFile($file, $data)) {

                try {
                    if (!$this->connection->checkMapForCurrentServerParams($file)) {
                        $msg = eXpGetMessage("#admin_error#The Map is not compatible with current server settings, map not added.");
                        $this->eXpChatSendServerMessage($msg, $login);
                        return;
                    }

                    $this->connection->addMap($file);

                    $map = $this->connection->getMapInfo($file);
                    $this->eXpChatSendServerMessage($this->msg_add, null, array($map->name));
                    if ($this->config->juke_newmaps) {
                        $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Maps\Maps', "queueMap", $login, $map, false);
                    }
                } catch (\Exception $e) {
                    $this->connection->chatSendServerMessage(__("Error: %s", $login, $e->getMessage()), $login);
                    $this->storage->resetMapInfos();
                }
            } else {
                $this->eXpChatSendServerMessage("#admin_error#Error while saving a map file at remote host: " . $file, $login);
            }
        } catch (Exception $ex) {
            $this->eXpChatSendServerMessage("#admin_error#Error while saving a map file at remote host :" . $e->getMessage(), $login);
        }
    }

    public function saveMapLocally($file, $dir, $data, $login)
    {
        try {
            if (!is_dir($dir)) {
                mkdir($dir, 0775);
            }

            if (is_dir($dir) && $this->dataAccess->save($file, $data)) {

                try {
                    if (!$this->connection->checkMapForCurrentServerParams($file)) {
                        $msg = eXpGetMessage("#admin_error#Map is not compatible with current server settings, map not added.");
                        $this->eXpChatSendServerMessage($msg, $login);
                        return;
                    }

                    $this->connection->addMap($file);

                    $map = $this->connection->getMapInfo($file);
                    $this->eXpChatSendServerMessage($this->msg_add, null, array($map->name));
                    if ($this->config->juke_newmaps) {
                        $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Maps\Maps', "queueMap", $login, $map, false);
                    }
                } catch (\Exception $e) {
                    $this->connection->chatSendServerMessage(__("Error: %s", $login, $e->getMessage()), $login);
                    $this->storage->resetMapInfos();
                }
            } else {
                $this->eXpChatSendServerMessage("#admin_error#Error while saving a map file: " . $file, $login);
            }
        } catch (\Exception $ex) {
            $this->eXpChatSendServerMessage("#admin_error#Error while saving a map file : " . $ex->getMessage(), $login);
        }
    }

    /**
     * @param Curl $job
     * @param      $jobData
     */
    public function xQueue($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];

        $additionalData = $job->__additionalData;

        $mxId = $additionalData['mxId'];
        $login = $additionalData['login'];

        $data = $job->getResponse();

        if ($code !== 200) {
            if ($code == 302) {
                $this->eXpChatSendServerMessage("#admin_error#Map author has declined the permission to download this map!", $login);
                return;
            }
            $this->eXpChatSendServerMessage("#admin_error#MX returned error code $code", $login);
            return;
        }

        $file = Helper::getPaths()->getDownloadMapsPath() . $this->storage->serverLogin . "/" . $mxId . ".Map.Gbx";

        if (!is_dir(Helper::getPaths()->getDownloadMapsPath() . $this->storage->serverLogin)) {
            mkdir(Helper::getPaths()->getDownloadMapsPath() . $this->storage->serverLogin, 0775);
        }

        if ($this->dataAccess->save($file, $data)) {
            try {
                if (!$this->connection->checkMapForCurrentServerParams($file)) {
                    $msg = eXpGetMessage("#admin_error#Map is not compatible with current server settings, map not added.");
                    $this->eXpChatSendServerMessage($msg, $login);
                    return;
                }
            } catch (\Exception $e) {
                $this->connection->chatSendServerMessage(__("Error: %s", $login, $e->getMessage()), $login);
                return;
            }
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\Maps\\Maps', 'queueMxMap', $login, $file);
        }
    }

    public function mxVote($login, $mxId)
    {
        if (!$this->config->mxVote_enable) return;

        if (!is_numeric($mxId)) {
            $this->connection->chatSendServerMessage(__('"%s" is not a numeric value.', $login, $mxId), $login);
            return;
        }

        if (\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::MAP_ADD_MX)) {
            $this->mxQueue($login, $mxId);
            return;
        }

        $queue = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\Maps\\Maps', 'returnQueue');
        foreach ($queue as $q) {
            if ($q->player->login == $login) {
                $msg = eXpGetMessage('#admin_error# $iYou already have a map in the queue...');
                $this->eXpChatSendServerMessage($msg, $login);
                return;
            }
        }


        $fields = "fields=MapUid,Name,Uploader.Name,TitlePack";
        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/maps?' . $fields . "&id=" . $mxId;

        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_HTTPHEADER => array("X-ManiaPlanet-ServerLogin" => $this->storage->serverLogin));
        $this->dataAccess->httpCurl($query, array($this, "xVote"), array("login" => $login, "mxId" => $mxId), $options);
    }

    /**
     * @param bool $append
     * @return string
     */
    public function getKey($append = false)
    {
        $key = "";
        $op = $append ? "&" : "?";

        if ($this->config->key) {
            $key = $op . "key=" . $this->config->key;
        }
        return $key;
    }

    //function xVote($data, $code, $login, $mxId)
    public function xVote($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];

        $additionalData = $job->__additionalData;

        $mxId = $additionalData['mxId'];
        $login = $additionalData['login'];

        $data = $job->getResponse();

        if ($data === false || $code !== 200) {
            $this->eXpChatSendServerMessage("#admin_error#Mx error: $code", $login);
        }

        $json = json_decode($data, true);
        if ($json == false || !array_key_exists("Results", $json)) {
            $this->connection->chatSendServerMessage(__('Unable to retrieve track info from MX..  wrong ID..?'), $login);
            return;
        }

        $map = MxMap::fromArray($json['Results'][0]);

        $mapFileName = ArrayOfObj::getObjbyPropValue($this->storage->maps, "uId", $map->mapUid);
        if ($mapFileName){
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Maps\Maps', "queueMap", $login, $mapFileName, false, true);
            return;
        }

        $version = $this->connection->getVersion();

        if (strpos(strtolower($version->titleId), strtolower($map->titlePack)) === false) {
            $this->connection->chatSendServerMessage(__('Wrong environment!'), $login);
            return;
        }

        $this->vote = array();
        $this->vote['login'] = $login;
        $this->vote['mxId'] = $mxId;

        $vote = new \Maniaplanet\DedicatedServer\Structures\Vote();
        $vote->callerLogin = $login;
        $vote->cmdName = '$0f0add $fff$o' . $map->name . '$o$0f0 by $eee' . $map->getUploader() . ' $0f0';
        $vote->cmdParam = array('to the queue from MX?$3f3');
        $this->connection->callVote( $vote, $this->config->mxVote_ratios, ($this->config->mxVote_timeouts * 1000), $this->config->mxVote_voters);
    }

    public function mxQueue($login, $mxId)
    {
        $this->download($mxId, $login, "xQueue");
    }

    public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
    {
        switch ($cmdParam) {
            case 'to the queue from MX?$3f3':
                switch ($stateName) {
                    case "VotePassed":
                        $msg = eXpGetMessage('#record# $iVote passed!');
                        $this->eXpChatSendServerMessage($msg, null);
                        $this->mxQueue($this->vote['login'], $this->vote['mxId']);
                        $this->vote = array();
                        break;
                    case "VoteFailed":
                        $msg = eXpGetMessage('#admin_error# $iVote failed!');
                        $this->eXpChatSendServerMessage($msg, null);
                        $this->vote = array();
                        break;
                    default:
                        break;
                }
                break;
            default:
                break;
        }
    }

    public function eXpOnUnload()
    {
        MxSearch::EraseAll();
        AdminGroups::removeAdminCommand($this->cmd_add);
        AdminGroups::removeAdminCommand($this->cmd_update);
        AdminGroups::removeAdminCommand($this->cmd_random);
        AdminGroups::removeAdminCommand($this->cmd_pack);
    }
}
