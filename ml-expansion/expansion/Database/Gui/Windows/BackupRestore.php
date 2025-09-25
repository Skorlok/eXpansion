<?php

namespace ManiaLivePlugins\eXpansion\Database\Gui\Windows;

class BackupRestore extends \ManiaLivePlugins\eXpansion\Gui\Windows\Window
{
    public static $mainPlugin;

    private $pager;

    /** @var \Maniaplanet\DedicatedServer\Connection */
    private $connection;

    private $items = array();
    private $ok;
    private $actionBackup;

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

        $inputbox = new \ManiaLive\Gui\Elements\Xml();
        $inputbox->setContent('<frame posn="0 -94 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Inputbox::getXML("filename", 60, true, "Backup filename", null, null, null) . '</frame>');
        $this->mainFrame->addComponent($inputbox);

        $this->ok = new \ManiaLive\Gui\Elements\Xml();
        $this->ok->setContent('<frame posn="62 -94 1">' . \ManiaLivePlugins\eXpansion\Gui\Elements\Button::getXML(32, 6, 'Create Backup', null, null, "0d0", null, null, $this->actionBackup, null, null, null, null, null, null) . '</frame>');
        $this->mainFrame->addComponent($this->ok);
    }

    protected function onResize($oldX, $oldY)
    {
        parent::onResize($oldX, $oldY);
        $this->pager->setSize($this->sizeX, $this->sizeY - 8);
        $this->pager->setPosition(0, 5);
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

    public function destroy()
    {
        foreach ($this->items as $item) {
            $item->erase();
        }

        $this->db = null;
        $this->items = array();
        $this->pager->destroy();
        $this->connection = null;
        $this->destroyComponents();
        parent::destroy();
    }
}
