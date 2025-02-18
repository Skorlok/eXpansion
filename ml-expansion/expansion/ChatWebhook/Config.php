<?php

namespace ManiaLivePlugins\eXpansion\ChatWebhook;

use ManiaLib\Utils\Singleton;

class Config extends Singleton
{
    public $webhookUrl = "";
    public $forwardChatCommands = true;
}
