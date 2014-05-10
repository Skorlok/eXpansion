<?php

namespace ManiaLivePlugins\eXpansion\Dedimania;

use ManiaLivePlugins\eXpansion\Dedimania\Classes\Connection as DediConnection;
use ManiaLivePlugins\eXpansion\Dedimania\Events\Event as DediEvent;
use ManiaLivePlugins\eXpansion\Dedimania\Config;
use \ManiaLive\Event\Dispatcher;
use \ManiaLive\Utilities\Console;

class Dedimania extends DedimaniaAbstract {

    
    public function exp_onInit() {
	parent::exp_onInit();

	$this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_ROUNDS);
	$this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TIMEATTACK);
	$this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_TEAM);
	$this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_LAPS);
	$this->exp_addGameModeCompability(\Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_CUP);
	$this->exp_setScriptCompatibilityMode(false);	
    }



    public function onBeginMap($map, $warmUp, $matchContinuation) {
	if (!$this->running)
	    return;
	$this->records = array();
	$this->rankings = array();
	$this->vReplay = "";
	$this->gReplay = "";
    }

    public function onPlayerFinish($playerUid, $login, $time) {
	if (!$this->running)
	    return;
	if ($time == 0)
	    return;

	if ($this->storage->currentMap->nbCheckpoints == 1)
	    return;

	if (!array_key_exists($login, DediConnection::$players))
	    return;

	// if player is banned from dedimania, don't send his time.
	if (DediConnection::$players[$login]->banned)
	    return;

	// if current map doesn't have records, create one.
	if (count($this->records) == 0) {
	    $player = $this->connection->getCurrentRankingForLogin($login);
	    //print_r($player);

	    // map first array entry to player object;
	    $player = $player[0];
	    if ($this->storage->players[$login]->bestCheckpoints !== $player->bestCheckpoints) {
		echo "\nplayer cp mismatch!\n";
	    }

	    $this->records[$login] = new Structures\DediRecord($login, $player->nickName, DediConnection::$players[$login]->maxRank, $time, -1, $player->bestCheckpoints);
	    $this->reArrage($login);
	    \ManiaLive\Event\Dispatcher::dispatch(new DediEvent(DediEvent::ON_NEW_DEDI_RECORD, $this->records[$login]));
	    return;
	}

// if last record is not set, don't continue.
	if (!is_object($this->lastRecord)) {
	    return;
	}

// so if the time is better than the last entry or the count of records

	$maxrank = DediConnection::$serverMaxRank;
	if (DediConnection::$players[$login]->maxRank > $maxrank) {
	    $maxrank = DediConnection::$players[$login]->maxRank;
	}

	if ($time <= $this->lastRecord->time || count($this->records) <= $maxrank) {

//  print "times matches!";
// if player exists on the list... see if he got better time

	    $player = $this->storage->getPlayerObject($login);

	    if (array_key_exists($login, $this->records)) {


		if ($this->records[$login]->time > $time) {
		    $oldRecord = $this->records[$login];

		    $this->records[$login] = new Structures\DediRecord($login, $player->nickName, DediConnection::$players[$login]->maxRank, $time, -1, array());

// if new records count is greater than old count, and doesn't exceed the maxrank of the server
		    $oldCount = count($this->records);
		    if ((count($this->records) > $oldCount) && ( (DediConnection::$dediMap->mapMaxRank + 1 ) < DediConnection::$serverMaxRank)) {
//print "increasing maxrank! \n";
			DediConnection::$dediMap->mapMaxRank++;
			echo "new maxrank:" . DediConnection::$dediMap->mapMaxRank . " \n";
		    }
		    $this->reArrage($login);
// have to recheck if the player is still at the dedi array
		    if (array_key_exists($login, $this->records)) // have to recheck if the player is still at the dedi array
			\ManiaLive\Event\Dispatcher::dispatch(new DediEvent(DediEvent::ON_DEDI_RECORD, $this->records[$login], $oldRecord));
		    return;
		}

// if not, add the player to records table
	    } else {
		$oldCount = count($this->records);
		$this->records[$login] = new Structures\DediRecord($login, $player->nickName, DediConnection::$players[$login]->maxRank, $time, -1, array());
// if new records count is greater than old count, increase the map records limit

		if ((count($this->records) > $oldCount) && ( (DediConnection::$dediMap->mapMaxRank + 1 ) < DediConnection::$serverMaxRank)) {

		    DediConnection::$dediMap->mapMaxRank++;
		    echo "new maxrank:" . DediConnection::$dediMap->mapMaxRank . " \n";
		}
		$this->reArrage($login);

// have to recheck if the player is still at the dedi array
		if (array_key_exists($login, $this->records))
		    \ManiaLive\Event\Dispatcher::dispatch(new DediEvent(DediEvent::ON_NEW_DEDI_RECORD, $this->records[$login]));
		return;
	    }
	}
    }
    
    /**
     * 
     * @param array $rankings
     * @param string $winnerTeamOrMap
     * 
     */
    public function onEndMatch($rankings, $winnerTeamOrMap) {
	if (!$this->running)
	    return;
	if ($this->wasWarmup) {
	    $this->console("[Dedimania] the last round was warmup, deditimes not send for warmup!");
	    return;
	}
	$this->rankings = $rankings;

	if ($this->exp_isRelay())
	    return;

	try {
	    if (sizeof($rankings) == 0) {
		$this->vReplay = "";
		$this->gReplay = "";
		return;
	    }
	    $this->vReplay = $this->connection->getValidationReplay($rankings[0]['Login']);
	    $greplay = "";
	    $grfile = sprintf('Dedimania/%s.%d.%07d.%s.Replay.Gbx', $this->storage->currentMap->uId, $this->storage->gameInfos->gameMode, $rankings[0]['BestTime'], $rankings[0]['Login']);
	    $this->connection->SaveBestGhostsReplay($rankings[0]['Login'], $grfile);
	    $this->gReplay = file_get_contents($this->connection->gameDataDirectory() . 'Replays/' . $grfile);

// Dedimania doesn't allow times sent without validation relay. So, let's just stop here if there is none.
	    if (empty($this->vReplay)) {
		$this->console("[Dedimania] Couldn't get validation replay of the first player. Dedimania times not sent.");
		return;
	    }

	    $this->dedimania->setChallengeTimes($this->storage->currentMap, $this->rankings, $this->vReplay, $this->gReplay);
	} catch (\Exception $e) {
	    $this->console("[Dedimania] " . $e->getMessage());
	    $this->vReplay = "";
	    $this->gReplay = "";
	}
    }
}

?>
