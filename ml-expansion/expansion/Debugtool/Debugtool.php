<?php
/*
 * Copyright (C) Error: on line 4, column 33 in Templates/Licenses/license-gpl20.txt
  The string doesn't match the expected date/time format. The string to parse was: "7.2.2014". The expected format was: "dd-MMM-yyyy". Petri
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace ManiaLivePlugins\eXpansion\Debugtool;

use ManiaLive\Event\Dispatcher;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\ManiaLink;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;

/**
 * Description of Debugtool
 *
 * @author Petri
 */
class Debugtool extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    private $ticker = 0;
    private $testActive = false;
    private $fakeLogin = "";

    /** @var Widget */
    private $memWidget;

    /** @var Window */
    private $testWindow;

    /** @var ManiaLink */
    private $testWidget;

    private $lastMemUsage = 0;

    private $nextLoop = 0;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableApplicationEvents();
        $this->enableTickerEvent();
        $this->registerChatCommand("connect", "connect", 1, true, \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::get());
        $this->registerChatCommand("disconnect", "disconnect", 1, true, \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::get());
        $this->registerChatCommand("faketest", "test", 0, true, \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::get());
        
        $this->memWidget = new Widget('Debugtool\Gui\debugWidget.xml');
        $this->memWidget->setName("Debug widget");
        $this->memWidget->setLayer("normal");
        $this->memWidget->setSize(60, 7);
        $this->memWidget->setPosition(155, -45, -60);

        $this->testWindow = new Window('Debugtool\Gui\testWindow.xml');
        $this->testWindow->setName("Test window");
        $this->testWindow->setSize(120, 60);
        $this->testWindow->setTitle("testwindow");

        $this->testWidget = new ManiaLink('Debugtool\Gui\testWidget.xml');
        $this->testWidget->setName("Test widget");
        $this->testWidget->setLayer("normal");
        $this->testWidget->setPosition(0, 0, -60);

        $this->mem(null);
        $this->registerChatCommand("te", "team", 0, false);
    }

    public function team()
    {
        $outScores = array();
        $outScores[] = array("PlayerId" => 0, "Score" => 1);
        $outScores[] = array("PlayerId" => 1, "Score" => 2);

        $this->connection->forceScores($outScores, false);
    }

    public function eXpOnUnload()
    {
        $this->disableTickerEvent();

        if ($this->memWidget instanceof Widget) {
            $this->memWidget->erase();
        }
        $this->memWidget = null;

        if ($this->testWindow instanceof Window) {
            $this->testWindow->erase();
        }
        $this->testWindow = null;

        if ($this->testWidget instanceof ManiaLink) {
            $this->testWidget->erase();
        }
        $this->testWidget = null;

        parent::eXpOnUnload();
    }

    public function onTick()
    {
        if ($this->testActive) {
            if ($this->ticker == 1) {
                $this->connection->disconnectFakePlayer($this->fakeLogin);
                $this->ticker = 0;
            } else {
                $this->fakeLogin = $this->connection->connectFakePlayer();
                $this->ticker = 1;
            }
        }
    }

    public function testWin($login)
    {
        $this->testWindow->show($login);
    }

    public function testWidget($login)
    {
        $rows = array();
        foreach ($this->storage->players + $this->storage->spectators as $player) {
            $rows[] = array(
                'posY'     => -5 * count($rows),
                'login'    => $this->testWidget->handleSpecialChars($player->login),
                'nickname' => $this->testWidget->handleSpecialChars($player->nickName),
                'slot'     => $player->hasPlayerSlot ? '$0d0true' : '$d00false',
            );
        }

        $this->testWidget->setParam("rows", $rows);
        $this->testWidget->show($login);
    }

    public function connect($login, $playercount)
    {
        for ($x = 0; $x < $playercount; $x++) {
            $this->connection->connectFakePlayer();
        }
    }

    public function disconnect($login, $amount)
    {
        try {
            if (is_numeric($amount)) {
                $x = 0;
                $players = array_merge($this->storage->players, $this->storage->spectators);

                foreach ($players as $login => $player) {
                    echo "$login, $x,  $amount\n";
                    if (strstr($login, "fakeplayer") !== false && $x < $amount) {
                        $this->connection->disconnectFakePlayer($login);
                        $x++;
                    }
                }
            } else {
                $this->connection->disconnectFakePlayer("*");
            }
        } catch (\Exception $e) {
            echo "error disconnecting;";
        }
    }

    public function onStatusChanged($statusCode, $statusName)
    {
        echo $statusCode . ": " . $statusName . " \n";
    }

    public function profilere()
    {
        Dispatcher::setApplicationListener(new Profiler());
    }

    public function test($login)
    {
        $this->testActive = !$this->testActive;
    }

    public function logMemory()
    {
        $mem = "Memory Usage: " . round(memory_get_usage() / 1024 / 1024) . "Kb";
        print "\n" . $mem . "\n";
        $this->connection->chatSend($mem);
    }

    public function mem($login)
    {
        $usage = memory_get_usage();

        if ($usage == $this->lastMemUsage) {
            $color = "fff";
        } else {
            $color = ($usage > $this->lastMemUsage) ? "d22" : "2d2";
        }
        $this->lastMemUsage = $usage;

        $this->memWidget->setParam("color", $color);
        $this->memWidget->setParam("text", "mem usage: " . round($usage / 1024 / 1024, 3) . " Mb");
        $this->memWidget->show(null, true);
    }

    public function onPostLoop()
    {
        $startTime = microtime(true);
        if ($startTime < $this->nextLoop) {
            return;
        }

        $this->mem(null);

        $this->nextLoop = microtime(true) + 0.95;
    }

    public function crash()
    {
        throw new \Exception("Crash Test");
    }
}
