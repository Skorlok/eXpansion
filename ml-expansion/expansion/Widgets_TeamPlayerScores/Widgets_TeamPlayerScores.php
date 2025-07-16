<?php

namespace ManiaLivePlugins\eXpansion\Widgets_TeamPlayerScores;

use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\Widgets_TeamPlayerScores\Structures\PlayerScore;

/**
 * Description of Widgets_PlayerScores
 *
 * @author Petri
 */
class Widgets_TeamPlayerScores extends ExpPlugin
{

    /**
     * @var PlayerScore[]
     */
    private $playerScores = array();

    private $config;

    private $widget;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();
        $this->reset();

        $this->enableScriptEvents("Maniaplanet.EndRound_Start");

        $this->widget = new Widget("Widgets_TeamPlayerScores\Gui\Widgets\PlayerScoreWidget.xml");
        $this->widget->setName("Player Scores for team mode");
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
        foreach (Core::$playerInfo as $player) {
            if ($player->finalTime > 0 && !$player->isSpectator) {

                if (!array_key_exists($player->login, $this->playerScores)) {
                    $this->playerScores[$player->login] = new PlayerScore();
                    $this->playerScores[$player->login]->login = $player->login;
                    $this->playerScores[$player->login]->nickName = Core::$players[$player->login];
                }
                
                // get points
                $this->playerScores[$player->login]->score += $this->getScore($player->position);

                // assign best time
                if ($this->playerScores[$player->login]->bestTime == 0 || $player->finalTime < $this->playerScores[$player->login]->bestTime) {
                    $this->playerScores[$player->login]->bestTime = $player->finalTime;
                }

                // count wins
                switch ($player->position) {
                    case 0:
                        $this->playerScores[$player->login]->winScore[0]++;
                        break;
                    case 1:
                        $this->playerScores[$player->login]->winScore[1]++;
                        break;
                    case 2:
                        $this->playerScores[$player->login]->winScore[2]++;
                        break;
                }
            }
        }
        ArrayOfObj::asortDesc($this->playerScores, "score");
        $this->showWidget();
    }

    public function onBeginMatch()
    {
        $this->reset();
        $this->showWidget();
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
        $this->playerScores = array();
    }

    private function showWidget()
    {
        $this->widget->setSize(42, (($this->config->teamPlayerScorePanel_nbFields) * 4 + 4.75));
        $this->widget->setPosition($this->config->teamPlayerScorePanel_PosX, $this->config->teamPlayerScorePanel_PosY, 0);
        $this->widget->setParam("nbFields", $this->config->teamPlayerScorePanel_nbFields);
        $this->widget->setParam("playerScores", $this->playerScores);
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
