<?php

namespace ManiaLivePlugins\eXpansion\ChatWebhook;

use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;

class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Chat: Chat Webhook Exporter");
        $this->setDescription("Provides a way to export chat messages to a webhook.");
        $this->setGroups(array("Chat", "Tools"));

        $this->setRelaySupport(false);

        $config = Config::getInstance();

        $var = new TypeString("webhookUrl", "Webhook URL", $config, false, false);
        $this->registerVariable($var);

        $var = new Boolean("forwardChatCommands", "Forward chat commands", $config, false, false);
        $var->setDescription("If true, chat commands will be forwarded to the webhook.");
        $var->setDefaultValue(true);
        $this->registerVariable($var);
    }
}
