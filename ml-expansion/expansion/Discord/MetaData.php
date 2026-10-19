<?php

namespace ManiaLivePlugins\eXpansion\Discord;

use ManiaLivePlugins\eXpansion\Core\types\config\types\SortedList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;

class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Chat: Discord Bot");
        $this->setDescription("Provides a Discord bot to talk from your server to Discord and vice versa.");
        $this->setGroups(array("Chat", "Connectivity"));

        $this->setRelaySupport(false);

        $config = Config::getInstance();

        $var = new TypeString("token", "Discord Bot Token", $config, false, false);
        $this->registerVariable($var);

        $var = new TypeString("channelId", "Discord Channel ID", $config, false, false);
        $this->registerVariable($var);

        $var = new Boolean("enableToDiscord", "Forward chat/connection/disconnection to Discord", $config, false, false);
        $var->setDescription("You can disable it if you want to use the ChatWebhook plugin instead");
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("forwardChatCommands", "Forward chat commands", $config, false, false);
        $var->setDescription("If true, chat commands will be forwarded to the webhook.");
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new TypeString('file_name', 'File Pattern', $config, true, false);
        $var->setDefaultValue('../Discord/{server_login}/{map_author}_{map_name}.map.gbx');
        $var->setDescription(
            array(
                'Pattern to define where the map will be saved. Available variables : ',
                '(relative to /UserData/Maps/Downloaded)',
                '{map_author}, {map_name}, {map_environment}, {map_vehicle}, {map_type}',
                '{map_style}, {server_title}, {server_login}',
                'You can use / to create sub directories.'
            )
        );
        $this->registerVariable($var);

        $var = new SortedList("allowedIds", "Allowed Discord IDs", $config, false, false);
        $var->setDescription("List of allowed Discord user IDs to use admin commands. (!help for more info)");
        $var->setType(new TypeInt("", "", null));
        $this->registerVariable($var);
    }
}
