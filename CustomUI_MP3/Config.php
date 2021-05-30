<?php

namespace ManiaLivePlugins\eXpansion\CustomUI_MP3;

class Config extends \ManiaLib\Utils\Singleton
{
	public $map_info = false;
    public $live_info = true;
    public $opponents_info = true;
    public $chat = true;
    public $chatline = 7;
    public $checkpoint_list = true;
    public $checkpoint_ranking = true;
    public $round_scores = false;
    public $countdown = true;
    public $go = true;
    public $chrono = true;
    public $speed_and_distance = true;
    public $personal_best_and_rank = false;
    public $position = true;
    public $checkpoint_time = true;
    public $chat_avatar = false;
    public $warmup = true;
    public $endmap_ladder_recap = false;
    public $multilap_info = true;
    public $spectator_info = true;
    public $scorestablealt = true;
    public $scorestable = true;
    public $viewers_count = true;


    public $chrono_x = 0.0;
    public $chrono_y = -80.0;
    public $chrono_z = 5.0;

    public $checkpoint_list_x = 40.0;
    public $checkpoint_list_y = -90.0;
    public $checkpoint_list_z = 5.0;

    public $round_scores_x = 104.0;
    public $round_scores_y = 14.0;
    public $round_scores_z = 5.0;

    public $countdown_x = 154.0;
    public $countdown_y = -57.0;
    public $countdown_z = 5.0;

    public $speed_and_distance_x = 158.0;
    public $speed_and_distance_y = -79.5;
    public $speed_and_distance_z = 5.0;

    public $personal_best_and_rank_x = 158.0;
    public $personal_best_and_rank_y = -61.0;
    public $personal_best_and_rank_z = 5.0;

    public $checkpoint_time_x = -8.0;
    public $checkpoint_time_y = 31.8;
    public $checkpoint_time_z = -10.0;

    public $warmup_x = 170.0;
    public $warmup_y = 27.0;
    public $warmup_z = 0.0;

    public $multilap_info_x = 152.0;
    public $multilap_info_y = 49.5;
    public $multilap_info_z = 5.0;
}
