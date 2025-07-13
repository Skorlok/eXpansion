<?php

namespace ManiaLivePlugins\eXpansion\Widgets_EndRankings;

use ManiaLive\PluginHandler\Dependency;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\Formaters\LongDate;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;

class Widgets_EndRankings extends ExpPlugin
{
    private $config;
    private $widget1;
    private $widget2;
    private $widget3;
    
    public function eXpOnInit()
    {
        $this->addDependency(new Dependency('\ManiaLivePlugins\eXpansion\\LocalRecords\\LocalRecords'));
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableDatabase();
        $this->config = Config::getInstance();

        $formatter = LongDate::getInstance();

        $this->widget1 = new Widget("Widgets_EndRankings\Gui\Widgets\Panel.xml");
        $this->widget1->setName("Server Ranks");
        $this->widget1->setLayer("normal");
        $this->widget1->setParam("title", "Server Ranks");
        $this->widget1->setParam("formatter", $formatter);

        $this->widget2 = new Widget("Widgets_EndRankings\Gui\Widgets\Panel.xml");
        $this->widget2->setName("Top Playtime");
        $this->widget2->setLayer("normal");
        $this->widget2->setParam("title", "Top Playtime");
        $this->widget2->setParam("formatter", $formatter);

        $this->widget3 = new Widget("Widgets_EndRankings\Gui\Widgets\Panel.xml");
        $this->widget3->setName("Top Donators");
        $this->widget3->setLayer("normal");
        $this->widget3->setParam("title", "Top Donators");
        $this->widget3->setParam("formatter", $formatter);
    }

    /**
     * displayWidget(string $login)
     *
     * @param string $login
     */
    public function displayWidgets()
    {
        /** @var Config $config */
        $this->config = Config::getInstance();

        $this->widget1->setSize(40, (3 * $this->config->rankPanel_nbFields) + 4.5);
        $this->widget1->setPosition($this->config->rankPanel_PosX, $this->config->rankPanel_PosY, 0);
        $this->widget1->setParam("items", $this->callPublicMethod("\\ManiaLivePlugins\\eXpansion\\LocalRecords\\LocalRecords", "getRanks"));
        $this->widget1->setParam("nbFields", $this->config->rankPanel_nbFields);
        $this->widget1->show(null, true);

        
        $this->widget2->setSize(40, (3 * $this->config->playtimePanel_nbFields) + 4.5);
        $this->widget2->setPosition($this->config->playtimePanel_PosX, $this->config->playtimePanel_PosY, 0);
        $this->widget2->setParam("items", $this->getTopPlaytime());
        $this->widget2->setParam("nbFields", $this->config->playtimePanel_nbFields);
        $this->widget2->show(null, true);

        
        $this->widget3->setSize(40, (3 * $this->config->donatorPanel_nbFields) + 4.5);
        $this->widget3->setPosition($this->config->donatorPanel_PosX, $this->config->donatorPanel_PosY, 0);
        $this->widget3->setParam("items", $this->getTopDonators());
        $this->widget3->setParam("nbFields", $this->config->donatorPanel_nbFields);
        $this->widget3->show(null, true);
    }

    public function getTopDonators()
    {
        $this->storage->serverLogin;
        $sql = 'SELECT transaction_fromLogin as login, player_nickname as nickname, SUM(transaction_amount) as data'
            . ' FROM exp_planet_transaction, exp_players'
            . ' WHERE transaction_toLogin = ' . $this->db->quote($this->storage->serverLogin) . ''
            . ' AND transaction_subject = \'server_donation\''
            . ' AND transaction_fromLogin = player_login'
            . ' GROUP BY transaction_fromLogin, player_nickname'
            . ' ORDER BY data DESC'
            . ' LIMIT 0, 100';
        $data = $this->db->execute($sql);

        return $data->fetchArrayOfObject();
    }

    public function getTopPlaytime()
    {
        $this->storage->serverLogin;
        $sql = 'SELECT player_nickname as nickname, player_timeplayed as longDate'
            . ' FROM exp_players'
            . ' ORDER BY longDate DESC'
            . ' LIMIT 0, 100';

        $data = $this->db->execute($sql);

        return $data->fetchArrayOfObject();
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
    {
        $this->hide();
    }

    public function onBeginMatch()
    {
        $this->hide();
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        if ($this->storage->getCleanGamemodeName() == "endurocup" && \ManiaLivePlugins\eXpansion\Endurance\Endurance::$last_round == false) {
            return;
        }
        $this->displayWidgets();
    }

    public function hide()
    {
        $this->widget1->erase();
        $this->widget2->erase();
        $this->widget3->erase();
    }

    public function eXpOnUnload()
    {
        $this->hide();
        $this->widget1 = null;
        $this->widget2 = null;
        $this->widget3 = null;
        $this->config = null;
        parent::eXpOnUnload();
    }
}
