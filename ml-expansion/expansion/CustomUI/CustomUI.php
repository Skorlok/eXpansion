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
		/** @var Config $config */
		$config = Config::getInstance();

        $ui  = '<ui_properties>';
		$ui .= '<map_info visible="' . (($config->map_info) ? 'true' : 'false') . '" pos="' . floatval($config->map_info_x).' '.floatval($config->map_info_y).' '.floatval($config->map_info_z). '" />';
		$ui .= '<live_info visible="' . (($config->live_info) ? 'true' : 'false') . '" pos="' . floatval($config->live_info_x).' '.floatval($config->live_info_y).' '.floatval($config->live_info_z). '" />';
		$ui .= '<opponents_info visible="' . (($config->opponents_info) ? 'true' : 'false') . '" />';
		$ui .= '<chat visible="' . (($config->chat) ? 'true' : 'false') . '" offset="0. 0." linecount="' . intval($config->chatline) . '" />';
		$ui .= '<checkpoint_list visible="' . (($config->checkpoint_list) ? 'true' : 'false') . '" pos="' . floatval($config->checkpoint_list_x).' '.floatval($config->checkpoint_list_y).' '.floatval($config->checkpoint_list_z). '" />';
		$ui .= '<checkpoint_ranking visible="' . (($config->checkpoint_ranking) ? 'true' : 'false') . '" pos="' . floatval($config->checkpoint_ranking_x).' '.floatval($config->checkpoint_ranking_y).' '.floatval($config->checkpoint_ranking_z). '" />';
		$ui .= '<round_scores visible="' . (($config->round_scores) ? 'true' : 'false') . '" pos="' . floatval($config->round_scores_x).' '.floatval($config->round_scores_y).' '.floatval($config->round_scores_z). '" />';
		$ui .= '<countdown visible="' . (($config->countdown) ? 'true' : 'false') . '" pos="' . floatval($config->countdown_x).' '.floatval($config->countdown_y).' '.floatval($config->countdown_z). '" />';
		$ui .= '<go visible="' . (($config->go) ? 'true' : 'false') . '" />';
		$ui .= '<chrono visible="' . (($config->chrono) ? 'true' : 'false') . '" pos="' . floatval($config->chrono_x).' '.floatval($config->chrono_y).' '.floatval($config->chrono_z). '" />';
		$ui .= '<speed_and_distance visible="' . (($config->speed_and_distance) ? 'true' : 'false') . '" pos="' . floatval($config->speed_and_distance_x).' '.floatval($config->speed_and_distance_y).' '.floatval($config->speed_and_distance_z). '" />';
		$ui .= '<personal_best_and_rank visible="' . (($config->personal_best_and_rank) ? 'true' : 'false') . '" pos="' . floatval($config->personal_best_and_rank_x).' '.floatval($config->personal_best_and_rank_y).' '.floatval($config->personal_best_and_rank_z). '" />';
		$ui .= '<position visible="' . (($config->position) ? 'true' : 'false') . '" pos="' . floatval($config->position_x).' '.floatval($config->position_y).' '.floatval($config->position_z). '" />';
		$ui .= '<checkpoint_time visible="' . (($config->checkpoint_time) ? 'true' : 'false') . '" pos="' . floatval($config->checkpoint_time_x).' '.floatval($config->checkpoint_time_y).' '.floatval($config->checkpoint_time_z). '" />';
		$ui .= '<chat_avatar visible="' . (($config->chat_avatar) ? 'true' : 'false') . '" />';
		$ui .= '<warmup visible="' . (($config->warmup) ? 'true' : 'false') . '" pos="' . floatval($config->warmup_x).' '.floatval($config->warmup_y).' '.floatval($config->warmup_z). '" />';
		$ui .= '<endmap_ladder_recap visible="' . (($config->endmap_ladder_recap) ? 'true' : 'false') . '" />';
		$ui .= '<multilap_info visible="' . (($config->multilap_info) ? 'true' : 'false') . '" pos="' . floatval($config->multilap_info_x).' '.floatval($config->multilap_info_y).' '.floatval($config->multilap_info_z). '" />';
		$ui .= '<spectator_info visible="' . (($config->spectator_info) ? 'true' : 'false') . '" pos="' . floatval($config->spectator_info_x).' '.floatval($config->spectator_info_y).' '.floatval($config->spectator_info_z). '" />';
		$ui .= '<scorestable alt_visible="' . (($config->scorestablealt) ? 'true' : 'false') . '" visible="' . (($config->scorestable) ? 'true' : 'false') . '" />';
		$ui .= '<viewers_count visible="' . (($config->viewers_count) ? 'true' : 'false') . '" pos="' . floatval($config->viewers_count_x).' '.floatval($config->viewers_count_y).' '.floatval($config->viewers_count_z). '" />';
		$ui .= '</ui_properties>';

        $this->connection->triggerModeScriptEvent("Trackmania.UI.SetProperties", array($ui));


		$ui  = '<ui_properties>';
		$ui .= '<notices visible="' . (($config->notices) ? 'true' : 'false') . '" />';
		$ui .= '<map_info visible="' . (($config->SMmap_info) ? 'true' : 'false') . '" />';
		$ui .= '<chat visible="' . (($config->SMchat) ? 'true' : 'false') . '" offset="0. 0." linecount="' . intval($config->SMchatline) . '" />';
		$ui .= '<countdown visible="' . (($config->SMcountdown) ? 'true' : 'false') . '" pos="' . floatval($config->SMcountdown_x).' '.floatval($config->SMcountdown_y).' '.floatval($config->SMcountdown_z). '" />';
		$ui .= '<crosshair visible="' . (($config->crosshair) ? 'true' : 'false') . '" />';
		$ui .= '<gauges visible="' . (($config->gauges) ? 'true' : 'false') . '" />';
		$ui .= '<consumables visible="' . (($config->consumables) ? 'true' : 'false') . '" />';
		$ui .= '<go visible="' . (($config->SMgo) ? 'true' : 'false') . '" />';
		$ui .= '<chat_avatar visible="' . (($config->SMchat_avatar) ? 'true' : 'false') . '" />';
		$ui .= '<endmap_ladder_recap visible="' . (($config->SMendmap_ladder_recap) ? 'true' : 'false') . '" />';
		$ui .= '<scorestable alt_visible="' . (($config->SMscorestablealt) ? 'true' : 'false') . '" visible="' . (($config->SMscorestable) ? 'true' : 'false') . '" />';
		$ui .= '</ui_properties>';

		$this->connection->triggerModeScriptEvent("Shootmania.UI.SetProperties", array($ui));


		$ui  = '<ui_properties>';
        $ui .= '<map_info visible="' . (($config->MP3map_info) ? 'true' : 'false') . '" />';
        $ui .= '<opponents_info visible="' . (($config->MP3opponents_info) ? 'true' : 'false') . '" />';
        $ui .= '<chat visible="' . (($config->MP3chat) ? 'true' : 'false') . '" offset="0. 0." linecount="' . intval($config->MP3chatline) . '" />';
		$ui .= '<checkpoint_list visible="' . (($config->MP3checkpoint_list) ? 'true' : 'false') . '" pos="' . floatval($config->MP3checkpoint_list_x).' '.floatval($config->MP3checkpoint_list_y).' '.floatval($config->MP3checkpoint_list_z). '" />';
		$ui .= '<round_scores visible="' . (($config->MP3round_scores) ? 'true' : 'false') . '" pos="' . floatval($config->MP3round_scores_x).' '.floatval($config->MP3round_scores_y).' '.floatval($config->MP3round_scores_z). '" />';
		$ui .= '<countdown visible="' . (($config->MP3countdown) ? 'true' : 'false') . '" pos="' . floatval($config->MP3countdown_x).' '.floatval($config->MP3countdown_y).' '.floatval($config->MP3countdown_z). '" />';
		$ui .= '<go visible="' . (($config->MP3go) ? 'true' : 'false') . '" />';
		$ui .= '<chrono visible="' . (($config->MP3chrono) ? 'true' : 'false') . '" pos="' . floatval($config->MP3chrono_x).' '.floatval($config->MP3chrono_y).' '.floatval($config->MP3chrono_z). '" />';
		$ui .= '<speed_and_distance visible="' . (($config->MP3speed_and_distance) ? 'true' : 'false') . '" pos="' . floatval($config->MP3speed_and_distance_x).' '.floatval($config->MP3speed_and_distance_y).' '.floatval($config->MP3speed_and_distance_z). '" />';
		$ui .= '<personal_best_and_rank visible="' . (($config->MP3personal_best_and_rank) ? 'true' : 'false') . '" pos="' . floatval($config->MP3personal_best_and_rank_x).' '.floatval($config->MP3personal_best_and_rank_y).' '.floatval($config->MP3personal_best_and_rank_z). '" />';
        $ui .= '<position visible="true" />';
		$ui .= '<checkpoint_time visible="' . (($config->MP3checkpoint_time) ? 'true' : 'false') . '" pos="' . floatval($config->MP3checkpoint_time_x).' '.floatval($config->MP3checkpoint_time_y).' '.floatval($config->MP3checkpoint_time_z). '" />';
		$ui .= '<chat_avatar visible="' . (($config->MP3chat_avatar) ? 'true' : 'false') . '" />';
		$ui .= '<warmup visible="' . (($config->MP3warmup) ? 'true' : 'false') . '" pos="' . floatval($config->MP3warmup_x).' '.floatval($config->MP3warmup_y).' '.floatval($config->MP3warmup_z). '" />';
		$ui .= '<endmap_ladder_recap visible="' . (($config->MP3endmap_ladder_recap) ? 'true' : 'false') . '" />';
		$ui .= '<multilap_info visible="' . (($config->MP3multilap_info) ? 'true' : 'false') . '" pos="' . floatval($config->MP3multilap_info_x).' '.floatval($config->MP3multilap_info_y).' '.floatval($config->MP3multilap_info_z). '" />';
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
