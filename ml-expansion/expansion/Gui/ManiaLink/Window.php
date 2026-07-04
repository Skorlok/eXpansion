<?php

namespace ManiaLivePlugins\eXpansion\Gui\ManiaLink;

use ManiaLivePlugins\eXpansion\Gui\Structures\Script;
use ManiaLivePlugins\eXpansion\Gui\Config as guiConfig;

class Window extends ManiaLink
{
    protected $guiConfig;
    protected $size;
    protected $title;
    protected $closeAction;

    /** @var Script[] */
    private $userScript = array();

    /** @var string[] raw ManiaScript strings */
    private $moreScripts = array();

    private $extraDeclares = "";
    private $extraWLoop    = "";
    private $extraScriptLib = "";

    /** @var Script */
    private $windowScript;

    protected $closeCallback;

    public function __construct($path)
    {
        parent::__construct($path);

        /** @var guiConfig */
        $this->guiConfig = guiConfig::getInstance();

        $this->maniaLinkPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "Gui\ManiaLink\Window.xml";
        $this->windowScript  = new Script('Gui\Scripts\templateWindowScript');

        $this->size  = array(0, 0);
        $this->title = "";

        /** @var \ManiaLive\Gui\ActionHandler $aH */
        $aH = \ManiaLive\Gui\ActionHandler::getInstance();
        $this->closeAction = $aH->createAction(array($this, 'closeWindow'));
    }

    public function setTitle($text, $parameter = null)
    {
        $this->title = $this->addLang($text, $parameter);
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getSizeX()
    {
        return $this->size[0];
    }

    public function getSizeY()
    {
        return $this->size[1];
    }

    public function setSize($x, $y)
    {
        $this->size = array($x, $y);
    }

    public function getCloseAction()
    {
        return $this->closeAction;
    }

    public function closeWindow($login)
    {
        $this->erase($login);
        if (is_callable($this->closeCallback)) {
            call_user_func($this->closeCallback, $login);
        }
    }

    public function registerScript(Script $script)
    {
        $this->userScript[] = $script;
    }

    public function registerMainScript($script)
    {
        $this->moreScripts[] = $script;
    }

    public function addScriptToMain($script)
    {
        $this->extraDeclares .= $script;
    }

    public function addScriptToWhile($script)
    {
        $this->extraWLoop .= $script;
    }

    public function addScriptToLib($script)
    {
        $this->extraScriptLib .= $script;
    }

    protected function getMlScripts()
    {
        $dDeclares = "";
        $wLoop     = "";
        $scriptLib = "";

        foreach ($this->userScript as $script) {
            $dDeclares .= $script->getDeclarationScript($this, false);
            $dDeclares .= $script->getEndScript($this, false);
            $wLoop     .= $script->getWhileLoopScript($this, false);
            $scriptLib .= $script->getlibScript($this, false);
        }

        foreach ($this->moreScripts as $raw) {
            $dDeclares .= $raw;
        }

        $dDeclares .= $this->extraDeclares;
        $wLoop     .= $this->extraWLoop;
        $scriptLib .= $this->extraScriptLib;

        $this->windowScript->setParam("name",              $this->getWidgetName());
        $this->windowScript->setParam("dDeclares",         $dDeclares);
        $this->windowScript->setParam("scriptLib",         $scriptLib);
        $this->windowScript->setParam("wLoop",             $wLoop);
        $this->windowScript->setParam("closeAction",       $this->closeAction);
        $this->windowScript->setParam("forceReset",        $this->getBoolean(DEBUG));
        $this->windowScript->setParam("disableAnimations", $this->getBoolean($this->guiConfig->disableAnimations));

        return $this->windowScript->getDeclarationScript($this, false);
    }

    public function registerCloseCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new \Exception("Callback is not callable");
        }
        $this->closeCallback = $callback;
    }

    public function __destruct()
    {
        /** @var \ManiaLive\Gui\ActionHandler $aH */
        $aH = \ManiaLive\Gui\ActionHandler::getInstance();
        $aH->deleteAction($this->closeAction);
        $this->closeAction = null;
        $this->windowScript = null;
    }
}
