<?php

namespace ManiaLivePlugins\eXpansion\Dedimania;

use Exception;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Dedimania\Classes\Connection as DediConnection;
use ManiaLivePlugins\eXpansion\Dedimania\Events\Event as DediEvent;
use Maniaplanet\DedicatedServer\Structures\GameInfos;

class Dedimania extends DedimaniaAbstract
{
    private $checkpoints = array();

    public function eXpOnInit()
    {
        parent::eXpOnInit();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        if (!$this->running) {
            return;
        }
        $this->checkpoints = array();
        $this->rankings = array();
        $this->vReplay = "";
        $this->gReplay = "";
        $this->AllCps = array();
        $this->BeginMap();
    }

    public function onPlayerFinish($playerUid, $login, $time)
    {
        if (!$this->running) {
            return;
        }
        if ($time == 0) {
            return;
        }

        if ($this->storage->currentMap->nbCheckpoints == 1) {
            return;
        }

        if (!array_key_exists($login, DediConnection::$players)) {
            return;
        }

        // if player is banned from dedimania, don't send his time.
        if (DediConnection::$players[$login]->banned) {
            return;
        }

        if (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_LAPS) {
            return;
        }

        $playerinfo = Core::$playerInfo;
        $checkpoints = implode(",", $playerinfo[$login]->checkpoints);

        $this->handlePlayerFinish($playerUid, $login, $time, $checkpoints);
    }

    function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
	{
        if (!$this->running) {
            return;
        }
        if ($timeOrScore == 0) {
            return;
        }

        if ($this->storage->currentMap->nbCheckpoints == 1) {
            return;
        }

        if (!array_key_exists($login, DediConnection::$players)) {
            return;
        }

        // if player is banned from dedimania, don't send his time.
        if (DediConnection::$players[$login]->banned) {
            return;
        }

        if (self::eXpGetCurrentCompatibilityGameMode() != GameInfos::GAMEMODE_LAPS) {
            return;
        }

		if ($checkpointIndex == 0)
			$this->checkpoints[$login] = array();

		elseif ($checkpointIndex > 0 && (!isset($this->checkpoints[$login]) || !isset($this->checkpoints[$login][$checkpointIndex - 1]) || $timeOrScore < $this->checkpoints[$login][$checkpointIndex - 1]))
			return;

		$this->checkpoints[$login][$checkpointIndex] = $timeOrScore;

		if ($this->storage->currentMap->nbCheckpoints && ($checkpointIndex + 1) % $this->storage->currentMap->nbCheckpoints == 0) {
			$player = $this->storage->getPlayerObject($login);
			if ($player) {
				$checkpoints = array_slice($this->checkpoints[$login], -$this->storage->currentMap->nbCheckpoints);

				if ($checkpointIndex >= $this->storage->currentMap->nbCheckpoints) {
					$offset = $this->checkpoints[$login][($checkpointIndex - $this->storage->currentMap->nbCheckpoints)];
					for ($i = 0; $i < count($checkpoints); ++$i)
						$checkpoints[$i] -= $offset;

					$timeOrScore -= $offset;
				}

				if (end($checkpoints) != $timeOrScore)
					return;

                $this->handlePlayerFinish($playerUid, $login, end($checkpoints), implode(",", $checkpoints));
			}
		}
	}

    public function handlePlayerFinish($playerUid, $login, $time, $checkpoints)
    {
        if (is_null(DediConnection::$dediMap)) {
            return;
        }

        if (Core::$warmUpActive) {
            return;
        }

        if (\ManiaLivePlugins\eXpansion\Endurance\Endurance::$enduro) {
            return;
        }

        $gamemode = self::eXpGetCurrentCompatibilityGameMode(); // special rounds mode with forced laps are ignored by dedimania
        if ($gamemode == GameInfos::GAMEMODE_ROUNDS || $gamemode == GameInfos::GAMEMODE_TEAM || $gamemode == GameInfos::GAMEMODE_CUP) {
            if ($this->storage->currentMap->nbLaps > 1) {
                $ScriptSettings = $this->connection->getModeScriptSettings();
                if (array_key_exists("S_ForceLapsNb", $ScriptSettings)) {
                    if ($ScriptSettings['S_ForceLapsNb'] != -1) {
                        if ($ScriptSettings['S_ForceLapsNb'] != $this->storage->currentMap->nbLaps) {
                            return;
                        }
                    }
                }
            }
        }

        if (!array_key_exists('BestTime', $this->rankings[$login])) {
            $this->rankings[$login] = array('Login' => $login, 'BestTime' => $time, 'BestCheckpoints' => $checkpoints);
        } else {
            if ($time < $this->rankings[$login]['BestTime']) {
                $this->rankings[$login] = array('Login' => $login, 'BestTime' => $time, 'BestCheckpoints' => $checkpoints);
            }
        }

        $this->getVReplay($login, $this->checkpoints[$login]);
        $this->getGReplay($login);

        // if current map doesn't have records, create one.
        if (count($this->records) == 0) {
            $player = $this->storage->getPlayerObject($login);

            $this->records[$login] = new Structures\DediRecord(
                $login,
                $player->nickName,
                DediConnection::$players[$login]->maxRank,
                $time,
                -1,
                $checkpoints
            );
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

                    $this->records[$login] = new Structures\DediRecord(
                        $login,
                        $player->nickName,
                        DediConnection::$players[$login]->maxRank,
                        $time,
                        -1,
                        $checkpoints
                    );

                    // if new records count is greater than old count, and doesn't exceed the maxrank of the server
                    $oldCount = count($this->records);
                    if ((count($this->records) > $oldCount)
                        && ((DediConnection::$dediMap->mapMaxRank + 1) < DediConnection::$serverMaxRank)
                    ) {
                        DediConnection::$dediMap->mapMaxRank++;
                    }
                    $this->reArrage($login);
                    // have to recheck if the player is still at the dedi array
                    if (array_key_exists($login, $this->records)
                    ) {// have to recheck if the player is still at the dedi array

                        \ManiaLive\Event\Dispatcher::dispatch(
                            new DediEvent(DediEvent::ON_DEDI_RECORD, $this->records[$login], $oldRecord)
                        );
                    }

                    return;
                }

                // if not, add the player to records table
            } else {
                $oldCount = count($this->records);
                $this->records[$login] = new Structures\DediRecord(
                    $login,
                    $player->nickName,
                    DediConnection::$players[$login]->maxRank,
                    $time,
                    -1,
                    $checkpoints
                );
                // if new records count is greater than old count, increase the map records limit

                if ((count($this->records) > $oldCount)
                    && ((DediConnection::$dediMap->mapMaxRank + 1) < DediConnection::$serverMaxRank)
                ) {

                    DediConnection::$dediMap->mapMaxRank++;
                }
                $this->reArrage($login);

                // have to recheck if the player is still at the dedi array
                if (array_key_exists($login, $this->records)) {
                    \ManiaLive\Event\Dispatcher::dispatch(
                        new DediEvent(DediEvent::ON_NEW_DEDI_RECORD, $this->records[$login])
                    );
                }

                return;
            }
        }
    }

    public function getVReplay($login, $checkpoints)
    {
        try {
            $currank = $this->rankings;
            usort($currank, array($this, "compare_BestTime"));

            if ($currank[0]['Login'] !== $login) {
                return;
            }

            $this->vReplay = $this->connection->getValidationReplay($currank[0]['Login']);
			if ($this->checkpoints !== array()) {
				$this->AllCps = implode(",", $checkpoints);
			}
        } catch (Exception $e) {
            $this->console("Unable to get validation replay, server said: " . $e->getMessage());
        }
    }

    public function getGReplay($login)
    {
        try {
            $currank = $this->rankings;
            usort($currank, array($this, "compare_BestTime"));

            if ($currank[0]['Login'] !== $login) {
                return;
            }

            $grfile = sprintf('%s.%d.%07d.%s.Replay.Gbx',$this->storage->currentMap->uId,$this->storage->gameInfos->gameMode,$currank[0]['BestTime'],$currank[0]['Login']);
            $this->connection->saveBestGhostsReplay($currank[0]['Login'], $grfile);
            $this->gReplay = file_get_contents($this->connection->gameDataDirectory().'Replays/'.$grfile);
            unlink($this->connection->gameDataDirectory().'Replays/'.$grfile);
        } catch (Exception $e) {
            $this->console("Unable to save ghost replay, server said: " . $e->getMessage());
        }
    }

    public function onEndMatch($rankings_old, $winnerTeamOrMap)
    {
        $this->sendScores();
        $this->EndMatch();
    }

    public function sendScores()
    {
        if (!$this->running) {
            return;
        }

        if ($this->expStorage->isRelay) {
            return;
        }

        usort($this->rankings, array($this, "compare_BestTime"));

        $rankings = array();
        $error = false;
        foreach ($this->rankings as $login => $rank) {
            $checks = explode(",", $rank['BestCheckpoints']);
            foreach ($checks as $list) {
                if ($list == 0) {
                    $error = true;
                }
            }
            $rank['BestCheckpoints'] = $checks;
            $rankings[] = $rank;
        }

        if ($error) {
            $this->console("Data integrity check failed. Dedimania times not sent.");
            return;
        }

        try {
            if (sizeof($rankings) == 0) {
                $this->vReplay = "";
                $this->gReplay = "";
                return;
            }

            // Dedimania doesn't allow times sent without validation replay. So, let's just stop here if there is none.
            if (empty($this->vReplay)) {
                $this->console("Couldn't get validation replay of the first player. Dedimania times not sent.");
                return;
            }
            $this->dedimania->setChallengeTimes($this->storage->currentMap, $rankings, $this->vReplay, $this->gReplay, $this->AllCps);
        } catch (Exception $e) {
            $this->console($e->getMessage());
            $this->vReplay = "";
            $this->gReplay = "";
            $this->rankings = array();
            $this->checkpoints = array();
            $this->AllCps = array();
        }
        // ignore exception and other, always reset;
        $this->rankings = array();
        $this->vReplay = "";
        $this->gReplay = "";
        $this->checkpoints = array();
        $this->AllCps = array();
    }
}
