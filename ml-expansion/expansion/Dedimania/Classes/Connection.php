<?php

namespace ManiaLivePlugins\eXpansion\Dedimania\Classes;

use Exception;
use ManiaLib\Utils\Singleton;
use ManiaLive\Application\Listener as AppListener;
use ManiaLive\Data\Player;
use ManiaLive\Data\Storage;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Features\Tick\Event as TickEvent;
use ManiaLive\Features\Tick\Listener as TickListener;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\DataAccess;
use ManiaLivePlugins\eXpansion\Dedimania\Classes\Request as dediRequest;
use ManiaLivePlugins\eXpansion\Dedimania\Config;
use ManiaLivePlugins\eXpansion\Dedimania\Events\Event as dediEvent;
use ManiaLivePlugins\eXpansion\Dedimania\Structures\DediMap;
use ManiaLivePlugins\eXpansion\Dedimania\Structures\DediPlayer;
use ManiaLivePlugins\eXpansion\Helpers\Helper;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;
use ManiaLivePlugins\eXpansion\Helpers\Storage as eXpStorage;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use Maniaplanet\DedicatedServer\Structures\Map;
use Maniaplanet\DedicatedServer\Xmlrpc\Request;

/**
 * Class Connection
 * @package ManiaLivePlugins\eXpansion\Dedimania\Classes
 */
class Connection extends Singleton implements AppListener, TickListener
{
    /** @var integer */
    public static $serverMaxRank = 15;

    /** @var \ManiaLivePlugins\eXpansion\Dedimania\Structures\DediMap */
    public static $dediMap = null;

    /** @var \ManiaLivePlugins\eXpansion\Dedimania\Structures\DediPlayer[] Cached players from dedimania */
    public static $players = array();

    /** @var \Maniaplanet\DedicatedServer\Connection */
    private $connection;

    /** @var Storage */
    private $storage;

    /** @var string $url dedimania url */
    private $url;

    /** @var string $sessionId dedimania session id */
    private $sessionId = null;

    /** @var DataAccess */
    public $dataAccess;

    // cached records from dedimania
    private $dediRecords = array();
    private $dediBest = 0;
    private $dediUid = null;
    private $lastUpdate = 0;

    public $forceDediSend = false;

    /**
     * Connection constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // if you are developing change port to 8081, othervice use 8082
        $this->url = "http://dedimania.net:8082/Dedimania";

        /** @var \Maniaplanet\DedicatedServer\Connection connection */
        $this->dataAccess = DataAccess::getInstance();
        $this->connection = Singletons::getInstance()->getDediConnection();
        $this->storage = Storage::getInstance();
        $this->lastUpdate = time();
        Dispatcher::register(TickEvent::getClass(), $this);
    }

    /**
     *
     */
    public function __destruct()
    {
        Dispatcher::unregister(TickEvent::getClass(), $this);
    }

    /**
     *
     */
    public function onTick()
    {
        try {
            if ($this->sessionId !== null && (time() - $this->lastUpdate) > 240) {
                $this->debug("Dedimania connection keepalive!");
                $this->updateServerPlayers($this->storage->currentMap);
                $this->lastUpdate = time();
            }
        } catch (Exception $e) {
            $this->console("OnTick Update failed: ".$e->getMessage());
        }
    }

    /**
     * dedimania.OpenSession
     * Should be called when starting the dedimania conversation
     *
     * @param string $packmask
     * @param Config $config
     * @throws Exception
     */
    public function openSession($packmask = "", $config = null)
    {
        $version = $this->connection->getVersion();

        $serverInfo = $this->connection->getDetailedPlayerInfo($this->storage->serverLogin);
        if (is_null($config)) {
            $config = Config::getInstance();
        }

        if (empty($config->login)) {
            throw new Exception("Server login is not configured!\n");
        }
        if (empty($config->code)) {
            throw new Exception("Server code is not configured! \n");
        }

        if ($packmask == "") {
            $packmask = $version->titleId;
        }

        $packmask = $this->storage->currentMap->environnement;

        $args = array(
            array(
                "Game" => "TM2",
                "Login" => strtolower($config->login),
                "Code" => $config->code,
                "Tool" => "eXpansion",
                "Version" => Core::EXP_VERSION,
                "Packmask" => $packmask,
                "ServerVersion" => $version->version,
                "ServerBuild" => $version->build,
                "Path" => $serverInfo->path,
            ),
        );

        $request = new dediRequest("dedimania.OpenSession", $args);
        $this->send($request, array($this, "xOpenSession"));
    }

    /**
     * invokes dedimania.SetChallengeTimes
     * Should be called onEndMatch
     *
     * @param Map $map from dedicated server
     * @param array $rankings from dedicated server
     * @param string $vreplay validation Replay
     * @param string $greplay ghost Replay
     *
     */
    public function setChallengeTimes(Map $map, $rankings, $vreplay, $greplay, $AllCps)
    {
        // disabled for relay server
        if (eXpStorage::getInstance()->isRelay) {
            return;
        }

        // only special maps under 6.2 seconds are allowed
        if ($map->authorTime < 6200 && strtolower($map->author) != 'nadeo') {
            $this->console("[Notice] Author time under 6.2 seconds, will not send records.");
            return;
        }

        if ($this->dediUid != $map->uId) {
            $this->console("[Warning] Map UId mismatch! Map UId differs from dedimania recieved uid for the map. Times are not sent.");
            return;
        }

        $times = array();

        foreach ($rankings as $rank) {
            if (sizeof($rank['BestCheckpoints']) > 0 && $rank['BestTime'] == end($rank['BestCheckpoints'])) {
                if ($rank['BestTime'] > 5000) { // should do sanity checks for more...
                    $times[] =
                        array(
                            "Login" => $rank['Login'],
                            "Best" => intval($rank['BestTime']),
                            "Checks" => implode(',', $rank['BestCheckpoints']),
                        );
                }
            }
        }

        usort($times, array($this, "dbsort"));

        if (sizeof($times) == 0) {
            $this->console("No new records, skipping dedimania send.");
            return;
        }

        $Vchecks = "";
        if (Core::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS) {
            $Vchecks = $AllCps;
        }

        if (empty($vreplay)) {
            $this->console("Validation replay is empty, cancel sending times.");
            return;
        }
        $base64Vreplay = new IXR_Base64($vreplay);

        $base64Greplay = "";
        if (($this->dediBest == null && sizeof($this->dediRecords['Records']) == 0) || $times[0]['Best'] < $this->dediBest) {
            $base64Greplay = new IXR_Base64($greplay);
        }

        $replays = array("VReplay" => $base64Vreplay, "VReplayChecks" => $Vchecks, "Top1GReplay" => $base64Greplay);

        $args = array($this->sessionId,$this->_getMapInfo($map),$this->_getGameMode(),$times,$replays,);

        $request = new dediRequest("dedimania.SetChallengeTimes", $args);
        $this->send($request, array($this, "xSetChallengeTimes"));
    }

    /**
     * @param \ManiaLivePlugins\eXpansion\Dedimania\Classes\Request $request
     * @param $callback
     */
    public function send(dediRequest $request, $callback)
    {
        if (function_exists('gzdeflate')) {
            $headers = array('Cache-Control: no-cache', 'Accept-Encoding: deflate', 'Content-Type: text/xml; charset=UTF-8', 'Content-Encoding: deflate', 'Keep-Alive: timeout=600, max=2000', 'Connection: Keep-Alive');

            $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_POST => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => gzdeflate($request->getXml()));
        } else {
            $headers = array('Cache-Control: no-cache', 'Content-Type: text/xml; charset=UTF-8', 'Keep-Alive: timeout=600, max=2000', 'Connection: Keep-Alive');

            $options = array(CURLOPT_CONNECTTIMEOUT => 60, CURLOPT_TIMEOUT => 300, CURLOPT_POST => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_POSTFIELDS => $request->getXml());
        }

        $this->dataAccess->httpCurl($this->url, array($this, "handleRequestResponse"), array("callback" => $callback), $options);
    }

    public function handleRequestResponse($job, $jobData)
    {
        $info = $job->getCurlInfo();
        $code = $info['http_code'];
        $data = $job->getResponse();
        $additionalData = $job->__additionalData;

        $callback = $additionalData['callback'];

        if ($code != 200) {
            $this->console("[Dedimania Error] Dedimania returned error code: " . $code);
            return;
        }

        if ($data == null || $data == false) {
            $this->console("[Dedimania Error] Can't find Message from Dedimania reply");
            return;
        }

        if (function_exists('gzinflate')) {
            $response = array('Message' => gzinflate($data));
        } else {
            $response = array('Message' => $data);
        }

        $this->_process($response, $callback);
    }

    /**
     * @return array
     */
    private function _getSrvInfo()
    {
        $info = array(
            "SrvName" => $this->storage->server->name,
            "Comment" => $this->storage->server->comment,
            "Private" => ($this->storage->server->password !== ""),
            "NumPlayers" => sizeof($this->storage->players),
            "MaxPlayers" => $this->storage->server->currentMaxPlayers,
            "NumSpecs" => sizeof($this->storage->spectators),
            "MaxSpecs" => $this->storage->server->currentMaxSpectators,
        );

        return $info;
    }

    /**
     * @param null $map
     * @return array
     * @throws Exception
     */
    private function _getMapInfo($map = null)
    {
        if ($map == null) {
            $map = $this->storage->currentMap;
        }
        if ($map instanceof Map) {
            $mapInfo = array(
                "UId" => $map->uId,
                "Name" => $map->name,
                "Environment" => $map->environnement,
                "Author" => $map->author,
                "NbCheckpoints" => $map->nbCheckpoints,
                "NbLaps" => $map->nbLaps,
            );

            return $mapInfo;
        }
        throw new Exception('error on _getMapInfo, map is in wrong format');
    }

    /**
     *
     */
    public function checkSession()
    {
        if ($this->sessionId === null) {
            $this->debug("Session id is null!");

            return;
        }
        $request = new dediRequest("dedimania.CheckSession", array($this->sessionId));
        $this->send($request, array($this, "xCheckSession"));
    }

    /**
     *  getChallengeRecords
     *  should be called onNewMap
     */
    public function getChallengeRecords()
    {
        if ($this->sessionId === null) {
            $this->debug("Session id is null!");

            return;
        }
        $players = array();
        foreach ($this->storage->players as $player) {
            if (is_object($player) && $player->login != $this->storage->serverLogin) {
                $players[] = array("Login" => $player->login, "IsSpec" => false);
            }
        }
        foreach ($this->storage->spectators as $player) {
            if (is_object($player)) {
                $players[] = array("Login" => $player->login, "IsSpec" => true);
            }
        }

        $args = array(
            $this->sessionId,
            $this->_getMapInfo(),
            $this->_getGameMode(),
            $this->_getSrvInfo(),
            $players,
        );
        $this->lastUpdate = time();

        $request = new dediRequest("dedimania.GetChallengeRecords", $args);
        $this->send($request, array($this, "xGetRecords"));
    }

    /**
     * PlayerConnect
     *
     * @param Player $player
     * @param bool $isSpec
     */
    public function playerConnect(Player $player, $isSpec)
    {

        if ($this->sessionId === null) {
            $this->console("Error: Session ID is null!");

            return;
        }

        if ($player->login == $this->storage->serverLogin) {
            $this->debug("Abort. tried to send server login.");

            return;
        }

        $args = array(
            $this->sessionId,
            $player->login,
            $player->nickName,
            $player->path,
            $isSpec,
        );

        $request = new dediRequest("dedimania.PlayerConnect", $args);
        $this->send($request, array($this, "xPlayerConnect"));
    }

    /**
     * playerMultiConnect
     *
     * @param Player[] $players
     */
    public function playerMultiConnect($players)
    {

        if ($this->sessionId === null) {
            $this->debug("Session id is null!");

            return;
        }
        if (!is_array($players)) {
            return;
        }

        $x = 0;
        $request = null;

        foreach ($players as $player) {

            if (is_a($player[0], "\\ManiaLive\\Data\\Player")) {

                if ($player[0]->login == $this->storage->serverLogin) {
                    $this->debug("[Dedimania Warning] Tried to send server login.");
                    continue;
                }

                $args = array(
                    $this->sessionId,
                    $player[0]->login,
                    $player[0]->nickName,
                    $player[0]->path,
                    $player[1],
                );

                if ($x == 0) {
                    $request = new dediRequest("dedimania.PlayerConnect", $args);
                    $x++;
                } else {
                    $request->add("dedimania.PlayerConnect", $args);
                    $x++;
                }
            }
        }

        if ($request instanceof dediRequest) {
            $this->send($request, array($this, "xPlayerMultiConnect"));
        }
    }

    /**
     * playerDisconnect
     *
     * @param string $login
     */
    public function playerDisconnect($login)
    {
        if ($this->sessionId === null) {
            $this->debug("Session id is null!");

            return;
        }
        $args = array(
            $this->sessionId,
            $login,
            "",
        );
        $request = new dediRequest("dedimania.PlayerDisconnect", $args);
        $this->send($request, array($this, "xPlayerDisconnect"));
    }

    /**
     * UpdateServerPlayers
     * Should be called Every 3 minutes + onEndChallenge.
     *
     * @param array|Map $map
     * @return null
     */
    public function updateServerPlayers($map)
    {
        if ($this->sessionId === null) {
            $this->debug("Session id is null!");

            return;
        }

        if (is_array($map)) {
            $uid = $map['UId'];
        } else {
            if (is_object($map)) {
                $uid = $map->uId;
            } else {
                $this->console("Error: updateServerPlayers: map is not array or object");

                return;
            }
        }

        $players = array();
        foreach ($this->storage->players as $player) {
            if (is_object($player) && $player->login != $this->storage->serverLogin) {
                $players[] = array("Login" => $player->login, "IsSpec" => false, "Vote" => -1);
            }
        }
        foreach ($this->storage->spectators as $player) {
            if (is_object($player)) {
                $players[] = array("Login" => $player->login, "IsSpec" => true, "Vote" => -1);
            }
        }
        $gamemode = $this->_getGameMode();

        $args = array(
            $this->sessionId,
            $this->_getSrvInfo(),
            array("UId" => $uid, "GameMode" => $gamemode),
            $players,
        );

        $request = new dediRequest("dedimania.UpdateServerPlayers", $args);
        $this->send($request, array($this, "xUpdateServerPlayers"));

        return;
    }

    /**
     * @return string
     */
    private function _getGameMode()
    {
        switch (Core::eXpGetCurrentCompatibilityGameMode()) {
            case GameInfos::GAMEMODE_LAPS:
            case GameInfos::GAMEMODE_TIMEATTACK:
                $gamemode = "TA";
                break;
            case GameInfos::GAMEMODE_CUP:
            case GameInfos::GAMEMODE_TEAM:
            case GameInfos::GAMEMODE_ROUNDS:
                $gamemode = "Rounds";
                break;
            default:
                $gamemode = "TA";
                break;
        }
        if ($this->storage->getCleanGamemodeName() == "endurocup") {
            return "TA";
        }
        return $gamemode;
    }

    /**
     * @param $dedires
     * @param $callback
     */
    public function _process($dedires, $callback)
    {
        try {

            if (is_array($dedires) && array_key_exists('Message', $dedires)) {
                /** @noinspection PhpUndefinedClassInspection */
                $msg = Request::decode($dedires['Message']);

                $errors = end($msg[1]);

                if (count($errors) > 0 && array_key_exists('methods', $errors[0])) {
                    foreach ($errors[0]['methods'] as $error) {
                        if (!empty($error['errors'])) {
                            $this->console('[Dedimania service return error] Method:'.$error['methodName']);
                            $this->console('Error string:'.$error['errors']);
                        }
                    }
                }
                // print "Actual Data\n";

                $array = $msg[1];
                unset($array[count($array) - 1]);


                if (array_key_exists("faultString", $array[0])) {
                    $this->console("Fault from dedimania server: ".$array[0]['faultString']);
                    return;
                }

                if (!empty($array[0][0]['Error'])) {
                    $this->console("Error from dedimania server: ".$array[0][0]['Error']);
                    return;
                }

                if (is_callable($callback)) {
                    call_user_func_array($callback, array($array));
                } else {
                    $this->console("[Dedimania Error] Callback-function is not valid!");
                }

            } else {
                $this->console("[Dedimania Error] Can't find Message from Dedimania reply");
            }
        } catch (Exception $e) {
            $this->console("[Dedimania Error] connection to dedimania server failed.".$e->getMessage());
        }

    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    public function dbsort($a, $b)
    {
        if ($b['Best'] <= 0) {
            return -1;
        } elseif ($a['Best'] <= 0) {// other best valid
            return 1;
        } elseif ($a['Best'] < $b['Best']) {// best a better than best b
            return -1;
        } elseif ($a['Best'] > $b['Best']) {// best b better than best a
            return 1;
        }

        return 0;
    }

    /**
     * @param $data
     */
    public function xOpenSession($data)
    {
        if (isset($data[0][0]['SessionId'])) {
            $this->sessionId = $data[0][0]['SessionId'];
            $this->console("Authentication success to dedimania server!");
            $this->debug("recieved Session key:".$this->sessionId);
            Dispatcher::dispatch(new dediEvent(dediEvent::ON_OPEN_SESSION, $this->sessionId));

            return;
        }
        if (!empty($data[0][0]['Error'])) {
            $this->console("Authentication Error occurred: ".$data[0][0]['Error']);
            return;
        }
    }

    /**
     * @param $data
     */
    public function xGetRecords($data)
    {
        $data = $data[0];

        $this->dediRecords = array();
        $this->dediUid = null;
        $this->dediBest = null;
        self::$dediMap = null;

        if (!empty($data[0]['Error'])) {
            $this->console("Error from dediserver: ".$data[0]['Error']);
            return;
        }


        $this->dediUid = $data[0]['UId'];
        $this->dediRecords = $data[0];
        self::$serverMaxRank = intval($data[0]['ServerMaxRank']);
        $maplimit = intval($data[0]['ServerMaxRank']);

        if ($data[0]['Records'] && count($data[0]['Records']) > 0) {
            $maplimit = count($data[0]['Records']);
        }

        self::$dediMap = new DediMap($data[0]['UId'], $maplimit, $data[0]['AllowedGameModes']);


        if (!$data[0]['Records']) {
            $this->debug("No records found.");
            return;
        }

        if (!empty($data[0]['Records'][0]['Best'])) {
            $this->dediBest = $data[0]['Records'][0]['Best'];
        }

        Dispatcher::dispatch(new dediEvent(dediEvent::ON_GET_RECORDS, $data[0]));
    }

    /**
     * @param $data
     */
    public function xUpdateServerPlayers($data)
    {

    }

    /**
     * @param $data
     */
    public function xSetChallengeTimes($data)
    {
        $this->console("Sending times new times: \$0f0Success");
        if ($this->forceDediSend) {
            $this->getChallengeRecords();
            $this->forceDediSend = false;
        }
    }

    /**
     * @param $data
     */
    public function xCheckSession($data)
    {

    }

    /**
     * @param $data
     */
    public function xPlayerConnect($data)
    {
        $dediplayer = DediPlayer::fromArray($data[0][0]);
        self::$players[$dediplayer->login] = $dediplayer;

        if ($dediplayer->banned) {
            try {
                $player = $this->storage->getPlayerObject($dediplayer->login);
                $this->connection->chatSendServerMessage(
                    "Player".$player->nickName.'$z$s$fff['.$player->login.'] is $f00BANNED$fff from dedimania.'
                );
            } catch (Exception $e) {

            }
        }
        Dispatcher::dispatch(new dediEvent(dediEvent::ON_PLAYER_CONNECT, $dediplayer));
    }

    /**
     * @param $data
     */
    public function xPlayerMultiConnect($data)
    {
        foreach ($data as $player) {
            $player = $player[0];
            $dediPlayer = DediPlayer::fromArray($player);
            self::$players[$dediPlayer->login] = $dediPlayer;

            if ($dediPlayer->banned) {
                try {
                    $pla = $this->storage->getPlayerObject($dediPlayer->login);
                    $this->connection->chatSendServerMessage(
                        "Player".$pla->nickName.'$z$s$fff['.$pla->login.'] is $f00BANNED$fff from dedimania.'
                    );
                } catch (Exception $e) {

                }
            }
            Dispatcher::dispatch(new dediEvent(dediEvent::ON_PLAYER_CONNECT, $dediPlayer));
        }
    }

    /**
     * @param $data
     */
    public function xPlayerDisconnect($data)
    {
        Dispatcher::dispatch(new dediEvent(dediEvent::ON_PLAYER_DISCONNECT, $data[0][0]['Login']));
    }

    /**
     * @param $message
     */
    public function debug($message)
    {
        if (DEBUG) {
            $this->console($message);
        }
    }

    /**
     * @param $message
     */
    public function console($message)
    {
        Helper::log("$message", array('Dedimania/Connection'));
    }

    /**
     *
     */
    public function onInit()
    {

    }

    /**
     *
     */
    public function onRun()
    {

    }

    /**
     *
     */
    public function onPostLoop()
    {

    }

    /**
     *
     */
    public function onTerminate()
    {

    }

    /**
     *
     */
    public function onPreLoop()
    {

    }
}
