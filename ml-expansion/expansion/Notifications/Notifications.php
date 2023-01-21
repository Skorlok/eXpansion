<?php

namespace ManiaLivePlugins\eXpansion\Notifications;

use ManiaLivePlugins\eXpansion\Notifications\Gui\Widgets\Panel2 as NotificationPanel;
use ManiaLivePlugins\eXpansion\Notifications\Structures\Message;

//use \ManiaLivePlugins\eXpansion\Notifications\Gui\Widgets\Panel2 as NotifiPanel2;

class Notifications extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    private $publicMessages = array();

    private $personalMessages = array(array());

    public function eXpOnInit()
    {
        $this->setPublicMethod("send");
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->checkRedirect();

        foreach ($this->storage->players as $login => $player) {
            $this->onPlayerConnect($login, false);
        } // force update...
        foreach ($this->storage->spectators as $login => $player) {
            $this->onPlayerConnect($login, false);
        } // force update...
    }

    public function send($login, $message, $icon = null, $callback = null, $pluginid = null)
    {
        if (is_callable($callback) || $callback === null) {
            if ($login == null) {
                array_unshift($this->publicMessages, new Message($login, $icon, $message, $callback));
            } else {
                if (!array_key_exists($login, $this->personalMessages)) {
                    $this->personalMessages[$login] = array();
                }
                array_unshift($this->personalMessages[$login], new Message($login, $icon, $message, $callback));
            }
            $this->publicMessages = array_slice($this->publicMessages, 0, 6, true);

            foreach ($this->personalMessages as $login => $messages) {
                $this->personalMessages[$login] = array_slice($this->personalMessages[$login], 0, 6, true);
            }

            $this->reDraw();
        } else {
        }
    }

    protected function reDraw()
    {
        try {
            foreach (NotificationPanel::GetAll() as $window) {
                $login = $window->getRecipient();
                $personal = array();
                if (array_key_exists($login, $this->personalMessages)) {
                    $personal = $this->personalMessages[$login];
                }

                $messages = array_merge($personal, $this->publicMessages);
                \ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj::sortDesc($messages, "timestamp");
                $messages = array_slice($messages, 0, 6);
                $messages = array_reverse($messages);

                /** @var Config $config */
                $config = Config::getInstance();

                $window->setPositionX($config->posX);
                $window->setPositionY($config->posY);
                $window->setMessages($messages);
                $window->redraw();
            }
        } catch (\Exception $e) {
            $this->console("error:" . $e->getMessage());
        }
    }

    function onPlayerConnect($login, $isSpectator)
    {
        /** @var Config $config */
        $config = Config::getInstance();

        NotificationPanel::Erase($login);

        if (array_key_exists($login, $this->personalMessages)) {
            unset($this->personalMessages[$login]);
        }

        $info = NotificationPanel::Create($login, true);
        $info->setSize(100, 40);
        $info->setMessages($this->publicMessages);
        $info->setPosition($config->posX, $config->posY);
        $info->show();
    }

    public function onPlayerDisconnect($login, $reason = null)
    {
        NotificationPanel::Erase($login);
    }

    private function checkRedirect()
    {
        /**
         * Redirecting The Announcements of the Admin Groups plugin
         */
        $config = Config::getInstance();

        foreach (\ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad::getAvailablePlugins() as $plugin => $meta) {
            if ($this->isPluginLoaded($plugin)) {
                if (in_array($plugin, $config->redirectedPlugins)) {
                    $this->callPublicMethod((string)$plugin, 'eXpActivateChatRedirect', array($this, 'send'));
                } else {
                    $this->callPublicMethod((string)$plugin, 'eXpDeactivateChatRedirect', array($this, 'send'));
                }
            }
        }
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getName() == "redirectedPlugins") {
            $this->checkRedirect();
        } elseif ($var->getName() == "posY") {
            $this->reDraw();
        }
    }

    public function eXpOnUnload()
    {
        parent::eXpOnUnload();
        NotificationPanel::EraseAll();
        foreach (\ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad::getAvailablePlugins() as $plugin => $meta) {
            if ($this->isPluginLoaded($plugin)) {
                $this->callPublicMethod((string)$plugin, 'eXpDeactivateChatRedirect', array($this, 'send'));
            }
        }
    }
}
