<?php

namespace ManiaLivePlugins\eXpansion\ForceSkin;

/**
 * ForceSkin
 * A plugin to enable custom graphics to be forced on server
 *
 *  * @author Reaby
 */
class ForceSkin extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    /** @var Config */
    public function eXpOnInit()
    {
        $this->config = Config::getInstance();
    }

    public function eXpOnReady()
    {
        $this->forceSkins();
    }

    private function forceSkins()
    {
        try {
            $this->console("Enabling forced skins");
            $this->connection->setForcedSkins($this->getSkins());
        } catch (\Exception $e) {
            $this->console("[eXp\ForceSkins] error while forcing a skin:" . $e->getMessage());

            return;
        }
    }

    private function getSkins()
    {
        try {
            $skin = new \Maniaplanet\DedicatedServer\Structures\ForcedSkin();
            $skin->name = $this->config->name;
            $skin->orig = "";
            $skin->url = $this->config->skinUrl;
            $skin->checksum = "";

            return array($skin);
        } catch (\Exception $e) {
            $this->console("Error : " . $e->getMessage());
            return array(new \Maniaplanet\DedicatedServer\Structures\ForcedSkin());
        }
    }
}
