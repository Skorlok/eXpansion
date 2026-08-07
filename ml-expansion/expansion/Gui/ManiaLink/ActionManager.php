<?php

namespace ManiaLivePlugins\eXpansion\Gui\ManiaLink;

use ManiaLive\Event\Dispatcher;
use ManiaLive\DedicatedApi\Callback\Listener as ServerListener;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;

final class ActionManager extends \ManiaLib\Utils\Singleton implements ServerListener
{

	protected $callbacks = array();
	protected $callbackKeys = array(); // reverse lookup: key => action id
	protected $lastAction = 0;

	protected function __construct()
	{
		Dispatcher::register(ServerEvent::getClass(), $this, ServerEvent::ON_PLAYER_MANIALINK_PAGE_ANSWER);
	}

	public function createAction($callback)
	{
		if (!is_array($callback) || !is_callable($callback)) {
			throw new \InvalidArgumentException('Invalid callback');
		}

		$args = func_get_args();
		array_shift($args);
		$callback = array($callback, $args);

		$key = $this->makeCallbackKey($callback);
		if ($key !== null) {
			if (isset($this->callbackKeys[$key]) && isset($this->callbacks[$this->callbackKeys[$key]])) {
				return 'exp' . $this->callbackKeys[$key];
			}
		} else {
			// Fallback for non-serializable callbacks (e.g. closures)
			$action = array_search($callback, $this->callbacks, true);
			if ($action !== false) {
				return 'exp' . $action;
			}
		}

		$this->callbacks[++$this->lastAction] = $callback;
		if ($key !== null) {
			$this->callbackKeys[$key] = $this->lastAction;
		}
		return 'exp' . $this->lastAction;
	}

	public function deleteAction($action)
	{
		//remove the prefix 'exp' from the answer to get the action id
		$action = substr($action, 3);
		if (isset($this->callbacks[$action])) {
			$key = $this->makeCallbackKey($this->callbacks[$action]);
			if ($key !== null) {
				unset($this->callbackKeys[$key]);
			}
			unset($this->callbacks[$action]);
		}
	}

	/**
	 * Build a stable string key for a callback entry, used as reverse-lookup index.
	 * Returns null if the callback cannot be keyed (non-serializable objects).
	 *
	 * @param array $callback Internal format: array($callable, $extraArgs)
	 * @return string|null
	 */
	private function makeCallbackKey($callback)
	{
		try {
			$callable = $callback[0];
			$args     = $callback[1];
			if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
				// Use spl_object_hash for every object arg — serializing large objects (e.g. Map)
				// was costing ~1 KB per key string, totalling ~70 MB for 70k callbacks.
				$argsKey = '';
				foreach ($args as $arg) {
					$argsKey .= is_object($arg) ? spl_object_hash($arg) : serialize($arg);
					$argsKey .= '|';
				}
				return spl_object_hash($callable[0]) . '::' . (string)$callable[1] . '::' . $argsKey;
			}
			return serialize($callback);
		} catch (\Exception $e) {
			return null;
		}
	}

	public function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
	{
		//remove the prefix 'exp' from the answer to get the action id
		$answer = substr($answer, 3);
		// check if the action exists
		if (isset($this->callbacks[$answer])) {
			$params = array($login);
			array_splice($params, count($params), 0, $this->callbacks[$answer][1]);
			if (count($entries)) {
				$entryValues = array();
				foreach ($entries as $entry) {
					$entryValues[$entry['Name']] = $entry['Value'];
				}
				$params[] = $entryValues;
			}
			call_user_func_array($this->callbacks[$answer][0], $params);
		}
	}

	public function onPlayerConnect($login, $isSpectator) {}
	public function onPlayerDisconnect($login, $disconnectionReason) {}
	public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd) {}
	public function onEcho($internal, $public) {}
	public function onServerStart() {}
	public function onServerStop() {}
	public function onBeginMatch() {}
	public function onEndMatch($rankings, $winnerTeamOrMap) {}
	public function onBeginMap($map, $warmUp, $matchContinuation) {}
	public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap) {}
	public function onBeginRound() {}
	public function onEndRound() {}
	public function onStatusChanged($statusCode, $statusName) {}
	public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex) {}
	public function onPlayerFinish($playerUid, $login, $timeOrScore) {}
	public function onPlayerIncoherence($playerUid, $login) {}
	public function onBillUpdated($billId, $state, $stateName, $transactionId) {}
	public function onTunnelDataReceived($playerUid, $login, $data) {}
	public function onMapListModified($curMapIndex, $nextMapIndex, $isListModified) {}
	public function onPlayerInfoChanged($playerInfo) {}
	public function onManualFlowControlTransition($transition) {}
	public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam) {}
	public function onModeScriptCallback($param1, $param2) {}
	public function onPlayerAlliesChanged($login) {}
}
