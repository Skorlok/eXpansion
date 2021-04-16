<?php

namespace ManiaLivePlugins\eXpansion\CustomUI;

use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;

class CustomUI extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    public function eXpOnLoad()
    {
        // $this->enableDedicatedEvents();
    }

    public function eXpOnReady()
    {
        $this->updateData();
    }

    protected function updateData()
    {
		$this->config = Config::getInstance();

        $ui  = '<ui_properties>';
		$ui .= '<map_info visible="' . (($this->config->map_info) ? 'true' : 'false') . '" pos="' . floatval($this->config->map_info_x).' '.floatval($this->config->map_info_y).' '.floatval($this->config->map_info_z). '" />';
		$ui .= '<live_info visible="' . (($this->config->live_info) ? 'true' : 'false') . '" pos="' . floatval($this->config->live_info_x).' '.floatval($this->config->live_info_y).' '.floatval($this->config->live_info_z). '" />';
		$ui .= '<opponents_info visible="' . (($this->config->opponents_info) ? 'true' : 'false') . '" />';
		$ui .= '<chat visible="' . (($this->config->chat) ? 'true' : 'false') . '" offset="0. 0." linecount="' . intval($this->config->chatline) . '" />';
		$ui .= '<checkpoint_list visible="' . (($this->config->checkpoint_list) ? 'true' : 'false') . '" pos="' . floatval($this->config->checkpoint_list_x).' '.floatval($this->config->checkpoint_list_y).' '.floatval($this->config->checkpoint_list_z). '" />';
		$ui .= '<checkpoint_ranking visible="' . (($this->config->checkpoint_ranking) ? 'true' : 'false') . '" pos="' . floatval($this->config->checkpoint_ranking_x).' '.floatval($this->config->checkpoint_ranking_y).' '.floatval($this->config->checkpoint_ranking_z). '" />';
		$ui .= '<round_scores visible="' . (($this->config->round_scores) ? 'true' : 'false') . '" pos="' . floatval($this->config->round_scores_x).' '.floatval($this->config->round_scores_y).' '.floatval($this->config->round_scores_z). '" />';
		$ui .= '<countdown visible="' . (($this->config->countdown) ? 'true' : 'false') . '" pos="' . floatval($this->config->countdown_x).' '.floatval($this->config->countdown_y).' '.floatval($this->config->countdown_z). '" />';
		$ui .= '<go visible="' . (($this->config->go) ? 'true' : 'false') . '" />';
		$ui .= '<chrono visible="' . (($this->config->chrono) ? 'true' : 'false') . '" pos="' . floatval($this->config->chrono_x).' '.floatval($this->config->chrono_y).' '.floatval($this->config->chrono_z). '" />';
		$ui .= '<speed_and_distance visible="' . (($this->config->speed_and_distance) ? 'true' : 'false') . '" pos="' . floatval($this->config->speed_and_distance_x).' '.floatval($this->config->speed_and_distance_y).' '.floatval($this->config->speed_and_distance_z). '" />';
		$ui .= '<personal_best_and_rank visible="' . (($this->config->personal_best_and_rank) ? 'true' : 'false') . '" pos="' . floatval($this->config->personal_best_and_rank_x).' '.floatval($this->config->personal_best_and_rank_y).' '.floatval($this->config->personal_best_and_rank_z). '" />';
		$ui .= '<position visible="' . (($this->config->position) ? 'true' : 'false') . '" pos="' . floatval($this->config->position_x).' '.floatval($this->config->position_y).' '.floatval($this->config->position_z). '" />';
		$ui .= '<checkpoint_time visible="' . (($this->config->checkpoint_time) ? 'true' : 'false') . '" pos="' . floatval($this->config->checkpoint_time_x).' '.floatval($this->config->checkpoint_time_y).' '.floatval($this->config->checkpoint_time_z). '" />';
		$ui .= '<chat_avatar visible="' . (($this->config->chat_avatar) ? 'true' : 'false') . '" />';
		$ui .= '<warmup visible="' . (($this->config->warmup) ? 'true' : 'false') . '" pos="' . floatval($this->config->warmup_x).' '.floatval($this->config->warmup_y).' '.floatval($this->config->warmup_z). '" />';
		$ui .= '<endmap_ladder_recap visible="' . (($this->config->endmap_ladder_recap) ? 'true' : 'false') . '" />';
		$ui .= '<multilap_info visible="' . (($this->config->multilap_info) ? 'true' : 'false') . '" pos="' . floatval($this->config->multilap_info_x).' '.floatval($this->config->multilap_info_y).' '.floatval($this->config->multilap_info_z). '" />';
		$ui .= '<spectator_info visible="' . (($this->config->spectator_info) ? 'true' : 'false') . '" pos="' . floatval($this->config->spectator_info_x).' '.floatval($this->config->spectator_info_y).' '.floatval($this->config->spectator_info_z). '" />';
		$ui .= '<scorestable alt_visible="' . (($this->config->scorestablealt) ? 'true' : 'false') . '" visible="' . (($this->config->scorestable) ? 'true' : 'false') . '" />';
		$ui .= '<viewers_count visible="' . (($this->config->viewers_count) ? 'true' : 'false') . '" pos="' . floatval($this->config->viewers_count_x).' '.floatval($this->config->viewers_count_y).' '.floatval($this->config->viewers_count_z). '" />';
		$ui .= '</ui_properties>';

        $this->connection->triggerModeScriptEvent("Trackmania.UI.SetProperties", array($ui));
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof \ManiaLivePlugins\eXpansion\CustomUI\Config) {
            $this->updateData();
        }
    }

    public function eXpOnUnload()
    {
		$ui  = '<ui_properties>';
		$ui .= '<map_info visible="true" pos="-160. 80. 150." />';
		$ui .= '<live_info visible="true" pos="-159. 84. 5." />';
		$ui .= '<opponents_info visible="true" />';
		$ui .= '<chat visible="true" offset="0. 0." linecount="7" />';
		$ui .= '<checkpoint_list visible="true" pos="48. -52. 5." />';
		$ui .= '<checkpoint_ranking visible="false" pos="0. 84. 5." />';
		$ui .= '<round_scores visible="true" pos="-158.5 40. 150." />';
		$ui .= '<countdown visible="true" pos="153. -7. 5." />';
		$ui .= '<go visible="true" />';
		$ui .= '<chrono visible="true" pos="0. -80. -5." />';
		$ui .= '<speed_and_distance visible="true" pos="137. -69. 5." />';
		$ui .= '<personal_best_and_rank visible="true" pos="157. -24. 5." />';
		$ui .= '<position visible="true" pos="150.5 -28. 5." />';
		$ui .= '<checkpoint_time visible="true" pos="0. 3. -10." />';
		$ui .= '<chat_avatar visible="true" />';
		$ui .= '<warmup visible="true" pos="153. 13. 0." />';
		$ui .= '<endmap_ladder_recap visible="true" />';
		$ui .= '<multilap_info visible="true" pos="140. 84. 5." />';
		$ui .= '<spectator_info visible="true" pos="0. -68. 5." />';
		$ui .= '<scorestable alt_visible="true" visible="true" />';
		$ui .= '<viewers_count visible="true" pos="157. -40. 5." />';
		$ui .= '</ui_properties>';

        $this->connection->triggerModeScriptEvent("Trackmania.UI.SetProperties", array($ui));
	}
}
