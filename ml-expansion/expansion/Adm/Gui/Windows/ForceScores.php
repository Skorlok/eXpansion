<?php

namespace ManiaLivePlugins\eXpansion\Adm\Gui\Windows;

use ManiaLivePlugins\eXpansion\Core\Core;

class ForceScores extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $pager;

    /** @var \Maniaplanet\DedicatedServer\Connection */
    protected $connection;

    /** @var \ManiaLive\Data\Storage */
    protected $storage;

    protected $items = array();

    protected $ok;

    protected $cancel;

    protected $actionOk;

    protected $actionCancel;

    protected $buttonframe;

    protected $btn_clearScores;
    protected $btn_resetSkip;
    protected $btn_resetRes;

    public static $mainPlugin;

    protected function onConstruct()
    {
        parent::onConstruct();
        $login = $this->getRecipient();

        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->storage = \ManiaLive\Data\Storage::getInstance();

        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->mainFrame->addComponent($this->pager);
        $this->actionOk = $this->createAction(array($this, "ok"));

        $this->buttonframe = new \ManiaLive\Gui\Controls\Frame(40, 2);
        $line = new \ManiaLib\Gui\Layouts\Line();
        $line->setMargin(2, 1);
        $this->buttonframe->setLayout($line);
        $this->mainFrame->addComponent($this->buttonframe);


        $this->btn_clearScores = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_clearScores->setContent(\ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Reset scores", $login), null, null, null, null, null, $this->createAction(array($this, "resetScores")), null, null, null, null, null, null));
        $this->buttonframe->addComponent($this->btn_clearScores);

        $this->btn_resetSkip = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_resetSkip->setContent('<frame posn="27.5 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, $this->handleSpecialChars(__("Skip & reset", $login)), null, null, null, null, null, $this->createAction(array($this, "resetSkip")), null, null, null, null, null, null) . '</frame>');
        $this->buttonframe->addComponent($this->btn_resetSkip);

        $this->btn_resetRes = new \ManiaLive\Gui\Elements\Xml();
        $this->btn_resetRes->setContent('<frame posn="55 0 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, $this->handleSpecialChars(__("Restart & reset", $login)), null, null, null, null, null, $this->createAction(array($this, "resetRes")), null, null, null, null, null, null) . '</frame>');
        $this->buttonframe->addComponent($this->btn_resetRes);


        $this->ok = new \ManiaLive\Gui\Elements\Xml();
        $this->ok->setContent('<frame posn="129 -74 0">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, __("Apply", $login), null, null, "0d0", null, null, $this->actionOk, null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->ok);
    }

    public function handleSpecialChars($string)
    {
        if ($string == null) {
            return "";
        }
        return str_replace(array('&', '"', "'", '>', '<'), array('&amp;', '&quot;', '&apos;', '&gt;', '&lt;'), $string);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX, $this->sizeY - 8);
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

        $x = 0;

        $rankings = Core::$rankings;

        foreach ($rankings as $player) {
            $this->items[$x] = new \ManiaLivePlugins\eXpansion\Adm\Gui\Controls\PlayerScore(
                $x,
                $player,
                $this->sizeX - 8
            );
            $this->pager->addItem($this->items[$x]);
            $x++;
        }
    }

    public function ok($fromLogin, $scores = array())
    {
        $outScores = array();

        foreach ($scores as $login => $val) {
            if ($val != null){
                if (!Core::$useTeams) {
                    $this->connection->triggerModeScriptEventArray('Trackmania.SetPlayerPoints', array("$login", "", "", "$val"));
                    $this->connection->triggerModeScriptEventArray('Shootmania.SetPlayerPoints', array("$login", "", "", "$val"));
                } else {
                    $this->connection->triggerModeScriptEventArray('Trackmania.SetTeamPoints', array("$login", "", "$val", "$val"));
                    $this->connection->triggerModeScriptEventArray('Shootmania.SetTeamPoints', array("$login", "", "$val", "$val"));
                }
            }
        }
        $this->connection->triggerModeScriptEventArray('Trackmania.GetScores', array());
        $this->connection->triggerModeScriptEventArray('Shootmania.GetScores', array());
        self::$mainPlugin->forceScoresOk();
        $this->erase($fromLogin);
    }

    public function resetScores($fromLogin)
    {
        $rankings = Core::$rankings;

        $outScores = array();
        foreach ($rankings as $rank) {
            if (!Core::$useTeams) {
                $this->connection->triggerModeScriptEventArray('Trackmania.SetPlayerPoints', array("$rank->login", "0", "0", "0"));
                $this->connection->triggerModeScriptEventArray('Shootmania.SetPlayerPoints', array("$rank->login", "0", "0", "0"));
            } else {
                $this->connection->triggerModeScriptEventArray('Trackmania.SetTeamPoints', array("$rank->login", "0", "0", "0"));
                $this->connection->triggerModeScriptEventArray('Shootmania.SetTeamPoints', array("$rank->login", "0", "0", "0"));
            }
        }
        $this->connection->triggerModeScriptEventArray('Trackmania.GetScores', array());
        $this->connection->triggerModeScriptEventArray('Shootmania.GetScores', array());
        self::$mainPlugin->forceScoresOk();

        $this->populateList();
        $this->RedrawAll();
    }

    public function resetSkip($login)
    {
        $ag = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::getInstance();
        $ag->adminCmd($login, "rskip");
        $this->Erase($login);
    }

    public function resetRes($login)
    {
        $ag = \ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups::getInstance();
        $ag->adminCmd($login, "rres");
        $this->Erase($login);
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->items = array();
        $this->pager->destroy();
        $this->connection = null;
        $this->storage = null;
        $this->destroyComponents();
        parent::destroy();
    }
}
