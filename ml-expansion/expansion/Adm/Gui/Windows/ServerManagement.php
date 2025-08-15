<?php

namespace ManiaLivePlugins\eXpansion\Adm\Gui\Windows;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;

/**
 * Server Controlpanel Main window
 *
 * @author Petri
 */
class ServerManagement extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    /** @var \Maniaplanet\DedicatedServer\Connection */
    private $connection;

    /** @var \ManiaLive\Data\Storage */
    private $storage;
    private $frame;
    private $closeButton;
    private $actions;
    private $btn1;
    private $btn2;

    protected function onConstruct()
    {
        parent::onConstruct();

        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());

        $this->actions = new \stdClass();
        $this->actions->stopServerf = ActionHandler::getInstance()->createAction(array($this, "stopServer"));
        $this->actions->stopServer = \ManiaLivePlugins\eXpansion\Gui\Gui::createConfirm($this->actions->stopServerf);
        $this->actions->stopManialivef = ActionHandler::getInstance()->createAction(array($this, "stopManialive"));
        $this->actions->stopManialive = \ManiaLivePlugins\eXpansion\Gui\Gui::createConfirm($this->actions->stopManialivef);

        if (AdminGroups::hasPermission($this->getRecipient(), Permission::SERVER_STOP_DEDICATED)) {
            $this->btn1 = new \ManiaLive\Gui\Elements\Xml();
            $this->btn1->setContent('<frame posn="0 0 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(40, 6, __("Stop Server", $this->getRecipient()), null, null, "d00", null, null, $this->actions->stopServer, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($this->btn1);
        }

        if (AdminGroups::hasPermission($this->getRecipient(), Permission::SERVER_STOP_MANIALIVE)) {
            $this->btn2 = new \ManiaLive\Gui\Elements\Xml();
            $this->btn2->setContent('<frame posn="31.5 0 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(40, 6, __("Stop Manialive", $this->getRecipient()), null, null, "d00", null, null, $this->actions->stopManialive, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($this->btn2);
        }

        $this->addComponent($this->frame);
    }

    public function stopServer($login)
    {
        $this->connection->stopServer();
    }

    public function stopManialive($login)
    {
        $this->connection->chatSendServerMessage("[Notice] Stopping eXpansion...");
        $this->connection->sendHideManialinkPage();
        \ManiaLive\Application\Application::getInstance()->kill();
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->frame->setPosition(2, -6);
    }

    public function destroy()
    {
        ActionHandler::getInstance()->deleteAction($this->actions->stopServer);
        ActionHandler::getInstance()->deleteAction($this->actions->stopServerf);
        ActionHandler::getInstance()->deleteAction($this->actions->stopManialive);
        ActionHandler::getInstance()->deleteAction($this->actions->stopManialivef);
        unset($this->actions);
        parent::destroy();
    }
}
