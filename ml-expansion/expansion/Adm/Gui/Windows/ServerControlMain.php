<?php

namespace ManiaLivePlugins\eXpansion\Adm\Gui\Windows;

use ManiaLivePlugins\eXpansion\Helpers\Storage;

/**
 * Server Control panel Main window
 *
 * @author Petri
 */
class ServerControlMain extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    /** @var \ManiaLivePlugins\eXpansion\Adm\Adm */
    public static $mainPlugin;
    private $frame;
    private $actions;


    public function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

        $this->setTitle(__('Control Panel', $login));
        $btnX = 40;
        $btnY = 5.5;

        $this->frame = new \ManiaLive\Gui\Controls\Frame(0, -7);
        $flow = new \ManiaLib\Gui\Layouts\Flow(150, $btnY + 2);
        $flow->setMargin(2, 1);

        $this->frame->setLayout($flow);

        $this->actions = new \stdClass();
        $this->actions->serverOptions = $this->createAction(array(self::$mainPlugin, "serverOptions"));
        $this->actions->gameOptions = $this->createAction(array(self::$mainPlugin, "gameOptions"));
        $this->actions->matchSettings = $this->createAction(array(self::$mainPlugin, "matchSettings"));
        $this->actions->serverManagement = $this->createAction(array(self::$mainPlugin, "serverManagement"));
        $this->actions->adminGroups = $this->createAction(array(self::$mainPlugin, "adminGroups"));
        $this->actions->scriptSettings = $this->createAction(array(self::$mainPlugin, "scriptSettings"));
        $this->actions->forceScores = $this->createAction(array(self::$mainPlugin, "forceScores"));
        $this->actions->roundPoints = $this->createAction(array(self::$mainPlugin, "roundPoints"));
        $this->actions->dbTools = $this->createAction(array(self::$mainPlugin, "dbTools"));
        $this->actions->expSettings = $this->createAction(array(self::$mainPlugin, "showExpSettings"));
        $this->actions->votesConfig = $this->createAction(array(self::$mainPlugin, "showVotesConfig"));
        $this->actions->pluginManagement = $this->createAction(array(self::$mainPlugin, "showPluginManagement"));


        $btn = new \ManiaLive\Gui\Elements\Xml();
        $btn->setContent('<frame posn="0 0 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Deciated Control", $login), null, null, null, null, null, $this->actions->serverManagement, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($btn);

        $btn = new \ManiaLive\Gui\Elements\Xml();
        $btn->setContent('<frame posn="33.5 0 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Server options", $login), null, null, null, null, null, $this->actions->serverOptions, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($btn);

        if (!$this->eXpIsRelay()) {
            $btn = new \ManiaLive\Gui\Elements\Xml();
            $btn->setContent('<frame posn="67 0 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Game options", $login), null, null, null, null, null, $this->actions->gameOptions, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($btn);
        }

        $btn = new \ManiaLive\Gui\Elements\Xml();
        $btn->setContent('<frame posn="100.5 0 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Admin Groups", $login), null, null, null, null, null, $this->actions->adminGroups, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($btn);

        if (!$this->eXpIsRelay()) {
            $btn = new \ManiaLive\Gui\Elements\Xml();
            $btn->setContent('<frame posn="0 -6.625 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Match settings", $login), null, null, null, null, null, $this->actions->matchSettings, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($btn);

            $btn = new \ManiaLive\Gui\Elements\Xml();
            $btn->setContent('<frame posn="33.5 -6.625 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("ScriptMode settings", $login), null, null, null, null, null, $this->actions->scriptSettings, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($btn);

            $btn = new \ManiaLive\Gui\Elements\Xml();
            $btn->setContent('<frame posn="67 -6.625 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Force Scores", $login), null, null, null, null, null, $this->actions->forceScores, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($btn);

            $btn = new \ManiaLive\Gui\Elements\Xml();
            $btn->setContent('<frame posn="100.5 -6.625 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Round points", $login), null, null, null, null, null, $this->actions->roundPoints, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($btn);
        }

        $btn = new \ManiaLive\Gui\Elements\Xml();
        $btn->setContent('<frame posn="0 -13.25 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Database tools", $login), null, null, null, null, null, $this->actions->dbTools, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($btn);

        $btn = new \ManiaLive\Gui\Elements\Xml();
        $btn->setContent('<frame posn="33.5 -13.25 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("eXpansion Settings", $login), null, null, null, null, null, $this->actions->expSettings, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($btn);

        $btn = new \ManiaLive\Gui\Elements\Xml();
        $btn->setContent('<frame posn="67 -13.25 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Plugin Management", $login), null, null, null, null, null, $this->actions->pluginManagement, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($btn);
        
        if (!$this->eXpIsRelay()) {
            $btn = new \ManiaLive\Gui\Elements\Xml();
            $btn->setContent('<frame posn="100.5 -13.25 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML($btnX, $btnY, __("Configure Votes", $login), null, null, null, null, null, $this->actions->votesConfig, null, null, null, null, null, null) . '</frame>');
            $this->frame->addComponent($btn);
        }

        $this->addComponent($this->frame);

    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
    }

    public function destroy()
    {
        $this->frame->clearComponents();
        $this->connection = null;
        $this->storage = null;

        parent::destroy();
    }

    public function eXpIsRelay()
    {
        return Storage::getInstance()->isRelay;
    }
}
