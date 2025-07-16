<?php

namespace ManiaLivePlugins\eXpansion\Widgets_TeamRoundScores;

use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
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

    private $config;

    private $widget;

    public function eXpOnLoad()
    {
        $this->roundScores = array();
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
        $this->reset();

        $this->enableScriptEvents("Maniaplanet.EndRound_Start");

        $this->widget = new Widget("Widgets_TeamRoundScores\Gui\Widgets\RoundScoreWidget.xml");
        $this->widget->setName("Round Scores for team mode");
        $this->widget->setLayer("scorestable");
        $this->widget->setParam("title", "Round Points");

        $this->showWidget();
    }

    public function eXpOnModeScriptCallback($callback, $array)
    {
        switch ($callback) {
            case "Maniaplanet.EndRound_Start":
                $this->onEndRound(0);
                break;
        }
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
        $this->showWidget();

        $this->roundNumber++;
    }

    public function onBeginMatch()
    {
        $this->reset();
        $this->showWidget();
    }

    public function onEndMatch($rankings_old, $winnerTeamOrMap)
    {
        $this->roundNumber = 0;
    }

    private function getScore($position)
    {
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

    private function showWidget()
    {
        $this->widget->setSize(42, (($this->config->teamRoundScorePanel_nbFields) * 4 + 4.75));
        $this->widget->setPosition($this->config->teamRoundScorePanel_PosX, $this->config->teamRoundScorePanel_PosY, 0);
        $this->widget->setParam("nbFields", $this->config->teamRoundScorePanel_nbFields);
        $this->widget->setParam("roundScores", $this->roundScores);
        $this->widget->show(null, true);
    }

    private function hideWidget()
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
    }

    public function eXpOnUnload()
    {
        $this->reset();
        $this->hideWidget();
        parent::eXpOnUnload();
    }
}
