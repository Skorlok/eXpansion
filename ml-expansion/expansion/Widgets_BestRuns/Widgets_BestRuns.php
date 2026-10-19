<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestRuns;

use ManiaLive\Utilities\Time;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\ColorParser;
use ManiaLivePlugins\eXpansion\Gui\Config as guiConfig;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Widgets_BestRuns\Structures\Run;

/**
 * Description of Widgets_BestRuns
 *
 * @author Reaby
 */
class Widgets_BestRuns extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    /** @var \ManiaLivePlugins\eXpansion\Widgets_BestRuns\Structures\Run[] */
    public $bestRuns;

    private $config;
    private $widget;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->enableStorageEvents();
        $this->config = Config::getInstance();

        $this->bestRuns = array();

        $this->widget = new Widget("Widgets_BestRuns\Gui\Widgets\BestRunPanel.xml");
        $this->widget->setName("Best Runs Widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(200, 7);
    }

    public function onPlayerFinish($playerUid, $login, $time)
    {
        // ignore finish without times
        if ($time <= 0) {
            return;
        }

        $data = new \Maniaplanet\DedicatedServer\Structures\PlayerRanking();
        $data->bestTime = $time;
        $data->nickName = $this->storage->getPlayerObject($login)->nickName;
        $data->bestCheckpoints = Core::$playerInfo[$login]->checkpoints;

        $this->bestRuns[] = new Run($data);

        // Sort the runs by time
        usort($this->bestRuns, function ($a, $b) {
            if ($a->totalTime == $b->totalTime) {
                return 0;
            }
            return ($a->totalTime < $b->totalTime) ? -1 : 1;
        });

        $this->displayWidget();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->bestRuns = array();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->bestRuns = array();
    }

    public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->bestRuns = array();
    }

    public function displayWidget()
    {
        /** @var guiConfig $guiConfig */
        $guiConfig   = guiConfig::getInstance();
        /** @var ColorParser $colorParser */
        $colorParser = ColorParser::getInstance();
        $rankColor   = $colorParser->getColor("#rank#");
        $timeColor   = $colorParser->getColor("#time#");

        $runs = array();
        foreach ($this->bestRuns as $run) {
            $checkpoints = array();
            foreach ($run->checkpoints as $cp) {
                $index = count($checkpoints);
                $checkpoints[] = array(
                    'posX' => 15 * $index,
                    'text' => $this->widget->handleSpecialChars($rankColor . ($index + 1) . ' ' . $timeColor . Time::fromTM($cp)),
                );
                if (count($checkpoints) > 10) {
                    break; // Putting more than 1 line of checkpoints takes too much space and hell to compute next player
                }
            }

            $runs[] = array(
                'posY'        => -5 * count($runs),
                'nickname'    => $this->widget->handleSpecialChars($run->nickname),
                'time'        => $this->widget->handleSpecialChars(Time::fromTM($run->totalTime)),
                'checkpoints' => $checkpoints,
            );
            if (count($runs) >= $this->config->bestRunsWidget_nbDisplay) {
                break;
            }
        }

        $this->widget->setPosition($this->config->bestRunsWidget_PosX, $this->config->bestRunsWidget_PosY, 0);
        $this->widget->setParam("runs",       $runs);
        $this->widget->setParam("bgColorize", $this->widget->handleSpecialChars($guiConfig->style_widget_bgColorize));
        $this->widget->setParam("timeColor",  $this->widget->handleSpecialChars(str_replace('$', "", $timeColor)));
        $this->widget->show(null, true);
    }

    public function eXpOnUnload()
    {
        $this->bestRuns = array();
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
            $this->widget = null;
        }
        parent::eXpOnUnload();
    }
}
