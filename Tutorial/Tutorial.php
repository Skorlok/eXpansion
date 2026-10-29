<?php

namespace ManiaLivePlugins\eXpansion\Tutorial;

use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Gui\Structures\Script;

class Tutorial extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    protected $tutorialWindow;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
        
        $this->tutorialWindow = new Window("Tutorial\Gui\Windows\TutorialWindow.xml");
        $this->tutorialWindow->setName("eXpansion Tutorial");
        $this->tutorialWindow->setSize(160, 80);
        $this->tutorialWindow->setTitle("eXpansion Plugin Pack - Tutorial");
        $script = new Script("Tutorial\Gui\Scripts");
        $this->tutorialWindow->registerScript($script);
        $this->tutorialWindow->show();
    }

    public function onPlayerConnect($login, $isSpectator)
    {
        $this->tutorialWindow->show($login);
    }

    public function eXpOnUnload()
    {
        if ($this->tutorialWindow instanceof Window) {
            $this->tutorialWindow->erase();
        }
        $this->tutorialWindow = null;
    }
}
