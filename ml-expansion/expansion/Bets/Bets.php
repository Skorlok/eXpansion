<?php
/*
 * Copyright (C) 2014 Reaby
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace ManiaLivePlugins\eXpansion\Bets;

use ManiaLive\Data\Player;
use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Bets\Classes\BetCounter;
use ManiaLivePlugins\eXpansion\Core\types\Bill;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Widget;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

/**
 * Description of Bets
 *
 * @author Reaby
 */
class Bets extends ExpPlugin
{
    const OFF = "off";
    const SET = "set";
    const ACCEPT = "accept";
    const RUNNING = "running";
    const NOBETS = "nobets";

    private $msg_fail;
    private $msg_billSuccess;
    private $msg_billPaySuccess;
    private $msg_totalStake;
    private $msg_winner;
    private $msg_payFail;

    private $widget;
    private $widgetAccept;
    private $script;
    private $actions;
    private $actionAccept;
    private $action;

    public static $state = self::OFF;
    public static $betAmount = 0;

    /** @var BetCounter[] */
    private $counters = array();

    /** @var Player[] */
    private $players = array();

    private $config;

    public function eXpOnLoad()
    {
        $this->msg_fail = eXpGetMessage('#donate#No planets billed');
        $this->msg_payFail = eXpGetMessage('#donate#The server was unable to pay your winning bet. Sorry.');
        $this->msg_billSuccess = eXpGetMessage('#donate#Bet accepted for#variable# %1$s #donate#planets');
        $this->msg_billPaySuccess = eXpGetMessage('#donate#You will recieve#variable# %1$s #donate#planets from the server soon.');
        $this->msg_totalStake = eXpGetMessage('#donate#The game is on as#variable# %1$s #donate#joins! Win stake of the bet is now#variable# %2$s #donate#planets');
        $this->msg_winner = eXpGetMessage('#variable# %1$s #donate#wins the bet with #variable# %2$s #donate#planets, congratulations');
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();

        $this->config = Config::getInstance();

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        $this->actionAccept = $aH->createAction(array($this, "acceptBet"));
        $this->action = $aH->createAction(array($this, "setBetAmount"), null);
        $this->actions = array();
        $this->actions[] = $aH->createAction(array($this, "setBetAmount"), 25);
        $this->actions[] = $aH->createAction(array($this, "setBetAmount"), 50);
        $this->actions[] = $aH->createAction(array($this, "setBetAmount"), 100);
        $this->actions[] = $aH->createAction(array($this, "setBetAmount"), 250);
        $this->actions[] = $aH->createAction(array($this, "setBetAmount"), 500);

        $this->script = new Script("Bets/Gui/Scripts");

        $this->widget = new Widget("Bets\Gui\Widgets\BetWidget.xml");
        $this->widget->setName("Bet widget");
        $this->widget->setLayer("normal");
        $this->widget->setSize(80, 20);
        $this->widget->setParam("actions", $this->actions);
        $this->widget->setParam("action", $this->action);
        $this->widget->setParam("values", array(25, 50, 100, 250, 500));
        $this->widget->registerScript($this->script);

        $this->widgetAccept = new Widget("Bets\Gui\Widgets\AcceptBetWidget.xml");
        $this->widgetAccept->setName("Bet accept widget");
        $this->widgetAccept->setLayer("normal");
        $this->widgetAccept->setSize(80, 20);
        $this->widgetAccept->setParam("action", $this->actionAccept);
        $this->widgetAccept->registerScript($this->script);

        $this->reset();
    }

    public function onTick()
    {
        foreach ($this->counters as $idx => $counter) {
            if ($counter->check()) {
                unset($this->counters[$idx]);
            }
        }
    }

    public function onBeginMatch()
    {
        $this->start(Config::getInstance()->timeoutSetBet);
    }

    public function onEndMatch($rankings, $winnerTeamOrMap)
    {
        switch (self::$state) {
            case self::RUNNING:
                $this->checkWinner();
                break;
            case self::NOBETS:
                break;
            default:
                $this->widget->erase();
                $this->widgetAccept->erase();
                $this->eXpChatSendServerMessage("#error#Map was skipped or replayed before bet was placed.");
                break;
        }
    }

    private function checkWinner()
    {
        $rankings = $this->expStorage->getCurrentRanking();
        $total = (count($this->players) * self::$betAmount);

        foreach ($rankings as $index => $player) {
            if (array_key_exists($player->login, $this->players)) {
                $this->eXpChatSendServerMessage($this->msg_winner, null, array($player->nickName . '$z$s', $total));
                $this->connection->pay($player->login, intval($total), 'Winner of the bet!');
                $this->players = array();
                return;
            }
        }
    }

    private function setState($data)
    {
        self::$state = $data;
    }

    public function setBetAmount($login, $amount = null, $data = array())
    {
        if ($amount == null) {
            $amount = $data['betAmount'];
        }

        if (!is_numeric($amount) || empty($amount) || $amount < 1) {
            $this->eXpChatSendServerMessage('#error#Can\'t place a bet, the value: "#variable#%1$s#error#" is not numeric value!', $login, array($amount));
            return;
        }

        if ($amount < 20) {
            $this->eXpChatSendServerMessage('#error#Custom value must be over 20 planets!', $login);
            return;
        }

        self::$betAmount = intval($amount);

        $bill = $this->eXpStartBill($login, $this->storage->serverLogin, $amount, 'Acccept Bet ?', array($this, 'billSetSuccess'));
        $bill->setErrorCallback(5, array($this, 'billFail'));
        $bill->setErrorCallback(6, array($this, 'billFail'));
        $bill->setSubject('bets_plugin');
    }

    public function acceptBet($login)
    {
        $bill = $this->eXpStartBill($login, $this->storage->serverLogin, self::$betAmount, 'Acccept Bet ?', array($this, 'billAcceptSuccess'));
        $bill->setErrorCallback(5, array($this, 'billFail'));
        $bill->setErrorCallback(6, array($this, 'billFail'));
        $bill->setSubject('bets_plugin');
    }

    /**
     *    this called when recieves the a bet from server
     *
     * @param \ManiaLivePlugins\eXpansion\Core\types\Bill $bill
     */
    public function billPaySuccess(Bill $bill)
    {
        $login = $bill->getSourceLogin();
        $this->eXpChatSendServerMessage($this->msg_billPaySuccess, $login, array($bill->getAmount()));
    }

    /**
     *    this called when player accepts a bet
     *
     * @param \ManiaLivePlugins\eXpansion\Core\types\Bill $bill
     */
    public function billAcceptSuccess(Bill $bill)
    {
        $login = $bill->getSourceLogin();
        try {
            $this->players[$login] = $this->storage->getPlayerObject($login);
            $this->eXpChatSendServerMessage($this->msg_billSuccess, $login, array($bill->getAmount()));
            $this->updateBetWidget();
            $this->announceTotal($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage($this->msg_fail, $login, array($e->getMessage()));
        }
    }

    /**
     * This is called when initial bet is accepted and planets has been transferred
     *
     * @param \ManiaLivePlugins\eXpansion\Core\types\Bill $bill
     */
    public function billSetSuccess(Bill $bill)
    {
        $this->setState(self::ACCEPT);
        $login = $bill->getSourceLogin();
        try {
            $this->players[$login] = $this->storage->getPlayerObject($login);
            $this->eXpChatSendServerMessage($this->msg_billSuccess, $login, array($bill->getAmount()));

            $this->widget->erase();

            $this->updateBetWidget();
            $this->announceTotal($login);
        } catch (\Exception $e) {
            $this->eXpChatSendServerMessage($this->msg_fail, $login, array($e->getMessage()));
        }
    }

    public function announceTotal($login)
    {
        $total = (count($this->players) * self::$betAmount);
        $nick = $this->players[$login]->nickName . '$z$s';
        $this->eXpChatSendServerMessage($this->msg_totalStake, null, array($nick, $total));
    }

    public function billFail(Bill $bill, $state, $stateName)
    {
        $this->eXpChatSendServerMessage($this->msg_fail, $bill->getSourceLogin());
    }

    public function billPayFail(Bill $bill, $state, $stateName)
    {
        $this->eXpChatSendServerMessage($this->msg_payFail, $bill->getSourceLogin());
    }

    public function updateBetWidget()
    {
        $out = \ManiaLivePlugins\eXpansion\Helpers\Maniascript::stringifyAsStringList(array_keys($this->players));
        if (count($this->players) == 0) {
            $out = "Text[]";
        }
        $this->script->setParam("hideFor", $out);

        if (self::$state == self::SET) {
            $this->widget->setPosition($this->config->betWidget_PosX, $this->config->betWidget_PosY, 0);
            $this->widgetAccept->erase();
            $this->widget->show(null, true);
        }
        if (self::$state == self::ACCEPT) {
            $this->widgetAccept->setPosition($this->config->betWidget_PosX, $this->config->betWidget_PosY, 0);
            $this->widget->erase();
            $this->widgetAccept->setParam("text", 'Accept bet for %1$s planets ?');
            $this->widgetAccept->setParam("textParam", array("" . self::$betAmount));
            $this->widgetAccept->show(null, true);
        }
    }

    public function start($timeout)
    {
        $this->reset();
        $this->setState(self::SET);
        $this->updateBetWidget();
        $this->counters[] = new BetCounter($timeout, array($this, "closeAccept"));
    }

    public function closeAccept($param = null)
    {
        if (self::$state == self::ACCEPT) {
            $this->setState(self::RUNNING);
        } else {
            $this->setState(self::NOBETS);
        }

        $this->widget->erase();
        $this->widgetAccept->erase();
    }

    private function reset()
    {
        self::$state = self::OFF;
        self::$betAmount = 0;
        $this->counters = array();
        $this->players = array();
        
        $this->widget->erase();
        $this->widgetAccept->erase();
    }

    public function eXpOnUnload()
    {
        $this->widget->erase();
        $this->widgetAccept->erase();

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        $aH->deleteAction($this->action);
        $aH->deleteAction($this->actionAccept);
        foreach ($this->actions as $action) {
            $aH->deleteAction($action);
        }
        $this->actions = array();
        $this->action = null;
        $this->script = null;
        $this->widget = null;
        $this->widgetAccept = null;

        parent::eXpOnUnload();
    }
}
