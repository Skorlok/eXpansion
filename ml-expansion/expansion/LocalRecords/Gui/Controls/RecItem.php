<?php

namespace ManiaLivePlugins\eXpansion\LocalRecords\Gui\Controls;

use ManiaLib\Gui\Elements\Label;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Gui\Elements\ListBackGround;
use ManiaLivePlugins\eXpansion\Gui\Gui;

/**
 * Description of RecItem
 *
 * @author oliverde8
 */
class RecItem extends \ManiaLivePlugins\eXpansion\Gui\Control implements \ManiaLivePlugins\eXpansion\Gui\Structures\OptimizedPagerElement
{

    protected $frame;

    /** @var \ManiaLib\Gui\Elements\Label */
    protected $label_rank;
    protected $label_nick;
    protected $label_login;
    protected $label_score;
    protected $label_avgScore;
    protected $label_nbFinish;
    protected $label_date;

    protected $button_delete;

    protected $bg;

    public static $widths;

    public function __construct($indexNumber, $login, $action)
    {
        $this->sizeY = 6;
        $this->bg = new ListBackGround($indexNumber, 120, 6);
        $this->addComponent($this->bg);

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setSize(180, 6);
        $this->frame->setPosY(0);
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $this->addComponent($this->frame);

        $this->label_rank = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_rank->setAlign('left', 'center');
        $this->label_rank->setId('column_' . $indexNumber . '_0');
        $this->frame->addComponent($this->label_rank);

        $this->label_nick = new \ManiaLib\Gui\Elements\Label(10., 6);
        $this->label_nick->setAlign('left', 'center');
        $this->label_nick->setId('column_' . $indexNumber . '_1');
        $this->frame->addComponent($this->label_nick);

        $this->label_login = new \ManiaLib\Gui\Elements\Label(10., 6);
        $this->label_login->setAlign('left', 'center');
        $this->label_login->setId('column_' . $indexNumber . '_2');
        $this->frame->addComponent($this->label_login);

        $this->label_score = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_score->setAlign('left', 'center');
        $this->label_score->setId('column_' . $indexNumber . '_3');
        $this->frame->addComponent($this->label_score);

        $this->label_avgScore = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_avgScore->setAlign('left', 'center');
        $this->label_avgScore->setId('column_' . $indexNumber . '_4');
        $this->frame->addComponent($this->label_avgScore);

        $this->label_nbFinish = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_nbFinish->setAlign('left', 'center');
        $this->label_nbFinish->setId('column_' . $indexNumber . '_5');
        $this->frame->addComponent($this->label_nbFinish);

        $this->label_date = new \ManiaLib\Gui\Elements\Label(10, 6);
        $this->label_date->setAlign('left', 'center');
        $this->label_date->setId('column_' . $indexNumber . '_6');
        $this->frame->addComponent($this->label_date);

        if (AdminGroups::hasPermission($login, Permission::LOCAL_RECORDS_DELETE) && $action) {
            $this->button_delete = new Label(15, 6);
            $this->button_delete->setId('column_' . $indexNumber . '_7');
            $this->button_delete->setAlign('left', 'center');
            $this->button_delete->setAttribute('class', "eXpOptimizedPagerAction");
            $this->button_delete->setAction($action);
            $this->button_delete->setStyle("CardButtonMediumS");
            $this->button_delete->setScriptEvents(true);
            $this->button_delete->setScale(0.5);
            $this->frame->addComponent($this->button_delete);
        }

        $this->setSizeX(180);
    }

    public function onResize($oldX, $oldY)
    {
        $scaledSizes = Gui::getScaledSize(self::$widths, ($this->getSizeX()) - 5);
        $this->bg->setSizeX($this->getSizeX() - 5);
        $this->label_rank->setSizeX($scaledSizes[0]);
        $this->label_nick->setSizeX($scaledSizes[1]);
        $this->label_login->setSizeX($scaledSizes[2]);
        $this->label_score->setSizeX($scaledSizes[3]);
        $this->label_avgScore->setSizeX($scaledSizes[4]);
        $this->label_nbFinish->setSizeX($scaledSizes[5]);
        $this->label_date->setSizeX($scaledSizes[6]);
        if ($this->button_delete != null) {
            $this->button_delete->setSizeX($scaledSizes[7]);
        }
    }

    // manialive 3.1 override to do nothing.
    public function destroy()
    {

    }

    /*
     * custom function to remove contents.
     */

    public function erase()
    {
        parent::destroy();
    }

    public function getNbTextColumns()
    {
        return 8;
    }
}
