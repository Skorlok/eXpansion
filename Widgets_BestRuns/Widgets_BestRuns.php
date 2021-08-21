<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestRuns;

use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Widgets_BestRuns\Gui\Widgets\BestRunPanel;
use ManiaLivePlugins\eXpansion\Widgets_BestRuns\Structures\Run;

/**
 * Description of Widgets_BestRuns
 *
 * @author Reaby
 */
class Widgets_BestRuns extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $bestTime = 0;

    private $nbDisplay = 1;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->enableStorageEvents();
    }

    public function eXpOnReady()
    {
        $this->onBeginMatch();

        $this->onPlayerFinish(null, -1, 0);
    }

    public function onBeginMatch()
    {
        $this->bestTime = 0;
        BestRunPanel::$bestRuns = array();

        foreach ($this->storage->players as $player) {
            $this->onPlayerConnect($player->login, false);
        }
        foreach ($this->storage->spectators as $player) {
            $this->onPlayerConnect($player->login, true);
        }
    }

    public function onPlayerFinish($playerUid, $login, $time)
    {
        // ignore finish without times
        if ($time == 0) {
            return;
        }

        // othervice if the players new best time is faster than the buffer, update
        if ($this->bestTime == 0 || $time < $this->bestTime) {

            $this->bestTime = $time;
            BestRunPanel::$bestRuns = array();

            $data = new \Maniaplanet\DedicatedServer\Structures\PlayerRanking();
            $data->bestTime = $time;
            $data->nickName = $this->storage->getPlayerObject($login)->nickName;
            $data->bestCheckpoints = Core::$playerInfo[$login]->checkpoints;

            BestRunPanel::$bestRuns[] = new Run($data);

            BestRunPanel::RedrawAll();
        }
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        BestRunPanel::EraseAll();
        $this->bestTime = 0;
        BestRunPanel::$bestRuns = array();
    }

    /**
     * displayWidget(string $login)
     *
     * @param string $login
     */
    public function displayWidget($login = null)
    {
        $info = BestRunPanel::Create($login);
        $info->setSize(220, 20);
        $info->setPosition(0, 86);
        $info->setAlign("center", "top");
        $info->show();
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->displayWidget($login);
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        BestRunPanel::Erase($login);
    }

    public function eXpOnUnload()
    {
        BestRunPanel::EraseAll();
        parent::eXpOnUnload();
    }
}
