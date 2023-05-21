<?php

namespace ManiaLivePlugins\eXpansion\Widgets_TeamPlayerScores;

use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\Widgets_TeamPlayerScores\Gui\Widgets\PlayerScoreWidget;
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

    public function eXpOnLoad()
    {

    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->reset();
        $this->showWidget(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);

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
        $this->showWidget(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);
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
        $this->hideWidget();
        $this->showWidget(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);
    }

    public function onBeginMatch()
    {
        $this->reset();
        $this->hideWidget();
        $this->showWidget(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);
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
        $this->playerScores = array();
    }

    private function showWidget($layer = null)
    {
        $widget = PlayerScoreWidget::Create();
        $widget->setSize(42, 56);
        $widget->setScores($this->playerScores);
        if ($layer != null) {
            $widget->setLayer($layer);
        }
        $widget->setPosition(-124, 6);
        $widget->show();
    }

    private function hideWidget()
    {
        PlayerScoreWidget::EraseAll();
    }

    public function eXpOnUnload()
    {
        $this->reset();
        $this->hideWidget();
        parent::eXpOnUnload();
    }
}
