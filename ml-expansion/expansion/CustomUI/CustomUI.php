<?php

namespace ManiaLivePlugins\eXpansion\CustomUI;

use Exception;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;

class CustomUI extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

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


		$ui  = '<ui_properties>';
		$ui .= '<notices visible="' . (($this->config->notices) ? 'true' : 'false') . '" />';
		$ui .= '<map_info visible="' . (($this->config->SMmap_info) ? 'true' : 'false') . '" />';
		$ui .= '<chat visible="' . (($this->config->SMchat) ? 'true' : 'false') . '" offset="0. 0." linecount="' . intval($this->config->SMchatline) . '" />';
		$ui .= '<countdown visible="' . (($this->config->SMcountdown) ? 'true' : 'false') . '" pos="' . floatval($this->config->SMcountdown_x).' '.floatval($this->config->SMcountdown_y).' '.floatval($this->config->SMcountdown_z). '" />';
		$ui .= '<crosshair visible="' . (($this->config->crosshair) ? 'true' : 'false') . '" />';
		$ui .= '<gauges visible="' . (($this->config->gauges) ? 'true' : 'false') . '" />';
		$ui .= '<consumables visible="' . (($this->config->consumables) ? 'true' : 'false') . '" />';
		$ui .= '<go visible="' . (($this->config->SMgo) ? 'true' : 'false') . '" />';
		$ui .= '<chat_avatar visible="' . (($this->config->SMchat_avatar) ? 'true' : 'false') . '" />';
		$ui .= '<endmap_ladder_recap visible="' . (($this->config->SMendmap_ladder_recap) ? 'true' : 'false') . '" />';
		$ui .= '<scorestable alt_visible="' . (($this->config->SMscorestablealt) ? 'true' : 'false') . '" visible="' . (($this->config->SMscorestable) ? 'true' : 'false') . '" />';
		$ui .= '</ui_properties>';

		$this->connection->triggerModeScriptEvent("Shootmania.UI.SetProperties", array($ui));


		$ui  = '<ui_properties>';
        $ui .= '<map_info visible="' . (($this->config->MP3map_info) ? 'true' : 'false') . '" />';
        $ui .= '<opponents_info visible="' . (($this->config->MP3opponents_info) ? 'true' : 'false') . '" />';
        $ui .= '<chat visible="' . (($this->config->MP3chat) ? 'true' : 'false') . '" offset="0. 0." linecount="' . intval($this->config->MP3chatline) . '" />';
		$ui .= '<checkpoint_list visible="' . (($this->config->MP3checkpoint_list) ? 'true' : 'false') . '" pos="' . floatval($this->config->MP3checkpoint_list_x).' '.floatval($this->config->MP3checkpoint_list_y).' '.floatval($this->config->MP3checkpoint_list_z). '" />';
		$ui .= '<round_scores visible="' . (($this->config->MP3round_scores) ? 'true' : 'false') . '" pos="' . floatval($this->config->MP3round_scores_x).' '.floatval($this->config->MP3round_scores_y).' '.floatval($this->config->MP3round_scores_z). '" />';
		$ui .= '<countdown visible="' . (($this->config->MP3countdown) ? 'true' : 'false') . '" pos="' . floatval($this->config->MP3countdown_x).' '.floatval($this->config->MP3countdown_y).' '.floatval($this->config->MP3countdown_z). '" />';
		$ui .= '<go visible="' . (($this->config->MP3go) ? 'true' : 'false') . '" />';
		$ui .= '<chrono visible="' . (($this->config->MP3chrono) ? 'true' : 'false') . '" pos="' . floatval($this->config->MP3chrono_x).' '.floatval($this->config->MP3chrono_y).' '.floatval($this->config->MP3chrono_z). '" />';
		$ui .= '<speed_and_distance visible="' . (($this->config->MP3speed_and_distance) ? 'true' : 'false') . '" pos="' . floatval($this->config->MP3speed_and_distance_x).' '.floatval($this->config->MP3speed_and_distance_y).' '.floatval($this->config->MP3speed_and_distance_z). '" />';
		$ui .= '<personal_best_and_rank visible="' . (($this->config->MP3personal_best_and_rank) ? 'true' : 'false') . '" pos="' . floatval($this->config->MP3personal_best_and_rank_x).' '.floatval($this->config->MP3personal_best_and_rank_y).' '.floatval($this->config->MP3personal_best_and_rank_z). '" />';
        $ui .= '<position visible="true" />';
		$ui .= '<checkpoint_time visible="' . (($this->config->MP3checkpoint_time) ? 'true' : 'false') . '" pos="' . floatval($this->config->MP3checkpoint_time_x).' '.floatval($this->config->MP3checkpoint_time_y).' '.floatval($this->config->MP3checkpoint_time_z). '" />';
		$ui .= '<chat_avatar visible="' . (($this->config->MP3chat_avatar) ? 'true' : 'false') . '" />';
		$ui .= '<warmup visible="' . (($this->config->MP3warmup) ? 'true' : 'false') . '" pos="' . floatval($this->config->MP3warmup_x).' '.floatval($this->config->MP3warmup_y).' '.floatval($this->config->MP3warmup_z). '" />';
		$ui .= '<endmap_ladder_recap visible="' . (($this->config->MP3endmap_ladder_recap) ? 'true' : 'false') . '" />';
		$ui .= '<multilap_info visible="' . (($this->config->MP3multilap_info) ? 'true' : 'false') . '" pos="' . floatval($this->config->MP3multilap_info_x).' '.floatval($this->config->MP3multilap_info_y).' '.floatval($this->config->MP3multilap_info_z). '" />';
		$ui .= '</ui_properties>';

        $this->connection->triggerModeScriptEvent("UI_SetProperties", $ui);
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof \ManiaLivePlugins\eXpansion\CustomUI\Config) {
            $this->updateData();
        }
    }

	public function onGameModeChange($oldGameMode, $newGameMode)
	{
		$this->updateData();
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

		try {
			$this->connection->triggerModeScriptEvent("Trackmania.UI.SetProperties", array($ui));
		} catch (Exception $e) {
			return;
		}


		$ui  = '<ui_properties>';
		$ui .= '<notices visible="true" />';
		$ui .= '<map_info visible="true" />';
		$ui .= '<chat visible="true" offset="0. 0." linecount="7" />';
		$ui .= '<countdown visible="true" pos="0. 85." />';
		$ui .= '<crosshair visible="true" />';
		$ui .= '<gauges visible="true" />';
		$ui .= '<consumables visible="true" />';
		$ui .= '<go visible="true" />';
		$ui .= '<chat_avatar visible="true" />';
		$ui .= '<endmap_ladder_recap visible="true" />';
		$ui .= '<scorestable alt_visible="true" visible="true" />';
		$ui .= '</ui_properties>';

		try {
			$this->connection->triggerModeScriptEvent("Shootmania.UI.SetProperties", array($ui));
		} catch (Exception $e) {
			return;
		}


		$ui  = '<ui_properties>';
		$ui .= '<map_info visible="true" />';
		$ui .= '<opponents_info visible="true" />';
		$ui .= '<chat visible="true" offset="0. 0." linecount="7" />';
		$ui .= '<checkpoint_list visible="true" pos="40. -90. 5." />';
		$ui .= '<round_scores visible="true" pos="104. 14. 5." />';
		$ui .= '<countdown visible="true" pos="154. -57. 5." />';
		$ui .= '<go visible="true" />';
		$ui .= '<chrono visible="true" pos="0. -80. 5." />';
		$ui .= '<speed_and_distance visible="true" pos="158. -79.5 5." />';
		$ui .= '<personal_best_and_rank visible="true" pos="158. -61. 5." />';
		$ui .= '<position visible="true" />';
		$ui .= '<checkpoint_time visible="true" pos="-8. 31.8 -10." />';
		$ui .= '<chat_avatar visible="true" />';
		$ui .= '<warmup visible="true" pos="170. 27. 0." />';
		$ui .= '<endmap_ladder_recap visible="true" />';
		$ui .= '<multilap_info visible="true" pos="152. 49.5 5." />';
		$ui .= '</ui_properties>';

		try {
			$this->connection->triggerModeScriptEvent("UI_SetProperties", $ui);
		} catch (Exception $e) {
			return;
		}
	}
}
