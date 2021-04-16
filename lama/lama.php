<?php

namespace ManiaLivePlugins\eXpansion\lama;

class lama extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{
	public $lama_on = false;

    public function eXpOnReady()
    {
        $this->enableDedicatedEvents();
    }
	
	public function eXpOnLoad()
    {
        $cmd = $this->registerChatCommand("lama", "switch_lama", 0, true);
        $cmd->help = 'show lama to all players';
		
		$cmd = $this->registerChatCommand("dlama", "switch_lama_off", 0, true);
        $cmd->help = 'hide lama to all players';
	}
	
	public function switch_lama()
	{
			$window = Gui\Windows\SnowParticle::Create(null);
			$window->show();
	}
	
	public function switch_lama_off()
	{
			Gui\Windows\SnowParticle::EraseAll();
	}

    public function eXpOnUnload()
    {
        Gui\Windows\SnowParticle::EraseAll();
        parent::eXpOnUnload();
    }
}
