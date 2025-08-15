<?php

namespace ManiaLivePlugins\eXpansion\Database\Gui\Windows;

class Maintainance extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{

    protected $pager;

    /** @var \Maniaplanet\DedicatedServer\Connection */
    protected $connection;

    /** @var \ManiaLive\Data\Storage */
    protected $storage;

    /** @var \ManiaLivePlugins\eXpansion\Database\Gui\Controls\DbTable[] */
    protected $items = array();

    protected $frame;

    protected $repair;

    protected $optimize;

    protected $backup;

    protected $cancel;

    protected $truncate;

    protected $actionRepair;

    protected $actionOptimize;

    protected $actionBackup;

    protected $actionConfirmTruncate;

    protected $actionTruncate;

    /** @var  \ManiaLive\Database\Connection */
    protected $db;

    protected function onConstruct()
    {
        parent::onConstruct();
        $config = \ManiaLive\DedicatedApi\Config::getInstance();
        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->addComponent($this->pager);

        $this->actionRepair = $this->createAction(array($this, "Repair"));
        $this->actionOptimize = $this->createAction(array($this, "Optimize"));
        $this->actionBackup = $this->createAction(array($this, "Backup"));
        $this->actionTruncate = $this->createAction(array($this, "Truncate"));

        $this->frame = new \ManiaLive\Gui\Controls\Frame();
        $this->frame->setLayout(new \ManiaLib\Gui\Layouts\Line());
        $this->addComponent($this->frame);

        $this->truncate = new \ManiaLive\Gui\Elements\Xml();
        $this->truncate->setContent('<frame posn="25.5 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, 'Clear Table', array("BEWARE, No confirm, No undo!", 60), null, "d00", null, null, $this->actionTruncate, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->truncate);

        $this->repair = new \ManiaLive\Gui\Elements\Xml();
        $this->repair->setContent('<frame posn="51 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, 'Repair', null, null, null, null, null, $this->actionRepair, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->repair);

        $this->optimize = new \ManiaLive\Gui\Elements\Xml();
        $this->optimize->setContent('<frame posn="76.5 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, 'Optimize', null, null, null, null, null, $this->actionOptimize, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->optimize);

        $this->backup = new \ManiaLive\Gui\Elements\Xml();
        $this->backup->setContent('<frame posn="102 0 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, 'Access Backups', null, null, "0d0", null, null, $this->actionBackup, null, null, null, null, null, null) . '</frame>');
        $this->frame->addComponent($this->backup);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX - 4, $this->sizeY - 10);
        $this->pager->setPosition(3, 0);
        $this->frame->setPosition(30, -$this->sizeY + 3);
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
        $dbconfig = \ManiaLive\Database\Config::getInstance();
        $dbName = $dbconfig->database;
        $tables = $this->db->execute("SHOW TABLES in `" . $dbName . "`;")->fetchArrayOfRow();

        foreach ($tables as $table) {
            $this->items[$x] = new \ManiaLivePlugins\eXpansion\Database\Gui\Controls\DbTable(
                $x,
                $table[0],
                $this->sizeX
            );
            $this->pager->addItem($this->items[$x]);
            $x++;
        }
    }

    public function init(\ManiaLive\Database\Connection $db)
    {
        $this->db = $db;
    }

    public function Backup($login)
    {

        $window = BackupRestore::Create($login);
        $window->init($this->db);
        $window->setTitle(__('Database Backup and Restore'));
        $window->centerOnScreen();
        $window->setSize(160, 100);
        $window->show();
        $this->erase($login);
    }

    public function Repair($login, $args)
    {

        foreach ($this->items as $item) {
            // if checkbox checked

            $this->syncCheckboxItem($item, $args);
            if ($item->checkBox->getStatus()) {
                // repair table
                $status = $this->db->execute("REPAIR TABLE " . $item->tableName . ";")->fetchObject();
                $this->connection->chatSendServerMessage(
                    "Table " . $status->Table . " repaired with " . $status->Msg_type . ":" . $status->Msg_text,
                    $login
                );
            }
        }
    }

    public function Truncate($login, $args)
    {
        foreach ($this->items as $item) {
            // if checkbox checked
            $this->syncCheckboxItem($item, $args);
            if ($item->checkBox->getStatus()) {
                // repair table
                $status = $this->db->execute("TRUNCATE TABLE " . $item->tableName . ";");
                $this->connection->chatSendServerMessage('Table \'$0d0' . $item->tableName . '$fff\' contents is now $d00CLEARED$fff!', $login);
            }
        }
    }

    public function Optimize($login, $args)
    {

        foreach ($this->items as $item) {
            // if checkbox checked
            $this->syncCheckboxItem($item, $args);
            if ($item->checkBox->getStatus()) {
                // repair table
                $status = $this->db->execute("OPTIMIZE TABLE `" . $item->tableName . "`;")->fetchObject();
                $this->connection->chatSendServerMessage("Table " . $status->Table . " Optimized with " . $status->Msg_type . ":" . $status->Msg_text, $login);
            }
        }
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->db = null;
        $this->connection = null;
        $this->storage = null;

        parent::destroy();
    }

    public function syncCheckboxItem(&$item, $args)
    {
        $components = $item->getComponents();
        foreach ($components as &$component) {
            if ($component instanceof \ManiaLivePlugins\eXpansion\Gui\Elements\CheckboxScripted) {
                $component->setArgs($args);
            }
        }
    }
}
