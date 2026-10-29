<?php

namespace ManiaLivePlugins\eXpansion\Helpers;

use ManiaLivePlugins\eXpansion\Helpers\Formatting;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\Application\Listener as AppListener;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\DedicatedApi\Config as DedicatedConfig;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Utilities\Console as ManiaLiveConsole;
use ManiaLive\Utilities\Singleton;
use Maniaplanet\DedicatedServer\Connection;
use Maniaplanet\DedicatedServer\Structures\Map;
use Maniaplanet\DedicatedServer\Structures\PlayerInfo;
use Maniaplanet\DedicatedServer\Structures\Version;
use Maniaplanet\DedicatedServer\Xmlrpc\IndexOutOfBoundException;
use ManiaLivePlugins\eXpansion\Core\MetaData as CoreMeta;
use ManiaLivePlugins\eXpansion\Database\Structures\DbPlayer;
use ManiaLivePlugins\eXpansion\Helpers\StorageEvent as Event;
use ManiaLivePlugins\eXpansion\Helpers\PlayerStorage as Player;

class Storage extends Singleton implements ServerListener, AppListener
{

    const TITLE_SIMPLE_TM = 'TM';

    const TITLE_SIMPLE_SM = 'SM';

    /** @var Player[] */
	public $players = array();

	/** @var Player[] */
	public $spectators = array();

	/** @var Player[] */
	public $disconnectedPlayers = array();

	/** @var \Maniaplanet\DedicatedServer\Structures\Map[] */
	public $maps;

	/** @var \Maniaplanet\DedicatedServer\Structures\Map */
	public $currentMap;

	/** @var \Maniaplanet\DedicatedServer\Structures\Map */
	public $nextMap;

	/** @var \Maniaplanet\DedicatedServer\Structures\ServerOptions */
	public $server;

	/** @var \Maniaplanet\DedicatedServer\Structures\GameInfos */
	public $gameInfos;

	/** @var \Maniaplanet\DedicatedServer\Structures\Status */
	public $serverStatus;

	/** @var string */
	public $serverLogin;

	/** @var \Maniaplanet\DedicatedServer\Connection */
	private $connection;

	/**
	 * Player's checkpoints
	 */
	private $checkpoints = array();

    // eXpansion

    /** @var DbPlayer[] */
    public $dbPlayers = array();

    /** @var Version */
    public $version;

    /** @var string */
    public $titleId;

    /** @var String */
    public $simpleEnviTitle;

    /** @var bool */
    public $isRelay;

    /** @var array */
    public $playerTimes = array();

    /** @var boolean */
    public $isRemoteControlled = false;

    private $currentRankings = array();

    private $startTime;

    private $dediUpTime;

    protected function __construct()
    {
        Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ON_PRE_LOOP | AppEvent::ON_POST_LOOP);
        Dispatcher::register(ServerEvent::getClass(), $this, ServerEvent::ALL);

        /** @var \ManiaLive\DedicatedApi\Config $config */
		$config = \ManiaLive\DedicatedApi\Config::getInstance();
		$this->connection = Connection::factory($config->host, $config->port, $config->timeout, $config->user, $config->password);
		$this->serverStatus = $this->connection->getStatus();

		$infos = $this->connection->getPlayerList(-1, 0);
		foreach ($infos as $info) {
			try {
				$details = $this->connection->getDetailedPlayerInfo($info->login);
				$player = new Player();
				$player->merge($details);
				$player->merge($info);
				if ($player->isManagedByAnOtherServer) {
				} else {
					if ($player->spectator) {
						$this->spectators[$player->login] = $player;
					} else {
						$this->players[$player->login] = $player;
					}
				}
			} catch (\Exception $e) {
			}
		}

		$this->loadMaplist();

		// Fetch current map and next map
		$this->currentMap = $this->connection->getCurrentMapInfo();
		if (isset($this->maps[$this->connection->getNextMapIndex()])) {
			$this->nextMap = $this->maps[$this->connection->getNextMapIndex()];
		} else {
			$this->nextMap = null;
		}

		$this->server = $this->connection->getServerOptions();
		$this->gameInfos = $this->connection->getCurrentGameInfo();
		try {
			$this->serverLogin = $this->connection->getSystemInfo()->serverLogin;
		} catch (\Exception $e) {
			$this->serverLogin = null;
		}

        // eXpansion

        $this->version = $this->connection->getVersion();

        $this->titleId = $this->version->titleId;

        $this->isRelay = $this->connection->isRelayServer();

        $this->simpleEnviTitle = $this->getSimpleTitleByEnvironment($this->currentMap->environnement);

        $this->startTime = time();

        /** @var DedicatedConfig $dedicatedConfig */
        $dedicatedConfig = DedicatedConfig::getInstance();
        if ($dedicatedConfig->host == "localhost" || $dedicatedConfig->host == "127.0.0.1" || !$dedicatedConfig->isRemote) {
            $this->isRemoteControlled = false;
        } else {
            $this->isRemoteControlled = true;
        }

        $this->dediUpTime = $this->connection->getNetworkStats()->uptime;
    }

    private function loadMaplist()
	{
		// Fetch all maps in chunks; getMapList(-1, 0) breaks above ~20 000 maps.
		// Max iterations = 500 guards against a server bug returning the same chunk forever (500 * 1000 = 500 000 maps).
		$this->maps = array();
		$chunkSize  = 1000;
		$offset     = 0;
		for ($i = 0; $i < 500; $i++) {
			try {
				$chunk = $this->connection->getMapList($chunkSize, $offset);
			} catch (\Exception $e) {
				break;
			}
			if (empty($chunk)) {
				break;
			}
			foreach ($chunk as $map) {
				$this->maps[] = $map;
			}
			// Server returned fewer maps than requested: this was the last page
			if (count($chunk) < $chunkSize) {
				break;
			}
			$offset += $chunkSize;
		}
	}

	public function onPostLoop()
	{
		$this->disconnectedPlayers = array();
	}

	public function onPlayerConnect($login, $isSpectator)
	{
		try {
			$info = $this->connection->getPlayerInfo($login, 1);
			$details = $this->connection->getDetailedPlayerInfo($login);
			$player = new Player();
			$player->merge($details);
			$player->merge($info);
			if ($player->isManagedByAnOtherServer) {
			} else {
				if ($isSpectator) {
					$this->spectators[$login] = $player;
				} else {
					$this->players[$login] = $player;
				}
			}
		} catch (\Exception $ex) {
			throw new \ManiaLive\Event\StopperException("Player alrady disconnected", 1, $ex);
		}
	}

	public function onPlayerDisconnect($login, $disconnectionReason)
	{
		if ($this->getPlayerObject($login) != null) {
			$this->disconnectedPlayers[$login] = $this->getPlayerObject($login);

			if (isset($this->spectators[$login])) {
				unset($this->spectators[$login]);
			} else if (isset($this->players[$login])) {
				unset($this->players[$login]);
			}
		} else {
			throw new \ManiaLive\Event\StopperException("Player never connected", 1);
		}
	}

	public function onServerStart()
	{
		$this->serverLogin = $this->connection->getMainServerPlayerInfo()->login;
		$this->loadMaplist();
	}

	public function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$this->checkpoints = array();

		$oldMap = $this->currentMap;
		$this->currentMap = Map::fromArray($map);
		if ($oldMap) {
			ManiaLiveConsole::printlnFormatted('Map change: ' . Formatting::stripStyles($oldMap->name) . ' -> ' . Formatting::stripStyles($this->currentMap->name));
		}

		$this->gameInfos = $this->connection->getCurrentGameInfo();
		$this->server = $this->connection->getServerOptions();

        // eXpansion

        $this->playerTimes = array();

        $this->loadBlackList();
        $this->loadGuestList();
	}

	public function onStatusChanged($statusCode, $statusName)
	{
		$this->serverStatus->code = $statusCode;
		$this->serverStatus->name = $statusName;
	}

	public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
	{
		if ($checkpointIndex == 0) {
			$this->checkpoints[$login] = array();
		} else if ($checkpointIndex > 0 && (!isset($this->checkpoints[$login]) || !isset($this->checkpoints[$login][$checkpointIndex - 1]) || $timeOrScore < $this->checkpoints[$login][$checkpointIndex - 1])) {
			return;
		}

		$this->checkpoints[$login][$checkpointIndex] = $timeOrScore;

		if ($this->currentMap->nbCheckpoints && ($checkpointIndex + 1) % $this->currentMap->nbCheckpoints == 0) {
			$player = $this->getPlayerObject($login);
			if ($player) {
				$checkpoints = array_slice($this->checkpoints[$login], -$this->currentMap->nbCheckpoints);

				if ($checkpointIndex >= $this->currentMap->nbCheckpoints) {
					$offset = $this->checkpoints[$login][($checkpointIndex - $this->currentMap->nbCheckpoints)];
					for ($i = 0; $i < count($checkpoints); ++$i) {
						$checkpoints[$i] -= $offset;
					}

					$timeOrScore -= $offset;
				}

				if (end($checkpoints) != $timeOrScore) {
					return;
				}

				Dispatcher::dispatch(new Event(Event::ON_PLAYER_FINISH_LAP, $player, end($checkpoints), $checkpoints, $curLap));
			}
		}
	}

	public function onMapListModified($curMapIndex, $nextMapIndex, $isListModified)
	{
		if ($isListModified) {
			// Build uid -> storage key index once (O(n)) to avoid O(n²) from findMap inside the chunk loop.
			$uidIndex = array();
			foreach ($this->maps as $key => $map) {
				if (isset($map->uId)) {
					$uidIndex[$map->uId] = $key;
				}
			}

			$maps      = array();
			$chunkSize = 1000;
			$offset    = 0;
			for ($i = 0; $i < 500; $i++) {
				try {
					$chunk = $this->connection->getMapList($chunkSize, $offset);
				} catch (\Exception $e) {
					break;
				}
				if (empty($chunk)) {
					break;
				}
				foreach ($chunk as $map) {
					if (isset($map->uId) && isset($uidIndex[$map->uId])) {
						$maps[] = $this->maps[$uidIndex[$map->uId]];
					} else {
						$maps[] = $map;
					}
				}
				if (count($chunk) < $chunkSize) {
					break;
				}
				$offset += $chunkSize;
			}
			$this->maps = $maps;
		}
		$this->nextMap = isset($this->maps[$nextMapIndex]) ? $this->maps[$nextMapIndex] : null;
	}

	public function onPlayerInfoChanged($playerInfo)
	{
		$info = PlayerInfo::fromArray($playerInfo);
		$player = $this->getPlayerObject($info->login);
		if (!$player)
			return;

		$formerPlayer = clone $player;
		$player->merge($info);

		if ($formerPlayer->spectator && !$player->spectator) {
			unset($this->spectators[$player->login]);
			$this->players[$player->login] = $player;
			Dispatcher::dispatch(new Event(Event::ON_PLAYER_CHANGE_SIDE, $player, 'spectator'));
		}
		elseif (!$formerPlayer->spectator && $player->spectator) {
			unset($this->players[$player->login]);
			$this->spectators[$player->login] = $player;
			Dispatcher::dispatch(new Event(Event::ON_PLAYER_CHANGE_SIDE, $player, 'player'));
		}
	}

	public function onPlayerAlliesChanged($login)
	{
		try {
			$allies = $this->connection->getDetailedPlayerInfo($login)->allies;
			$this->getPlayerObject($login)->allies = $allies;
		} catch (\Exception $e) {
		}
	}

	/**
	 * Give a Player Object for the corresponding login
	 *
	 * @param string $login
	 *
	 * @return Player
	 */
	public function getPlayerObject($login)
	{
		if (!is_string($login)) {
			return null;
		}
		if (isset($this->players[$login])) {
			return $this->players[$login];
		} elseif (isset($this->spectators[$login])) {
			return $this->spectators[$login];
		}
		return null;
	}

	public function getDisconnectPlayerObject($login)
	{
		if (!is_string($login)) {
			return null;
		}
		if (isset($this->disconnectedPlayers[$login])) {
			return $this->disconnectedPlayers[$login];
		}
		return null;
	}

	public function getCleanGamemodeName() {
		$gm = $this->gameInfos->scriptName;
        $lastSlashPos = strrpos($gm, '/');
        
        if ($lastSlashPos !== false) {
            $gm = substr($gm, $lastSlashPos + 1);
        }
    
        $dotPos = strpos($gm, '.');
        
        if ($dotPos !== false) {
            return strtolower(substr($gm, 0, $dotPos));
        }
    
        return strtolower($gm);
    }

	// eXpansion

    public function saveBlackList()
    {
        $file = CoreMeta::getInstance('expansion/core')->getVariable('blackListSettingsFile')->getRawValue();
        $this->connection->saveBlackList($file);
    }

    public function loadBlackList()
    {
        $file = CoreMeta::getInstance('expansion/core')->getVariable('blackListSettingsFile')->getRawValue();
        $this->connection->loadBlackList($file);
    }

    public function saveGuestList()
    {
        $file = CoreMeta::getInstance('expansion/core')->getVariable('guestListSettingsFile')->getRawValue();
        $this->connection->saveGuestList($file);
    }

    public function loadGuestList()
    {
        $file = CoreMeta::getInstance('expansion/core')->getVariable('guestListSettingsFile')->getRawValue();
        $this->connection->loadGuestList($file);
    }

    protected function getSimpleTitleByEnvironment($enviName)
    {
        if ($enviName == "Stadium" || $enviName == "Valley" || $enviName == "Canyon" || $enviName == "Lagoon") {
            return self::TITLE_SIMPLE_TM;
        } else {
            return self::TITLE_SIMPLE_SM;
        }
    }

    /**
     * @param $login
     *
     * @return DbPlayer|null
     */
    public function getDbPlayer($login)
    {
        if (isset($this->dbPlayers[$login])) {
            return $this->dbPlayers[$login];
        } else {
            return null;
        }
    }

    /**
     * Get total time expansion has been running.
     *
     * @return int
     */
    public function getExpansionUpTime()
    {
        return time() - $this->startTime;
    }

    /**
     * Get total time the dedicated server has been running.
     *
     * @return int
     */
    public function getDediUpTime()
    {
        return $this->getExpansionUpTime() + $this->dediUpTime;
    }

    public function getCurrentRanking()
    {
        if (empty($this->currentRankings)) {
            $chunkSize = 50;
            $offset = 0;

            do {
                try {
                    $rankings = $this->connection->getCurrentRanking($chunkSize, $offset);
                    $offset += $chunkSize;

                    $this->currentRankings = array_merge($this->currentRankings, $rankings);
                } catch (IndexOutOfBoundException $e) {
                    // We are expecting this exception, if we have an empty chunk.
                    $rankings = array();
                }
            } while (!empty($rankings) && count($rankings) == $chunkSize);
        }

        return $this->currentRankings;
    }

	public function getTeamsDisplay($default = array('$00F', 'Team Blue', '$F00', 'Team Red'))
	{
		$teams = array();
		$var = \ManiaLivePlugins\eXpansion\Gui\MetaData::getInstance()->getVariable('teamParams')->getRawValue();

		if (isset($var["team1Name"], $var["team1ColorHSL"], $var["team1Color"])) {
			$color = '$' . $var["team1Color"];
			$name = $var["team1Name"];
			$teams[0] = array("color" => $color, "name" => $name);
		} else {
			if (isset($default[0], $default[1])) {
				$teams[0] = array("color" => $default[0], "name" => $default[1]);
			} else {
				$teams[0] = array("color" => '$00F', "name" => 'Team Blue');
			}
		}

		if (isset($var["team2Name"], $var["team2ColorHSL"], $var["team2Color"])) {
			$color = '$' . $var["team2Color"];
			$name = $var["team2Name"];
			$teams[1] = array("color" => $color, "name" => $name);
		} else {
			if (isset($default[2], $default[3])) {
				$teams[1] = array("color" => $default[2], "name" => $default[3]);
			} else {
				$teams[1] = array("color" => '$F00', "name" => 'Team Red');
			}
		}

		return $teams;
	}

    /**
     * Method called when the Race Begin
     */
    public function onBeginMatch()
    {
        $this->playerTimes = array();
    }

    /**
     * Method called when a player finish a round
     *
     * @param int $playerUid
     * @param string $login
     * @param int $timeOrScore
     */
    public function onPlayerFinish($playerUid, $login, $timeOrScore)
    {
        if ($timeOrScore <= 0) {
            return;
        }
        if (!isset($this->playerTimes[$login])) {
            $this->playerTimes[$login] = $timeOrScore;
        } else {
            if ($this->playerTimes[$login] > $timeOrScore) {
                $this->playerTimes[$login] = $timeOrScore;
            }
        }
    }

    public function onPreLoop()
    {
        // Reset current rankings
        $this->currentRankings = array();
    }

    public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
    {
    }

    public function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
    {
    }

    public function onEcho($internal, $public)
    {
    }

    public function onServerStop()
    {
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
    }

    public function onBeginRound()
    {
    }

    public function onEndRound()
    {
    }

    public function onPlayerIncoherence($playerUid, $login)
    {
    }

    public function onBillUpdated($billId, $state, $stateName, $transactionId)
    {
    }

    public function onTunnelDataReceived($playerUid, $login, $data)
    {
    }

    public function onManualFlowControlTransition($transition)
    {
    }

    public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
    {
    }

    public function onModeScriptCallback($param1, $param2)
    {
    }

    public function onInit()
    {
    }

    public function onRun()
    {
    }

    public function onTerminate()
    {
    }
}
