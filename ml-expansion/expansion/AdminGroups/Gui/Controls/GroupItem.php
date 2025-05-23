<?php

namespace ManiaLivePlugins\eXpansion\AdminGroups\Gui\Controls;

use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Gui\ActionHandler;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Group;
use ManiaLivePlugins\eXpansion\AdminGroups\GuestGroup;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button as MyButton;
use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLivePlugins\eXpansion\Gui\Gui;

/**
 * Description of GroupItem
 *
 * @author oliverde8
 */
class GroupItem extends Control
{
    protected $group;

    protected $action_changePermissions;
    protected $action_playerList;
    protected $action_deleteGroup;
    protected $action_inherticances;

    protected $plistButton;
    protected $permiButton;
    protected $deleteButton;
    protected $InheritButton;
    protected $action_deleteGroupf;

    /**
     * GroupItem constructor.
     * @param $indexNumber
     * @param Group $group
     * @param $controller
     * @param $login
     */
    public function __construct($indexNumber, Group $group, $controller, $login)
    {
        $this->group = $group;
        $sizeX = 155;
        $sizeY = 6;

        $scale = 0.8;
        $buttonScale = 0.7;

        $this->action_changePermissions = $this->createAction(array($controller, 'changePermission'), $group);
        $this->action_playerList = $this->createAction(array($controller, 'playerList'), $group);
        $this->action_deleteGroupf = $this->createAction(array($controller, 'deleteGroup'), $group);
        $this->action_deleteGroup = Gui::createConfirm($this->action_deleteGroupf);
        $this->action_inherticances = $this->createAction(array($controller, 'inheritList'), $group);

        $frame = new Frame();
        $frame->setSize($sizeX, $sizeY);
        $frame->setLayout(new Line());

        $this->addComponent(new ListBackGround($indexNumber, $sizeX, $sizeY));

        $gui_name = new Label(35/$scale, 4);
        $gui_name->setAlign('left', 'center');
        $gui_name->setText($group->getGroupName());
        $gui_name->setScale($scale);
        $frame->addComponent($gui_name);

        $gui_nbPlayers = new Label(15/$scale, 4);
        $gui_nbPlayers->setAlign('left', 'center');
        $gui_nbPlayers->setText(sizeof($group->getGroupUsers()));
        $gui_nbPlayers->setScale($scale);
        $frame->addComponent($gui_nbPlayers);

        if (!($group instanceof GuestGroup)) {
            if (AdminGroups::hasPermission($login, Permission::ADMINGROUPS_ADMIN_ALL_GROUPS) || (
                AdminGroups::hasPermission($login, Permission::ADMINGROUPS_ONLY_OWN_GROUP)
                && AdminGroups::getAdmin($login)->getGroup()->getGroupName() == $group->getGroupName())
            ) {
                $this->plistButton = new MyButton(30, 6);
                $this->plistButton->setAction($this->action_playerList);
                $this->plistButton->setText(__(AdminGroups::$txt_playerList, $login));
                $this->plistButton->setScale($buttonScale);
                $frame->addComponent($this->plistButton);
            }
        }

        if (AdminGroups::hasPermission($login, Permission::ADMINGROUPS_ADMIN_ALL_GROUPS) || (
                AdminGroups::hasPermission($login, Permission::ADMINGROUPS_ONLY_OWN_GROUP)
                && AdminGroups::getAdmin($login)->getGroup()->getGroupName() == $group->getGroupName())
        ) {

            $this->permiButton = new MyButton(40, 6);
            $this->permiButton->setAction($this->action_changePermissions);
            $this->permiButton->setText(__(AdminGroups::$txt_permissionList, $login));
            $this->permiButton->setScale($buttonScale);
            $frame->addComponent($this->permiButton);

            if (!($group instanceof GuestGroup)) {
                $this->deleteButton = new MyButton(40, 6);
                $this->deleteButton->setAction($this->action_deleteGroup);
                $this->deleteButton->setText(__(AdminGroups::$txt_deletegroup, $login));
                $this->deleteButton->setScale($buttonScale);
                $frame->addComponent($this->deleteButton);
            }
        }

        if (AdminGroups::hasPermission($login, Permission::ADMINGROUPS_ADMIN_ALL_GROUPS)
            && !($group instanceof GuestGroup)
        ) {
            $this->InheritButton = new MyButton(30, 6);
            $this->InheritButton->setAction($this->action_inherticances);
            $this->InheritButton->setText(__(AdminGroups::$txt_inherits, $login));
            $this->InheritButton->setScale($buttonScale);
            $frame->addComponent($this->InheritButton);
        }

        $this->addComponent($frame);

        $this->sizeX = $sizeX;
        $this->sizeY = $sizeY;
        $this->setSize($sizeX, $sizeY);
    }

    // manialive 3.1 override to do nothing.
    public function destroy()
    {

    }


    /**
     *
     */
    public function erase()
    {
        if ($this->permiButton != null) {
            $this->permiButton->destroy();
        }

        if ($this->deleteButton != null) {
            $this->deleteButton->destroy();
        }

        if ($this->plistButton != null) {
            $this->plistButton->destroy();
        }

        $this->permiButton = null;
        $this->plistButton = null;
        $this->deleteButton = null;
        $this->destroyComponents();
        ActionHandler::getInstance()->deleteAction($this->action_deleteGroupf);
        ActionHandler::getInstance()->deleteAction($this->action_deleteGroup);

        parent::destroy();
    }
}
