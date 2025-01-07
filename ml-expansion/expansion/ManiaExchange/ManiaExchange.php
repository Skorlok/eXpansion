<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Helpers\GBXChallMapFetcher;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\Storage;
use ManiaLivePlugins\eXpansion\Helpers\Console;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\ManiaExchange\Gui\Windows\MxSearch;
use ManiaLivePlugins\eXpansion\ManiaExchange\Structures\MxMap;
use ManiaLivePlugins\eXpansion\Maps\Maps;
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

    public static $mxInfo = array();
    public static $mxReplays = array();
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
        self::$mxInfo = array();
        self::$mxReplays = array();

        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/maps/get_map_info/multi/' . $this->storage->currentMap->uId;

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

        $json = json_decode($data);
        if ($json == false || !isset($json[0])) {
            return;
        }

        self::$mxInfo = $json[0];

        if ($this->expStorage->simpleEnviTitle == "TM" && $json[0]->ReplayCount > 0) {
            //$query = "https://tm.mania.exchange/api/replays/?count=25&mapId=" .$json[0]->TrackID;
            $query = "https://tm.mania.exchange/api/replays/get_replays/" .$json[0]->TrackID;

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
        if ($jsonReplay === false || sizeof($jsonReplay) == 0) {
            return;
        }

        self::$mxReplays = $jsonReplay;

        if ($this->config->announceMxRecord) {
            $this->eXpChatSendServerMessage($this->msg_worldRec, null, array(Time::fromTM($jsonReplay[0]->ReplayTime), $jsonReplay[0]->Username));
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
        if (self::$mxInfo == array()) {
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

        $this->eXpChatSendServerMessage("#mx#Download starting for: %s", $login, array($packId));

        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/mappack/get_mappack_tracks/' . $packId;

        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_HTTPHEADER => array("X-ManiaPlanet-ServerLogin" => $this->storage->serverLogin));
        $this->dataAccess->httpCurl($query, array($this, "xAddMxPackAdmin"), array("login" => $login), $options);
    }

    public function xAddMxPackAdmin($job, $jobData)
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
        if (!$json) {
            $this->eXpChatSendServerMessage("#error#No maps found in mappack !", $login);
            return;
        }
        if (isset($json->Message) && $json->Message == "Specified mappack does not exist.") {
            $this->eXpChatSendServerMessage("#error#No maps found in mappack !", $login);
            return;
        }

        foreach($json->results as $map) {
            $this->addMap($login, $map->TrackID);
        }
    }

    public function mxRandom($login)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_ADD_MX)) {
            $this->eXpChatSendServerMessage("#error#You don't have permission to run this command.", $login);
            return;
        }

        $storage = Storage::getInstance();
        $titlePack = $storage->version->titleId;
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
                $this->eXpChatSendServerMessage("#mx#Map already in playlist! Update? remove it first or use /mx update", $login);
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
                    $this->updateMxInfo($map->uId);
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
                    $this->updateMxInfo($map->uId);
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
            if (!$this->connection->checkMapForCurrentServerParams($file)) {
                $msg = eXpGetMessage("#admin_error#Map is not compatible with current server settings, map not added.");
                $this->eXpChatSendServerMessage($msg, $login);
                return;
            }
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\\Maps\\Maps', 'queueMxMap', $login, $file);
        }
    }

    private function updateMxInfo($mapUid)
    {
        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/maps/get_map_info/multi/' . $mapUid . $this->getKey(true);

        $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_HTTPHEADER => array("Content-Type" => "application/json"));
        $this->dataAccess->httpCurl($query, array($this, "xUpdateInfo"), null, $options);
    }

    public function xUpdateInfo($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();

        if ($code !== 200) {
            $this->console("mx returned http code: " . $code);
            return;
        }

        $json = json_decode($data, true);

        if ($json == false || !isset($json[0])) {
            $this->console("Error when parsing mx json.");
            return;
        }

        self::addMxInfo($json[0]);
    }

    /**
     * @param MxMap $map
     */
    public static function addMxInfo($map)
    {
        if (is_array($map)) {
            $map = MxMap::fromArray($map);
        }

        $config = \ManiaLive\Database\Config::getInstance();
        $db = \ManiaLive\Database\Connection::getConnection($config->host, $config->username, $config->password, $config->database, $config->type, $config->port);

        try {
            $sql = "UPDATE `exp_maps` SET 
                    `mx_trackID`=" . $db->quote($map->trackID) . ",
                     `mx_userID`=" . $db->quote($map->userID) . ",
                     `mx_username`=" . $db->quote($map->username) . ",
                     `mx_uploadedAt`=" . $db->quote($map->uploadedAt) . ",
                     `mx_updatedAt`=" . $db->quote($map->updatedAt) . ",
                     `mx_typeName`=" . $db->quote($map->typeName) . ",
                     `mx_mapType`=" . $db->quote($map->mapType) . ",
                     `mx_titlePack`=" . $db->quote($map->titlePack) . ",
                     `mx_styleName`=" . $db->quote($map->styleName) . ",
                     `mx_displayCost`=" . $db->quote($map->displayCost) . ",
                     `mx_modName`=" . $db->quote($map->modName) . ",
                     `mx_lightMap`=" . $db->quote($map->lightmap) . ",
                     `mx_exeVersion`=" . $db->quote($map->exeVersion) . ",
                     `mx_exeBuild`=" . $db->quote($map->exeBuild) . ",
                     `mx_environmentName`=" . $db->quote($map->environmentName) . ",
                     `mx_vehicleName`=" . $db->quote($map->vehicleName) . ",
                     `mx_unlimiterRequired`=" . (empty($map->unlimiterRequired) ? 0 : $db->quote($map->unlimiterRequired)) . ",
                     `mx_routeName`=" . $db->quote($map->routeName) . ",
                     `mx_lengthName`=" . $db->quote($map->lengthName) . ",
                     `mx_laps`=" . $db->quote($map->laps) . ",
                     `mx_difficultyName`=" . $db->quote($map->difficultyName) . ",
                     `mx_replayTypeName`=" . $db->quote($map->replayTypeName) . ",
                     `mx_replayCount`=" . $db->quote($map->replayCount) . ",
                     `mx_trackValue`=" . $db->quote($map->trackValue) . ",
                     `mx_comments`=" . $db->quote($map->comments) . ",
                     `mx_commentsCount`=" . $db->quote($map->commentCount) . ",
                     `mx_awardCount`=" . $db->quote($map->awardCount) . ",
                     `mx_hasScreenshot`=" . $db->quote(intval($map->hasScreenshot)) . ",
                     `mx_hasThumbnail`=" . $db->quote(intval($map->hasThumbnail)) . "                   
                    WHERE `challenge_uid`=" . $db->quote($map->trackUID) . ";";
            $db->execute($sql);
        } catch (\Exception $ex) {
            Console::out_error("Error: " . $ex->getMessage(), Console::b_red);
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


        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/tracks/get_track_info/id/' . $mxId;

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

        if ($code !== 200) {
            if ($code == 302) {
                $this->eXpChatSendServerMessage("#admin_error#Map author has declined the permission to download this map!", $login);
                return;
            }
            $this->eXpChatSendServerMessage("#admin_error#Mx error: $code", $login);
            return;
        }
        $map = json_decode($data, true);

        if (!$map) {
            $this->connection->chatSendServerMessage(__('Unable to retrieve track info from MX..  wrong ID..?'), $login);
            return;
        }

        $version = $this->connection->getVersion();

        if (strtolower(substr($version->titleId, 2)) != strtolower($map['TitlePack'])) {
            $this->connection->chatSendServerMessage(__('Wrong environment!'), $login);
            return;
        }

        $this->vote = array();
        $this->vote['login'] = $login;
        $this->vote['mxId'] = $mxId;

        $vote = new \Maniaplanet\DedicatedServer\Structures\Vote();
        $vote->callerLogin = $login;
        $vote->cmdName = '$0f0add $fff$o' . $map['Name'] . '$o$0f0 by $eee' . $map['Username'] . ' $0f0';
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
