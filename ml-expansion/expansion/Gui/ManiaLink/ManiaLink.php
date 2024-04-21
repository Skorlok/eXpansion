<?php

namespace ManiaLivePlugins\eXpansion\Gui\ManiaLink;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Helpers\Storage;
use ManiaLivePlugins\eXpansion\Helpers\Singletons;

abstract class ManiaLink extends Singletons
{

    protected $relPath = "";
    protected $name = "";
    protected $layer = "";
    protected $position = array(0, 0, 0);
    protected $size = array(0, 0);

    protected $userScript = array();
    protected $userElements = array();

    protected $scripts = array('declarationScript' => null, 'whileLoopScript' => null, 'libScript' => null, 'endDeclarationScript' => null, 'componantScript' => null);
    protected $dicoMessages = array();
    protected $maniaLinkPath = "";

    protected $connection = null;
    protected $eXpStorage = null;


    public function __construct($path, $pluginsRoot = false)
    {
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);

        $this->relPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . $path;
        $this->maniaLinkPath = "Gui\ManiaLink\Head.xml";
        $this->eXpStorage = Storage::getInstance();
        $this->connection = $this->getDediConnection();
    }

    /**
     * @param $relPath
     */
    public function setRelPath($relPath)
    {
        $this->relPath = $relPath;
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

    // For users to add their own elements
    
    public function registerScript(Script $script)
    {
        $this->userScript[] = $script;
    }

    public function registerComponant($element)
    {
        $this->userElements[] = $element;
    }

    // Others

    /**
     * @return string The code of the widget
     */
    final protected function getWidget()
    {
        $path = $this->relPath;
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        if (file_exists($path)) {
            ob_start();
            include $path;

            $script = ob_get_contents();
            ob_end_clean();

            return $script;
        }
    }

    // For XML

    protected function getPosX()
    {
        return $this->position[0];
    }

    protected function getPosY()
    {
        return $this->position[1];
    }

    protected function getPosZ()
    {
        return $this->position[2];
    }

    protected function getSizeX()
    {
        return $this->size[0];
    }

    protected function getSizeY()
    {
        return $this->size[1];
    }

    abstract protected function getMlScripts();

    abstract protected function getLanguages();

    protected function getLayer()
    {
        if (strtolower($this->layer == "scorestable")) {
            return "scorestable";
        } else {
            return "normal";
        }
    }

    public function show($login)
    {
        $xml = $this->getWidget();
        if ($login !== null) {
            $this->connection->sendDisplayManialinkPage($login, $xml, 0, false, false);
        } else {
            $this->connection->sendDisplayManialinkPage(null, $xml, 0, false, false);
        }
    }
}
