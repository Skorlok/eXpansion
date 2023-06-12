<?php

namespace ManiaLivePlugins\eXpansion\Database\Gui\Windows;

use ManiaLivePlugins\eXpansion\Gui\Elements\Button as OkButton;
use ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox;

class BackupRestore extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    public static $mainPlugin;

    private $pager;

    /** @var \Maniaplanet\DedicatedServer\Connection */
    private $connection;

    /** @var \ManiaLive\Data\Storage */
    private $storage;
    private $items = array();
    private $ok;
    private $cancel;
    private $inputbox;
    private $actionBackup;
    private $actionCancel;

    /** @var  \ManiaLive\Database\Connection */
    private $db;

    protected function onConstruct()
    {
        parent::onConstruct();
        $config = \ManiaLive\DedicatedApi\Config::getInstance();
        $this->connection = \ManiaLivePlugins\eXpansion\Helpers\Singletons::getInstance()->getDediConnection();
        $this->pager = new \ManiaLivePlugins\eXpansion\Gui\Elements\Pager();
        $this->mainFrame->addComponent($this->pager);
        $this->actionBackup = $this->createAction(array(self::$mainPlugin, "exportToSql"));
        $this->actionCancel = $this->createAction(array($this, "Cancel"));
        $this->inputbox = new Inputbox("filename", 60);
        $this->inputbox->setLabel("Backup filename");
        $this->mainFrame->addComponent($this->inputbox);
        $this->ok = new OkButton();
        $this->ok->colorize("0d0");
        $this->ok->setText("Create Backup");
        $this->ok->setAction($this->actionBackup);
        $this->mainFrame->addComponent($this->ok);

        $this->cancel = new OkButton();
        $this->cancel->setText("Cancel");
        $this->cancel->setAction($this->actionCancel);
        $this->mainFrame->addComponent($this->cancel);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX, $this->sizeY - 8);
        $this->pager->setStretchContentX($this->sizeX);
        $this->inputbox->setPosition(4, -$this->sizeY + 6);
        $this->ok->setPosition($this->sizeX - 38, -$this->sizeY + 6);
        $this->cancel->setPosition($this->sizeX - 20, -$this->sizeY + 6);
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

        if (!is_dir("./backup")) {
            if (!mkdir("./backup", 0777)) {
                $this->connection->chatSendServerMessage("Error while creating backup folder", $login);
                return false;
            }
        }

        $files = glob("./backup/*.sql");

        $x = 0;
        foreach ($files as $file) {
            $this->items[$x] = new \ManiaLivePlugins\eXpansion\Database\Gui\Controls\SqlFile($x, $this, $file, $this->sizeX);
            $this->pager->addItem($this->items[$x]);
            $x++;
        }
    }

    public function init(\ManiaLive\Database\Connection $db)
    {
        $this->db = $db;
    }

    /** @todo imlement restore from .sql file */
    public function restoreFile($login, $file)
    {
        $tempLine = '';
        $lines = file($file);
        foreach ($lines as $line) {

            if (substr($line, 0, 2) == '--' || $line == '')
                continue;

            $tempLine .= $line;
            if (substr(trim($line), -1, 1) == ';')  {
                try {
                    $this->db->execute($tempLine);
                } catch (\Exception $e) {
                    $this->connection->chatSendServerMessage("Error while restoring file: " . $e->getMessage(), $login);
                    return;
                }
                $tempLine = '';
            }
        }
        
        $this->connection->chatSendServerMessage("Backup restored successfully.", $login);
    }

    public function deleteFile($login, $file)
    {
        unlink($file);
        $this->connection->chatSendServerMessage("Deleted.", $login);
        $this->populateList();
        $this->RedrawAll();
    }

    public function Cancel($login)
    {
        $this->erase($login);
    }

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->db = null;
        $this->items = array();
        $this->pager->destroy();
        $this->ok->destroy();
        $this->cancel->destroy();
        $this->inputbox->destroy();
        $this->connection = null;
        $this->storage = null;
        $this->destroyComponents();
        parent::destroy();
    }
}
