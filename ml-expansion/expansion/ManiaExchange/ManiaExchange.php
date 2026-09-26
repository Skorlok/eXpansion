<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Helpers\GBXChallMapFetcher;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLivePlugins\eXpansion\Helpers\Storage as ExpStorage;
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

    /** @var Window */
    private $mxInfosWindow;

    /** @var Window */
    private $mxUpdateWindow;

    /** @var Window */
    private $mxSearchWindow;

    /** Number of maps asked to MX in a single request */
    const MX_UPDATE_CHUNK_SIZE = 50;

    /** Fields asked to the MX map api for the update window */
    public static $mxUpdateFields = "fields=MapUid,MapId,TitlePack,Environment,VehicleName,GbxMapName,Difficulty,MoodFull,Tags,Length,AwardCount,Uploader.Name,UpdatedAt";

    /** Fields asked to the MX map api for the search window */
    public static $mxSearchFields = "fields=MapId,TitlePack,Environment,VehicleName,GbxMapName,Difficulty,MoodFull,Tags,Length,AwardCount,Uploader.Name";

    /** Entries of the "style" dropdown of the search window, the index is the MX tag id */
    public static $styleOptions = array("All", "Race", "Fullspeed", "Tech", "RPG", "LOL", "PressForward", "SpeedTech", "Multilap", "Offroad");

    /** Entries of the "length" dropdown of the search window */
    public static $lengthOptions = array("All", "0-15sec", "15-30sec", "30-45sec", "45-1min", "1min+");

    /** @var StdClass $mxInfo */
    public static $mxInfo = null;
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

        Menu::addMenuItem("ManiaExchange",
            array("Maps" => array(null, array(
                "ManiaExchange" => array(Permission::MAP_ADD_MX, 'exp:eXpansion.ManiaExchange:mxSearch')
            )))
        );
    }

    public function eXpOnReady()
    {
        $this->registerManialinkCallback('mxSearch');
        $this->registerManialinkCallback('mxSearchDo', true);
        $this->registerManialinkCallback('mxSearchAuthor', true, true);
        $this->registerManialinkCallback('addMap', false, true);
        $this->registerManialinkCallback('mxVote', false, true);
        $this->registerManialinkCallback('mxUpdateMap', false, true);
        
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

        $this->enableDedicatedEvents();

        ManiaExchange::$openInfosAction = array($this, 'showMxInfos');

        $this->mxInfosWindow = new Window("ManiaExchange\Gui\Windows\MxInfos.xml");
        $this->mxInfosWindow->setName("MX Infos");
        $this->mxInfosWindow->setSize(220, 100);

        $this->mxUpdateWindow = new Window("ManiaExchange\Gui\Windows\MxUpdate.xml");
        $this->mxUpdateWindow->setName("MX Update");
        $this->mxUpdateWindow->setSize(210, 100);

        $this->mxSearchWindow = new Window("ManiaExchange\Gui\Windows\MxSearch.xml");
        $this->mxSearchWindow->setName("MX Search");
        $this->mxSearchWindow->setSize(210, 100);
        $this->mxSearchWindow->setTitle('ManiaExchange');

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
        self::$mxReplays = array();

        $fields = "fields=MapId,MapUid,Name,GbxMapName,UploadedAt,UpdatedAt,Uploader.Name,Tags,Images,MapType,MoodFull,Routes,Difficulty,Length,AwardCount,TitlePack,ReplayCount,AuthorComments";

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
        if ($json == false || !array_key_exists("Results", $json) || !isset($json['Results'][0])) {
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
        if ($this->expStorage->simpleEnviTitle == "TM" && self::$mxReplays != null && count(self::$mxReplays) > 0 && $this->config->announceMxRecord) {
            $this->eXpChatSendServerMessage($this->msg_worldRec, $login, array(Time::fromTM(self::$mxReplays[0]->ReplayTime), self::$mxReplays[0]->Username));
        }
    }

    public function showMxInfos($login)
    {
        if (!self::$mxInfo) {
            $this->eXpChatSendServerMessage($this->msg_not_found, $login);
            return;
        }

        $replays = array();
        foreach (self::$mxReplays as $rec_nb => $rec) {
            $pass = $this->storage->server->password ? ":" . $this->storage->server->password : "";
            $link = '$h[https://skorlok.com/r.php?login=' . $this->storage->serverLogin . $pass
                . '&tp=' . $this->expStorage->titleId . '&mx=t&replay=' . $rec->ReplayId . ']';
            $replays[] = array(
                'name' => $rec->Username,
                'time' => Time::fromTM($rec->ReplayTime),
                'link' => $link,
            );
        }

        $baseUrl = "https://" . strtolower($this->expStorage->simpleEnviTitle) . ".mania.exchange/";
        if (self::$mxInfo->images && isset(self::$mxInfo->images[0])
            && self::$mxInfo->images[0]['Width'] > 0 && self::$mxInfo->images[0]['Height'] > 0) {
            $imageUrl = $baseUrl . "mapimage/" . self::$mxInfo->mapId . "/1?hq=true&.webp";
        } else {
            $imageUrl = $baseUrl . "mapimage/" . self::$mxInfo->mapId . "/1?hq=true&.png";
        }
        $mxPageUrl = $baseUrl . "mapshow/" . self::$mxInfo->mapId;

        $mxRows = array(
            array('key' => 'Name:',       'value' => strval(self::$mxInfo->name)),
            array('key' => 'Author:',     'value' => strval(self::$mxInfo->getUploader())),
            array('key' => 'Uploaded:',   'value' => substr(str_replace('T', " ", self::$mxInfo->uploadedAt), 0, 16)),
            array('key' => 'Updated:',    'value' => substr(str_replace('T', " ", self::$mxInfo->updatedAt), 0, 16)),
            array('key' => 'Awards:',     'value' => strval(self::$mxInfo->awardCount)),
            array('key' => 'Difficulty:', 'value' => strval(self::$mxInfo->getDifficulty())),
            array('key' => 'Length:',     'value' => strval(self::$mxInfo->getLength())),
            array('key' => 'Mood:',       'value' => strval(self::$mxInfo->moodFull)),
            array('key' => 'Style:',      'value' => strval(self::$mxInfo->getStyle())),
            array('key' => 'TitlePack:',  'value' => strval(self::$mxInfo->titlePack)),
            array('key' => 'Routes:',     'value' => strval(self::$mxInfo->getRouteType())),
            array('key' => 'MapType:',    'value' => strval(self::$mxInfo->mapType)),
        );

        $rawText = self::$mxInfo->authorComments !== null ? self::$mxInfo->authorComments : '';
        $description = $this->mxInfosWindow->handleSpecialChars($rawText);
        $description = preg_replace('#\[url=#i', '$L[', $description);
        $description = preg_replace('#\[/url\]#i', '$L', $description);
        $description = preg_replace('#\[[a-z=]+\]#Ui', '', $description);
        $description = preg_replace('#\[/[a-z]+\]#Ui', '', $description);
        $description = preg_replace('#<.*>#Ui', '', $description);
        $description = preg_replace('#</.*>#Ui', '', $description);

        $hasLocalMap = false;
        $uid         = '';
        $fileName    = '';
        $localRows   = array();
        $rightRows   = array();
        $modUrl      = null;
        $songUrl     = null;

        $map     = ArrayOfObj::getObjbyPropValue($this->storage->maps, "uId", $this->storage->currentMap->uId);
        $mapPath = $this->connection->getMapsDirectory();

        if ($map !== false && file_exists($mapPath . DIRECTORY_SEPARATOR . $map->fileName)) {
            $hasLocalMap      = true;
            $map->{"nick"}    = "n/a";
            $gbxInfo          = null;

            try {
                $fetcher = new GBXChallMapFetcher(true, false, false);
                $fetcher->processFile($mapPath . DIRECTORY_SEPARATOR . $map->fileName);
                $gbxInfo           = $fetcher;
                $map->mood         = $gbxInfo->mood;
                $map->nbLap        = $gbxInfo->nbLaps;
                $map->nbCheckpoint = $gbxInfo->nbChecks;
                $map->authorTime   = $gbxInfo->authorTime;
                $map->silverTime   = $gbxInfo->silverTime;
                $map->bronzeTime   = $gbxInfo->bronzeTime;
                $map->songFile     = $gbxInfo->songFile;
                $map->modName      = $gbxInfo->modName;
                $map->{"nick"}     = $gbxInfo->authorNick;
                $modUrl            = $gbxInfo->modUrl ? $gbxInfo->modUrl : null;
                $songUrl           = $gbxInfo->songUrl ? $gbxInfo->songUrl : null;
            } catch (\Exception $ex) {
                \ManiaLive\Utilities\Console::println("Info: Map not found or error while reading gbx info for map.");
            }

            $uid      = $map->uId;
            $fileName = $map->fileName;

            $date = new \DateTime();
            $date->setTimestamp((int)$map->addTime);

            $localRows = array(
                array('key' => 'Name:',        'value' => strval($map->name)),
                array('key' => 'Author:',      'value' => strval($map->author)),
                array('key' => 'Author Nick:', 'value' => strval($map->nick)),
                array('key' => 'Mood:',        'value' => strval($map->mood)),
                array('key' => 'Map Style:',   'value' => strval($map->mapStyle)),
                array('key' => 'Map Type:',    'value' => strval($map->mapType)),
                array('key' => 'Environment:', 'value' => strval($map->environnement)),
                array('key' => 'Car type:',    'value' => $gbxInfo ? strval($gbxInfo->vehicle) : ''),
            );

            $rightRows = array(
                array('key' => 'Add Date:',     'value' => $date->format("d.m.Y")),
                array('key' => 'Author Time:',  'value' => Time::fromTM($map->authorTime)),
                array('key' => 'Gold Time:',    'value' => Time::fromTM($map->goldTime)),
                array('key' => 'Silver Time:',  'value' => Time::fromTM($map->silverTime)),
                array('key' => 'Bronze Time:',  'value' => Time::fromTM($map->bronzeTime)),
                array('key' => 'Checkpoints:',  'value' => strval($map->nbCheckpoint)),
                array('key' => 'Laps:',         'value' => strval($map->nbLap)),
                array('key' => 'Display Cost:', 'value' => strval($map->copperPrice)),
                array('key' => 'Song Name:',    'value' => strval($map->songFile)),
                array('key' => 'Mod Name:',     'value' => strval($map->modName)),
            );
        }

        $this->mxInfosWindow->setTitle('ManiaExchange Map Infos');
        $this->mxInfosWindow->setParam("replays",     $replays);
        $this->mxInfosWindow->setParam("imageUrl",    $imageUrl);
        $this->mxInfosWindow->setParam("mxPageUrl",   $mxPageUrl);
        $this->mxInfosWindow->setParam("description", $description);
        $this->mxInfosWindow->setParam("mxRows",      $mxRows);
        $this->mxInfosWindow->setParam("hasLocalMap", $hasLocalMap);
        $this->mxInfosWindow->setParam("uid",         $uid);
        $this->mxInfosWindow->setParam("fileName",    $fileName);
        $this->mxInfosWindow->setParam("localRows",   $localRows);
        $this->mxInfosWindow->setParam("rightRows",   $rightRows);
        $this->mxInfosWindow->setParam("modUrl",      $modUrl);
        $this->mxInfosWindow->setParam("songUrl",     $songUrl);
        $this->mxInfosWindow->show($login);
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
        
        Maps::$dbMapsByUid = array();

        $chunks = array_chunk($this->storage->maps, self::MX_UPDATE_CHUNK_SIZE);

        if (empty($chunks)) {
            $this->eXpChatSendServerMessage("#error#Not enough maps to check for updates.", $login);
            return;
        }

        $this->eXpChatSendServerMessage("#mx#Recieving maps info, please wait...", $login);
        $this->mxUpdateQuery($login, $chunks, 0, array());
    }

    private function mxUpdateQuery($login, $chunks, $index, $maps)
    {
        $uids = "";
        foreach ($chunks[$index] as $map) {
            $uids .= $map->uId . ",";
        }

        $key = $this->config->key ? "&key=" . $this->config->key : "";

        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/maps?' . self::$mxUpdateFields . "&uid=" . rtrim($uids, ",") . $key;

        $options = array(CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => array("Content-Type" => "application/json"));

        $this->dataAccess->httpCurl($query, array($this, "xMxUpdate"), array("login" => $login, "chunks" => $chunks, "index" => $index, "maps" => $maps), $options);
    }

    public function xMxUpdate($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();

        $additionalData = $job->__additionalData;

        $login  = $additionalData['login'];
        $chunks = $additionalData['chunks'];
        $index  = $additionalData['index'];
        $maps   = $additionalData['maps'];

        if ($code !== 200) {
            $this->eXpChatSendServerMessage("#error#MX returned error code $code", $login);
            return;
        }

        $json = json_decode($data, true);

        if (!$json || !isset($json["Results"])) {
            $this->eXpChatSendServerMessage("#error#Error while processing json data from MX.", $login);
            return;
        }

        foreach ($json["Results"] as $map) {
            $maps[] = MxMap::fromArray($map);
        }

        $index++;

        if (array_key_exists($index, $chunks)) {
            $this->eXpChatSendServerMessage("#mx#Processing chunk %s/%s", $login, array($index + 1, count($chunks)));
            $this->mxUpdateQuery($login, $chunks, $index, $maps);
            return;
        }

        $this->showMxUpdateResults($login, $maps);
    }

    private function showMxUpdateResults($login, $maps)
    {
        $mapsByUid = array();
        foreach ($this->storage->maps as $map) {
            $mapsByUid[$map->uId] = $map;
        }

        $mapDir = $this->connection->getMapsDirectory();

        $items = array();
        $data  = array();
        $i     = 0;

        foreach ($maps as $map) {
            if (!array_key_exists($map->mapUid, $mapsByUid)) {
                continue;
            }

            $fileCreated = filectime($mapDir . DIRECTORY_SEPARATOR . $mapsByUid[$map->mapUid]->fileName);
            $mapUpdated  = strtotime($map->updatedAt);

            if ($fileCreated > $mapUpdated) {
                continue;
            }

            $pack = str_replace("TM", "", $map->titlePack);
            if (empty($pack) || $pack == "TMAll") {
                $pack = $map->getEnvironment();
            }

            $vehicle = "";
            if ($map->vehicleName) {
                $vehicle = str_replace("Car", "", $map->vehicleName);
                $vehicle = ($vehicle == $pack) ? "" : "Car: " . $vehicle;
            }

            $items[$i] = array($pack, $map->gbxMapName, $map->getDifficulty(), $map->getStyle(), $vehicle, '$fff' . $map->getUploader(), $map->moodFull, $map->getLength(), $map->awardCount);
            $data[$i] = array(-1, -1, -1, -1, -1, -1, -1, -1, -1, 'exp:eXpansion.ManiaExchange:mxUpdateMap:' . $map->mapId . ':' . $map->mapUid);
            $i++;
        }

        if ($i <= 0) {
            $this->eXpChatSendServerMessage("#mx#All maps up-to-date!", $login);
            return;
        }

        $this->mxUpdateWindow->setTitle('Update Maps (%s)', array($i));
        $this->mxUpdateWindow->setParam("mapItems",    $items);
        $this->mxUpdateWindow->setParam("mapData",     $data);
        $this->mxUpdateWindow->show($login);
    }

    public function mxUpdateMap($login, $target)
    {
        if (!AdminGroups::hasPermission($login, Permission::MAP_ADD_MX)) {
            $this->eXpChatSendServerMessage("#error#You don't have permission to run this command.", $login);
            return;
        }

        $target = explode(':', str_replace('–', '-', $target), 2);
        if (count($target) < 2) {
            return;
        }

        $mapId  = (int)$target[0];
        $mapUid = $target[1];

        $map = ArrayOfObj::getObjbyPropValue($this->storage->maps, "uId", $mapUid);
        if ($map) {
            $this->connection->removeMap($map->fileName);
        }

        $this->addMap($login, $mapId);
    }

    public function mxSearch($login, $search = "", $author = "")
    {
        $this->mxSearchRun($login, $search, $author, 0, 0, false);
    }

    public function mxSearchDo($login, $params = array())
    {
        $this->mxSearchRun(
            $login,
            isset($params['mapName']) ? $params['mapName'] : "",
            isset($params['author']) ? $params['author'] : "",
            isset($params['style']) ? intval($params['style']) : 0,
            isset($params['length']) ? intval($params['length']) : 0,
            isset($params['filterAllPacks']) && $params['filterAllPacks'] == '1'
        );
    }

    public function mxSearchAuthor($login, $author, $params = array())
    {
        $this->mxSearchRun(
            $login,
            isset($params['mapName']) ? $params['mapName'] : "",
            str_replace('–', '-', $author),
            isset($params['style']) ? intval($params['style']) : 0,
            isset($params['length']) ? intval($params['length']) : 0,
            isset($params['filterAllPacks']) && $params['filterAllPacks'] == '1'
        );
    }

    private function mxSearchRun($login, $trackname, $author, $styleIdx, $lengthIdx, $filter)
    {
        $this->showMxSearch($login, array(), array(), $styleIdx, $lengthIdx, $filter, $trackname, $author, "Searching, please wait");

        $out = "";
        if ($trackname != "") {
            $out .= "&name=" . rawurlencode($trackname);
        }
        if ($author != "") {
            $out .= "&author=" . rawurlencode($author);
        }
        if ($styleIdx > 0) {
            $out .= "&tag=" . $styleIdx;
        }

        if ($this->expStorage->simpleEnviTitle != ExpStorage::TITLE_SIMPLE_SM) {
            switch ($lengthIdx) {
                case 1:
                    $out .= "&lengthmin=0&lengthmax=15000";
                    break;
                case 2:
                    $out .= "&lengthmin=15000&lengthmax=30000";
                    break;
                case 3:
                    $out .= "&lengthmin=30000&lengthmax=45000";
                    break;
                case 4:
                    $out .= "&lengthmin=45000&lengthmax=60000";
                    break;
                case 5:
                    $out .= "&lengthmin=60000";
                    break;
            }
        }

        if (!$filter) {
            $titlePack = explode("@", $this->expStorage->titleId);
            $out .= "&titlepack=" . $titlePack[0];
        }

        $query = 'https://' . strtolower($this->expStorage->simpleEnviTitle) . '.mania.exchange/api/maps?' . self::$mxSearchFields . "&" . $out . '&order1=0&count=200' . $this->getKey(true);

        $options = array(CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => array("Content-Type" => "application/json"));

        $this->dataAccess->httpCurl($query, array($this, "xMxSearch"), array("login" => $login, "styleIdx" => $styleIdx, "lengthIdx" => $lengthIdx, "filter" => $filter, "searchMapName" => $trackname, "searchAuthor" => $author), $options);
    }

    /**
     * Answer of a search request.
     *
     * @param Curl $job
     * @param      $jobData
     */
    public function xMxSearch($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();

        $additionalData = $job->__additionalData;

        $login     = $additionalData['login'];
        $styleIdx  = $additionalData['styleIdx'];
        $lengthIdx = $additionalData['lengthIdx'];
        $filter    = $additionalData['filter'];
        $searchMapName = $additionalData['searchMapName'];
        $searchAuthor  = $additionalData['searchAuthor'];

        if ($code !== 200) {
            $this->showMxSearch($login, array(), array(), $styleIdx, $lengthIdx, $filter, $searchMapName, $searchAuthor, "search returned a http error " . $code);
            return;
        }

        if (!$data) {
            $this->showMxSearch($login, array(), array(), $styleIdx, $lengthIdx, $filter, $searchMapName, $searchAuthor, "search returned no data");
            return;
        }

        $json = json_decode($data, true);

        if (isset($json[0]) && !isset($json['Results'])) {
            $json = array('Results' => $json);
        }

        if (!$json || !array_key_exists("Results", $json)) {
            $this->showMxSearch($login, array(), array(), $styleIdx, $lengthIdx, $filter, $searchMapName, $searchAuthor, "Error while processing json data from MX.");
            return;
        }

        $maps = MxMap::fromArrayOfArray($json['Results']);

        if (empty($maps)) {
            $this->showMxSearch($login, array(), array(), $styleIdx, $lengthIdx, $filter, $searchMapName, $searchAuthor, "No maps found with this search terms.");
            return;
        }

        $items = array();
        $rows  = array();
        $i     = 0;

        foreach ($maps as $map) {
            $pack = str_replace("TM", "", $map->titlePack);
            if (empty($pack) || $pack == "TMAll") {
                $pack = $map->getEnvironment();
            }

            $vehicle = "";
            if ($map->vehicleName) {
                $vehicle = str_replace("Car", "", $map->vehicleName);
                $vehicle = ($vehicle == $pack) ? "" : "Car: " . $vehicle;
            }

            $items[$i] = array($pack, $map->gbxMapName, $map->getDifficulty(), $map->getStyle(), $vehicle, '$fff' . $map->getUploader(), $map->moodFull, $map->getLength(), $map->awardCount);
            $rows[$i] = array(
                -1, -1, -1, -1, -1,
                'exp:eXpansion.ManiaExchange:mxSearchAuthor:' . $map->getUploader(),
                -1, -1, -1,
                'exp:eXpansion.ManiaExchange:mxVote:' . $map->mapId,
                'exp:eXpansion.ManiaExchange:addMap:' . $map->mapId,
                'exp:eXpansion.MapSuggestion:addMapToWish:' . $map->mapId,
            );
            $i++;
        }

        $this->showMxSearch($login, $items, $rows, $styleIdx, $lengthIdx, $filter, $searchMapName, $searchAuthor);
    }

    private function showMxSearch($login, $items, $rows, $styleIdx, $lengthIdx, $filter, $searchMapName, $searchAuthor, $notice = null)
    {
        $this->mxSearchWindow->setParam("styleOptions",   self::$styleOptions);
        $this->mxSearchWindow->setParam("lengthOptions",  self::$lengthOptions);
        $this->mxSearchWindow->setParam("styleSelected",  $styleIdx);
        $this->mxSearchWindow->setParam("lengthSelected", $lengthIdx);
        $this->mxSearchWindow->setParam("filterAllPacks", $filter);
        $this->mxSearchWindow->setParam("hideInstall",    !AdminGroups::hasPermission($login, Permission::MAP_ADD_MX));
        $this->mxSearchWindow->setParam("hideQueue",      !$this->config->mxVote_enable);
        $this->mxSearchWindow->setParam("mapItems",       $items);
        $this->mxSearchWindow->setParam("mapData",        $rows);
        $this->mxSearchWindow->setParam("searchMapName",  $searchMapName);
        $this->mxSearchWindow->setParam("searchAuthor",   $searchAuthor);
        $this->mxSearchWindow->setParam("noticeText",     $notice);
        $this->mxSearchWindow->setParam("hideNotice",     empty($notice));
        $this->mxSearchWindow->setParam("hideSuggest",    ($this->isPluginLoaded("\\ManiaLivePlugins\\eXpansion\\MapSuggestion\\MapSuggestion")) ? false : true);
        $this->mxSearchWindow->show($login);
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
        $mapName = $this->cleanMapName(trim(mb_convert_encoding(substr(Formatting::stripStyles($gbxReader->name), 0, 40), "7bit", "UTF-8")));

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
        if ($this->mxSearchWindow instanceof Window) {
            $this->mxSearchWindow->erase();
        }
        $this->mxSearchWindow = null;
        if ($this->mxInfosWindow instanceof Window) {
            $this->mxInfosWindow->erase();
        }
        $this->mxInfosWindow = null;
        if ($this->mxUpdateWindow instanceof Window) {
            $this->mxUpdateWindow->erase();
        }
        $this->mxUpdateWindow = null;
        AdminGroups::removeAdminCommand($this->cmd_add);
        AdminGroups::removeAdminCommand($this->cmd_update);
        AdminGroups::removeAdminCommand($this->cmd_random);
        AdminGroups::removeAdminCommand($this->cmd_pack);
    }
}
