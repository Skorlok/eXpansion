<?php

namespace ManiaLivePlugins\eXpansion\CustomUI_MP3;

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
        $this->setName("Tools: Game UI Elements ( MP3 )");
        $this->setDescription("Customise the UI");
        $this->setGroups(array('Tools'));

        $this->addTitleSupport("TM");
        $this->addTitleSupport("Trackmania");

        $config = Config::getInstance();

        $var = new Boolean("map_info", "map_info", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new Boolean("opponents_info", "opponents_info", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("chat", "chat", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new TypeInt("chatline", "chatline", $config, false, false);
        $var->setDefaultValue(7);
        $this->registerVariable($var);

        $var = new Boolean("checkpoint_list", "checkpoint_list", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("round_scores", "round_scores", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);

        $var = new Boolean("countdown", "countdown", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("go", "go", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("chrono", "chrono", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);

        $var = new Boolean("speed_and_distance", "speed_and_distance", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);
		
		$var = new Boolean("personal_best_and_rank", "personal_best_and_rank", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);

		$var = new Boolean("position", "position", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);
		
		$var = new Boolean("checkpoint_time", "checkpoint_time", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);
		
		$var = new Boolean("chat_avatar", "chat_avatar", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);
		
		$var = new Boolean("warmup", "warmup", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);
		
		$var = new Boolean("endmap_ladder_recap", "endmap_ladder_recap", $config, false, false);
        $var->setDefaultValue(false);
        $this->registerVariable($var);
		
		$var = new Boolean("multilap_info", "multilap_info", $config, false, false);
        $var->setDefaultValue(true);
        $this->registerVariable($var);



        $var = new TypeFloat("chrono_x", "chrono_x", $config, false, false);
        $var->setDefaultValue(0.0);
        $this->registerVariable($var);

        $var = new TypeFloat("chrono_y", "chrono_y", $config, false, false);
        $var->setDefaultValue(-80.0);
        $this->registerVariable($var);

        $var = new TypeFloat("chrono_z", "chrono_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $this->registerVariable($var);


        $var = new TypeFloat("checkpoint_list_x", "checkpoint_list_x", $config, false, false);
        $var->setDefaultValue(40.0);
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_list_y", "checkpoint_list_y", $config, false, false);
        $var->setDefaultValue(-90.0);
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_list_z", "checkpoint_list_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $this->registerVariable($var);


        $var = new TypeFloat("round_scores_x", "round_scores_x", $config, false, false);
        $var->setDefaultValue(104.0);
        $this->registerVariable($var);

        $var = new TypeFloat("round_scores_y", "round_scores_y", $config, false, false);
        $var->setDefaultValue(14.0);
        $this->registerVariable($var);

        $var = new TypeFloat("round_scores_z", "round_scores_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $this->registerVariable($var);


        $var = new TypeFloat("countdown_x", "countdown_x", $config, false, false);
        $var->setDefaultValue(154.0);
        $this->registerVariable($var);

        $var = new TypeFloat("countdown_y", "countdown_y", $config, false, false);
        $var->setDefaultValue(-57.0);
        $this->registerVariable($var);

        $var = new TypeFloat("countdown_z", "countdown_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $this->registerVariable($var);


        $var = new TypeFloat("speed_and_distance_x", "speed_and_distance_x", $config, false, false);
        $var->setDefaultValue(158.0);
        $this->registerVariable($var);

        $var = new TypeFloat("speed_and_distance_y", "speed_and_distance_y", $config, false, false);
        $var->setDefaultValue(-79.5);
        $this->registerVariable($var);

        $var = new TypeFloat("speed_and_distance_z", "speed_and_distance_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $this->registerVariable($var);


        $var = new TypeFloat("personal_best_and_rank_x", "personal_best_and_rank_x", $config, false, false);
        $var->setDefaultValue(158.0);
        $this->registerVariable($var);

        $var = new TypeFloat("personal_best_and_rank_y", "personal_best_and_rank_y", $config, false, false);
        $var->setDefaultValue(-61.0);
        $this->registerVariable($var);

        $var = new TypeFloat("personal_best_and_rank_z", "personal_best_and_rank_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $this->registerVariable($var);


        $var = new TypeFloat("checkpoint_time_x", "checkpoint_time_x", $config, false, false);
        $var->setDefaultValue(-8.0);
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_time_y", "checkpoint_time_y", $config, false, false);
        $var->setDefaultValue(31.8);
        $this->registerVariable($var);

        $var = new TypeFloat("checkpoint_time_z", "checkpoint_time_z", $config, false, false);
        $var->setDefaultValue(-10.0);
        $this->registerVariable($var);


        $var = new TypeFloat("warmup_x", "warmup_x", $config, false, false);
        $var->setDefaultValue(170.0);
        $this->registerVariable($var);

        $var = new TypeFloat("warmup_y", "warmup_y", $config, false, false);
        $var->setDefaultValue(27.0);
        $this->registerVariable($var);

        $var = new TypeFloat("warmup_z", "warmup_z", $config, false, false);
        $var->setDefaultValue(0.0);
        $this->registerVariable($var);


        $var = new TypeFloat("multilap_info_x", "multilap_info_x", $config, false, false);
        $var->setDefaultValue(152.0);
        $this->registerVariable($var);

        $var = new TypeFloat("multilap_info_y", "multilap_info_y", $config, false, false);
        $var->setDefaultValue(49.5);
        $this->registerVariable($var);

        $var = new TypeFloat("multilap_info_z", "multilap_info_z", $config, false, false);
        $var->setDefaultValue(5.0);
        $this->registerVariable($var);
    }
}
