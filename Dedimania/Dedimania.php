<?php

namespace ManiaLivePlugins\eXpansion\Dedimania;

use Exception;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Dedimania\Classes\Connection as DediConnection;
use ManiaLivePlugins\eXpansion\Dedimania\Events\Event as DediEvent;

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
        $this->rankings = array();
        $this->vReplay = "";
        $this->gReplay = "";
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

        if (self::eXpGetCurrentCompatibilityGameMode() == \Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_LAPS) {
            return;
        }

        $playerinfo = Core::$playerInfo;
        $checkpoints = implode(",", $playerinfo[$login]->checkpoints);

        $this->handlePlayerFinish($playerUid, $login, $time, $checkpoints);
    }

    /**
     * @param \ManiaLive\Data\Player $player
     * @param                        $time
     * @param                        $checkpoints
     * @param int $nbLap
     */
    public function onPlayerFinishLap($player, $time, $checkpoints, $nbLap)
    {
        $gamemode = self::eXpGetCurrentCompatibilityGameMode();

        if ($gamemode != \Maniaplanet\DedicatedServer\Structures\GameInfos::GAMEMODE_LAPS && strtolower($this->connection->getScriptName()['CurrentValue']) != "endurocup.script.txt") {
            return;
        }

        $checkpoints = implode(",", $checkpoints);

        $this->handlePlayerFinish($player->playerId, $player->login, $time, $checkpoints);
    }

    public function handlePlayerFinish($playerUid, $login, $time, $checkpoints)
    {
        if (is_null(DediConnection::$dediMap)) {
            return;
        }

        if (Core::$warmUpActive) {
            return;
        }

        if (!array_key_exists('BestTime', $this->rankings[$login])) {
            $this->rankings[$login] = array('Login' => $login, 'BestTime' => $time, 'BestCheckpoints' => $checkpoints);
        } else {
            if ($time < $this->rankings[$login]['BestTime']) {
                $this->rankings[$login] = array('Login' => $login, 'BestTime' => $time, 'BestCheckpoints' => $checkpoints);
            }
        }

        $this->getVReplay();
        $this->getGReplay();

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

    public function getVReplay()
    {
        try {
            $currank = $this->rankings;
            usort($currank, array($this, "compare_BestTime"));
            $this->vReplay = $this->connection->getValidationReplay($currank[0]['Login']);
        } catch (Exception $e) {
            $this->console("Unable to get validation replay, server said: " . $e->getMessage());
        }
    }

    public function getGReplay()
    {
        try {
            $currank = $this->rankings;
            usort($currank, array($this, "compare_BestTime"));

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

            $playerinfo = Core::$playerInfo;
            $AllChecks = implode(",", $playerinfo[$rankings[0]['Login']]->checkpoints);

            // Dedimania doesn't allow times sent without validation replay. So, let's just stop here if there is none.
            if (empty($this->vReplay)) {
                $this->console("Couldn't get validation replay of the first player. Dedimania times not sent.");
                return;
            }
            $this->dedimania->setChallengeTimes($this->storage->currentMap, $rankings, $this->vReplay, $this->gReplay, $AllChecks);
        } catch (Exception $e) {
            $this->console($e->getMessage());
            $this->vReplay = "";
            $this->gReplay = "";
            $this->rankings = array();
        }
        // ignore exception and other, always reset;
        $this->rankings = array();
        $this->vReplay = "";
        $this->gReplay = "";
    }
}
