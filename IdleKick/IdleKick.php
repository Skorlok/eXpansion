<?php

namespace ManiaLivePlugins\eXpansion\IdleKick;

class IdleKick extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $timeStamps = array();

    /** @var integer */
    private $tickCounter = 0;

    /** @var Config */
    private $config;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        $this->enableTickerEvent();
        $this->enableStorageEvents();
        $this->enableScriptEvents("Trackmania.Event.Respawn");
        foreach ($this->storage->players as $player) {
            $this->onPlayerConnect($player->login, false);
        }
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        if (!$isSpectator) {
            $this->checkActivity($login);
        }
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        if (array_key_exists($login, $this->timeStamps) && !empty($login)) {
            unset($this->timeStamps[$login]);
        }
    }

    public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
    {
        $this->checkActivity($login);
    }

    public function onTick()
    {
        if ($this->tickCounter % 10 == 0) {
            $this->tickCounter = 0;
            $this->config = Config::getInstance();
            foreach ($this->timeStamps as $playerLogin => $time) {
                if ((time() - $time) > ($this->config->idleMinutes * 60)) {

                    $player = $this->storage->getPlayerObject($playerLogin);

                    if ($this->config->idleKickReally) {
                        $this->eXpChatSendServerMessage('%s $z$s$fff is idle and is being idle kicked!', null, array($player->nickName));
                        $this->connection->kick($playerLogin, "Idle Kick");
                    } else {
                        $this->eXpChatSendServerMessage('%s $z$s$fff is idle and is being sent to spectate!', null, array($player->nickName));
                        unset($this->timeStamps[$playerLogin]);
                        $this->connection->forceSpectator($playerLogin, 3);
                    }
                }
            }
        }
        $this->tickCounter++;
    }

    public function checkActivity($login)
    {
        if ($login != $this->storage->serverLogin && !empty($login)) {
            $this->timeStamps[$login] = time();
        }
    }

    public function eXpOnModeScriptCallback($callback, $array)
    {
        $params = array();
		if (isset($array[0]) && !empty($array[0])) {
			$params = json_decode($array[0], true);
		}

        switch ($callback) {
            case 'Trackmania.Event.Respawn':
                $this->checkActivity($params['login']);
		    	break;
        }
    }

    public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
    {
        if ($playerUid != 0) {
            $player = $this->storage->getPlayerObject($login);
            if (!$player->spectator) {
                $this->checkActivity($login);
            }
        }
    }

    public function onPlayerChangeSide($playerInfo, $old)
    {
        $player = \Maniaplanet\DedicatedServer\Structures\PlayerInfo::fromArray($playerInfo);
        $login = $player->login;

        if ($player->spectator) {
            if (array_key_exists($login, $this->timeStamps)) {
                unset($this->timeStamps[$login]);
            }
        } else {
            $this->checkActivity($login);
        }
    }
}
