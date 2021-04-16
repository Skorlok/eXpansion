<?php

namespace ManiaLivePlugins\eXpansion\Core\Gui\Controls;

use ManiaLib\Gui\Elements\Icons128x128_1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Quad;
use ManiaLivePlugins\eXpansion\Core\Gui\Windows\ExpListSetting;
use ManiaLivePlugins\eXpansion\Core\Gui\Windows\ExpSettings;
use ManiaLivePlugins\eXpansion\Core\types\config\types\BasicList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\ColorCode;
use ManiaLivePlugins\eXpansion\Core\types\config\types\HashList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\SortedList;
use ManiaLivePlugins\eXpansion\Core\types\config\Variable;
use ManiaLivePlugins\eXpansion\Gui\Control;
use ManiaLivePlugins\eXpansion\Gui\Elements\Button;
use ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;
use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;

class ExpSetting extends Control
{

    protected $bg;

    protected $label_varName;

    protected $label_varValue;

    protected $button_change = null;

    protected $button_reset = null;

    protected $icon_global = null;

    protected $input;

    protected $var;

    private $win;

    public function __construct($indexNumber, Variable $var, $login, ExpSettings $win)
    {
        $this->var = $var;
        $this->win = $win;

        $this->label_varName = new Label(40, 5);
        $this->label_varName->setPosY(4);
        $this->label_varName->setPosX(7);
        $this->label_varName->setText($var->getVisibleName());
        $this->addComponent($this->label_varName);

        $this->bg = new ListBackGround($indexNumber, 100, 4);
        $this->addComponent($this->bg);

        if ($var instanceof HashList
            || $var instanceof BasicList
            || $var instanceof SortedList
            || $var->hasConfWindow()
        ) {

            $this->label_varValue = new Label(40, 5);
            $this->label_varValue->setScale(0.9);
            $this->label_varValue->setPosX(15);
            $this->label_varValue->setId('column_' . $indexNumber . '_1');
            $this->label_varValue->setText($var->getPreviewValues());
            $this->addComponent($this->label_varValue);

            $this->button_change = new Button(25, 6);
            $this->button_change->setText(__('Change', $login));
            $this->button_change->setDescription(__('Allows you to edit values', $login), 40);
            $this->button_change->setAction($this->createAction(array($this, "openWin"), $var));
            $this->addComponent($this->button_change);
        } elseif ($var->getDescription() != "") {

            $this->button_change = new Button(8, 8);
            $this->button_change->setIcon('UIConstructionSimple_Buttons', 'Help');
            $this->button_change->setDescription($var->getDescription(), 120, 5, 2);
            $this->addComponent($this->button_change);
        }

        $this->button_reset = new Button(8, 8);
        $this->button_reset->setIcon(Quad::Icons128x128_1, Icons128x128_1::DefaultIcon);
        $this->button_reset->setDescription(__('Reset the settings !', $login));
        $this->button_reset->setAction($this->createAction(array($this, 'reset')));
        if ($var->getDefaultValue() != null)
            $this->addComponent($this->button_reset);

        if ($var instanceof HashList
            || $var instanceof BasicList
            || $var instanceof SortedList
            || $var->hasConfWindow()
        ) {

        } else {
            if ($var instanceof Boolean) {
                $this->input = new CheckboxScripted(10, 5);
                $this->input->setSkin();
                $this->input->setStatus($var->getRawValue());
                $this->input->setPosY(-2);
                $this->input->setPosX(7);
                $this->addComponent($this->input);
            } elseif ($var instanceof ColorCode) {
                $this->input = new \ManiaLivePlugins\eXpansion\Gui\Elements\ColorChooser(
                    $var->getName(),
                    35,
                    $var->getUseFullHex(),
                    $var->getUsePrefix()
                );
                $this->input->setColor($var->getRawValue());
                $this->input->setPosY(-2);
                $this->input->setPosX(7);
                $this->addComponent($this->input);
            } else {
                $this->input = new Inputbox($var->getName());
                $this->input->setText($var->getRawValue());
                $this->input->setPosY(-2);
                $this->input->setPosX(7);
                $this->addComponent($this->input);
            }
        }

        $this->icon_global = new Button(7, 7);
        if ($var->getIsGlobal()) {
            $this->icon_global->setIcon('Icons64x64_1', 'IconLeaguesLadder');
            $this->icon_global->setDescription(
                __("Global Setting, Saved for all servers sharing this configuration", $login),
                120
            );
        } else {
            $this->icon_global->setIcon('Icons64x64_1', 'IconServers');
            $this->icon_global->setDescription(__("Server Setting, Saved for this server only", $login), 80);
        }
        $this->addComponent($this->icon_global);

        $this->sizeX = 130;
        $this->sizeY = 10;

        $this->setSize(130, 10);
    }

    protected function onResize($oldX, $oldY)
    {

        $this->label_varName->setSizeX($this->getSizeX() - 27);
        $this->bg->setSize($this->getSizeX(), $this->getSizeY());

        $this->button_reset->setPosition($this->getSizeX() - 7, 0);

        if ($this->button_change != null) {
            $this->button_change->setPosition($this->getSizeX() - $this->button_change->getSizeX() + 4, 0);
            $this->button_reset->setPosition($this->getSizeX() - $this->button_change->getSizeX() - 4, 0);
        }
        if ($this->label_varValue != null) {
            $this->label_varValue->setSizeX($this->getSizeX() - 30);
            $this->label_varValue->setPosition(7, -1);
        }

        if ($this->input != null) {
            $this->input->setSizeX($this->getSizeX() - 20);
        }
        parent::onResize($oldX, $oldY);
    }

    public function getNbTextColumns()
    {
        return 2;
    }

    public function openWin($login, Variable $var)
    {
        if ($var->hasConfWindow()) {
            $var->showConfWindow($login);
        } else {
            ExpListSetting::Erase($login);
            $win = ExpListSetting::Create($login);
            $win->setTitle("Expansion Settings: " . $var->getVisibleName());
            $win->centerOnScreen();
            $win->setSize(140, 100);
            $win->populate($var);
            $win->show();
        }
    }

    public function reset($login)
    {
        $this->var->setRawValue($this->var->getDefaultValue());
        $this->win->refreshInfo();
        $this->win->redraw();
    }

    public function getVar()
    {
        if ($this->input != null) {
            return $this->var;
        } else {
            return null;
        }
    }

    public function getVarValue($options)
    {
        if ($this->input != null) {
            if ($this->input instanceof CheckboxScripted) {
                $this->input->setArgs($options);

                return $this->input->getStatus();
            } else {
                return isset($options[$this->var->getName()]) ? $options[$this->var->getName()] : null;
            }
        }
    }

    public function destroy()
    {
        parent::destroy();
        // disabling for now, since reset didn't work...
        // $this->win = null;
    }
}
