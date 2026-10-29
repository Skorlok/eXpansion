<?php

namespace ManiaLivePlugins\eXpansion\ChatWebhook;

use ManiaLive\Utilities\Singleton;

class Config extends Singleton
{
    public $webhookUrl = "";
    public $forwardChatCommands = true;
}
