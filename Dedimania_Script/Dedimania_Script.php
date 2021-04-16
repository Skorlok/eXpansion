<?php

namespace ManiaLivePlugins\eXpansion\Dedimania_Script;

use Exception;
use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Dedimania\Classes\Connection as DediConnection;
use ManiaLivePlugins\eXpansion\Dedimania\DedimaniaAbstract;
use ManiaLivePlugins\eXpansion\Dedimania\Events\Event as DediEvent;
use ManiaLivePlugins\eXpansion\Dedimania\Structures\DediRecord;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class Dedimania_Script extends DedimaniaAbstract
{
    private $endmatchTriggered = false;

    public function eXpOnReady()
    {
        parent::eXpOnReady();
        $this->enableScriptEvents("Trackmania.Event.WayPoint");
    }

    public function eXpOnModeScriptCallback($callback, $array)
    {
        switch ($callback) {
            case "Trackmania.Event.WayPoint":
                call_user_func_array(array($this, "LibXmlRpc_OnWayPoint"),
                    json_decode($array[0], true)
                );
                break;
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        if (!$this->running) {
            return;
        }

        $this->records = array();
        $this->rankings = array();
        $this->AllCps = array();
        $this->vReplay = "";
        $this->gReplay = "";
    }
    
    public function LibXmlRpc_OnWayPoint($gametime,$login,$time,$lapTime,$stuntsscore,$checkpoint,$checkpointinlap,$isendrace,$isendlap,$curracecheckpoints,$curlapcheckpoints,$blockid,$speed,$distance)
    {

        if (!$this->running) {
            return;
        }

        if ($time == 0) {
            return;
        }

        $playerinfo = Core::$playerInfo;

        if (!$isendrace) {
            return;
        }

        if ($this->storage->currentMap->nbCheckpoints == 1) {
            return;
        }

        if (empty($login) || !is_string($login)) {
            return;
        }

        if (!array_key_exists($login, DediConnection::$players)) {
            return;
        }

        if (!isset($playerinfo[$login])) {
            return;
        }

        if (is_null(DediConnection::$dediMap)) {
            return;
        }

        // if player is banned from dedimania, don't send his time.
        if (DediConnection::$players[$login]->banned) {
            return;
        }

        if (!array_key_exists($login, $this->rankings)) {
            $this->rankings[$login] = array();
        }

        $this->AllCps[$login] = array(
            'Login' => $login,
            'AllCheckpoints' => implode(",", $curracecheckpoints),
        );

        if (!array_key_exists('BestTime', $this->rankings[$login])) {
            $this->rankings[$login] = array(
                'Login' => $login,
                'BestTime' => $time,
                'BestCheckpoints' => implode(",", $curracecheckpoints),
            );
        } else {
            if ($time < $this->rankings[$login]['BestTime']) {
                $this->rankings[$login] = array(
                    'Login' => $login,
                    'BestTime' => $time,
                    'BestCheckpoints' => implode(",", $curracecheckpoints),
                );
            }
        }

        $this->getVReplay();
        $this->getGReplay();

        // if current map doesn't have records, create one.
        if (count($this->records) == 0) {
            $player = $this->storage->getPlayerObject($login);
            $playerinfo = Core::$playerInfo;
            if ($this->storage->currentMap->nbCheckpoints !== count($curracecheckpoints)) {
                $this->console("Player CP mismatch");
            }

            $this->records[$login] = new DediRecord(
                $login,
                $player->nickName,
                DediConnection::$players[$login]->maxRank,
                $time,
                -1,
                $curracecheckpoints
            );
            $this->reArrage($login);
            Dispatcher::dispatch(new DediEvent(DediEvent::ON_NEW_DEDI_RECORD, $this->records[$login]));

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

            // print "times matches!";
            // if player exists on the list... see if he got better time

            $player = $this->storage->getPlayerObject($login);

            if (is_null($player)) {
                return;
            }

            if (array_key_exists($login, $this->records)) {


                if ($this->records[$login]->time > $time) {
                    $oldRecord = $this->records[$login];

                    $this->records[$login] = new DediRecord(
                        $login,
                        $player->nickName,
                        DediConnection::$players[$login]->maxRank,
                        $time,
                        -1,
                        $curracecheckpoints
                    );

                    // if new records count is greater than old count, and doesn't exceed the maxrank of the server
                    $oldCount = count($this->records);
                    if ((count($this->records) > $oldCount)
                        && ((DediConnection::$dediMap->mapMaxRank + 1) < DediConnection::$serverMaxRank)
                    ) {
                        //print "increasing maxrank! \n";
                        DediConnection::$dediMap->mapMaxRank++;
                    }
                    $this->reArrage($login);
                    // have to recheck if the player is still at the dedi array
                    if (array_key_exists($login, $this->records)
                    ) {// have to recheck if the player is still at the dedi array
                        Dispatcher::dispatch(
                            new DediEvent(DediEvent::ON_DEDI_RECORD, $this->records[$login], $oldRecord)
                        );
                    }

                    return;
                }

                // if not, add the player to records table
            } else {
                $oldCount = count($this->records);
                $this->records[$login] = new DediRecord(
                    $login,
                    $player->nickName,
                    DediConnection::$players[$login]->maxRank,
                    $time,
                    -1,
                    $curracecheckpoints
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
                    Dispatcher::dispatch(new DediEvent(DediEvent::ON_NEW_DEDI_RECORD, $this->records[$login]));
                }

                return;
            }
        }
    }

    public function getVReplay()
    {
        $currank = $this->rankings;
        usort($currank, array($this, "compare_BestTime"));

        $this->vReplay = $this->connection->getValidationReplay($currank[0]['Login']);
    }

    public function getGReplay()
    {
        $currank = $this->rankings;
        usort($currank, array($this, "compare_BestTime"));

        $grfile = sprintf('Dedimania/%s.%d.%07d.%s.Replay.Gbx',$this->storage->currentMap->uId,$this->storage->gameInfos->gameMode,$rankings[0]['BestTime'],$rankings[0]['Login']);
        $this->connection->saveBestGhostsReplay($rankings[0]['Login'], $grfile);
        $this->gReplay = file_get_contents($this->connection->gameDataDirectory().'Replays/'.$grfile);
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
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
        usort($this->AllCps, array($this, "compare_BestTime"));

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
                $this->console("No new times driven. Skipping dedimania sent.");
                return;
            }

            //$this->vReplay = $this->connection->getValidationReplay($rankings[0]['Login']);
            $vrfile = sprintf('Dedimania/VReplay/%s.%d.%07d.%s.Replay.Gbx',$this->storage->currentMap->uId,$this->storage->gameInfos->gameMode,$rankings[0]['BestTime'],$rankings[0]['Login']);

            $gameData = $this->connection->gameDataDirectory();
            file_put_contents($gameData .'Replays/'. $vrfile, $this->vReplay);

            //$grfile = sprintf('Dedimania/%s.%d.%07d.%s.Replay.Gbx',$this->storage->currentMap->uId,$this->storage->gameInfos->gameMode,$rankings[0]['BestTime'],$rankings[0]['Login']);
            //$this->connection->saveBestGhostsReplay($rankings[0]['Login'], $grfile);
            //$this->gReplay = file_get_contents($this->connection->gameDataDirectory().'Replays/'.$grfile);

            // Dedimania doesn't allow times sent without validation relay. So, let's just stop here if there is none.
            if (empty($this->vReplay)) {
                $this->console("Couldn't get validation replay of the first player. Dedimania times not sent.");
                return;
            }
            $this->console("Attempting to send times");
            $this->dedimania->setChallengeTimes($this->storage->currentMap, $rankings, $this->vReplay, $this->gReplay, $this->AllCps);
        } catch (Exception $e) {
            $this->console($e->getMessage());
            $this->vReplay = "";
            $this->gReplay = "";
        }
    }
}
