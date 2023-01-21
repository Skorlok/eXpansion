<?php

namespace ManiaLivePlugins\eXpansion\AdminGroups\Gui\Windows;

use ManiaLive\Gui\ActionHandler;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Group;
use ManiaLivePlugins\eXpansion\AdminGroups\Gui\Controls\CheckboxItem;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button;
use ManiaLivePlugins\eXpansion\Gui\Elements\Checkbox;
use ManiaLive\Gui\Controls\Pager;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

/**
 * Description of Permissions
 *
 * @author oliverde8
 */
class Permissions extends Window
{

    /** @var  AdminGroups */
    protected $adminGroups;
    /** @var  Pager */
    protected $pager;
    /** @var  Group */
    protected $group;
    /** @var  Button */
    protected $button_ok;
    /** @var  Button */
    protected $button_cancel;
    protected $action_ok;
    protected $action_cancel;
    protected $items = array();

    /**
     * @var array(Checkbox, Checkbox)
     */
    protected $permissions = array();

    /**
     *
     */
    protected function onConstruct()
    {
        parent::onConstruct();

        $this->pager = new Pager();
        $this->_windowFrame->addComponent($this->pager);

        $this->button_ok = new Button(20, 5);
        $this->button_ok->setText(__("OK"));
        $this->action_ok = $this->createAction(array($this, 'clickOk'));
        $this->button_ok->setAction($this->action_ok);
        $this->_windowFrame->addComponent($this->button_ok);

        $this->button_cancel = new Button(20, 5);
        $this->button_cancel->setText(__("Cancel"));
        $this->action_cancel = $this->createAction(array($this, 'clickCancel'));
        $this->button_cancel->setAction($this->action_cancel);
        $this->_windowFrame->addComponent($this->button_cancel);
    }

    /**
     * @param Group $g
     */
    public function setGroup(Group $g)
    {
        $this->group = $g;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param $oldX
     * @param $oldY
     */
    public function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX, $this->sizeY - 10);
        $this->pager->setPosition(0, -3);

        $centerX = $this->sizeX / 2 - 10;
        $this->button_ok->setPosition($centerX + 5, -$this->sizeY - 1);
        $this->button_cancel->setPosition($centerX + 30, -$this->sizeY - 1);
    }

    /**
     *
     */
    public function onShow()
    {
        $this->populateList();
    }

    /**
     *
     */
    public function populateList()
    {
        /** @var Checkbox[] $item */
        foreach ($this->permissions as $item) {
            $item[0]->destroy();
            if ($item[1] != null) {
                $item[1]->destroy();
            }
        }
        /** @var CheckboxItem $item */
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->pager->clearItems();
        $this->permissions = array();
        $this->items = array();
        $x = 0;
        $adminGroups = AdminGroups::getInstance();
        foreach ($adminGroups->getPermissionList() as $key => $value) {
            $cPermission = new Checkbox(4, 4, 60);
            $cPermission->setStatus($this->group->hasPermission($key));
            $cPermission->setText('$fff' . __(AdminGroups::getPermissionTitleMessage($key), $this->getRecipient()));

            $cInherit = null;

            $inheritances = $this->group->getInherits();
            if (!empty($inheritances)) {
                $cInherit = new Checkbox(4, 4, 23, $cPermission);
                $cInherit->setText('$fff' . __(AdminGroups::$txt_inherits, $this->getRecipient()) . "?");
                if ($this->group->getPermission($key) == AdminGroups::UNKNOWN_PERMISSION) {
                    $cPermission->SetIsWorking(false);
                    $cInherit->setStatus(true);
                } else {
                    $cInherit->setStatus(false);
                }
            } else {
                $cPermission->setSizeX(85);
            }

            $this->permissions[$key] = array($cPermission, $cInherit);
            $this->items[$x] = new CheckboxItem(
                $x,
                $cPermission,
                $cInherit
            );
            $this->pager->addItem($this->items[$x]);
            $x++;
        }
    }

    /**
     * @param $login
     */
    public function clickOk($login)
    {
        $newPermissions = array();
        foreach ($this->permissions as $key => $val) {
            /** @var Checkbox $inheritance */
            $inheritance = $val[1];
            /** @var Checkbox $permission */
            $permission = $val[0];

            if ($inheritance == null) {
                $newPermissions[$key] = $permission->getStatus() == false ?
                    AdminGroups::UNKNOWN_PERMISSION : AdminGroups::HAVE_PERMISSION;
            } else {
                if ($inheritance->getStatus()) {
                    $newPermissions[$key] = AdminGroups::UNKNOWN_PERMISSION;
                } else {
                    $newPermissions[$key] = $permission->getStatus() == false ?
                        AdminGroups::NO_PERMISSION : AdminGroups::HAVE_PERMISSION;
                }
            }
        }

        $adminGroups = AdminGroups::getInstance();
        $adminGroups->changePermissionOfGroup($login, $this->group, $newPermissions);

        /** @var Groups[] $windows */
        $windows = Groups::GetAll();
        foreach ($windows as $window) {
            $login = $window->getRecipient();
            $window->onShow();
            $window->redraw($login);
            $window->refreshAll();
        }
    }

    /**
     *
     */
    public function clickCancel()
    {
        $this->Erase($this->getRecipient());
    }

    /**
     *
     */
    public function destroy()
    {
        /** @var Checkbox[] $item */
        foreach ($this->permissions as $item) {
            $item[0]->destroy();
            if ($item[1] != null) {
                $item[1]->destroy();
            }
        }

        /** @var CheckboxItem $item */
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->permissions = null;
        $this->items = array();
        $this->pager->destroy();
        ActionHandler::getInstance()->deleteAction($this->action_ok);
        ActionHandler::getInstance()->deleteAction($this->action_cancel);

        $this->button_cancel->destroy();
        $this->button_ok->destroy();

        parent::destroy();
    }
}
