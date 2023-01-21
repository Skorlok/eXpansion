<?php

/**
 * ManiaLive - TrackMania dedicated server manager in PHP
 *
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLive\Data;

use ManiaLib\Utils\Formatting;
use ManiaLive\Application\Event as AppEvent;
use ManiaLive\Application\Listener as AppListener;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\Event\Dispatcher;
use ManiaLive\Utilities\Console;
use Maniaplanet\DedicatedServer\Connection;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use Maniaplanet\DedicatedServer\Structures\Map;
use Maniaplanet\DedicatedServer\Structures\PlayerInfo;
use Maniaplanet\DedicatedServer\Structures\PlayerRanking;
use Maniaplanet\DedicatedServer\Structures\Vote;

/**
 * Contain every important data about the server
 */
class Storage extends \ManiaLib\Utils\Singleton implements ServerListener, AppListener
{

	/** @var Player[] */
	public $players = array();

	/** @var Player[] */
	public $spectators = array();

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

	/** @var Vote */
	public $currentVote;

	/** @var \Maniaplanet\DedicatedServer\Connection */
	private $connection;

	/** @var string[] */
	private $disconnectedPlayers = array();

	/**
	 * Player's checkpoints
	 */
	private $checkpoints = array();

	/** @var bool */
	private $isWarmUp = false;

	protected function __construct()
	{
		Dispatcher::register(AppEvent::getClass(), $this, AppEvent::ON_INIT | AppEvent::ON_POST_LOOP);
		Dispatcher::register(ServerEvent::getClass(), $this, ServerEvent::ALL);
	}

	function onInit()
	{
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
					
				}
				else {
					if ($player->spectator)
						$this->spectators[$player->login] = $player;
					else {

						$this->players[$player->login] = $player;
					}
				}
			} catch (\Exception $e) {

			}
		}

		try {
			$this->maps = $this->connection->getMapList(-1, 0);
			$this->currentMap = $this->connection->getCurrentMapInfo();
			if (isset($this->maps[$this->connection->getNextMapIndex()]))
				$this->nextMap = $this->maps[$this->connection->getNextMapIndex()];
			else
				$this->nextMap = null;
		} catch (\Exception $e) {
			$this->maps = array();
			$this->nextMap = null;
			$this->currentMap = null;
		}
		$this->server = $this->connection->getServerOptions();
		$this->gameInfos = $this->connection->getCurrentGameInfo();
		try {
			$this->serverLogin = $this->connection->getSystemInfo()->serverLogin;
		} catch (\Exception $e) {
			$this->serverLogin = null;
		}
	}

	function onPostLoop()
	{
		foreach ($this->disconnectedPlayers as $login) {
			if (isset($this->spectators[$login]) && !$this->spectators[$login]->isConnected)
				unset($this->spectators[$login]);
			else if (isset($this->players[$login]) && !$this->players[$login]->isConnected)
				unset($this->players[$login]);
		}
		$this->disconnectedPlayers = array();

		if ($this->currentVote instanceof Vote && $this->currentVote->status != Vote::STATE_NEW)
			$this->currentVote = null;
	}

	function onRun()
	{

	}

	function onPreLoop()
	{
		
	}

	function onTerminate()
	{

	}

	function onPlayerConnect($login, $isSpectator)
	{
		try {
			$info = $this->connection->getPlayerInfo($login, 1);
			$details = $this->connection->getDetailedPlayerInfo($login);
			$player = new Player();
			$player->merge($details);
			$player->merge($info);
			if ($player->isManagedByAnOtherServer) {

			}
			else {
				if ($isSpectator)
					$this->spectators[$login] = $player;
				else
					$this->players[$login] = $player;
			}
		} catch (\Exception $ex) {
			throw new \ManiaLive\Event\StopperException("Player alrady disconnected", 1, $ex);
		}
	}

	function onPlayerDisconnect($login, $disconnectionReason)
	{
		if ($this->getPlayerObject($login) != null) {
			$this->disconnectedPlayers[] = $login;
			$this->getPlayerObject($login)->isConnected = false;
		}
		else {
			throw new \ManiaLive\Event\StopperException("Player never connected", 1);
		}
	}

	function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
	{

	}

	function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
	{
		
	}

	function onEcho($internal, $public)
	{

	}

	function onServerStart()
	{
		try {
			$this->serverLogin = $this->connection->getMainServerPlayerInfo()->login;
			$this->maps = $this->connection->getMapList(-1, 0);
		} catch (\Exception $e) {
			$this->serverLogin = null;
			$this->maps = array();
		}
	}

	function onServerStop()
	{
		
	}

	function onBeginMatch()
	{

	}

	function onEndMatch($rankings, $winnerTeamOrMap)
	{
		if ($this->isWarmUp && $this->gameInfos->gameMode == GameInfos::GAMEMODE_LAPS) {
			$this->resetScores();
			$this->isWarmUp = false;
		}
		else
			$this->updateRanking(PlayerRanking::fromArrayOfArray($rankings));
	}

	function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$this->checkpoints = array();

		$oldMap = $this->currentMap;
		$this->currentMap = Map::fromArray($map);
		if ($oldMap) {
			Console::printlnFormatted('Map change: ' . Formatting::stripStyles($oldMap->name) . ' -> ' . Formatting::stripStyles($this->currentMap->name));
		}
		$this->resetScores();

		if ($warmUp)
			$this->isWarmUp = true;

		$this->gameInfos = $this->connection->getCurrentGameInfo();
		$this->server = $this->connection->getServerOptions();
	}

	function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
	{
		if (!$wasWarmUp) {
			$rankings = PlayerRanking::fromArrayOfArray($rankings);
			$this->updateRanking($rankings);
		}
		else {
			$this->resetScores();
			$this->isWarmUp = false;
		}
	}

	function onBeginRound()
	{
		
	}

	function onEndRound()
	{
// TODO find a better way to handle the -1000 "no race in progress" error ...
		try {
			if (count($this->players) || count($this->spectators))
				$this->updateRanking($this->connection->getCurrentRanking(-1, 0));
		} catch (\Exception $ex) {

		}
	}

	function onStatusChanged($statusCode, $statusName)
	{
		$this->serverStatus->code = $statusCode;
		$this->serverStatus->name = $statusName;
	}

	function getLapCheckpoints($player)
	{
		$login = $player->login;
		if (isset($this->checkpoints[$login])) {
			$checkCount = count($this->checkpoints[$login]) - 1;
			$offset = ($checkCount % $this->currentMap->nbCheckpoints) + 1;
			$checks = array_slice($this->checkpoints[$login], -$offset);

			if ($checkCount >= $this->currentMap->nbCheckpoints) {
				$timeOffset = $this->checkpoints[$login][$checkCount - $offset];

				foreach ($checks as &$check)
					$check -= $timeOffset;
			}

			return $checks;
		}
		else
			return array();
	}

	function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
	{
// reset all checkpoints on first checkpoint
		if ($checkpointIndex == 0)
			$this->checkpoints[$login] = array();
// sanity check
		elseif ($checkpointIndex > 0 && (!isset($this->checkpoints[$login]) || !isset($this->checkpoints[$login][$checkpointIndex - 1]) || $timeOrScore < $this->checkpoints[$login][$checkpointIndex - 1]))
			return;

// store current checkpoint score in array
		$this->checkpoints[$login][$checkpointIndex] = $timeOrScore;

// if player has finished a complete lap
		if ($this->currentMap->nbCheckpoints && ($checkpointIndex + 1) % $this->currentMap->nbCheckpoints == 0) {
			$player = $this->getPlayerObject($login);
			if ($player) {
// get the checkpoints for current lap
				$checkpoints = array_slice($this->checkpoints[$login], -$this->currentMap->nbCheckpoints);

// if we're at least in second lap we need to strip times from previous laps
				if ($checkpointIndex >= $this->currentMap->nbCheckpoints) {
// calculate checkpoint scores for current lap
					$offset = $this->checkpoints[$login][($checkpointIndex - $this->currentMap->nbCheckpoints)];
					for ($i = 0; $i < count($checkpoints); ++$i)
						$checkpoints[$i] -= $offset;

// calculate current lap score
					$timeOrScore -= $offset;
				}

// last checkpoint has to be equal to finish time
				if (end($checkpoints) != $timeOrScore)
					return;

// finally we tell everyone of the new lap time
				Dispatcher::dispatch(new Event(Event::ON_PLAYER_FINISH_LAP, $player, end($checkpoints), $checkpoints, $curLap));
			}
		}
	}

	function onPlayerFinish($playerUid, $login, $timeOrScore)
	{
		if (!isset($this->players[$login]))
			return;
		$player = $this->players[$login];

		if ($timeOrScore <= 0 || ($player->bestTime > 0 && $timeOrScore >= $player->bestTime))
			return;

		$oldBest = $player->bestTime;
		$this->updateRanking($this->connection->getCurrentRanking(-1, 0));

		if ($player->bestTime == $timeOrScore) {
// sanity checks
			$totalChecks = 0;
			switch ($this->gameInfos->gameMode) {
				case GameInfos::GAMEMODE_LAPS:
					$totalChecks = $this->currentMap->nbCheckpoints * $this->gameInfos->lapsNbLaps;
					break;
				case GameInfos::GAMEMODE_TEAM:
				case GameInfos::GAMEMODE_ROUNDS:
				case GameInfos::GAMEMODE_CUP:
					if ($this->currentMap->nbLaps > 0) {
						$totalChecks = $this->currentMap->nbCheckpoints * ($this->gameInfos->roundsForcedLaps ? : $this->currentMap->nbLaps);
						break;
					}
// fallthrough
				default:
					$totalChecks = $this->currentMap->nbCheckpoints;
					break;
			}

			if (count($player->bestCheckpoints) != $totalChecks) {
				Console::println('Best time\'s checkpoint count does not match and was ignored!');
				Console::printPlayerBest($player);
				$player->bestTime = $oldBest;
				return;
			}

			Dispatcher::dispatch(new Event(Event::ON_PLAYER_NEW_BEST_TIME, $player, $oldBest, $timeOrScore));
		}
	}

	function onPlayerIncoherence($playerUid, $login)
	{
		
	}

	function onBillUpdated($billId, $state, $stateName, $transactionId)
	{

	}

	function onTunnelDataReceived($playerUid, $login, $data)
	{
		
	}

	function onMapListModified($curMapIndex, $nextMapIndex, $isListModified)
	{
		if ($isListModified) {
			$maps = $this->connection->getMapList(-1, 0);

			foreach ($maps as $key => $map) {
				$storageKey = $this->findMap($map, $this->maps);
				if ($storageKey !== false)
					$maps[$key] = $this->maps[$storageKey];
				else
					$this->maps[$storageKey] = null;
			}
			$this->maps = $maps;
		}
		$this->nextMap = isset($this->maps[$nextMapIndex]) ? $this->maps[$nextMapIndex] : null;
	}

	protected function findMap(Map $newMap, $listMaps)
	{
		foreach ($listMaps as $key => $map) {
			if (isset($map->uId) && $map->uId == $newMap->uId)
				return $key;
		}
		return false;
	}

	function onPlayerInfoChanged($playerInfo)
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
		if ($formerPlayer->hasJoinedGame == false && $player->hasJoinedGame == true) {
			Dispatcher::dispatch(new Event(Event::ON_PLAYER_JOIN_GAME, $player->login));
		}
		if (($formerPlayer->teamId != -1 || $player->teamId != -1) && $formerPlayer->teamId != $player->teamId) {
			Dispatcher::dispatch(new Event(Event::ON_PLAYER_CHANGE_TEAM, $player->login, $formerPlayer->teamId, $player->teamId));
		}
	}

	function onManualFlowControlTransition($transition)
	{
		
	}

	function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
	{
		if (!($this->currentVote instanceof Vote))
			$this->currentVote = new Vote();
		$this->currentVote->status = $stateName;
		$this->currentVote->callerLogin = $login;
		$this->currentVote->cmdName = $cmdName;
		$this->currentVote->cmdParam = $cmdParam;
	}

	function onModeScriptCallback($param1, $param2)
	{
		
	}

	function onPlayerAlliesChanged($login)
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
	function getPlayerObject($login)
	{
		if (isset($this->players[$login]))
			return $this->players[$login];
		elseif (isset($this->spectators[$login]))
			return $this->spectators[$login];
		return null;
	}

	function resetMapInfos()
	{
		try {
			$this->maps = $this->connection->getMapList(-1, 0);
			$this->currentMap = $this->connection->getCurrentMapInfo();
			if (isset($this->maps[$this->connection->getNextMapIndex()]))
				$this->nextMap = $this->maps[$this->connection->getNextMapIndex()];
			else
				$this->nextMap = null;
		} catch (\Exception $e) {
			$this->maps = array();
			$this->nextMap = null;
			$this->currentMap = null;
		}
	}

	/**
	 * @param PlayerRanking[] $rankings
	 */
	protected function updateRanking($rankings)
	{
		foreach ($rankings as $ranking) {
			if ($ranking->rank == 0)
				continue;

			$player = $this->getPlayerObject($ranking->login);
			if (!$player)
				continue;

			$rankOld = $player->rank;
			$player->merge($ranking);

			if (!$player->isSpectator && $rankOld != $player->rank)
				Dispatcher::dispatch(new Event(Event::ON_PLAYER_NEW_RANK, $player, $rankOld, $player->rank));
		}
	}

	protected function resetScores()
	{
		foreach ($this->players as $player) {
			$player->bestTime = 0;
			$player->rank = 0;
			$player->point = 0;
		}

		foreach ($this->spectators as $spectator) {
			$spectator->bestTime = 0;
			$spectator->rank = 0;
			$spectator->point = 0;
		}
	}

	public static function keys($players) {
		$logins = array();
		foreach ($players as $login => $player) {
			$logins[] = strval($login);
		}

		return $logins;
	}
}

?>
