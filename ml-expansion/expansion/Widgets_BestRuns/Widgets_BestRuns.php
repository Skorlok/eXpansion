<?php

namespace ManiaLivePlugins\eXpansion\Widgets_BestRuns;

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

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->widget instanceof Widget) {
            $this->widget->erase();
        }
        $this->bestRuns = array();
    }

    public function displayWidget()
    {
        $this->widget->setPosition($this->config->bestRunsWidget_PosX, $this->config->bestRunsWidget_PosY, 0);
        $this->widget->setParam("bestRuns", $this->bestRuns);
        $this->widget->setParam("nbFields", $this->config->bestRunsWidget_nbDisplay);
        $this->widget->setParam("guiConfig", guiConfig::getInstance());
        $this->widget->setParam("colorParser", ColorParser::getInstance());
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
