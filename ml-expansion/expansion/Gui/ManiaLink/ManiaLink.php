<?php

namespace ManiaLivePlugins\eXpansion\Gui\ManiaLink;

use ManiaLive\Data\Storage;
use ManiaLivePlugins\eXpansion\Gui\Gui;
use ManiaLivePlugins\eXpansion\Helpers\Storage as eXpStorage;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;
use ManiaLivePlugins\eXpansion\Helpers\Helper;

class ManiaLink extends Singletons
{

    protected $relPath;
    protected $maniaLinkPath;

    protected $name;
    protected $layer;
    protected $position;
    protected $size;

    protected $xml;
    protected $scripts;
    protected $dicoMessages;

    /** @var \ManiaLive\Data\Storage\Storage $storage */
    protected $storage;
    protected $eXpStorage;
    protected $connection;

    protected $parameters;

    public function __construct($path)
    {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);

        $this->relPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $path;
        $this->maniaLinkPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "Gui\ManiaLink\Head.xml";
        $this->storage = Storage::getInstance();
        $this->eXpStorage = eXpStorage::getInstance();
        $this->connection = $this->getDediConnection();

        $this->name = "";
        $this->layer = "normal";
        $this->position = array(0, 0, 0);
        $this->size = array(0, 0);
        $this->dicoMessages = array();
    }

    // Getters

    public function getWidgetName()
    {
        return $this->name;
    }

    public function getWidgetHashName()
    {
        return $this->simpleHashName($this->name . $this->getLayer());
    }

    public function getPosX()
    {
        return $this->position[0];
    }

    public function getPosY()
    {
        return $this->position[1];
    }

    public function getPosZ()
    {
        return $this->position[2];
    }

    public function getSizeX()
    {
        return $this->size[0];
    }

    public function getSizeY()
    {
        return $this->size[1];
    }

    public function getLayer()
    {
        if (strtolower($this->layer == "scorestable")) {
            return "scorestable";
        } else {
            return "normal";
        }
    }

    // Setters

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setLayer($layer)
    {
        $this->layer = $layer;
    }

    public function setPosition($x, $y, $z)
    {
        $this->position = array($x, $y, $z);
    }

    public function setSize($x, $y)
    {
        $this->size = array($x, $y);
    }

    public function setScripts($scripts)
    {
        $this->scripts = $scripts;
    }

    public function getParam($key)
    {
        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }

        return null;
    }

    public function setParam($key, $value)
    {
        $this->parameters[$key] = $value;
    }

    public function addLang($lang)
    {
        if ($lang == "") {
            return "";
        }
        $this->dicoMessages[$lang] = eXpGetMessage($lang);
        return "l" . $this->simpleHashName($lang);
    }

    // Others

    public function simpleHashName($name)
    {
        if ($name == "") {
            return "";
        }
        $hash = "";
        for ($i = 0; $i < strlen($name); $i++) {
            $hash .= ord($name[$i]);
        }
        return $hash;
    }

    public function getBoolean($boolean)
    {
        if ($boolean) {
            return "True";
        }

        return "False";
    }

    /**
     * @return string The code of the widget
     */
    final protected function getWidget()
    {
        $path = $this->maniaLinkPath;
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        if (file_exists($path)) {
            ob_start();
            include $path;

            $widget = ob_get_contents();
            ob_end_clean();

            return $widget;
        }
    }

    // For XML

    protected function getUserXML()
    {
        $path = $this->relPath;
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        if (file_exists($path)) {
            ob_start();
            include $path;

            $widget = ob_get_contents();
            ob_end_clean();

            //return $widget;
            $this->xml = $widget;
        }
    }

    protected function getXML()
    {
        return $this->xml;
    }

    protected function getMlScripts() {
        return $this->scripts;
    }

    protected function getLanguages() {
        $dico = array();
        foreach ($this->dicoMessages as $key => $value) {
            $lang = $value->getMultiLangArray();
            foreach ($lang as $l) {
                $dico[$l['Lang']]["l" . $this->simpleHashName($key)] = $l['Text'];
            }
        }

        $dicoXml = "";
        foreach ($dico as $lang => $values) {
            $dicoXml .= "<language id=\"" . $lang . "\">" . PHP_EOL;
            foreach ($values as $key => $value) {
                $dicoXml .= "<" . $key . ">" . $value . "</" . $key . ">" . PHP_EOL;
            }
            $dicoXml .= "</language>" . PHP_EOL;
        }
        
        if ($dicoXml) {
            return "<dico>" . $dicoXml . "</dico>";
        }
    }

    public function show($login = null, $persistant = false)
    {
        $this->getUserXML();
        $xml = $this->getWidget();
        /*echo preg_replace('/<script.*?>.*?<\/script>/is', '', $xml);*/
        if ($login !== null) {
            try {
                $this->connection->sendDisplayManialinkPage($login, $xml, 0, false, false); // fix the bug where player leave so method return `login unknown`
            } catch (\Exception $e) {
                Helper::log("Cannot send widget to player, server said: " . $e->getMessage(), array("Gui", "ManiaLink"));
            }
        } else {
            $this->connection->sendDisplayManialinkPage(null, $xml, 0, false, true);
        }

        if ($persistant && $login == null) {
            Gui::$persistentWidgets[$this->getWidgetHashName()] = $xml;
        }
    }

    public function erase($login = null)
    {
        if ($login !== null) {
            try {
                $this->connection->sendDisplayManialinkPage($login, '<manialink id="' . $this->getWidgetHashName() . '"></manialink>', 0, false, false); // fix the bug where player leave so method return `login unknown`
            } catch (\Exception $e) {
                Helper::log("Cannot erase player widget, server said: " . $e->getMessage(), array("Gui", "ManiaLink"));
            }
        } else {
            $this->connection->sendDisplayManialinkPage(null, '<manialink id="' . $this->getWidgetHashName() . '"></manialink>', 0, false, true);
        }

        if (isset(Gui::$persistentWidgets[$this->getWidgetHashName()])) {
            unset(Gui::$persistentWidgets[$this->getWidgetHashName()]);
        }
    }
}
