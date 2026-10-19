<?php

namespace ManiaLivePlugins\eXpansion\Notifications;

use ManiaLivePlugins\eXpansion\Core\I18n\Message as I18nMessage;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\StaticWidget;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\Notifications\Structures\Message;

class Notifications extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
    private $publicMessages = array();

    private $personalMessages = array(array());

    /** @var StaticWidget */
    private $panel;

    public function eXpOnInit()
    {
        $this->setPublicMethod("send");
    }

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();

        $this->panel = new StaticWidget('Notifications\Gui\Widgets\NotificationPanel.xml');
        $this->panel->setName("Notifications");
        $this->panel->setLayer("normal");

        $this->checkRedirect();

        $this->reDraw();
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
        }
    }

    protected function reDraw()
    {
        try {
            foreach ($this->storage->players as $login => $player) {
                $this->showPanel($login);
            }
            foreach ($this->storage->spectators as $login => $player) {
                $this->showPanel($login);
            }
        } catch (\Exception $e) {
            $this->console("error:" . $e->getMessage());
        }
    }

    private function showPanel($login)
    {
        /** @var Config $config */
        $config = Config::getInstance();

        $personal = array();
        if (array_key_exists($login, $this->personalMessages)) {
            $personal = $this->personalMessages[$login];
        }

        $messages = array_merge($personal, $this->publicMessages);
        ArrayOfObj::sortDesc($messages, "timestamp");
        $messages = array_slice($messages, 0, 6);
        $messages = array_reverse($messages);

        $player = $this->storage->getPlayerObject($login);
        $lang = $player ? $player->language : null;

        for ($i = 0; $i < 6; $i++) {
            $text = "";
            if (isset($messages[$i])) {
                $text = $messages[$i]->message;
                if ($text instanceof I18nMessage) {
                    $text = $text->getParsedMessage($lang);
                }
            }
            $this->panel->setParam("msg" . $i, $this->panel->handleSpecialChars($text));
        }

        $this->panel->setPosition($config->posX, $config->posY, -60);
        $this->panel->show($login);
    }

    function onPlayerConnect($login, $isSpectator)
    {
        if (array_key_exists($login, $this->personalMessages)) {
            unset($this->personalMessages[$login]);
        }

        $this->showPanel($login);
    }

    private function checkRedirect()
    {
        /**
         * Redirecting The Announcements of the Admin Groups plugin
         */
        /** @var Config $config */
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

        if ($this->panel instanceof StaticWidget) {
            $this->panel->erase();
        }
        $this->panel = null;

        foreach (\ManiaLivePlugins\eXpansion\AutoLoad\AutoLoad::getAvailablePlugins() as $plugin => $meta) {
            if ($this->isPluginLoaded($plugin)) {
                $this->callPublicMethod((string)$plugin, 'eXpDeactivateChatRedirect', array($this, 'send'));
            }
        }
    }
}
