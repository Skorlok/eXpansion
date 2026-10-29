<?php

namespace ManiaLivePlugins\eXpansion\ManiaExchange;

class Config extends \ManiaLive\Utilities\Singleton
{

    public $mxVote_enable = false;   // allow players/admins to queue tracks from MX
    public $mxVote_ratios = .5;      // vote ratio required to pass, 0.00 - 1.00
    public $mxVote_timeouts = 60;     // '0' for server default, '1' for indefinite, otherwise set number of desired seconds
    public $mxVote_voters = 1;      // '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
    public $juke_newmaps = true;  // whaever newly added maps should be put to jukebox
    public $key = "";
    public $file_name = '{server_login}/{map_author}_{map_name}_{mx_id}.map.gbx';
    public $announceMxRecord = true;
}
