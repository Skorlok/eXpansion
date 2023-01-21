<?php

namespace ManiaLivePlugins\eXpansion\AdminGroups\Gui\Windows;

use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Group;

/**
 * Description of Permissions
 *
 * @author oliverde8
 */
class Inherits extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $pager;
    protected $group;
    protected $button_ok;
    protected $button_cancel;
    protected $action_ok;
    protected $action_cancel;
    protected $items = array();
    protected $inherits = array();

    protected function onConstruct()
    {
        parent::onConstruct();
        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->mainFrame->addComponent($this->pager);

        $this->button_ok = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(20, 5);
        $this->button_ok->setText(__("OK"));
        $this->action_ok = $this->createAction(array($this, 'clickOk'));
        $this->button_ok->setAction($this->action_ok);
        $this->mainFrame->addComponent($this->button_ok);

        $this->button_cancel = new \ManiaLivePlugins\eXpansion\Gui\Elements\Button(20, 5);
        $this->button_cancel->setText(__("Cancel"));
        $this->action_cancel = $this->createAction(array($this, 'clickCancel'));
        $this->button_cancel->setAction($this->action_cancel);
        $this->mainFrame->addComponent($this->button_cancel);
    }

    public function setGroup(Group $group)
    {
        $this->group = $group;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX - 2, $this->sizeY - 12);
        $this->pager->setPosition(1, -1);

        $centerX = $this->sizeX / 2 - 10;
        $this->button_ok->setPosition($centerX + 5, -$this->sizeY + 5);
        $this->button_cancel->setPosition($centerX + 30, -$this->sizeY + 5);
    }

    public function onShow()
    {
        $this->populateList();
    }

    public function populateList()
    {
        foreach ($this->inherits as $item) {
            $item->destroy();
        }
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->pager->clearItems();
        $this->inherits = array();
        $this->items = array();
        $x = 0;
        $adminGroups = AdminGroups::getInstance();

        $inherits = $this->group->getInherits();

        foreach ($adminGroups->getGroupList() as $i => $group) {
            $nh = $group->getInherits();

            if ($this->group != $group && !isset($nh[$this->group->getGroupName()])) {

                $cInherit = new \ManiaLivePlugins\eXpansion\Gui\Elements\Checkbox(4, 4, 38);
                $cInherit->setText($group->getGroupName());
                $cInherit->setScale(0.8);

                if (!empty($inherits)) {
                    $cInherit->setStatus(isset($inherits[$group->getGroupName()]));
                }

                $this->inherits[$i] = $cInherit;
                $this->items[$x] = new \ManiaLivePlugins\eXpansion\AdminGroups\Gui\Controls\CheckboxItem($x, $cInherit);
                $this->pager->addItem($this->items[$x]);
                $x++;
            }
        }
    }

    public function clickOk($login)
    {

        $adminGroups = AdminGroups::getInstance();

        $groups = $adminGroups->getGroupList();
        $newInheritances = array();

        foreach ($this->inherits as $i => $cbox) {
            $nh = $groups[$i]->getInherits();
            if ($cbox->getStatus() && !isset($nh[$this->group->getGroupName()])) {
                $newInheritances[] = $groups[$i];
            }
        }

        $adminGroups = AdminGroups::getInstance();
        $adminGroups->changeInheritanceOfGroup($login, $this->group, $newInheritances);
        $this->Erase($login);

        $windows = \ManiaLivePlugins\eXpansion\AdminGroups\Gui\Windows\Groups::GetAll();
        foreach ($windows as $window) {
            $login = $window->getRecipient();
            $window->onShow();
            $window->redraw($login);
        }
    }

    public function clickCancel()
    {
        $this->Erase($this->getRecipient());
    }

    public function destroy()
    {
        foreach ($this->inherits as $item) {
            $item->destroy();
        }
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->inherits = null;
        $this->items = array();
        $this->pager->destroy();
        \ManiaLive\Gui\ActionHandler::getInstance()->deleteAction($this->action_ok);
        \ManiaLive\Gui\ActionHandler::getInstance()->deleteAction($this->action_cancel);

        $this->button_cancel->destroy();
        $this->button_ok->destroy();

        parent::destroy();
    }
}
