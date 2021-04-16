<?php

namespace ManiaLivePlugins\eXpansion\Core;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\ColorCode;
use ManiaLivePlugins\eXpansion\Core\types\config\types\ConfigFile;
use ManiaLivePlugins\eXpansion\Core\types\config\types\SortedList;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeString;

/**
 * Description of MetaData
 *
 * @author De Cramer Oliver
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Core: eXpansion Core");
        $this->setDescription("Core plugin, all other plugins depend on this");
        $this->setGroups(array('Core'));

        $config = Config::getInstance();

        $var = new ColorCode('Colors_admin_error', 'Color code for admin error ', $config, false, false);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$f00');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_error', 'Color code for generic error', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$f00');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_info', 'Color code for generic info messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$bbb');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_admin_action', 'Color code for actions made by admins', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$fff');
        $this->registerVariable($var);

        $var = new ColorCode(
            'Colors_variable',
            'Color code for all variables used in chatmessages',
            $config,
            false,
            true
        );
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$fff');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_record', 'Color code for all localrecord messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$0af');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_record_top', 'Color code for top 5 localrecord messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$2d0');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_dedirecord', 'Color code for dedimania record messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$0af');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_rank', 'Color code for rank in records messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$ff0');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_time', 'Color code for time in records messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$fff');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_rating', 'Color code for map rating messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$fb3');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_queue', 'Color code for map queue messages (jukebox)', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$0af');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_personalmessage', 'Color code for personal messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$f90');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_admingroup_chat', 'Color code for admin chat channel', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$f00');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_donate', 'Color code for donation messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$f0f');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_player', 'Color code for generic player messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$3f0');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_joinmsg', 'Color code for joining  message', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$3f0');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_leavemsg', 'Color code for leaving message', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$3f0');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_music', 'Color code for musicbox messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$f7f');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_emote', 'Color code for emotes messages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$ff0$i');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_quiz', 'Color code for Quiz messsages', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$3e3');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_question', 'Color code for Quiz questions', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$z$s$o$fa0');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_vote', 'Color code for voting', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$0f0');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_vote_success', 'Color code for vote passing', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$0f0');
        $this->registerVariable($var);

        $var = new ColorCode('Colors_vote_failure', 'Color code for vote failure', $config, false, true);
        $var->setGroup("Chat Messages");
        $var->setDefaultValue('$f00');
        $this->registerVariable($var);

        $var = new TypeString('time_dynamic_max', 'Max time for dynamic TA limit, use format mm:ss', $config, false);
        $var->setGroup("Misc");
        $var->setDefaultValue('7:00');
        $this->registerVariable($var);

        $var = new TypeString('time_dynamic_min', 'Min time for dynamic TA limit, use format mm:ss', $config, false);
        $var->setGroup("Misc");
        $var->setDefaultValue('4:00');
        $this->registerVariable($var);

        $var = new Boolean('enableRanksCalc', 'Enable player position calculation', $config, false, true);
        $var->setGroup("Misc");
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new TypeString('defaultMatchSettingsFile', 'This servers autosave matchsettings file', $config, false);
        $var->setGroup("Config Files");
        $var->setDefaultValue('eXpansion_autosave.txt');
        $this->registerVariable($var);

        $var = new TypeString('dedicatedConfigFile', 'This servers autosave dedicated config file', $config, false);
        $var->setGroup("Config Files");
        $var->setDefaultValue('dedicated_cfg.txt');
        $this->registerVariable($var);

        $var = new ConfigFile('blackListSettingsFile', 'The file to save/load servers black list', $config, false);
        $var->setGroup("Config Files");
        $var->setDefaultValue('blacklist.txt');
        $this->registerVariable($var);

        $var = new ConfigFile('guestListSettingsFile', 'The file to save/load servers guest list', $config, false);
        $var->setGroup("Config Files");
        $var->setDefaultValue('guestlist.txt');
        $this->registerVariable($var);

        $var = new ConfigFile('saveSettingsFile', 'The file to save server settings', $config, false);
        $var->setGroup("Config Files");
        $var->setDefaultValue('casualRace');
        $this->registerVariable($var);

        $var = new TypeString(
            'contact',
            'Server administrators contact info (displayed at serverinfo window)',
            $config,
            false
        );
        $var->setDefaultValue('YOUR@EMAIL.COM');
        $this->registerVariable($var);

        $var = new SortedList('roundsPoints', 'Round points', $config, false);
        $var->setVisible(false);
        $var->setGroup("Misc");
        $var->setType(new TypeInt(""));
        $var->setOrder("desc");
        $var->setDefaultValue(array(10, 8, 7, 6, 5, 4, 3, 2, 1));
        $this->registerVariable($var);

        $var = new TypeString(
            'quitDialogManialink',
            'Quit dialog customization, use url with custom manialink.xml',
            $config,
            false
        );
        $var->setDescription('Customize quit dialog with your own manialink!');
        $var->setGroup('GUI');
        $var->setDefaultValue('');
        $this->registerVariable($var);

        $var = new Boolean('useWhitelist', 'Use Whitelist', $config, false, true);
        $var->setDescription("Kicks everybody else from server, than players in quest list");
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new Boolean('debug', 'Enable Debug-mode', $config, false, true);
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new Boolean('analytics', 'Send analytic data to eXpansion server', $config, false);
        $var->setDescription("All data are anonymous.");
        $var->setGroup("General");
        $var->setDefaultValue(true);
        $this->registerVariable($var);
    }
}
