<?php

namespace ManiaLivePlugins\eXpansion\Discord;

use ManiaLib\Utils\Singleton;

class Config extends Singleton
{
    public $token = "";
    public $channelId = "";
    public $forwardChatCommands = true;
    public $file_name = '../Discord/{server_login}/{map_author}_{map_name}.map.gbx';
    public $allowedIds = array();
    public $enableToDiscord = true;
}
