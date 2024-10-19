<?php

namespace ManiaLivePlugins\eXpansion\Endurance;

use ManiaLib\Utils\Formatting;
use ManiaLive\Utilities\Time;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use ManiaLivePlugins\eXpansion\Core\types\ExpPlugin;
use ManiaLivePlugins\eXpansion\Helpers\ArrayOfObj;
use ManiaLivePlugins\eXpansion\AdminGroups\AdminGroups;
use ManiaLivePlugins\eXpansion\AdminGroups\Permission;
use ManiaLivePlugins\eXpansion\Endurance\Gui\Windows\EnduroScores;
use ManiaLivePlugins\eXpansion\Endurance\Gui\Widgets\EnduroPanel;
use ManiaLivePlugins\eXpansion\Endurance\Gui\Widgets\EnduroPanel2;

class Endurance extends ExpPlugin
{

    public static $enduro = false;
	public static $last_round = false;
	public static $openScoresAction = -1;
	public static $enduro_total_points = array();
    private $enduro_version = "V5.3.4";
    private $finishtime = array();
    private $lastcptime = array();
    private $rounds = null;
    private $maps = null;
    private $decreaser = null;
    private $points = null;
    private $points_last = null;
	private $save_csv = null;
	private $save_total_points = null;
    private $auto_reset = null;
    private $roundsdone = 0;
    private $mapsdone = 0;
	private $widgetPosX;
	private $widgetPosY;
	private $widgetNbFields;
	private $widgetNbFirstFields;

	public function eXpOnReady()
    {
        $this->enableDatabase();
        $this->enableDedicatedEvents();

        $config = Config::getInstance();
        $this->rounds = $config->rounds;
        $this->maps = $config->maps;
        $this->decreaser = $config->decreaser;
        $this->points = $config->points;
        $this->points_last = $config->points_last;
		$this->save_csv = $config->save_csv;
		$this->save_total_points = $config->save_total_points;
        $this->auto_reset = $config->auto_reset;
		$this->widgetPosX = $config->enduroPointPanel_PosX;
		$this->widgetPosY = $config->enduroPointPanel_PosY;
		$this->widgetNbFields = $config->enduroPointPanel_nbFields;
		$this->widgetNbFirstFields = $config->enduroPointPanel_nbFirstFields;

		$this->connection->setModeScriptVariables(array('WarmupTime' => $config->wu*1000));
		$this->connection->setModeScriptVariables(array('WarmupTimeNewMap' => $config->wustart*1000));
        unset($config);

        $tableColumns = $this->db->execute('SHOW COLUMNS FROM `exp_players`;')->fetchArrayOfObject();

        if (!ArrayOfObj::getObjbyPropValue($tableColumns, "Field", "EnduroPoints")) {
            $this->db->execute('ALTER TABLE `exp_players` ADD `EnduroPoints` int(11) NOT NULL DEFAULT "0" AFTER `player_nation`;');
        }

		$cmd = AdminGroups::addAdminCommand("setrounds", $this, "chat_setrounds", Permission::GAME_SETTINGS);
        $cmd->setHelp("Change number of rounds per map in Endurance gamemode");

		$cmd = AdminGroups::addAdminCommand("setmaps", $this, "chat_setmaps", Permission::GAME_SETTINGS);
        $cmd->setHelp("Change number of map per match in Endurance gamemode");

		$cmd = AdminGroups::addAdminCommand("resetpoints", $this, "chat_resetpoints", Permission::GAME_SETTINGS);
        $cmd->setHelp("Reset Endurance score");

		$cmd = AdminGroups::addAdminCommand("resetmatch", $this, "chat_resetmatch", Permission::GAME_SETTINGS);
        $cmd->setHelp("Reset Endurance match");

		$cmd = $this->registerChatCommand("enduropoints", "chat_points", 0, true);
        $cmd->help = 'Show endurance point system';

		$cmd = AdminGroups::addAdminCommand("setdecreaser", $this, "chat_setdecreaser", Permission::GAME_SETTINGS);
        $cmd->setHelp("Change decreaser(multiplication per CP) in Endurance gamemode");

		$cmd = AdminGroups::addAdminCommand("endurowu", $this, "chat_endurowu", Permission::GAME_SETTINGS);
        $cmd->setHelp("Change warmup time between rounds in Endurance gamemode");

		$cmd = AdminGroups::addAdminCommand("endurowustart", $this, "chat_endurowustart", Permission::GAME_SETTINGS);
        $cmd->setHelp("Change warmup time on new map in Endurance gamemode");

		$cmd = AdminGroups::addAdminCommand("savepoints", $this, "chat_savepoints", Permission::GAME_SETTINGS);
        $cmd->setHelp("Save current (total) points in the CSV file");

		$cmd = AdminGroups::addAdminCommand("removepoints", $this, "chat_removepoints", Permission::GAME_SETTINGS);
        $cmd->setHelp("Remove last (total) points in the CSV file");

		Endurance::$openScoresAction = \ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, 'showEnduroWindow'));

        $this->onBeginMap(null, null, null);
    }

    public function onSettingsChanged(\ManiaLivePlugins\eXpansion\Core\types\config\Variable $var)
    {
        $config = Config::getInstance();
        $this->rounds = $config->rounds;
        $this->maps = $config->maps;
        $this->decreaser = $config->decreaser;
        $this->points = $config->points;
        $this->points_last = $config->points_last;
		$this->save_csv = $config->save_csv;
		$this->save_total_points = $config->save_total_points;
        $this->auto_reset = $config->auto_reset;
		$this->widgetPosX = $config->enduroPointPanel_PosX;
		$this->widgetPosY = $config->enduroPointPanel_PosY;
		$this->widgetNbFields = $config->enduroPointPanel_nbFields;
		$this->widgetNbFirstFields = $config->enduroPointPanel_nbFirstFields;

		$this->connection->setModeScriptVariables(array('WarmupTime' => $config->wu*1000));
		$this->connection->setModeScriptVariables(array('WarmupTimeNewMap' => $config->wustart*1000));
        unset($config);

		EnduroPanel::EraseAll();
		$this->updateEnduroPanel();
    }

    public function onBeginRound()
    {
        $this->finishtime = array();
	    $this->lastcptime = array_fill_keys(array_keys($this->lastcptime), 0);
	    if (self::$enduro) {
		    $this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zRound $FFF' . ($this->roundsdone+1) . '$z/' . $this->rounds . ' on map '  . ($this->mapsdone+1) . '/' . $this->maps . '.', null);
		    if ($this->roundsdone+1 >= $this->rounds) {
			    $this->connection->setModeScriptVariables(array('last_round' => true));
				self::$last_round = true;
		    } else {
			    $this->connection->setModeScriptVariables(array('last_round' => false));
				self::$last_round = false;
		    }
		    $this->connection->setModeScriptVariables(array('decreaser' => floatval($this->decreaser)));
	    }
    }

    public function onEndRound()
	{
		if (self::$enduro) {
			$this->roundsdone++;
			$enduro_points = explode(",", $this->points);

			$vars = $this->connection->getModeScriptVariables();
			$scores = array_filter(explode(",", $vars["enduro_scores"]));

			$winner_time = 0;
			$pos = 0;
			$cp_pos = 0;
			$cp_r = -1;
			foreach ($scores as &$score) {
				$cpscore = explode(":",$score);
				$player_login = $cpscore[0];
				$cp = intval($cpscore[1]/1000);

				if (!isset($this->lastcptime[$player_login]) || $this->lastcptime[$player_login] == 0) {
					if (substr($player_login, 0, 11) == "*fakeplayer") {
						$this->lastcptime[$player_login] = 0;
					} else {
						$this->console('[plugin.records_eyepiece_enduro.php] Ignoring player login '.$player_login.' for invalid CPs! (no CP time registered)');
						$nick = $this->db->execute('SELECT player_nickname FROM exp_players WHERE player_login = "' . $player_login . '";')->fetchArrayOfObject();
						$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00WARNING$FF0] $z$i$f00$iIgnoring player $z'. $nick[0]->player_nickname .'$z$i$f00$i for invalid CPs! (no CP time registered)', null);
						continue;
					}
				}

				$pos++;
				if ($cp_r == $cp) {
					$cp_pos++;
				} else {
					$cp_r = $cp;
					$cp_pos = 1;
				}

				if ($winner_time == 0) $winner_time = $this->lastcptime[$player_login];

				if (isset($enduro_points[$pos-1])) {
					$points = intval($enduro_points[$pos-1]);
				} else {
					if (intval($this->points_last) == 0) {
						break;
					} else {
						$points = intval($this->points_last);
					}
				}

				$this->addPoints($player_login, $points);

				if (isset($this->storage->players[$player_login]) || isset($this->storage->spectators[$player_login])) {
					$this->eXpChatSendServerMessage('$z$s$FF0> [$F00INFO$FF0] $zEliminated at $FFF'. $this->ordinal($pos) .'$z place. CP: $FFF' . $cp . '$z ('. $this->ordinal($cp_pos) .').', $player_login);
					$this->eXpChatSendServerMessage('$z$s$FF0> [$F00INFO$FF0] $zYou gained $FFF' . $points . '$z points.', $player_login);
				}
			}

			if ($winner_time != 0) {
				$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zRound ended after $FFF' . Time::fromTM($winner_time) . '$z.', null);
			} else {
				$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zRound ended.', null);
			}

			$this->getPoints();
			$this->updateEnduroPanel();

			if ($this->roundsdone >= $this->rounds) {
				$this->mapsdone++;
				if ($this->mapsdone >= $this->maps) {
					$this->mapsdone = 0;
					$this->showEnduroWindow(null);
					EnduroPanel::EraseAll();
					EnduroPanel2::EraseAll();
				} else {
					$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zNext: map ' . ($this->mapsdone+1) . '/' . $this->maps . '.', null);
					EnduroPanel::EraseAll();
					EnduroPanel2::EraseAll();
				}
			}
		}
	}

    public function getPoints()
    {
	    self::$enduro_total_points = array();
	    $scores = $this->db->execute('SELECT player_login,player_nickname,EnduroPoints FROM exp_players WHERE EnduroPoints > 0 ORDER BY EnduroPoints DESC;')->fetchArrayOfObject();

        for ($i = 0; $i < count($scores); $i++) {
            self::$enduro_total_points[$scores[$i]->player_login]['name'] = $scores[$i]->player_nickname;
			self::$enduro_total_points[$scores[$i]->player_login]['points'] = $scores[$i]->EnduroPoints;
        }

        foreach ($this->lastcptime as $player_login => &$value) {
		    if (!isset(self::$enduro_total_points[$player_login])) {
                $nick = $this->db->execute('SELECT player_nickname FROM exp_players WHERE player_login = "' . $player_login . '";')->fetchArrayOfObject();
                self::$enduro_total_points[$player_login]['name'] = $nick[0]->player_nickname;
			    self::$enduro_total_points[$player_login]['points'] = 0;
		    }
	    }
    }

    public function addPoints($player_login, $points)
    {
	    $this->db->execute('UPDATE exp_players SET EnduroPoints = EnduroPoints + ' . $points . ' WHERE player_login="' . $player_login . '"');
    }

    public function resetPoints()
    {
	    $this->db->execute('UPDATE exp_players SET EnduroPoints = 0');

	    self::$enduro_total_points = array();

	    foreach ($this->lastcptime as $player_login => &$value) {
		    if ($value == 0) {
			    unset($this->lastcptime[$player_login]);
		    }
	    }
	    $this->updateEnduroPanel();
    }

    public function ordinal($number)
	{
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($number % 100) >= 11) && (($number%100) <= 13)) {
            return $number. 'th';
        } else {
            return $number. $ends[$number % 10];
        }
    }

    public function onBeginMap($map, $warmUp, $matchContinuation)
	{
		EnduroScores::EraseAll();

		self::$enduro = false;
		self::$last_round = false;
	
		if ($this->storage->gameInfos->gameMode == GameInfos::GAMEMODE_SCRIPT) {

			$scriptNameArr = $this->connection->getScriptName();
            $scriptName = $scriptNameArr['CurrentValue'];

            // Workaround for a 'bug' in setModeScriptText.
            if ($scriptName === '<in-development>') {
                $scriptName = $scriptNameArr['NextValue'];
            }


			$scriptName = mb_strtolower(substr(basename($scriptName), 0, -11));
			if ($scriptName == 'endurocup') {
				self::$enduro = true;
			} else {
				EnduroPanel::EraseAll();
				EnduroPanel2::EraseAll();
                return;
            }

            $version_check = substr($this->enduro_version, 0, 4);
		    $verror = false;
	
		    if (self::$enduro) {
			    $this->roundsdone = 0;
			    $vars = $this->connection->getModeScriptVariables();

			    $verror = substr($vars["version"], 0, 4) != $version_check;
			    if ($this->auto_reset && $this->mapsdone == 0)
				    $this->resetPoints();
		    }
	
		    if ($verror) {
			    $this->console('[plugin.records_eyepiece_enduro.php] Version not compatible! (Plugin: "' . $this->enduro_version . '" Script: "' .  $vars["version"] . '")');
			    $this->eXpChatSendServerMessage('$z$s$FF0>> [$F00WARNING$FF0] $z$i$f00$iVersion not compatible!', null);
			    $this->eXpChatSendServerMessage('$z$s$FF0>> [$F00WARNING$FF0] $z$i$f00$iPlugin: ' . $this->enduro_version, null);
			    $this->eXpChatSendServerMessage('$z$s$FF0>> [$F00WARNING$FF0] $z$i$f00$iScript: ' . $vars["version"], null);
		    }
		    $this->getPoints();
			$this->updateEnduroPanel();
		}
	}

    public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
	{
		if (self::$enduro) {

			$player = $this->storage->getPlayerObject($login);
			$this->lastcptime[$login] = $timeOrScore;

			if (($checkpointIndex+1) % $this->storage->currentMap->nbCheckpoints == 0) {
				if (($checkpointIndex+1) == $this->storage->currentMap->nbCheckpoints) {
					$this->finishtime[$player->playerId] = 0;
				} else if (!isset($this->finishtime[$player->playerId])) {
					$this->finishtime[$player->playerId] = $timeOrScore;
					return; // Missing previous finishtime data to calculate round time
				}
			}
		}
	}

	public function updateEnduroPanel($login = null)
    {
		if (!self::$enduro) {
			return;
		}
        $gui = \ManiaLivePlugins\eXpansion\Gui\Config::getInstance();

		if ($login != null) {
            Gui\Widgets\EnduroPanel::Erase($login);
            Gui\Widgets\EnduroPanel2::Erase($login);
        } else {
            Gui\Widgets\EnduroPanel::EraseAll();
            Gui\Widgets\EnduroPanel2::EraseAll();
        }

        $localRecs = self::$enduro_total_points;
        if ($login == null) {
            $panelMain = Gui\Widgets\EnduroPanel::Create($login);
            $panelMain->setLayer(\ManiaLive\Gui\Window::LAYER_NORMAL);
			$panelMain->setPosition($this->widgetPosX, $this->widgetPosY);
			$panelMain->setNbFields($this->widgetNbFields);
			$panelMain->setNbFirstFields($this->widgetNbFirstFields);
            $this->widgetIds["EnduroPanel"] = $panelMain;
            $this->widgetIds["EnduroPanel"]->update();
            $this->widgetIds["EnduroPanel"]->show();
        } elseif (count($localRecs) > 0) {
            $localRecs[0]->update();
            $localRecs[0]->show($login);
        }

        if (!$gui->disablePersonalHud) {
            $localRecs = EnduroPanel2::GetAll();
            if ($login == null) {
                $panelScore = Gui\Widgets\EnduroPanel2::Create($login);
                $panelScore->setLayer(\ManiaLive\Gui\Window::LAYER_SCORES_TABLE);
                $panelScore->setVisibleLayer("scorestable");
				$panelMain->setPosition($this->widgetPosX, $this->widgetPosY);
				$panelMain->setNbFields($this->widgetNbFields);
				$panelMain->setNbFirstFields($this->widgetNbFirstFields);
                $this->widgetIds["EnduroPanel2"] = $panelScore;
                $this->widgetIds["EnduroPanel2"]->update();
                $this->widgetIds["EnduroPanel2"]->show();
            } elseif (isset($localRecs[0])) {
                $localRecs[0]->update();
                $localRecs[0]->show($login);
            }
        }
    }

	public function showEnduroWindow($login)
	{
		EnduroScores::Erase($login);

        $window = EnduroScores::Create($login);
        $window->setTitle(__('Current Points', $login));
        $window->populateList(self::$enduro_total_points);
        $window->setSize(170, 100);
        $window->centerOnScreen();
        $window->show();
	}

	public function chat_setrounds($fromLogin, $params)
	{
		if (isset($params[0])) {
			$this->rounds = intval($params[0]);
		}
		if ($this->rounds <= 1) {
			$this->rounds = 1;
		}
		if ($this->roundsdone+1 >= $this->rounds) {
			$this->connection->setModeScriptVariables(array('last_round' => true));
			self::$last_round = true;
		} else {
			$this->connection->setModeScriptVariables(array('last_round' => false));
			self::$last_round = false;
		}

		$var = MetaData::getInstance()->getVariable('rounds');
		$var->setRawValue($this->rounds);
		\ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

		$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zEnduro rounds set to: $FFF' . $this->rounds, null);
	}

	public function chat_setmaps($fromLogin, $params)
	{
		if (isset($params[0])) {
			$this->maps = intval($params[0]);
		}
		if ($this->maps <= 1) {
			$this->maps = 1;
		}

		$var = MetaData::getInstance()->getVariable('maps');
		$var->setRawValue($this->maps);
		\ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

		$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zEnduro maps set to: $FFF' . $this->maps, null);
	}

	public function chat_resetpoints($fromLogin, $params)
	{
		$this->resetPoints();
		$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zTotal points has been reset', null);
	}

	public function chat_resetmatch($fromLogin, $params)
	{
		$this->roundsdone = 0;
		$this->mapsdone = 0;

		if ($this->roundsdone+1 >= $this->rounds) {
			$this->connection->setModeScriptVariables(array('last_round' => true));
			self::$last_round = true;
		} else {
			$this->connection->setModeScriptVariables(array('last_round' => false));
			self::$last_round = false;
		}

		$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zMatch maps and rounds has been reset', null);
	}

	public function chat_points($fromLogin)
	{
		$enduro_points = explode(",",$this->points);
		$enduro_points_last = intval($this->points_last);
		$this->eXpChatSendServerMessage('$z$s$FF0> [$F00INFO$FF0] $zEnduroCup points system: $FFF' . join(', ', $enduro_points) . ', ' . $enduro_points_last . '...', $fromLogin);
	}

	public function chat_setdecreaser($fromLogin, $params)
	{
		if (isset($params[0])) {
			$this->connection->setModeScriptVariables(array('decreaser' => floatval($params[0])));
			$this->decreaser = floatval($params[0]);

			$var = MetaData::getInstance()->getVariable('decreaser');
			$var->setRawValue($this->decreaser);
			\ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();
		}
		$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zDecreaser set to: $FFF' . $this->decreaser . '$z (multiplication per CP)', null);
	}

	public function chat_savepoints($fromLogin)
	{
		if (!is_dir("./backup")) {
            if (!mkdir("./backup", 0777)) {
                $this->connection->chatSendServerMessage("Error while creating backup folder", $fromLogin);
                return;
            }
        }
		$csv_file = "./backup/" . $this->save_csv;
		$save_total_points = $this->save_total_points;

		if ($save_total_points) {
			$type_points = "Total points";
		} else {
			$enduro_points = explode(",", $this->points);
			$type_points = "Points (according to enduro rounds points)";
		}
		if (!file_exists($csv_file)) {
			$w = file_put_contents($csv_file, "\xEF\xBB\xBFnickname;login;total_points;best_total_points"); // With UTF-8 BOM
			if ($w === false) {
				$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i' . "Can't create ".$csv_file, $fromLogin);
				$this->console("Can't create ".$csv_file);
				return;
			}
		}
		if (($handle_read = fopen($csv_file,'r')) === false) {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i' . "No read ".$csv_file." access", $fromLogin);
			$this->console("No read ".$csv_file." access");
			return;
		}
		$header = fgetcsv($handle_read, 1000, ";");
		if ($header[0] != "\xEF\xBB\xBFnickname" || $header[1] != "login" || end($header) != "best_total_points") {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i' . 'Invalid CSV format, created new one automatically', $fromLogin);
			$this->console('Invalid CSV format, created new one automatically');
			fclose($handle_read);
			if (rename($csv_file, $csv_file.'.old') !== false) {
				$this->chat_savepoints($fromLogin);
			}
			return;
		}
		if (($handle_write = fopen($csv_file.'.temp','w')) === false) {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i' . "No write ".$csv_file.".temp access", $fromLogin);
			$this->console("No write ".$csv_file.".temp access");
			fclose($handle_read);
			return;
		}
		$number = count($header)-3;
		array_splice($header, -2, 0, array("#".$number));
		fputcsv($handle_write, $header, ";");
		$this->eXpChatSendServerMessage('$z$s$FF0> [$F00INFO$FF0] $zSaving '.$type_points.' (#'.$number.')...', $fromLogin);
		$csv_points = array();
		while (($data = fgetcsv($handle_read, 1000, ";")) !== false) {
			$csv_points[$data[1]] = array_slice($data, 0, -2);
		}
		fclose($handle_read);

		$i = 0;
		$points_check = -1;
		$points_check_points = -1;
		foreach (self::$enduro_total_points as $plogin => &$pdata) {
			$pnickname = $this->handleSpecialChars(Formatting::stripStyles($pdata['name']));
			if (isset($csv_points[$plogin])) {
				$data = &$csv_points[$plogin];
				$data[0] = $pnickname;
			} else {
				$data = array($pnickname, $plogin);
				for ($j = 1; $j < $number; $j++) {
					$data[] = "0";
				}
			}
			if ($save_total_points) {
				$data[] = $pdata['points'];
			} else {
				if ($pdata['points'] == $points_check) {
					$points = $points_check_points;
				} else {
					if (isset($enduro_points[$i]))
						$points = intval($enduro_points[$i]);
					else
						$points = intval($this->points_last);
					$points_check = $pdata['points'];
					$points_check_points = $points;
				}
				$data[] = $points;
				$i++;
			}
			$this->calculate_total_and_best_points($data, $number);
			fputcsv($handle_write, $data, ";");
			unset($csv_points[$plogin]);
		}
		foreach ($csv_points as $key => &$data) {
			$data[] = "0";
			$this->calculate_total_and_best_points($data, $number);
			fputcsv($handle_write, $data, ";");
		}
		fclose($handle_write);
		if (rename($csv_file.'.temp',$csv_file) === false) {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i' . "No write ".$csv_file." access", $fromLogin);
			$this->console("No write ".$csv_file." access");
		} else {
			$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $z'.$type_points.' for #'.$number.' has been saved', null);
		}
	}

	public function chat_removepoints($fromLogin)
	{
		if (!is_dir("./backup")) {
            if (!mkdir("./backup", 0777)) {
                $this->connection->chatSendServerMessage("Error while creating backup folder", $fromLogin);
                return;
            }
        }
		$csv_file = "./backup/" . $this->save_csv;

		if (!file_exists($csv_file)) return;
		if (($handle_read = fopen($csv_file,'r')) !== false && ($handle_write = fopen($csv_file.'.temp','w')) !== false) {
			$header = fgetcsv($handle_read, 1000, ";");
			$key = count($header)-3;
			if ($key < 2) return;
			unset($header[$key]);
			fputcsv($handle_write, $header, ";");
			while (($data = fgetcsv($handle_read, 1000, ";")) !== false){
				$new_data = array_slice($data, 0, -2);
				unset($new_data[$key]);
				$this->calculate_total_and_best_points($new_data, $key-2);
				fputcsv($handle_write, $new_data, ";");
			}
			fclose($handle_read);
			fclose($handle_write);
			if (rename($csv_file.'.temp',$csv_file) !== false) {
				$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zPoints of #'.($key-1).' has been removed', null);
			}
		}
	}

	public function chat_endurowu($fromLogin, $params)
	{
		if (!isset($params[0])) {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i Usage: //endurowu <seconds>', $fromLogin);
			return;
		}
		if (!is_numeric($params[0])) {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i Value must be an integer', $fromLogin);
			return;
		}
		if ($params[0] < 0) {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i Value must be positive', $fromLogin);
			return;
		}

		$this->connection->setModeScriptVariables(array('WarmupTime' => $params[0]*1000));

		$var = MetaData::getInstance()->getVariable('wu');
		$var->setRawValue($params[0]);
		\ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

		$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zEnduro WU set to '. $params[0] . " seconds", null);
	}

	public function chat_endurowustart($fromLogin, $params)
	{
		if (!isset($params[0])) {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i Usage: //endurowustart <seconds>', $fromLogin);
			return;
		}
		if (!is_numeric($params[0])) {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i Value must be an integer', $fromLogin);
			return;
		}
		if ($params[0] < 0) {
			$this->eXpChatSendServerMessage('$z$s$FF0> [$F00ERROR$FF0] $f00$i Value must be positive', $fromLogin);
			return;
		}

		$this->connection->setModeScriptVariables(array('WarmupTimeNewMap' => $params[0]*1000));

		$var = MetaData::getInstance()->getVariable('wustart');
		$var->setRawValue($params[0]);
		\ManiaLivePlugins\eXpansion\Core\ConfigManager::getInstance()->check();

		$this->eXpChatSendServerMessage('$z$s$FF0>> [$F00INFO$FF0] $zEnduro WU (new map) set to '. $params[0] . " seconds", null);
	}

	public function handleSpecialChars ($string)
	{
		$string = preg_replace('/\${1}(L|H|P)\[.*?\](.*?)\$(L|H|P)/i', '$2', $string);
		$string = preg_replace('/\${1}(L|H|P)\[.*?\](.*?)/i', '$2', $string);
		$string = preg_replace('/\${1}(L|H|P)(.*?)/i', '$2', $string);
		$string = preg_replace('/\${1}[SHWILON]/i', '', $string);
		$string = str_replace(array("\n\n", "\r", "\n"), array(' ', '', ''), $string);
		return $string;
	}

	public function calculate_total_and_best_points(&$data, $size)
	{
		$points_array = array_slice($data, 2);
		$total_points = array_sum($points_array);
		$data[] = $total_points;
		if ($size < 9) {
			$best_points = $total_points;
		} elseif ($size == 9) {
			$best_points = $total_points-min($points_array);
		} else {
			$min = min($points_array);
			$keys = array_keys($points_array, $min);
			if (count($keys) != 1) {
				$worst_two = $min*2;
			} else {
				unset($points_array[$keys[0]]);
				$worst_two = $min+min($points_array);
			}
			$best_points = $total_points-$worst_two;
		}
		$data[] = $best_points;
	}

	public function eXpOnUnload()
    {
		self::$enduro = false;
        EnduroPanel::EraseAll();
		EnduroPanel2::EraseAll();
		EnduroScores::EraseAll();
    }
}
