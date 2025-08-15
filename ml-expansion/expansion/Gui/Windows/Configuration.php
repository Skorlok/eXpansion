<?php

namespace ManiaLivePlugins\eXpansion\Gui\Windows;

use ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted as Checkbox;

class Configuration extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $pager;

    /** @var CheckboxScripted[] */
    protected $items = array();

    protected $ok;

    protected $cancel;

    private $actionOk;

    private $gameMode;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();
        $this->setTitle(__("Configure HUD", $login));
        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->mainFrame->addComponent($this->pager);

        $this->actionOk = $this->createAction(array($this, "Ok"));

        $this->ok = new \ManiaLive\Gui\Elements\Xml();
        $this->ok->setContent('<frame posn="94 -85 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Apply", $login), null, null, "0D0", null, null, $this->actionOk, null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->ok);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX, $this->sizeY - 8);
        $this->pager->setStretchContentX($this->sizeX);
    }

    public function setData($data)
    {
        foreach ($this->items as $item) {
            $item->destroy();
        }
        $this->pager->clearItems();
        $this->items = array();

        $statuses = $this->parseData($data);
        $x = 0;
        foreach ($statuses as $status) {
            $this->items[$x] = new \ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted(4, 4, 50);
            $this->items[$x]->setPosZ(1);
            $this->items[$x]->setText($status->id);
            $this->items[$x]->setStatus($status->value);
            $this->pager->addItem($this->items[$x]);
            $x++;
        }
    }

    /**
     * data is following format:
     *
     * widgetname ^ ":" ^ gameMode ^ ":" ^ bool ^ "|";
     * you can assume only one gamemode is sent at a time, so multiple gamemodes are not mixed
     *
     * @param array $data
     *
     * @return \ManiaLivePlugins\eXpansion\Gui\Structures\ConfigItem
     */
    private function parseData($data)
    {
        if (!array_key_exists("widgetStatus", $data)) {
            return array();
        }

        $entries = explode("|", $data["widgetStatus"]);
        $items = array();
        foreach ($entries as $entry) {
            if (empty($entry)) {
                continue;
            }
            $val = explode(":", $entry, 3);
            $this->gameMode = $val[1];
            $items[] = new \ManiaLivePlugins\eXpansion\Gui\Structures\ConfigItem($val[0], $val[1], $val[2]);
        }

        return $items;
    }

    public function Ok($login, $options)
    {
        $outValues = array();

        foreach ($this->items as $component) {
            if ($component instanceof Checkbox) {
                $component->setArgs($options);
                $outValues[] = new \ManiaLivePlugins\eXpansion\Gui\Structures\ConfigItem($component->getText(), $this->gameMode, $component->getStatus());
            }
        }

        $apply = HudSetVisibility::Create($login);
        $apply->setData($outValues);
        $apply->setTimeout(5);
        $apply->show();
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
