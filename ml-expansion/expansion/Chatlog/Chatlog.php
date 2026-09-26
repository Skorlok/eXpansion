<?php

namespace ManiaLivePlugins\eXpansion\Chatlog;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;

class Chatlog extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $log = array();

    /** @var Window */
    protected $logWindow = null;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents();
        $this->registerChatCommand("chatlog", "showLog", 0, true);
        $this->setPublicMethod('showLog');
    }

    public function eXpOnReady()
    {
        $this->registerManialinkCallback('showLog');

        $this->logWindow = new Window("Chatlog\Gui\Windows\ChatlogWindow.xml");
        $this->logWindow->setName("Chatlog");
        $this->logWindow->setSize(140, 100);
        $this->logWindow->setTitle('Chatlog');
    }

    public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
    {
        if ($playerUid == 0 || substr($text, 0, 1) == "/") {
            return;
        }
        $player = $this->storage->getPlayerObject($login);
        if ($player == null) {
            return;
        }
        /** @var Config $config */
        $config = Config::getInstance();
        $chatMessage = new Structures\ChatMessage(time(), $login, $player->nickName, $text);
        array_unshift($this->log, $chatMessage);
        $this->log = array_slice($this->log, 0, $config->historyLenght, true);
    }

    public function showLog($login)
    {
        $items = array();
        $data  = array();
        $x     = 0;

        foreach (array_reverse($this->log) as $message) {
            $items[$x] = array(date("H:i", $message->time), $message->nickName, $message->text);
            $data[$x]  = array(-1, -1, -1);
            $x++;
        }

        $this->logWindow->setParam("chatItems", $items);
        $this->logWindow->setParam("chatData", $data);
        $this->logWindow->show($login);
    }

    public function eXpOnUnload()
    {
        if ($this->logWindow instanceof Window) {
            $this->logWindow->erase();
        }
        $this->logWindow = null;
    }
}
