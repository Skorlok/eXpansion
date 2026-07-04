<?php

namespace ManiaLivePlugins\eXpansion\NetStat;

use ManiaLive\Application\Event;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Core\Core;
use ManiaLivePlugins\eXpansion\Core\Structures\NetStat as NetStatStructure;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\NetStat\Gui\Widgets\Helper_Netstat;
use ManiaLivePlugins\eXpansion\NetStat\Gui\Widgets\Helper_PingAnswer;

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
    private $kickActions = array();
    private $netStatViewers = array();
    private $lastUpdate = 0;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();
        $this->enableApplicationEvents(Event::ON_POST_LOOP);

        /** @var ActionHandler $ahandler */
        $ahandler = ActionHandler::getInstance();
        $value = $ahandler->createAction(array($this, "answerNetstat"));

        $widget = Helper_Netstat::Create(null);
        $widget->setActionId($value);
        $widget->show();

        $this->netStatWindow = new Window("NetStat\Gui\Windows\NetStatWindow.xml");
        $this->netStatWindow->setName("Network Status");
        $this->netStatWindow->setSize(140, 100);
        $this->netStatWindow->setTitle("Network Status");
        $this->netStatWindow->registerCloseCallback(array($this, 'onNetStatWindowClose'));

        $this->cmdNetStat = AdminGroups::addAdminCommand("lag", $this, 'showNetStat', Permission::CHAT_ADMINCHAT);
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();

        /** @var ActionHandler $aH */
        $aH = ActionHandler::getInstance();
        foreach ($this->kickActions as $action) {
            $aH->deleteAction($action);
        }
        $this->kickActions = array();

        if ($this->netStatWindow instanceof Window) {
            $this->netStatWindow->erase();
        }
        $this->netStatWindow = null;

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

        /** @var ActionHandler $ahandler */
        $ahandler = ActionHandler::getInstance();
        $rows = array();
        $index = 0;

        foreach ($netStat as $login => $stat) {
            if ($index >= 50) {
                break;
            }

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

            if (!isset($this->kickActions[$login])) {
                $this->kickActions[$login] = $ahandler->createAction(array($this, 'kick'), $login);
            }

            $rows[] = array(
                'display'    => ($index + 1) . '. ' . $nick,
                'latency'    => $color . $stat->updateLatency . 'ms',
                'kickAction' => $this->kickActions[$login],
            );
            $index++;
        }

        $this->netStatWindow->setParam("rows", $rows);
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
        if (isset($this->kickActions[$login])) {
            /** @var ActionHandler $aH */
            $aH = ActionHandler::getInstance();
            $aH->deleteAction($this->kickActions[$login]);
            unset($this->kickActions[$login]);
        }
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

        $widget = Helper_PingAnswer::Create($login);
        $widget->setStamp($data['stamp']);
        $widget->show();
    }
}
