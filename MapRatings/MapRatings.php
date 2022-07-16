<?php

namespace ManiaLivePlugins\eXpansion\MapRatings;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\MapRatings\Gui\Widgets\EndMapRatings;
use ManiaLivePlugins\eXpansion\MapRatings\Gui\Widgets\RatingsWidget;
use ManiaLivePlugins\eXpansion\MapRatings\Gui\Windows\MapRatingsManager;
use ManiaLivePlugins\eXpansion\MapRatings\Structures\PlayerVote;
use ManiaLivePlugins\eXpansion\MapRatings\Classes\Connection as mxConnection;
use ManiaLivePlugins\eXpansion\MapRatings\Events\MXKarmaEvent;
use ManiaLivePlugins\eXpansion\MapRatings\Events\MXKarmaEventListener;
use ManiaLivePlugins\eXpansion\MapRatings\Structures\MXRating;
use ManiaLivePlugins\eXpansion\MapRatings\Structures\MXVote;

class MapRatings extends ExpPlugin
{

    private $rating = 0;

    private $ratingTotal = 0;

    /** @var Config */
    private $config;

    private $msg_rating;

    private $msg_noRating;

    private $pendingRatings = array();

    private $oldRatings = array();

    /** @var Connection */
    private $mxConnection;

    private $mxMapStart = -1;

    /** @var MXRating */
    private $mxRatings = null;

    /** @var String[][] */
    private $mx_votes = array();

    /** @var MXVote[] */
    private $mx_votesTemp = array();

    private $mx_msg_error;
    private $mx_msg_connected;

    private $settingsChanged = array();

    public function eXpOnInit()
    {
        EndMapRatings::$parentPlugin = $this;
        \ManiaLivePlugins\eXpansion\MapRatings\Gui\Widgets\RatingsWidget::$parentPlugin = $this;
        $actionFinal = ActionHandler::getInstance()->createAction(array($this, "autoRemove"));
        Gui\Windows\MapRatingsManager::$removeId = \ManiaLivePlugins\eXpansion\Gui\Gui::createConfirm($actionFinal);
        $this->config = Config::getInstance();
    }

    public function eXpOnLoad()
    {
        $this->enableDatabase();
        $this->enableDedicatedEvents();
        $this->msg_rating_mx = eXpGetMessage('#rating#Map Approval Rating: #variable#%2$s#rating# (#variable#%3$s #rating#votes), MX: #variable#%4$s#rating# (#variable#%5$s #rating#votes).  Your Rating: #variable#%6$s#rating# / #variable#5');
        $this->msg_rating_only_mx = eXpGetMessage('#rating#Map Approval Rating: not been rated yet, MX: #variable#%2$s#rating# (#variable#%3$s #rating#votes)');
        $this->msg_rating_mx_no_votes = eXpGetMessage('#rating#Map Approval Rating: #variable#%2$s#rating# (#variable#%3$s #rating#votes), MX: not been rated yet.  Your Rating: #variable#%4$s#rating# / #variable#5');
        $this->msg_not_enough_finishes = eXpGetMessage('#admin_error#You need to finish this Map at least #variable#%s#admin_error# time before being able to vote!');
        
        
        $this->msg_rating = eXpGetMessage('#rating#Map Approval Rating: #variable#%2$s#rating# (#variable#%3$s #rating#votes).  Your Rating: #variable#%4$s#rating# / #variable#5');
        $this->msg_noRating = eXpGetMessage('#rating# $iMap has not been rated yet!');
        if (!$this->db->tableExists("exp_ratings")) {
            $this->db->execute(
                'CREATE TABLE IF NOT EXISTS `exp_ratings` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `uid` text NOT NULL,
                  `login` varchar(255) NOT NULL,
                  `rating` int(11) NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;'
            );
        }

        //Checking the version if the table
        $version = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Database\Database', 'getDatabaseVersion', 'exp_ratings');
        if (!$version) {
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Database\Database', 'setDatabaseVersion', 'exp_records', 1);
        }

        $version = $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Database\Database', 'getDatabaseVersion', 'exp_ratings');

        if ($version == 1) {
            try {
                $q = "ALTER TABLE `exp_ratings` CHANGE COLUMN `uid` `uid` VARCHAR(27) NOT NULL ;";
                $this->db->execute($q);
                $q = "CREATE INDEX `uid`  ON exp_ratings` (uid, rating) COMMENT '' ALGORITHM DEFAULT LOCK DEFAULT";
                $this->db->execute($q);
            } catch (\Exception $ex) {
                $this->console("[MapRatings] There was error while changing your database structure to newer one, "."most likely the database is converted already!");
            }
            $this->callPublicMethod('\ManiaLivePlugins\eXpansion\Database\Database', 'setDatabaseVersion', 'exp_ratings', 2);
        }

        $cmd = $this->registerChatCommand("---", "vote_moinsmoinsmoins", 0, true);
        $cmd->help = '/---';

        $cmd = $this->registerChatCommand("--", "vote_moinsmoins", 0, true);
        $cmd->help = '/--';

        $cmd = $this->registerChatCommand("-", "vote_moins", 0, true);
        $cmd->help = '/-';

        $cmd = $this->registerChatCommand("+", "vote_plus", 0, true);
        $cmd->help = '/+';

        $cmd = $this->registerChatCommand("++", "vote_plusplus", 0, true);
        $cmd->help = '/++';

        $cmd = $this->registerChatCommand("+++", "vote_plusplusplus", 0, true);
        $cmd->help = '/+++';

        $cmd = $this->registerChatCommand("rating", "chatRating", 0, true);
        $cmd->help = 'Show the rating message';

        $cmd = $this->registerChatCommand("karma", "chatRating", 0, true);
        $cmd->help = 'Show the rating message';

        $this->setPublicMethod("getRatings");
        $this->setPublicMethod("showRatingsManager");

        $this->mxConnection = new mxConnection();
        $this->mx_msg_error = eXpGetMessage('MXKarma error %1$s: %2$s');
        $this->mx_msg_connected = eXpGetMessage('MXKarma connection Success!');
    }

    public function eXpOnReady()
    {
        $this->reload();

        $info = RatingsWidget::Create(null);
        $info->setSize(34, 12);
        $info->setRating($this->rating * 20, $this->ratingTotal);
        $info->show();

        $this->affectAllRatings();


        \ManiaLive\Event\Dispatcher::register(MXKarmaEvent::getClass(), $this);

        $this->mxMapStart = time();
        if ($this->config->mxKarmaEnabled) {
            $this->tryConnect();
        }
    }

    private function tryConnect()
    {
        $admins = AdminGroups::getInstance();
        $this->config = Config::getInstance();
        if (!$this->mxConnection->isConnected()) {
            if (empty($this->config->mxKarmaServerLogin) || empty($this->config->mxKarmaApiKey)) {
                $admins->announceToPermission(Permission::EXPANSION_PLUGIN_SETTINGS, "#admin_error#Server login or/and Server code is empty in MXKarma Configuration");
                $this->console("Server code or/and login is not configured for MXKarma plugin!");
                return;
            }
            $this->mxConnection->connect($this->config->mxKarmaServerLogin, $this->config->mxKarmaApiKey);
        } else {
            $admins->announceToPermission(Permission::EXPANSION_PLUGIN_SETTINGS, "#admin_error#Tried to connect to MXKarma, but connection is already made.");
            $this->console("Tried to connect to MXKarma, but connection is already made.");
        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        $this->config = Config::getInstance();
        if ($this->config->mxKarmaEnabled) {
            $this->settingsChanged[$var->getName()] = true;
            if (array_key_exists("mxKarmaApiKey", $this->settingsChanged) && array_key_exists("mxKarmaServerLogin", $this->settingsChanged)) {
                $this->tryConnect();
                $this->settingsChanged = array();

                $this->mxRatings = null;
                $this->mx_votes = array();
                $this->mx_votesTemp = array();
                $this->mxMapStart = time();
                if ($this->mxConnection->isConnected()) {
                    $this->mxConnection->getRatings($this->getPlayers(), false);
                }
            }
        } else {
            $info = RatingsWidget::Create(null);
            $info->setSize(34, 12);
            $info->setRating($this->rating * 20, $this->ratingTotal);
            $info->show();
        }
    }

    public function MXKarma_onConnected()
    {
        $this->mxConnection->getRatings($this->getPlayers(), false);
    }

    public function MXKarma_onDisconnected()
    {

    }

    public function MXKarma_onError($state, $number, $reason)
    {
        if ($reason == "invalid session") {
            $this->mxConnection->connect($this->config->mxKarmaServerLogin, $this->config->mxKarmaApiKey);
            return;
        }
        $this->eXpChatSendServerMessage($this->mx_msg_error, null, array($state, $reason));
        $this->console("MXKarma error  " . $state . ": " . $reason);
    }

    public function MXKarma_onVotesRecieved(MXRating $votes)
    {
        if ($this->mxRatings == null) {

            $this->mxRatings = $votes;
            $this->mx_votes = array();
            foreach ($votes->votes as $vote) {
                $this->mx_votes[] = $vote;
            }

            $widget = RatingsWidget::Create();
            $widget->setRating($this->mxRatings->voteaverage, $this->mxRatings->votecount);
            $widget->show();

            //send msg
            if ($this->config->sendBeginMapNotices) {
                if ($this->ratingTotal == 0 && $this->mxRatings->votecount == 0) {
                    $this->eXpChatSendServerMessage($this->msg_noRating, null, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->currentMap->name, 'wosnm')));
                } else {
                    foreach ($this->storage->players as $login => $player) {
                        $this->sendRatingMsg($login, null);
                    }
                    foreach ($this->storage->spectators as $login => $player) {
                        $this->sendRatingMsg($login, null);
                    }
                }
            }

        } else {
            foreach ($votes->votes as $vote) {
                $this->mx_votes[] = $vote;
                $this->mxRatings->votes[] = $vote;
            }
        }
    }

    public function MXKarma_onVotesSave($isSuccess)
    {
        if ($isSuccess) {
            $this->console("MXKarma saved successfully!");
        } else {
            $this->console("Failed to save MXKarma!");
        }
    }

    public function getPlayers()
    {
        $players = array();

        $players = array_keys($this->storage->players);
        array_merge($players, array_keys($this->storage->players));

        $spectators = array_keys($this->storage->spectators);
        array_merge($spectators, array_keys($this->storage->spectators));

        $total = array_merge($spectators, $players);
        return $total;
    }

    public function getRatings()
    {
        $ratings = $this->db->execute("SELECT uid, avg(rating) AS rating, COUNT(rating) AS ratingTotal FROM exp_ratings GROUP BY uid;")->fetchArrayOfObject();
        $out = array();
        foreach ($ratings as $rating) {
            $out[$rating->uid] = new Structures\Rating($rating->rating, $rating->ratingTotal, $rating->uid);
        }

        return $out;
    }

    /**
     * Will affect the rating to all the maps in the storage
     */
    public function affectAllRatings()
    {
        $uids = "";
        $mapsByUid = array();
        foreach ($this->storage->maps as $map) {
            $uids .= $this->db->quote($map->uId) . ",";
            $mapsByUid[$map->uId] = $map;
        }
        $uids = trim($uids, ",");

        $ratings = $this->db->execute("SELECT uid, avg(rating) AS rating, COUNT(rating) AS ratingTotal FROM exp_ratings WHERE uid IN (" . $uids . ") GROUP BY uid;")->fetchArrayOfObject();
        $out = array();
        foreach ($ratings as $rating) {
            $mapsByUid[$rating->uid]->mapRating = new Structures\Rating($rating->rating, $rating->ratingTotal, $rating->uid);
        }
    }

    /**
     *
     * @param null|string|\Maniaplanet\DedicatedServer\Structures\Map $uId
     *
     * @return PlayerVote[]
     */
    public function getVotesForMap($uId = null)
    {
        if ($uId == null) {
            $uId = $this->storage->currentMap->uId;
        } else {
            if ($uId instanceof \Maniaplanet\DedicatedServer\Structures\Map) {
                $uId = $uId->uId;
            }
        }

        $ratings = $this->db->execute("SELECT login, rating FROM exp_ratings WHERE `uid` = " . $this->db->quote($uId) . ";")->fetchArrayOfAssoc();

        $out = array();
        foreach ($ratings as $data) {
            $vote = PlayerVote::fromArray($data);
            $out[$vote->login] = $vote;
        }

        return $out;
    }

    public function reload()
    {
        $database = $this->db->execute("SELECT avg(rating) AS rating, COUNT(rating) AS ratingTotal" . " FROM exp_ratings" . " WHERE `uid`=" . $this->db->quote($this->storage->currentMap->uId) . ";")->fetchObject();
        $this->rating = 0;
        $this->ratingTotal = 0;
        if ($database) {
            $this->rating = $database->rating;
            $this->ratingTotal = $database->ratingTotal;
            $this->oldRatings = $this->getVotesForMap($this->storage->currentMap->uId);
            foreach ($this->storage->maps as $map) {
                if ($map->uId == $this->storage->currentMap->uId) {
                    $map->mapRating = new Structures\Rating($database->rating, $database->ratingTotal, $this->storage->currentMap->uId);
                }
            }
        }
    }

    public function saveRatings($uid)
    {
        try {

            if (empty($this->pendingRatings)) {
                return;
            }

            $sqlInsert = "INSERT INTO exp_ratings (`uid`, `login`, `rating`  ) VALUES ";
            $loginList = "";
            $i = 0;
            foreach ($this->pendingRatings as $login => $rating) {
                if ($i != 0) {
                    $sqlInsert .= ", ";
                }
                $i++;
                $sqlInsert .= "(" . $this->db->quote($uid) . "," . $this->db->quote($login) . "," . $this->db->quote($rating) . ")";
                $loginList .= $this->db->quote($login) . ",";
            }
            $loginList = rtrim($loginList, ",");

            $this->db->execute("DELETE FROM exp_ratings " . " WHERE `uid`= " . $this->db->quote($uid) . " " . " AND `login` IN (" . $loginList . ")");

            $this->db->execute($sqlInsert);
            $this->pendingRatings = array();
        } catch (\Exception $e) {
            $this->pendingRatings = array();
            $this->console("Error in MapRating: " . $e->getMessage());
        }
    }

    public function saveRating($login, $rating)
    {
        EndMapRatings::Erase($login);

        if ($this->config->karmaRequireFinishes > 0) {
            if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                $localrecs = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");

                $player_localrec = ArrayOfObj::getObjbyPropValue($localrecs, "login", $login);

                if ($player_localrec) {
                    if ($player_localrec->nbFinish < $this->config->karmaRequireFinishes) {
                        $this->eXpChatSendServerMessage($this->msg_not_enough_finishes, $login, array($this->config->karmaRequireFinishes));
                        return;
                    }
                } else {
                    $this->eXpChatSendServerMessage($this->msg_not_enough_finishes, $login, array($this->config->karmaRequireFinishes));
                    return;
                }
            }
        }

        if ($this->config->mxKarmaEnabled) {
            $player = $this->storage->getPlayerObject($login);
            $this->vote($player, $rating*20);
        }

        $oldRating = 0;
        $sum = $this->rating * $this->ratingTotal;

        if (isset($this->pendingRatings[$login])) {
            $oldRating = $this->pendingRatings[$login];
        } else {
            if (isset($this->oldRatings[$login])) {
                $oldRating = $this->oldRatings[$login]->rating;
            } else {
                $this->ratingTotal++;
            }
        }

        if ($this->ratingTotal == 0) {
            $this->rating = $rating;
        } else {
            $this->rating = ($sum - $oldRating + $rating) / $this->ratingTotal;
        }

        $this->pendingRatings[$login] = $rating;

        if (!$this->config->mxKarmaEnabled) {
            $info = RatingsWidget::Create(null);
            $info->setSize(34, 12);
            $info->setRating($this->rating * 20, $this->ratingTotal);
            $info->show();
        }

        $this->sendRatingMsg($login, $rating);
    }

    public function vote($player, $vote)
    {
        if ($this->mxRatings == null) {
            return;
        }
        $oldVote = $this->getObjbyPropValue($this->mxRatings->votes, "login", $player->login);

        if ($oldVote) {
            if ($oldVote[0]->vote == $vote) {
                $this->eXpChatSendServerMessage("Vote registered for MXKarma", $player->login);
                return;
            } else {
                if ($this->mxRatings->votecount != 1) {

                    $reAverage = ($this->mxRatings->voteaverage) - (($oldVote[0]->vote - $this->mxRatings->voteaverage) / ($this->mxRatings->votecount-1));
                    $this->mxRatings->votecount -= 1;
                    $this->mxRatings->voteaverage = $reAverage;
                    unset($this->mxRatings->votes[$oldVote[1]]);
                    $this->mxRatings->votes = array_values($this->mxRatings->votes);

                } else {

                    $this->mxRatings->votecount = 0;
                    $this->mxRatings->voteaverage = 50;
                    unset($this->mxRatings->votes[$oldVote[1]]);
                    $this->mxRatings->votes = array_values($this->mxRatings->votes);

                }
            }
        }
        
        $this->mx_votesTemp[$player->login] = new MXVote($player, $vote);
        $this->eXpChatSendServerMessage("Vote registered for MXKarma", $player->login);

        $widget = RatingsWidget::Create();
        $x = 0;
        $avgTempVotes = 0;
        foreach ($this->mx_votesTemp as $vote) {
            $avgTempVotes += $vote->vote;
            $x++;
        }
        if ($x > 0) {
            $avgTempVotes = $avgTempVotes / $x;
        }
        $newAverage = (($this->mxRatings->voteaverage * $this->mxRatings->votecount) + ($avgTempVotes*$x)) / ($this->mxRatings->votecount+$x);
        $widget->setRating($newAverage, ($this->mxRatings->votecount+$x));
        $widget->show();
    }

    public function getObjbyPropValue(&$array, $prop, $value)
    {
        if (!is_array($array)) {
            return false;
        }

        $index = 0;
        foreach ($array as $class) {
            if (!property_exists($class, $prop)) {
                throw new \Exception("Property $prop doesn't exists!");
            }

            if ($class->$prop == $value) {
                return array($class, $index);
            }
            $index++;
        }
        return false;
    }

    public function sendRatingMsg($login, $playerRating)
    {
        if ($login != null) {
            if ($this->config->mxKarmaEnabled && $this->mxRatings != null) {

                if ($this->ratingTotal == 0 && $this->mxRatings->votecount == 0) {
                    $this->eXpChatSendServerMessage($this->msg_noRating, $login, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->currentMap->name, 'wosnm')));
                    return;
                }
                if ($playerRating === null) {
                    $query = $this->db->execute("SELECT rating AS playerRating FROM exp_ratings WHERE `uid`=" . $this->db->quote($this->storage->currentMap->uId) . " AND `login`=" . $this->db->quote($login) . ";")->fetchObject();
                    if (!$query || !isset($query->playerRating)) {
                        $playerRating = '-';
                    } else {
                        $playerRating = $query->playerRating;
                    }
                }

                $rating = ($this->rating / 5) * 100;
                $rating = round($rating) . "%";

                $x = 0;
                $avgTempVotes = 0;
                foreach ($this->mx_votesTemp as $vote) {
                    $avgTempVotes += $vote->vote;
                    $x++;
                }
                if ($x > 0) {
                    $avgTempVotes = $avgTempVotes / $x;
                }

                if ($this->mxRatings->votecount + $x == 0) {
                    $mxAverage = 0;
                } else {
                    $mxAverage = (($this->mxRatings->voteaverage * $this->mxRatings->votecount) + ($avgTempVotes*$x)) / ($this->mxRatings->votecount+$x);
                    $mxAverage = round($mxAverage) . "%";
                }

                if ($this->ratingTotal == 0) {
                    $this->eXpChatSendServerMessage($this->msg_rating_only_mx, $login, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->currentMap->name, 'wosnm'), $mxAverage, $this->mxRatings->votecount+$x, $playerRating));
                } else {
                    if ($this->mxRatings->votecount + $x == 0) {
                        $this->eXpChatSendServerMessage($this->msg_rating_mx_no_votes, $login, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->currentMap->name, 'wosnm'), $rating, $this->ratingTotal, $playerRating));
                    } else {
                        $this->eXpChatSendServerMessage($this->msg_rating_mx, $login, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->currentMap->name, 'wosnm'), $rating, $this->ratingTotal, $mxAverage, $this->mxRatings->votecount+$x, $playerRating));
                    }
                }

            } else {

                if ($this->ratingTotal == 0) {
                    $this->eXpChatSendServerMessage($this->msg_noRating, $login, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->currentMap->name, 'wosnm')));
                    return;
                }
                if ($playerRating === null) {
                    $query = $this->db->execute("SELECT rating AS playerRating FROM exp_ratings WHERE `uid`=" . $this->db->quote($this->storage->currentMap->uId) . " AND `login`=" . $this->db->quote($login) . ";")->fetchObject();
                    if (!$query || !isset($query->playerRating)) {
                        $playerRating = '-';
                    } else {
                        $playerRating = $query->playerRating;
                    }
                }

                $rating = ($this->rating / 5) * 100;
                $rating = round($rating) . "%";
                $this->eXpChatSendServerMessage($this->msg_rating, $login, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->currentMap->name, 'wosnm'), $rating, $this->ratingTotal, $playerRating));

            }
        }
    }

    public function autoRemove($login)
    {
        if (\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP)) {

            $filenames = array();
            foreach ($this->autoMapManager_getMaps() as $rating) {
                $filenames[] = $rating->map->fileName;
            }

            try {
                $this->connection->removeMapList($filenames);
                $this->eXpChatSendServerMessage(eXpGetMessage("Maps with bad rating removed successfully."));
                Gui\Windows\MapRatingsManager::Erase($login);
            } catch (\Exception $e) {
                $this->eXpChatSendServerMessage("#error#Error: %s", $login, array($e->getMessage()));
            }
        }
    }

    /**
     *
     * @return \ManiaLivePlugins\eXpansion\MapRatings\MapRating[]
     */
    public function autoMapManager_getMaps()
    {
        $items = array();
        foreach ($this->getRatings() as $uid => $rating) {
            $value = round(($rating->rating / 5) * 100);
            if ($rating->totalvotes >= $this->config->minVotes && $value <= $this->config->removeTresholdPercentage) {
                $map = \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::getObjbyPropValue($this->storage->maps, "uId", $uid);
                if ($map) {
                    $items[] = new Structures\MapRating($rating, $map);
                }
            }
        }
        return $items;
    }

    public function showRatingsManager($login)
    {
        if (\ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::hasPermission($login, Permission::MAP_REMOVE_MAP)) {
            $window = Gui\Windows\MapRatingsManager::Create($login);
            $window->setTitle(__("Ratings Manager", $login));
            $window->setSize(120, 90);
            $window->setRatings($this->autoMapManager_getMaps());
            $window->show();
        }
    }

    public function chatRating($login = null)
    {
        if ($login !== null) {
            $this->sendRatingMsg($login, null);
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if ($this->config->mxKarmaEnabled) {
            if ($this->mxConnection->isConnected()) {
                $playerVote = false;
                if ($this->mxRatings != null) {
                    $playerVote = ArrayOfObj::getObjbyPropValue($this->mxRatings->votes, "login", $login);
                }
                if (!$playerVote) {
                    if (!array_key_exists($login, $this->mx_votesTemp)) {
                        $this->mxConnection->getRatings(array($login), true);
                    }
                }
            }
        }

        if ($this->config->sendBeginMapNotices) {
            $this->sendRatingMsg($login, null);
        }
    }

    public function onBeginMap($var, $var2, $var3)
    {
        $this->mxRatings = null;
        $this->mx_votes = array();
        $this->mx_votesTemp = array();
        $this->mxMapStart = time();
        if ($this->config->mxKarmaEnabled) {
            if ($this->mxConnection->isConnected()) {
                $this->mxConnection->getRatings($this->getPlayers(), false);
            }
        }


        $this->reload();

        $this->affectAllRatings();

        EndMapRatings::EraseAll();

        $info = RatingsWidget::Create(null);
        $info->setSize(34, 12);
        $info->setRating($this->rating * 20, $this->ratingTotal);
        $info->show();

        //send msg
        if ($this->config->sendBeginMapNotices && !$this->config->mxKarmaEnabled) {
            if ($this->ratingTotal == 0) {
                $this->eXpChatSendServerMessage($this->msg_noRating, null, array(\ManiaLib\Utils\Formatting::stripCodes($this->storage->currentMap->name, 'wosnm')));
            } else {
                foreach ($this->storage->players as $login => $player) {
                    $this->sendRatingMsg($login, null);
                }
                foreach ($this->storage->spectators as $login => $player) {
                    $this->sendRatingMsg($login, null);
                }
            }
        }
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        $this->saveRatings($this->storage->currentMap->uId);

        //Updating ratings in map object
        if (!isset($this->storage->currentMap->mapRating)) {
            $this->storage->currentMap->mapRating = new Structures\Rating($this->rating, $this->ratingTotal, $this->storage->currentMap->uId);
        } else {
            $this->storage->currentMap->mapRating->rating = $this->rating;
            $this->storage->currentMap->mapRating->totalvotes = $this->ratingTotal;
        }

        RatingsWidget::EraseAll();

        // MXKarma
        if ($this->config->mxKarmaEnabled) {
            $newVotes = array();

            foreach ($this->mx_votesTemp as $login => $vote) {
                $newVotes[] = $vote;
            }

            if (count($newVotes) > 0) {
                $outArray = array();
                foreach ($newVotes as $login => $vote) {
                    $outArray[] = $vote;
                }

                $this->mxConnection->saveVotes($this->storage->currentMap, time() - $this->mxMapStart, $outArray);
            }
        }
    }

    public function onBeginMatch()
    {
        $this->saveRatings($this->storage->currentMap->uId);
        $this->reload();

        EndMapRatings::EraseAll();

        if (!$this->config->mxKarmaEnabled) {
            $info = RatingsWidget::Create(null);
            $info->setSize(34, 12);
            $info->setRating($this->rating * 20, $this->ratingTotal);
            $info->show();
        }
    }

    public function onEndMatch($rankings = "", $winnerTeamOrMap = "")
    {

        if ($this->config->showPodiumWindow) {
            $this->reload();
            $ratings = $this->getVotesForMap(null);

            $logins = array();
            foreach ($this->storage->players as $login => $player) {
                if (array_key_exists($login, $ratings) == false) {
                    $logins[$login] = $login;
                }
                if ($this->config->karmaRequireFinishes > 0) {
                    if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                        $localrecs = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");

                        $player_localrec = ArrayOfObj::getObjbyPropValue($localrecs, "login", $login);

                        if ($player_localrec) {
                            if ($player_localrec->nbFinish < $this->config->karmaRequireFinishes) {
                                unset($logins[$login]);
                            }
                        } else {
                            unset($logins[$login]);
                        }
                    }
                }
                if (array_key_exists($login, $this->pendingRatings)) {
                    unset($logins[$login]);
                }
            }
            foreach ($this->storage->spectators as $login => $player) {
                if (array_key_exists($login, $ratings) == false) {
                    $logins[$login] = $login;
                }
                if ($this->config->karmaRequireFinishes > 0) {
                    if ($this->isPluginLoaded('\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords')) {
                        $localrecs = $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRecords");

                        $player_localrec = ArrayOfObj::getObjbyPropValue($localrecs, "login", $login);

                        if ($player_localrec) {
                            if ($player_localrec->nbFinish < $this->config->karmaRequireFinishes) {
                                unset($logins[$login]);
                            }
                        } else {
                            unset($logins[$login]);
                        }
                    }
                }
                if (array_key_exists($login, $this->pendingRatings)) {
                    unset($logins[$login]);
                }
            }


            if (sizeof($logins) > 0) {
                \ManiaLive\Gui\Group::Erase("mapratings");
                $group = \ManiaLive\Gui\Group::Create("mapratings", $logins);
                EndMapRatings::EraseAll();
                $widget = EndMapRatings::Create(null);
                $widget->setMap($this->storage->currentMap);
                $widget->show($group);
            }
        }
    }

    public function vote_moinsmoinsmoins($fromLogin)
    {
        $this->saveRating($fromLogin, 0);
    }

    public function vote_moinsmoins($fromLogin)
    {
        $this->saveRating($fromLogin, 1);
    }

    public function vote_moins($fromLogin)
    {
        $this->saveRating($fromLogin, 2);
    }

    public function vote_plus($fromLogin)
    {
        $this->saveRating($fromLogin, 3);
    }

    public function vote_plusplus($fromLogin)
    {
        $this->saveRating($fromLogin, 4);
    }

    public function vote_plusplusplus($fromLogin)
    {
        $this->saveRating($fromLogin, 5);
    }

    public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
    {
        if ($playerUid == 0) {
            return;
        }
        if ($text == "0/5") {
            $this->saveRating($login, 0);
        }
        if ($text == "1/5") {
            $this->saveRating($login, 1);
        }
        if ($text == "2/5") {
            $this->saveRating($login, 2);
        }
        if ($text == "3/5") {
            $this->saveRating($login, 3);
        }
        if ($text == "4/5") {
            $this->saveRating($login, 4);
        }
        if ($text == "5/5") {
            $this->saveRating($login, 5);
        }

        if ($text == "---") {
            $this->saveRating($login, 0);
        }
        if ($text == "--") {
            $this->saveRating($login, 1);
        }
        if ($text == "-") {
            $this->saveRating($login, 2);
        }
        if ($text == "+") {
            $this->saveRating($login, 3);
        }
        if ($text == "++") {
            $this->saveRating($login, 4);
        }
        if ($text == "+++") {
            $this->saveRating($login, 5);
        }
    }

    public function eXpOnUnload()
    {
        EndMapRatings::EraseAll();
        RatingsWidget::EraseAll();
        MapRatingsManager::EraseAll();

        \ManiaLive\Event\Dispatcher::unregister(MXKarmaEvent::getClass(), $this);
        unset($this->mxConnection);
    }
}
