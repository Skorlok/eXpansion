<?php

namespace ManiaLivePlugins\eXpansion\Adm\Gui\Windows;

class ScriptSettings extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    private $pager;

    /** @var \Maniaplanet\DedicatedServer\Connection */
    private $connection;

    /** @var \ManiaLive\Data\Storage */
    private $storage;
    private $items = array();
    private $ok;
    private $actionOk;

    public static $mainPlugin;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->pager->setPosX(5);
        $this->mainFrame->addComponent($this->pager);
        $this->actionOk = $this->createAction(array($this, "ok"));

        $this->ok = new \ManiaLive\Gui\Elements\Xml();
        $this->ok->setContent('<frame posn="140 -95.75 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Apply", $login), null, null, "0d0", null, null, $this->actionOk, null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->ok);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX - 5, $this->sizeY - 8);
        $this->pager->setStretchContentX($this->sizeX);
    }

    protected function onShow()
    {
        $this->populateList();
    }

    public function populateList()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }
        $this->pager->clearItems();
        $this->items = array();

        $login = $this->getRecipient();
        $x = 0;
        $settings = $this->connection->getModeScriptSettings();

        foreach ($settings as $var => $setting) {
            $this->items[$x] = new \ManiaLivePlugins\eXpansion\Adm\Gui\Controls\ScriptSetting($x, $var, $setting, $this->sizeX);
            $this->pager->addItem($this->items[$x]);
            $x++;
        }
    }

    public function ok($login, $settings = null)
    {
        $diffParams = array();

        foreach ($this->items as $item) {
            if ($item->checkBox !== null) {
                if ($settings[$item->settingName] == 1) {
                    $settings[$item->settingName] = true;
                } else if ($settings[$item->settingName] == 0) {
                    $settings[$item->settingName] = false;
                } else {
                    $settings[$item->settingName] = $item->checkBox->getStatus();
                }

                if ($item->checkBox->getStatus() != $settings[$item->settingName]) {
                    $diffParams[$item->settingName] = array(($item->checkBox->getStatus() ? "True" : "False"), ($settings[$item->settingName] ? "True" : "False"));
                }
            } else {
                settype($settings[$item->settingName], $item->type);

                if ($item->inputbox->getText() != $settings[$item->settingName]) {
                    $diffParams[$item->settingName] = array(($item->inputbox->getText() ? $item->inputbox->getText() : '$iEmpty$i'), ($settings[$item->settingName] ? $settings[$item->settingName] : '$iEmpty$i'));
                }
            }
        }

        if ($settings) {
            $this->connection->setModeScriptSettings($settings);
            self::$mainPlugin->afterScriptSettings($login, $diffParams);
        }

        $this->Erase($login);
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }

        $this->items = array();
        $this->pager->destroy();
        $this->connection = null;
        $this->storage = null;
        $this->destroyComponents();
        parent::destroy();
    }
}
