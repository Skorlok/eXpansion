<?php

namespace ManiaLivePlugins\eXpansion\AdminGroups\Gui\Windows;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminCmd;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Gui\Controls\HelpItem;
use ManiaLivePlugins\eXpansion\Gui\Elements\Pager;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

/**
 * Description of Help
 *
 * @author oliverde8
 */
class Help extends Window
{
    /**
     * @var AdminGroups
     */
    protected $adminGroups;
    /** @var  Pager */
    protected $pager;

    /** @var HelpItem[]  */
    protected $items = array();

    /** @var Label */
    protected $labelCmd;

    /** @var Label */
    protected $labelDesc;

    /** @var Label */
    protected $labelShortCmd;

    protected $inputName;

    protected $buttonSearch;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

        $this->adminGroups = AdminGroups::getInstance();
        $this->pager = new Pager();
        $this->mainFrame->addComponent($this->pager);

        $this->inputName = new \ManiaLive\Gui\Elements\Xml();
        $this->inputName->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("search", 60, true, __("Name/Description", $login), null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->inputName);

        $this->buttonSearch = new \ManiaLive\Gui\Elements\Xml();
        $this->buttonSearch->setContent('<frame posn="62 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(20, 6, __("Search", $login), null, null, '0a0', null, null, $this->createAction(array($this, "doSearch")), null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->buttonSearch);

        $frame = new Frame();
        $frame->setSize(80, 4);
        $frame->setPosY(-6);
        $frame->setLayout(new Line());
        $this->mainFrame->addComponent($frame);

        $this->labelCmd = new Label(40, 4);
        $this->labelCmd->setAlign('left', 'center');
        $frame->addComponent($this->labelCmd);

        $this->labelShortCmd = new Label(20, 4);
        $this->labelShortCmd->setAlign('left', 'center');
        $frame->addComponent($this->labelShortCmd);

        $this->labelDesc = new Label(80, 4);
        $this->labelDesc->setAlign('left', 'center');
        $frame->addComponent($this->labelDesc);
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX, $this->sizeY - 14);
        $this->pager->setStretchContentX($this->sizeX);
        $this->pager->setPosition(0, -7);
    }

    public function onShow()
    {
        $this->labelCmd->setText(__(AdminGroups::$txt_command, $this->getRecipient()));
        $this->labelShortCmd->setText(__('Alias', $this->getRecipient()));
        $this->labelDesc->setText(__(AdminGroups::$txt_description, $this->getRecipient()));

        $this->populateList();
    }

    public function populateList($searchCriteria = "")
    {
        foreach ($this->items as $item) {
            $item->erase();
        }
        $this->pager->clearItems();
        $this->items = array();

        $x = 0;
        $login = $this->getRecipient();
        foreach ($this->adminGroups->getAdminCommands() as $cmd) {
            if ($this->validateCmd($cmd, $searchCriteria)) {
                $this->items[$x] = new HelpItem($x, $cmd, $this, $login);
                $this->pager->addItem($this->items[$x]);
                $x++;
            }
        }
    }

    protected function validateCmd(AdminCmd $cmd, $searchCriteria)
    {
        if (empty($searchCriteria)) {
            return true;
        } else if (!is_null($cmd->getCmd())) {
            if (strpos($cmd->getCmd(), $searchCriteria) !== false) {
                return true;
            }
        } else if(!is_null($cmd->getHelp())) {
            if(strpos($cmd->getHelp(), $searchCriteria)) {
                return true;
            }
        } else if(!is_null($cmd->getHelpMore())) {
            if(strpos($cmd->getHelpMore(), $searchCriteria)) {
                return true;
            }
        } else {
            foreach ($cmd->getAliases() as $alias) {
                if (strpos($alias, $searchCriteria)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }
        $this->items = null;
        $this->pager->destroy();
        $this->destroyComponents();
        parent::destroy();
    }

    public function doSearch($login, $params)
    {
        $this->inputName->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("search", 60, true, __("Name/Description", $login), $params['search'], null, null) . '</frame>');
        $this->populateList($params['search']);
        $this->redraw($login);
    }
}
