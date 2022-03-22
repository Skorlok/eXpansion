<?php

namespace ManiaLivePlugins\eXpansion\Core;

class Config extends \ManiaLib\Utils\Singleton
{

    public $debug = false;
    public $language = null;
    public $defaultLanguage = null;
    public $Colors_admin_error = '$f00'; // error message color for admin
    public $Colors_error = '$f00'; // general error message color
    public $Colors_admin_action = '$fff'; // admin actions color
    public $Colors_variable = '$fff'; // generic variable color
    public $Colors_record = '$3af'; // all other local records
    public $Colors_record_top = '$2d0'; // top5 local records
    public $Colors_dedirecord = '$2d0'; // dedimania records
    public $Colors_rank = '$dee'; // used in record messages and widgets for rank
    public $Colors_time = '$fff'; // used for record messages and widgets
    public $Colors_rating = '$f80'; // map ratings color
    public $Colors_queue = '$ff0'; // map queue messages
    public $Colors_personalmessage = '$3bd'; // personal messages
    public $Colors_admingroup_chat = '$f00'; // admin chat channel
    public $Colors_donate = '$f0f'; // donate
    public $Colors_player = '$3f0'; // used in joinleave-messages
    public $Colors_joinmsg = '$0c0$i'; // used in joinleave-messages
    public $Colors_leavemsg = '$c00$i'; // used in joinleave-messages
    public $Colors_music = '$f7f'; // music box
    public $Colors_emote = '$ff0$i'; // music box
    public $Colors_quiz = '$3e3'; // quiz
    public $Colors_question = '$fa0'; // quiz answer
    public $Colors_vote = '$3bd$i'; // votes
    public $Colors_info = '$bbb'; // votes
    public $Colors_vote_success = '$5d3'; // vote success
    public $Colors_vote_failure = '$d10'; // vote failure
    public $Colors_mx = '$3bd'; // mx record
    public $API_Version = '2013-04-16'; //ApiVersion can be 2011-10-06 for TM and 2013-04-16 for SM Add in config
    public $enableRanksCalc = true; // enable calculation of player ranks on checkpoints
    public $mapBase = "";
    public $defaultMatchSettingsFile = "eXpansion_autosave.txt";
    public $dedicatedConfigFile = "dedicated_cfg.txt";
    public $blackListSettingsFile = "blacklist.txt";
    public $guestListSettingsFile = "guestlist.txt";
    public $saveSettingsFile = "casualRace";
    public $contact = "YOUR@EMAIL.COM";
    public $disableGameMode = array();
    public $netLostTime = 4000;   // time in milliseconds for lastresponse time, used to determine netlost
    public $roundsPoints = array(10, 8, 6, 5, 4, 3, 2, 1);
    public $scriptRoundsPoints = array('10', '8', '7', '6', '5', '4', '3', '2', '1');
    public $quitDialogManialink = "";
    public $useWhitelist = false;
}
