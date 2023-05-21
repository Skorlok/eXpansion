<?php

namespace ManiaLivePlugins\eXpansion\Widgets_TeamRoundScores;

use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Widgets_TeamRoundScores\Structures\RoundScore;

/**
 * Description of Widgets_RoundScores
 *
 * @author Petri
 */
class Widgets_TeamRoundScores extends ExpPlugin
{

    /**
     * @var RoundScore[]
     */
    private $roundScores = array();
    private $roundNumber = 0;
    private $totalScores = array();

    public function eXpOnLoad()
    {
        $this->roundScores = array();
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->reset();
        $this->showWidget(\ManiaLivePlugins\eXpansion\Gui\Widgets\Widget::LAYER_SCORES_TABLE);

        $this->enableScriptEvents(array("Maniaplanet.StartRound_Start", "Maniaplanet.EndRound_Start"));
    }

    public function eXpOnModeScriptCallback($callback, $array)
    {
        switch ($callback) {
            case "Maniaplanet.StartRound_Start":
                $this->onBeginRound(0);
                break;
            case "Maniaplanet.EndRound_Start":
                $this->onEndRound(0);
                break;
        }
    }

    public function onBeginRound()
    {
        $this->hideWidget();
        $this->showWidget(\ManiaLivePlugins\eXpansion\Gui\Widgets\Widget::LAYER_SCORES_TABLE);
    }

    public function test()
    {
        $this->roundScores = array();

        $ttlScore = array(0, 0);
        $ttlScore[-1] = 0;
        for ($x = 0; $x < 12; $x++) {
            $teamScores = array(mt_rand(0, 27), mt_rand(0, 27));

            arsort($teamScores, SORT_NUMERIC);
            reset($teamScores);
            $winnerTeam = key($teamScores);

            if ($teamScores[0] == $teamScores[1]) {
                $winnerTeam = -1;
            }

            $score = new RoundScore();
            $score->roundNumber = $x;
            $score->winningTeamId = $winnerTeam;

            // assign scores
            foreach ($teamScores as $team => $roundScore) {
                $score->score[$team] = $roundScore;
            }
            $ttlScore[$winnerTeam]++;
            $score->totalScore = $ttlScore;

            $this->roundScores[] = $score;
        }


        $this->showWidget();
    }

    public function onEndRound()
    {
        // get players infos and create array for counting points...
        $teamScores = array(0 => 0, 1 => 0);

        foreach (Core::$playerInfo as $player) {
            if ($player->finalTime != 0 && !$player->isSpectator) {
                $teamScores[$player->teamId] += $this->getScore($player->position);
            }
        }

        // first entry of array has more points, so it should be the winner...
        arsort($teamScores, SORT_NUMERIC);
        reset($teamScores);
        $winnerTeam = key($teamScores);

        if ($teamScores[0] == $teamScores[1]) {
            $winnerTeam = -1;
        }

        $score = new RoundScore();
        $score->roundNumber = $this->roundNumber;
        $score->winningTeamId = $winnerTeam;

        // assign scores
        foreach ($teamScores as $team => $roundScore) {
            $score->score[$team] = $roundScore;
        }

        // assign total scores
        foreach (Core::$rankings as $ranking) {
            $score->totalScore[$ranking->login] = $ranking->score;
        }

        $this->roundScores[$this->roundNumber] = $score;
        $this->hideWidget();
        $this->showWidget(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);

        $this->roundNumber++;
    }

    public function onBeginMatch()
    {
        $this->reset();
        $this->hideWidget();
        $this->showWidget(\ManiaLivePlugins\eXpansion\Gui\Widgets\Widget::LAYER_SCORES_TABLE);
    }

    public function onEndMatch($rankings_old, $winnerTeamOrMap)
    {
        $this->roundNumber = 0;
    }

    private function getScore($position)
    {
        /** @var int[] */
        $total = count($this->storage->players);
        $points = $total - $position;
        if ($points < 0) {
            $points = 0;
        }

        return $points;
    }

    private function reset()
    {
        $this->roundScores = array();
        $this->totalScores = array(0 => 0, 1 => 0);
        $this->totalScores[-1] = 0;
    }

    private function showWidget($layer = null)
    {
        $widget = Gui\Widgets\RoundScoreWidget::Create();
        $widget->setSize(42, 56);
        $widget->setScores($this->roundScores);
        if ($layer != null) {
            $widget->setLayer($layer);
        }
        $widget->setPosition(-124, 58);
        $widget->show();
    }

    private function hideWidget()
    {
        Gui\Widgets\RoundScoreWidget::EraseAll();
    }

    public function eXpOnUnload()
    {
        $this->reset();
        $this->hideWidget();
        parent::eXpOnUnload();
    }
}
