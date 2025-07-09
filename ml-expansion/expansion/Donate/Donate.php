<?php

namespace ManiaLivePlugins\eXpansion\Donate;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\Menu\Menu;

class Donate extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    public static $billId = array();
    private $config;

    /**
     * onLoad()
     * Function called on loading of ManiaLive.
     *
     * @return void
     */
    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->config = Config::getInstance();

        $this->setPublicMethod("donate");

        $cmd = $this->registerChatCommand("donate", "donate", 2, true);
        $cmd = $this->registerChatCommand("donate", "donate", 1, true);
        $cmd->help = '/donate X where X is ammount of Planets';

        /** @var ActionHandler @aH */
        $aH = ActionHandler::getInstance();
        Menu::addMenuItem("Donate",
            array("Donate" => array(null, array(
                "Donate 20 planets" => array(null, $aH->createAction(array($this, "donate"), "20")),
                "Donate 50 planets" => array(null, $aH->createAction(array($this, "donate"), "50")),
                "Donate 100 planets" => array(null, $aH->createAction(array($this, "donate"), "100")),
                "Donate 250 planets" => array(null, $aH->createAction(array($this, "donate"), "250")),
                "Donate 500 planets" => array(null, $aH->createAction(array($this, "donate"), "500")),
                "Donate 1000 planets" => array(null, $aH->createAction(array($this, "donate"), "1000")),
                "Donate 2000 planets" => array(null, $aH->createAction(array($this, "donate"), "2000"))
            )))
        );
    }

    /**
     * Donate()
     * Function provides the /donate command.
     *
     * @param mixed $login
     * @param mixed $amount
     *
     * @return void
     */
    public function donate($login, $amount = null, $someOtherPlayer = null)
    {
        $player = $this->storage->getPlayerObject($login);
        if ($amount == "help" || $amount == null) {
            $this->eXpChatSendServerMessage('#error#Donate takes one argument and it needs to be numeric.', $login);
            return;
        }
        if (is_numeric($amount)) {
            $amount = (int)$amount;
        } else {
            $this->eXpChatSendServerMessage('#error#Donate takes one argument and it needs to be numeric.', $login);
            return;
        }

        $toLogin = $someOtherPlayer;
        if (empty($someOtherPlayer)) {
            $toLogin = $this->storage->serverLogin;
        }

        $bill = $this->eXpStartBill($login, $toLogin, $amount, 'Planets Donation', array($this, 'billSucess'));
        $bill->setErrorCallback(5, array($this, 'billFail'));
        $bill->setErrorCallback(6, array($this, 'billFail'));
        $bill->setSubject('server_donation');

    }

    public function billSucess(\ManiaLivePlugins\eXpansion\Core\types\Bill $bill)
    {
        if ($bill->getDestinationLogin() != $this->storage->serverLogin) {
            $this->eXpChatSendServerMessage('#donate#You donated #variable#' . $bill->getAmount() . '#donate# Planets to #variable#' . $toLogin . '$z$s#donate#', $bill->getSourceLogin());
        } else {
            if ($bill->getAmount() < $this->config->donateAmountForGlobalMsg) {
                $this->eXpChatSendServerMessage('#donate#You donated #variable#' . $bill->getAmount() . '#donate# Planets to server$z$s#donate#, Thank You.', $bill->getSourceLogin());
            } else {
                $fromPlayer = $this->storage->getPlayerObject($bill->getSourceLogin());
                $this->eXpChatSendServerMessage('#donate#The server recieved a donation of #variable#' . $bill->getAmount() . '#donate# Planets from #variable#' . $fromPlayer->nickName . '$z$s#donate#, Thank You.', null);
            }
        }
    }

    public function billFail(\ManiaLivePlugins\eXpansion\Core\types\Bill $bill, $state, $stateName)
    {
        if ($state == 5) { // No go
            $login = $bill->getSourceLogin();

            $this->eXpChatSendServerMessage('#error#No Planets billed.', $login);
        }

        if ($state == 6) {  // Error
            $fromPlayer = $this->storage->getPlayerObject($bill->getSourceLogin());
            $this->eXpChatSendServerMessage('#error# There was error with player #variable#' . $fromPlayer->nickName . '$z$s#error# donation.');
            $this->eXpChatSendServerMessage('#error#' . $stateName, $bill->getSourceLogin());
        }
    }
}
