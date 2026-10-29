<?php

namespace ManiaLivePlugins\eXpansion\Overlay_TeamScores;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\DataAccess;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use Maniaplanet\DedicatedServer\Structures\PlayerInfo;

class Overlay_TeamScores extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    /** @var Structures\Team[] */
    private $teams = array();

    /** @var Maniaplanet\DedicatedServer\Structures\Status */
    private $status;
    private $access;
    private $clublinks = array("", "");

    /** true while the team names can be clicked to pick a club link */
    private $pickActive = false;

    /** @var Widget */
    private $widget;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->access = DataAccess::getInstance();
    }

    public function eXpOnReady()
    {
        $scores = $this->connection->getCurrentRanking(1, 0);
        $this->teams[0] = new Structures\Team($scores[0]->nickName);
        $this->teams[0]->score = $scores[0]->score;
        $scores = $this->connection->getCurrentRanking(1, 0); // CHANGE TO 1, 1
        $this->teams[1] = new Structures\Team($scores[0]->nickName);
        $this->teams[1]->score = $scores[0]->score;

        $this->registerManialinkCallback('setData0');
        $this->registerManialinkCallback('setData1');
        $this->registerManialinkCallback('reset');
        $this->registerManialinkCallback('toggle');

        $this->widget = new Widget('Overlay_TeamScores\Gui\Widgets\ScoresOverlay.xml');
        $this->widget->setName("Team Scores");
        $this->widget->setLayer("normal");
        $this->widget->setSize(82, 18);
        $this->widget->setPosition(0, 80, -60);

        if (self::eXpGetCurrentCompatibilityGameMode() == GameInfos::GAMEMODE_TEAM) {
            foreach ($this->storage->spectators as $player) {
                $this->showWidget($player->login);
            }
        }
    }

    public function setData0($login)
    {
        if (!$this->pickActive) {
            return;
        }

        $player = $this->storage->getPlayerObject($login);
        if (empty($player->clubLink)) {
            return;
        }
        if (substr($player->clubLink, 0, 4) !== "http") {
            return;
        }
        $this->clublinks[0] = $player->clubLink;

        $this->connection->setForcedClubLinks($this->clublinks[0], $this->clublinks[1]);
        $this->syncWidget(0);
    }

    public function setData1($login)
    {
        if (!$this->pickActive) {
            return;
        }

        $player = $this->storage->getPlayerObject($login);
        if (empty($player->clubLink)) {
            return;
        }
        if (substr($player->clubLink, 0, 4) !== "http") {
            return;
        }
        $this->clublinks[1] = $player->clubLink;
        $this->connection->setForcedClubLinks($this->clublinks[0], $this->clublinks[1]);
        $this->syncWidget(1);
    }

    public function reset($login)
    {
        if (!AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            return;
        }

        $scores = $this->storage->getCurrentRanking();
        $this->teams[0]->name = $scores[0]->nickName;
        $this->teams[1]->name = $scores[1]->nickName;
        $this->showWidget();
    }

    public function toggle($login)
    {
        if (!AdminGroups::hasPermission($login, Permission::GAME_SETTINGS)) {
            return;
        }

        $this->pickActive = !$this->pickActive;
        $this->showWidget();
    }

    protected function syncWidget($team)
    {
        if (empty($this->clublinks[$team])) {
            return;
        }
        if (substr($this->clublinks[$team], 0, 4) !== "http") {
            return;
        }
        $ch = curl_init($this->clublinks[$team]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $output = curl_exec($ch);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }
        $xml = simplexml_load_string($output);
        $this->teams[$team]->name = $xml->name;
        $this->showWidget();
    }

    public function showWidget($login = null)
    {
        if (self::eXpGetCurrentCompatibilityGameMode() != GameInfos::GAMEMODE_TEAM) {
            return;
        }

        /** @var Config $config */
        $config = Config::getInstance();

        $this->widget->setParam("imageUrl", $config->imageurl);
        $this->widget->setParam("teamStyle", $this->pickActive ? "TextTitle2Blink" : "TextRankingsBig");
        $this->widget->setParam("toggleText", $this->pickActive ? "Disable" : "Enable");
        $this->widget->setParam("team1Name", $this->widget->handleSpecialChars($this->teams[1]->name));
        $this->widget->setParam("team2Name", $this->widget->handleSpecialChars($this->teams[0]->name));
        $this->widget->setParam("team1Score", $this->teams[1]->score);
        $this->widget->setParam("team2Score", $this->teams[0]->score);

        if ($login === null) {
            foreach ($this->storage->spectators as $specLogin => $player) {
                $this->showForLogin($specLogin);
            }

            return;
        }

        $this->showForLogin($login);
    }

    private function showForLogin($login)
    {
        $this->widget->setParam("hideAdmin", !AdminGroups::hasPermission($login, Permission::GAME_SETTINGS));
        $this->widget->show($login);
    }

    public function hideWidget($login = null)
    {
        $this->widget->erase($login);
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if (self::eXpGetCurrentCompatibilityGameMode() != GameInfos::GAMEMODE_TEAM) {
            return;
        }
        if ($isSpectator) {
            $this->showWidget($login);
        }
    }

    public function onPlayerInfoChanged($playerInfo)
    {
        if (self::eXpGetCurrentCompatibilityGameMode() != GameInfos::GAMEMODE_TEAM) {
            return;
        }
        $player = PlayerInfo::fromArray($playerInfo);

        if ($player->spectator == 1) {
            $this->showWidget($player->login);
        }
        if ($playerInfo['SpectatorStatus'] == 0) {
            $this->hideWidget($playerInfo['Login']);
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        $this->hideWidget();
    }

    public function onBeginRound()
    {
        $this->syncRanking();
    }

    public function onEndRound()
    {
        $this->syncRanking();
    }

    public function syncRanking()
    {
        if (self::eXpGetCurrentCompatibilityGameMode() != GameInfos::GAMEMODE_TEAM) {
            return;
        }
        $scores = $this->connection->getCurrentRanking(2, 0);
        $this->teams[0]->score = $scores[0]->score;
        $this->teams[1]->score = $scores[1]->score;
        $this->showWidget();
    }

    public function eXpOnUnload()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->widget = null;

        parent::eXpOnUnload();
    }
}
