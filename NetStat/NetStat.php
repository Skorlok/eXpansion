<?php

namespace ManiaLivePlugins\eXpansion\NetStat;

use ManiaLive\Application\Event;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\Structures\NetStat as NetStatStructure;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\ManiaLink;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

/**
 * Description of Netstat
 *
 * @author Petri
 */
class NetStat extends ExpPlugin
{

    private $postLoopStamp = 0;

    /**
     *
     * @var NetStatStructure[]
     */
    public static $netStat = array();
    private $cmdNetStat;

    /** @var Window */
    private $netStatWindow;

    /** @var ManiaLink */
    private $netStatHelper;

    /** @var ManiaLink */
    private $pingAnswer;

    /** @var Script */
    private $pingAnswerScript;

    private $netStatViewers = array();
    private $lastUpdate = 0;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();
        $this->enableApplicationEvents(Event::ON_POST_LOOP);

        $this->registerManialinkCallback('kick', false, true);
        $this->registerManialinkCallback('answerNetstat', true);

        $script = new Script('NetStat\Gui\Scripts\Netstat');
        $script->setParam('updateFreq', 5000);
        $script->setParam('action', 'exp:eXpansion.NetStat:answerNetstat');

        $this->netStatHelper = new ManiaLink('NetStat\Gui\Widgets\Helper_Netstat.xml');
        $this->netStatHelper->setName('Netstat Helper');
        $this->netStatHelper->setLayer('normal');
        $this->netStatHelper->setPosition(0, 0, -60);
        $this->netStatHelper->setScripts($script);
        $this->netStatHelper->show(null, true);

        $this->pingAnswerScript = new Script('NetStat\Gui\Scripts\PingAnswer');

        $this->pingAnswer = new ManiaLink('NetStat\Gui\Widgets\Helper_PingAnswer.xml');
        $this->pingAnswer->setName('Netstat PingAnswer');
        $this->pingAnswer->setLayer('normal');
        $this->pingAnswer->setPosition(0, 0, -60);
        $this->pingAnswer->setScripts($this->pingAnswerScript);

        $this->netStatWindow = new Window("NetStat\Gui\Windows\NetStatWindow.xml");
        $this->netStatWindow->setName("Network Status");
        $this->netStatWindow->setSize(70, 100);
        $this->netStatWindow->setTitle("Network Status");
        $this->netStatWindow->registerCloseCallback(array($this, 'onNetStatWindowClose'));

        $this->cmdNetStat = AdminGroups::addAdminCommand("lag", $this, 'showNetStat', Permission::CHAT_ADMINCHAT);
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();

        if ($this->netStatWindow instanceof Window) {
            $this->netStatWindow->erase();
        }
        $this->netStatWindow = null;

        if ($this->netStatHelper instanceof ManiaLink) {
            $this->netStatHelper->erase();
        }
        $this->netStatHelper = null;

        if ($this->pingAnswer instanceof ManiaLink) {
            $this->pingAnswer->erase();
        }
        $this->pingAnswer = null;
        $this->pingAnswerScript = null;

        $admingroup = AdminGroups::getInstance();
        $admingroup->removeAdminCommand($this->cmdNetStat);
        $admingroup->removeShortAllias($this->cmdNetStat);
        $this->cmdNetStat = null;
    }

    public function showNetStat($login, $params)
    {
        $this->netStatViewers[$login] = $login;
        $this->refreshWindow(array($login));
    }

    private function refreshWindow($logins)
    {
        $netStat = self::$netStat;
        ksort($netStat);

        $items = array();
        $data  = array();
        $i     = 0;
        foreach ($netStat as $login => $stat) {
            if (isset($this->storage->players[$login])) {
                $nick = $this->storage->players[$login]->nickName;
            } else if (isset($this->storage->spectators[$login])) {
                $nick = $this->storage->spectators[$login]->nickName;
            } else {
                $nick = $login;
            }

            $color = '$f00';
            if ($stat->updateLatency < 300) {
                $color = '$f90';
            }
            if ($stat->updateLatency < 600) {
                $color = '$0f0';
            }

            $items[$i] = array(($i + 1) . '. ' . $nick, $color . $stat->updateLatency . 'ms');
            $data[$i] = array(-1, -1, 'exp:eXpansion.NetStat:kick:' . $login);
            $i++;
        }

        $this->netStatWindow->setParam("netstatsItems", $items);
        $this->netStatWindow->setParam("netstatsData",  $data);
        $this->netStatWindow->show($logins);
    }

    public function onTick()
    {
        if (!empty($this->netStatViewers) && time() - $this->lastUpdate > 5) {
            $this->lastUpdate = time();
            $this->refreshWindow(array_values($this->netStatViewers));
        }
    }

    public function kick($login, $kickLogin)
    {
        AdminGroups::getInstance()->adminCmd($login, "kick " . $kickLogin . " \"Network Lag was too big\"");
    }

    public function onNetStatWindowClose($login)
    {
        unset($this->netStatViewers[$login]);
    }

    public function onPlayerDisconnect($login, $disconnectionReason = null)
    {
        unset($this->netStatViewers[$login]);
    }

    public function onPostLoop()
    {
        $this->postLoopStamp = microtime(true);
    }

    public function answerNetstat($login, $data)
    {
        $loop = (microtime(true) - $this->postLoopStamp) * 1000;

        if (array_key_exists($login, Core::$netStat)) {
            $stat = Core::$netStat[$login];
            $stat->updateLatency = $data['latency'] - floor($loop);
            self::$netStat[$login] = $stat;
        }

        $this->pingAnswerScript->setParam('stamp', $data['stamp']);
        $this->pingAnswer->show($login);
    }
}
