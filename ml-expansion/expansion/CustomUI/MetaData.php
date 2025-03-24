<?php

namespace ManiaLivePlugins\eXpansion\CustomUI;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeInt;
use ManiaLivePlugins\eXpansion\Core\types\config\types\TypeFloat;


/**
 * Description of MetaData
 *
 * @author Skorlok
 */
class MetaData extends \ManiaLivePlugins\eXpansion\Core\types\config\MetaData
{

    public function onBeginLoad()
    {
        parent::onBeginLoad();
        $this->setName("Tools: Game UI Elements");
        $this->setDescription("Customise the UI");
        $this->setGroups(array('Tools'));

        $config = Config::getInstance();

        $var = new Boolean("map_info", "map_info", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("live_info", "live_info", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("opponents_info", "opponents_info", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("chat", "chat", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("checkpoint_list", "checkpoint_list", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("checkpoint_ranking", "checkpoint_ranking", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("round_scores", "round_scores", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("countdown", "countdown", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("go", "go", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("chrono", "chrono", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("speed_and_distance", "speed_and_distance", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("personal_best_and_rank", "personal_best_and_rank", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

		$var = new Boolean("position", "position", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("checkpoint_time", "checkpoint_time", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("chat_avatar", "chat_avatar", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("warmup", "warmup", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("endmap_ladder_recap", "endmap_ladder_recap", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("multilap_info", "multilap_info", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("spectator_info", "spectator_info", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("scorestablealt", "scorestablealt", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("scorestable", "scorestable", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("viewers_count", "viewers_count", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);

        $var = new TypeInt("chatline", "chatline", $config, false, false);
        $var->setDefaultValue(7);
        $var->setGroup("TM Visibility");
        $this->registerVariable($var);



        $var = new TypeFloat("chrono_x", "chrono_x", $config, false, false);
        $var->setDefaultValue(0.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("chrono_y", "chrono_y", $config, false, false);
        $var->setDefaultValue(-84.5);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("chrono_z", "chrono_z", $config, false, false);
        $var->setDefaultValue(-5.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("map_info_x", "map_info_x", $config, false, false);
        $var->setDefaultValue(-160.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("map_info_y", "map_info_y", $config, false, false);
        $var->setDefaultValue(80.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("map_info_z", "map_info_z", $config, false, false);
        $var->setDefaultValue(150.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("live_info_x", "live_info_x", $config, false, false);
        $var->setDefaultValue(-115);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("live_info_y", "live_info_y", $config, false, false);
        $var->setDefaultValue(75);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("live_info_z", "live_info_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("checkpoint_list_x", "checkpoint_list_x", $config, false, false);
        $var->setDefaultValue(38.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_list_y", "checkpoint_list_y", $config, false, false);
        $var->setDefaultValue(-57.5);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_list_z", "checkpoint_list_z", $config, false, false);
        $var->setDefaultValue(10.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("checkpoint_ranking_x", "checkpoint_ranking_x", $config, false, false);
        $var->setDefaultValue(0.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_ranking_y", "checkpoint_ranking_y", $config, false, false);
        $var->setDefaultValue(84.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_ranking_z", "checkpoint_ranking_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("round_scores_x", "round_scores_x", $config, false, false);
        $var->setDefaultValue(110.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("round_scores_y", "round_scores_y", $config, false, false);
        $var->setDefaultValue(55.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("round_scores_z", "round_scores_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("countdown_x", "countdown_x", $config, false, false);
        $var->setDefaultValue(115.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("countdown_y", "countdown_y", $config, false, false);
        $var->setDefaultValue(-60.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("countdown_z", "countdown_z", $config, false, false);
        $var->setDefaultValue(10.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("speed_and_distance_x", "speed_and_distance_x", $config, false, false);
        $var->setDefaultValue(137.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("speed_and_distance_y", "speed_and_distance_y", $config, false, false);
        $var->setDefaultValue(-69.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("speed_and_distance_z", "speed_and_distance_z", $config, false, false);
        $var->setDefaultValue(10.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("personal_best_and_rank_x", "personal_best_and_rank_x", $config, false, false);
        $var->setDefaultValue(157.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("personal_best_and_rank_y", "personal_best_and_rank_y", $config, false, false);
        $var->setDefaultValue(-49.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("personal_best_and_rank_z", "personal_best_and_rank_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("position_x", "position_x", $config, false, false);
        $var->setDefaultValue(150.5);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("position_y", "position_y", $config, false, false);
        $var->setDefaultValue(-53.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("position_z", "position_z", $config, false, false);
        $var->setDefaultValue(10.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("checkpoint_time_x", "checkpoint_time_x", $config, false, false);
        $var->setDefaultValue(0.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_time_y", "checkpoint_time_y", $config, false, false);
        $var->setDefaultValue(0.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_time_z", "checkpoint_time_z", $config, false, false);
        $var->setDefaultValue(10.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("warmup_x", "warmup_x", $config, false, false);
        $var->setDefaultValue(115.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("warmup_y", "warmup_y", $config, false, false);
        $var->setDefaultValue(60.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("warmup_z", "warmup_z", $config, false, false);
        $var->setDefaultValue(0.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("multilap_info_x", "multilap_info_x", $config, false, false);
        $var->setDefaultValue(140.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("multilap_info_y", "multilap_info_y", $config, false, false);
        $var->setDefaultValue(-35.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("multilap_info_z", "multilap_info_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("spectator_info_x", "spectator_info_x", $config, false, false);
        $var->setDefaultValue(0.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("spectator_info_y", "spectator_info_y", $config, false, false);
        $var->setDefaultValue(-68.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("spectator_info_z", "spectator_info_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("viewers_count_x", "viewers_count_x", $config, false, false);
        $var->setDefaultValue(140.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("viewers_count_y", "viewers_count_y", $config, false, false);
        $var->setDefaultValue(-58.5);
        $var->setGroup("TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("viewers_count_z", "viewers_count_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("TM Position");
        $this->registerVariable($var);



        /*
        #///////////////////////////////////////////////////////////////////////#
        #									#
        #///////////////////////////////////////////////////////////////////////#
        */



        $var = new Boolean("notices", "notices", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("SMmap_info", "map_info", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("SMchat", "chat", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("SMcountdown", "countdown", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("crosshair", "crosshair", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("gauges", "gauges", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("consumables", "consumables", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("SMgo", "go", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("SMchat_avatar", "chat_avatar", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("SMendmap_ladder_recap", "endmap_ladder_recap", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("SMscorestablealt", "scorestablealt", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("SMscorestable", "scorestable", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);

        $var = new TypeInt("SMchatline", "chatline", $config, false, false);
        $var->setDefaultValue(7);
        $var->setGroup("SM Visibility");
        $this->registerVariable($var);





        $var = new TypeFloat("SMcountdown_x", "countdown_x", $config, false, false);
        $var->setDefaultValue(0.0);
        $var->setGroup("SM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("SMcountdown_y", "countdown_y", $config, false, false);
        $var->setDefaultValue(85.0);
        $var->setGroup("SM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("SMcountdown_z", "countdown_z", $config, false, false);
        $var->setDefaultValue(10.0);
        $var->setGroup("SM Position");
        $this->registerVariable($var);



        /*
        #///////////////////////////////////////////////////////////////////////#
        #									#
        #///////////////////////////////////////////////////////////////////////#
        */



        $var = new Boolean("MP3map_info", "map_info", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("MP3opponents_info", "opponents_info", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("MP3chat", "chat", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("MP3checkpoint_list", "checkpoint_list", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("MP3round_scores", "round_scores", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("MP3countdown", "countdown", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("MP3go", "go", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("MP3chrono", "chrono", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

        $var = new Boolean("MP3speed_and_distance", "speed_and_distance", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("MP3personal_best_and_rank", "personal_best_and_rank", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

		$var = new Boolean("MP3position", "position", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("MP3checkpoint_time", "checkpoint_time", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("MP3chat_avatar", "chat_avatar", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("MP3warmup", "warmup", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("MP3endmap_ladder_recap", "endmap_ladder_recap", $config, false, false);
        $var->setDefaultValue(false);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);
		
		$var = new Boolean("MP3multilap_info", "multilap_info", $config, false, false);
        $var->setDefaultValue(true);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);

        $var = new TypeInt("MP3chatline", "chatline", $config, false, false);
        $var->setDefaultValue(7);
        $var->setGroup("MP3 TM Visibility");
        $this->registerVariable($var);



        $var = new TypeFloat("MP3chrono_x", "chrono_x", $config, false, false);
        $var->setDefaultValue(0.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3chrono_y", "chrono_y", $config, false, false);
        $var->setDefaultValue(-80.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3chrono_z", "chrono_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("MP3checkpoint_list_x", "checkpoint_list_x", $config, false, false);
        $var->setDefaultValue(40.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3checkpoint_list_y", "checkpoint_list_y", $config, false, false);
        $var->setDefaultValue(-90.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3checkpoint_list_z", "checkpoint_list_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("MP3round_scores_x", "round_scores_x", $config, false, false);
        $var->setDefaultValue(104.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3round_scores_y", "round_scores_y", $config, false, false);
        $var->setDefaultValue(14.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3round_scores_z", "round_scores_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("MP3countdown_x", "countdown_x", $config, false, false);
        $var->setDefaultValue(154.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3countdown_y", "countdown_y", $config, false, false);
        $var->setDefaultValue(-57.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3countdown_z", "countdown_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("MP3speed_and_distance_x", "speed_and_distance_x", $config, false, false);
        $var->setDefaultValue(158.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3speed_and_distance_y", "speed_and_distance_y", $config, false, false);
        $var->setDefaultValue(-79.5);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3speed_and_distance_z", "speed_and_distance_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("MP3personal_best_and_rank_x", "personal_best_and_rank_x", $config, false, false);
        $var->setDefaultValue(158.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3personal_best_and_rank_y", "personal_best_and_rank_y", $config, false, false);
        $var->setDefaultValue(-61.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3personal_best_and_rank_z", "personal_best_and_rank_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("MP3checkpoint_time_x", "checkpoint_time_x", $config, false, false);
        $var->setDefaultValue(-8.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3checkpoint_time_y", "checkpoint_time_y", $config, false, false);
        $var->setDefaultValue(31.8);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3checkpoint_time_z", "checkpoint_time_z", $config, false, false);
        $var->setDefaultValue(-10.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("MP3warmup_x", "warmup_x", $config, false, false);
        $var->setDefaultValue(170.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3warmup_y", "warmup_y", $config, false, false);
        $var->setDefaultValue(27.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3warmup_z", "warmup_z", $config, false, false);
        $var->setDefaultValue(0.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);


        $var = new TypeFloat("MP3multilap_info_x", "multilap_info_x", $config, false, false);
        $var->setDefaultValue(152.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3multilap_info_y", "multilap_info_y", $config, false, false);
        $var->setDefaultValue(49.5);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);

        $var = new TypeFloat("MP3multilap_info_z", "multilap_info_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $var->setGroup("MP3 TM Position");
        $this->registerVariable($var);
    }
}
