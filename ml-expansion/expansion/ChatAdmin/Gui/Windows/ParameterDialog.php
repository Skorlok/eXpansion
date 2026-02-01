<?php
namespace ManiaLivePlugins\eXpansion\ChatAdmin\Gui\Windows;


use ManiaLib\Gui\Layouts\Column;
use ManiaLib\Gui\Layouts\Line;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\ChatAdmin\ChatAdmin;
use ManiaLivePlugins\eXpansion\Gui\Windows\Window;

class ParameterDialog extends Window
{

    protected $btn_ok;
    /** @var  Frame */
    protected $frame;
    /** @var  Frame */
    protected $frm;
    
    protected $compobox;
    protected $compoboxScript;

    protected $adminAction;
    protected $adminParams;

    protected static $dropdownItems = array(
        "permanent",
        "30 seconds",
        "5 min",
        "10 min",
        "15 min",
        "30 min",
        "1 hour",
        "1 day",
        "5 day",
        "week",
        "month"
    );

    /** @var ChatAdmin */
    public static $mainPlugin;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

        $this->frm = new Frame(2, -6);
        $this->frm->setLayout(new Column());
        $this->addComponent($this->frm);

        $inputbox = new \ManiaLive\Gui\Elements\Xml();
        $inputbox->setContent('<frame posn="0 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("parameter", 100, true, "Give a reason", "Bad Behavior", null, null) . '</frame>');
        $this->frm->addComponent($inputbox);

        $dropDown = \ManiaLivePlugins\eXpansion\Gui\Elements\Dropdown::getXML($this, "select", self::$dropdownItems);
        $this->compobox = new \ManiaLive\Gui\Elements\Xml();
        $this->compobox->setContent('<frame posn="0 -6 1">' . $dropDown[0] . '</frame>');
        $this->compoboxScript = $dropDown[1];


        $this->frame = new Frame(0, 0, new Line());
        $this->frame->setPosition("right", "top");
        $this->addComponent($this->frame);

        $this->setSize(110, 20);
    }

    public function onResize($oldX, $oldY)
    {
        $this->frame->setSize($this->sizeX, $this->sizeY);
        $this->frame->setPosition($this->sizeX - 48, -$this->sizeY + 6);
        parent::onResize($oldX, $oldY);
    }

    protected function onShow()
    {
        if ($this->adminAction != "kick") {
            $this->registerScript($this->compoboxScript);
            $this->frm->addComponent($this->compobox);
        }
    }

    public function setData($action, $params)
    {
        $login = $this->getRecipient();
        $this->adminAction = $action;
        $this->adminParams = $params;
        
        $actionA = $this->createAction(array($this, "ok"));
        $this->btn_ok = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_ok->setContent('<frame posn="25.5 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __($action, $login), null, null, null, null, null, $actionA, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->btn_ok);
    }

    public function ok($login, $inputbox)
    {
        if ($this->adminAction == "kick") {
            $params = $this->adminAction . " " . $this->adminParams . " " . $inputbox['parameter'];
        } else {
            if (empty($inputbox['select'])) {
                $inputbox['select'] = 0;
            }
            $params = $this->adminAction . " " . $this->adminParams . " " . $inputbox['parameter'] . ", duration: " . self::$dropdownItems[$inputbox['select']];
            $prms = explode(" ", $this->adminParams);
            self::$mainPlugin->addActionDuration($prms[0], $this->adminAction, self::$dropdownItems[$inputbox['select']]);
        }
        AdminGroups::getInstance()->adminCmd($login, $params);
        $this->Erase($login);
    }

    public function destroy()
    {
        parent::destroy();
    }
}
