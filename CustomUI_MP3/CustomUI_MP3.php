<?php

namespace ManiaLivePlugins\eXpansion\CustomUI_MP3;

use Exception;
use ManiaLivePlugins\eXpansion\Core\types\config\types\Boolean;

class CustomUI_MP3 extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
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
        $ui .= '<map_info visible="' . (($this->config->map_info) ? 'true' : 'false') . '" />';
        $ui .= '<opponents_info visible="' . (($this->config->opponents_info) ? 'true' : 'false') . '" />';
        $ui .= '<chat visible="' . (($this->config->chat) ? 'true' : 'false') . '" offset="0. 0." linecount="' . intval($this->config->chatline) . '" />';
		$ui .= '<checkpoint_list visible="' . (($this->config->checkpoint_list) ? 'true' : 'false') . '" pos="' . floatval($this->config->checkpoint_list_x).' '.floatval($this->config->checkpoint_list_y).' '.floatval($this->config->checkpoint_list_z). '" />';
		$ui .= '<round_scores visible="' . (($this->config->round_scores) ? 'true' : 'false') . '" pos="' . floatval($this->config->round_scores_x).' '.floatval($this->config->round_scores_y).' '.floatval($this->config->round_scores_z). '" />';
		$ui .= '<countdown visible="' . (($this->config->countdown) ? 'true' : 'false') . '" pos="' . floatval($this->config->countdown_x).' '.floatval($this->config->countdown_y).' '.floatval($this->config->countdown_z). '" />';
		$ui .= '<go visible="' . (($this->config->go) ? 'true' : 'false') . '" />';
		$ui .= '<chrono visible="' . (($this->config->chrono) ? 'true' : 'false') . '" pos="' . floatval($this->config->chrono_x).' '.floatval($this->config->chrono_y).' '.floatval($this->config->chrono_z). '" />';
		$ui .= '<speed_and_distance visible="' . (($this->config->speed_and_distance) ? 'true' : 'false') . '" pos="' . floatval($this->config->speed_and_distance_x).' '.floatval($this->config->speed_and_distance_y).' '.floatval($this->config->speed_and_distance_z). '" />';
		$ui .= '<personal_best_and_rank visible="' . (($this->config->personal_best_and_rank) ? 'true' : 'false') . '" pos="' . floatval($this->config->personal_best_and_rank_x).' '.floatval($this->config->personal_best_and_rank_y).' '.floatval($this->config->personal_best_and_rank_z). '" />';
        $ui .= '<position visible="true" />';
		$ui .= '<checkpoint_time visible="' . (($this->config->checkpoint_time) ? 'true' : 'false') . '" pos="' . floatval($this->config->checkpoint_time_x).' '.floatval($this->config->checkpoint_time_y).' '.floatval($this->config->checkpoint_time_z). '" />';
		$ui .= '<chat_avatar visible="' . (($this->config->chat_avatar) ? 'true' : 'false') . '" />';
		$ui .= '<warmup visible="' . (($this->config->warmup) ? 'true' : 'false') . '" pos="' . floatval($this->config->warmup_x).' '.floatval($this->config->warmup_y).' '.floatval($this->config->warmup_z). '" />';
		$ui .= '<endmap_ladder_recap visible="' . (($this->config->endmap_ladder_recap) ? 'true' : 'false') . '" />';
		$ui .= '<multilap_info visible="' . (($this->config->multilap_info) ? 'true' : 'false') . '" pos="' . floatval($this->config->multilap_info_x).' '.floatval($this->config->multilap_info_y).' '.floatval($this->config->multilap_info_z). '" />';
		$ui .= '</ui_properties>';

        $this->connection->triggerModeScriptEvent("UI_SetProperties", $ui);
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        if ($var->getConfigInstance() instanceof \ManiaLivePlugins\eXpansion\CustomUI_MP3\Config) {
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
