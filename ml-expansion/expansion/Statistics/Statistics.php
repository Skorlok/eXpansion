<?php

namespace ManiaLivePlugins\eXpansion\Statistics;

use ManiaLivePlugins\eXpansion\Gui\Formaters\Country;
use ManiaLivePlugins\eXpansion\Gui\Formaters\DaysDiff;
use ManiaLivePlugins\eXpansion\Gui\Formaters\LongDate;
use ManiaLivePlugins\eXpansion\Gui\ManiaLink\Window;
use ManiaLivePlugins\eXpansion\Menu\Menu;

class Statistics extends \ManiaLivePlugins\eXpansion\Core\types\ExpPlugin
{

    /** @var Window */
    protected $statsWindow = null;

    public function eXpOnLoad()
    {
        $this->enableDedicatedEvents(\ManiaLive\DedicatedApi\Callback\Event::ON_PLAYER_MANIALINK_PAGE_ANSWER);

        Menu::addMenuItem("Statistics", array("Statistics" => array(null, "exp:eXpansion.Statistics:showTopWinners")));
    }

    public function eXpOnReady()
    {
        $this->registerManialinkCallback('showTopIncome');
        $this->registerManialinkCallback('showTopDonators');
        $this->registerManialinkCallback('showQTopDonators');
        $this->registerManialinkCallback('showTopDonatorsTotal');
        $this->registerManialinkCallback('showTopQDonatorsTotal');
        $this->registerManialinkCallback('showTopWinners');
        $this->registerManialinkCallback('showTopOnline');
        $this->registerManialinkCallback('showTopPlayTime');
        $this->registerManialinkCallback('showTopFinish');
        $this->registerManialinkCallback('showTopTrackPlay');
        $this->registerManialinkCallback('showTopVoter');
        $this->registerManialinkCallback('showTopActive');
        $this->registerManialinkCallback('showTopFinishCountry');
        $this->registerManialinkCallback('showTopOnlineCountry');
        $this->registerManialinkCallback('showTopWinnerCountry');
        $this->registerManialinkCallback('showTopCountry');

        $this->statsWindow = new Window("Statistics\Gui\Windows\StatsWindow.xml");
        $this->statsWindow->setName("Statistics");
        $this->statsWindow->setSize(140, 110);

        $this->setPublicMethod("showTopWinners");
        $this->registerChatCommand("stats", "showTopWinners", 0, true);
        $this->registerChatCommand("wins", "chat_wins", 0, true);
        $this->registerChatCommand("laston", "chat_laston", 0, true);
        $this->registerChatCommand("laston", "chat_laston", 1, true);
    }

    public function chat_wins($login)
    {
        $sql = 'SELECT player_wins FROM exp_players WHERE player_login LIKE "' . $login . '"';
        $wins = $this->db->execute($sql)->fetchArrayOfObject();

        $message = '#player#You have #variable#%1$s#player# wins!';
        $this->eXpChatSendServerMessage($message, $login, array($wins[0]->player_wins));
    }

    public function chat_laston($login, $params = null)
    {
        if ($params == null) {
            $params = $login;
        }

        $sql = 'SELECT player_updated FROM exp_players WHERE player_login LIKE "' . $params . '"';
        $last_update = $this->db->execute($sql)->fetchArrayOfObject();

        if (!isset($last_update[0]->player_updated)) {
            $message = '#admin_error#There are no player with login #variable#%1$s#admin_error# on this server!';
            $this->eXpChatSendServerMessage($message, $login, array($params));
            return;
        }

        $time = date('d/m/Y H:i:s', $last_update[0]->player_updated);
        $nick = $this->db->execute('SELECT player_nickname FROM exp_players WHERE player_login = "' . $params . '";')->fetchArrayOfObject();

        $message = '#player#Player #variable#%s$s#player# was last online on: #variable#%s';
        $this->eXpChatSendServerMessage($message, $login, array($nick[0]->player_nickname, $time));
    }

    public function showTopIncome($login)
    {
        $sql = 'SELECT transaction_plugin as plugin, transaction_subject as subject, '
            .'SUM(transaction_amount) as totalPlanets'
            . ' FROM exp_planet_transaction'
            . ' WHERE transaction_toLogin = ' . $this->db->quote($this->storage->serverLogin)
            . ' GROUP BY transaction_plugin, transaction_subject'
            . ' ORDER BY totalPlanets DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Planet Incomes',
            array('source', 'Amount of Planets'),
            array(null, 'subject', 'totalPlanets'),
            array(),
            $this->getData($sql)
        );
    }

    public function showTopDonatorsTotal($login)
    {
        $sql = 'SELECT transaction_fromLogin as login, player_nickname as nickname, '
            .'SUM(transaction_amount) as totalPlanets'
            . ' FROM exp_planet_transaction, exp_players'
            . ' WHERE transaction_subject = \'server_donation\''
            . ' AND transaction_fromLogin = player_login'
            . ' GROUP BY transaction_fromLogin, player_nickname'
            . ' ORDER BY totalPlanets DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Donators(Amount)',
            array('NickName', 'Amount of Planets'),
            array(null, 'nickname', 'totalPlanets'),
            array(),
            $this->getData($sql)
        );
    }

    public function showTopDonators($login)
    {
        $sql = 'SELECT transaction_fromLogin as login, player_nickname as nickname, '
            .'SUM(transaction_amount) as totalPlanets'
            . ' FROM exp_planet_transaction, exp_players'
            . ' WHERE transaction_toLogin = ' . $this->db->quote($this->storage->serverLogin) . ''
            . ' AND transaction_subject = \'server_donation\''
            . ' AND transaction_fromLogin = player_login'
            . ' GROUP BY transaction_fromLogin, player_nickname'
            . ' ORDER BY totalPlanets DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Server Donators(Amount)',
            array('NickName', 'Amount of Planets'),
            array(null, 'nickname', 'totalPlanets'),
            array(),
            $this->getData($sql)
        );
    }

    public function showTopQDonatorsTotal($login)
    {
        $sql = 'SELECT transaction_fromLogin as login, player_nickname as nickname, count(*) as nb'
            . ' FROM exp_planet_transaction, exp_players'
            . ' WHERE transaction_subject = \'server_donation\''
            . ' AND transaction_fromLogin = player_login'
            . ' GROUP BY transaction_fromLogin, player_nickname'
            . ' ORDER BY nb DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Donators(Amount)',
            array('NickName', 'nbDonation'),
            array(null, 'nickname', 'nb'),
            array(),
            $this->getData($sql)
        );
    }

    public function showQTopDonators($login)
    {
        $sql = 'SELECT transaction_fromLogin as login, player_nickname as nickname, count(*) as nb'
            . ' FROM exp_planet_transaction, exp_players'
            . ' WHERE transaction_toLogin = ' . $this->db->quote($this->storage->serverLogin) . ''
            . ' AND transaction_subject = \'server_donation\''
            . ' AND transaction_fromLogin = player_login'
            . ' GROUP BY transaction_fromLogin, player_nickname'
            . ' ORDER BY nb DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Server Donators(Amount)',
            array('NickName', 'nbDonation'),
            array(null, 'nickname', 'nb'),
            array(),
            $this->getData($sql)
        );
    }

    public function showTopWinners($login)
    {
        $sql = 'SELECT player_login as login, player_nickname as nickname, player_wins as wins'
            . ' FROM exp_players'
            . ' WHERE player_wins > 0'
            . ' ORDER BY wins DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Server Winners',
            array('NickName', 'nb Wins'),
            array(null, 'nickname', 'wins'),
            array(),
            $this->getData($sql)
        );
    }

    public function showTopOnline($login)
    {
        $sql = 'SELECT player_login as login, player_nickname as nickname, player_timeplayed as time'
            . ' FROM exp_players'
            . ' ORDER BY time DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Online Time',
            array('NickName', 'Time Online'),
            array(null, 'nickname', 'time'),
            array(null, null, LongDate::getInstance()),
            $this->getData($sql)
        );
    }

    public function showTopPlayTime($login)
    {
        if (!$this->db->tableExists("exp_records")) {
            return;
        }

        $sql = 'SELECT player_login as login, player_nickname as nickname, '
            .'SUM(record_nbFinish * record_avgScore)/1000 as time'
            . ' FROM exp_records, exp_players'
            . ' WHERE record_playerlogin = player_login'
            . '	AND record_nbFinish > 0'
            . ' GROUP BY player_login, player_nickname'
            . ' ORDER BY time DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Play Time',
            array('NickName', 'Time Online'),
            array(null, 'nickname', 'time'),
            array(null, null, LongDate::getInstance()),
            $this->getData($sql)
        );
    }

    public function showTopTrackPlay($login)
    {
        if (!$this->db->tableExists("exp_records")) {
            return;
        }

        $sql = 'SELECT player_login as login, player_nickname as nickname, count(*) as nb'
            . ' FROM exp_records, exp_players'
            . ' WHERE record_playerlogin = player_login'
            . ' GROUP BY player_login, player_nickname'
            . ' HAVING count(*) > 0'
            . ' ORDER BY nb DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Number tracks played',
            array('NickName', 'nb Maps'),
            array(null, 'nickname', 'nb'),
            array(),
            $this->getData($sql)
        );
    }

    public function showTopVoter($login)
    {
        if (!$this->db->tableExists("exp_ratings")) {
            return;
        }

        $sql = 'SELECT player_login as login, player_nickname as nickname, count(*) as nb'
            . ' FROM exp_ratings, exp_players'
            . ' WHERE login = player_login'
            . ' GROUP BY player_login, player_nickname'
            . ' HAVING count(*) > 0'
            . ' ORDER BY nb DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Karma Voter',
            array('NickName', 'nb Votes'),
            array(null, 'nickname', 'nb'),
            array(),
            $this->getData($sql)
        );
    }

    public function showTopActive($login)
    {
        $sql = "SELECT player_login as login, player_nickname as nickname, DATEDIFF('". date('Y-m-d H:i:s', time() - date('Z')) ."', FROM_UNIXTIME(`player_updated`)) AS `days`"
            . ' FROM exp_players'
            . ' ORDER BY days ASC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Active Players',
            array('NickName', 'Last connection'),
            array(null, 'nickname', 'days'),
            array(null, null, DaysDiff::getInstance()),
            $this->getData($sql)
        );
    }

    public function showTopFinish($login)
    {
        if (!$this->db->tableExists("exp_records")) {
            return;
        }

        $sql = 'SELECT player_login as login, player_nickname as nickname, SUM(record_nbFinish) as nb'
            . ' FROM exp_records, exp_players'
            . ' WHERE record_playerlogin = player_login'
            . ' GROUP BY player_login, player_nickname'
            . ' HAVING SUM(record_nbFinish) > 0'
            . ' ORDER BY nb DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Top Finish',
            array('NickName', 'nb Finish'),
            array(null, 'nickname', 'nb'),
            array(),
            $this->getData($sql)
        );
    }

    public function showTopFinishCountry($login)
    {
        if (!$this->db->tableExists("exp_records")) {
            return;
        }

        $sql = 'SELECT player_nation as nation, SUM(record_nbFinish) as nb'
            . ' FROM exp_records, exp_players'
            . ' WHERE record_playerlogin = player_login'
            . ' GROUP BY player_nation'
            . ' HAVING SUM(record_nbFinish) > 0'
            . ' ORDER BY nb DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Country with top Finish',
            array('Country', 'nb Finish'),
            array(null, 'nation', 'nb'),
            array(null, Country::getInstance(), null),
            $this->groupByCountry($this->getData($sql), 'nb')
        );
    }

    public function showTopOnlineCountry($login)
    {
        $sql = 'SELECT player_nation as nation, SUM(player_timeplayed) as time'
            . ' FROM exp_players'
            . ' GROUP BY player_nation'
            . ' ORDER BY time DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Country with top Online Time',
            array('Country', 'Time Online'),
            array(null, 'nation', 'time'),
            array(null, Country::getInstance(), LongDate::getInstance()),
            $this->groupByCountry($this->getData($sql), 'time')
        );
    }

    public function showTopWinnerCountry($login)
    {
        $sql = 'SELECT player_nation as nation, SUM(player_wins) as nb'
            . ' FROM exp_players'
            . ' GROUP BY player_nation'
            . ' HAVING SUM(player_wins) > 0'
            . ' ORDER BY nb DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Most winning country',
            array('Country', 'nb Players'),
            array(null, 'nation', 'nb'),
            array(null, Country::getInstance(), null),
            $this->groupByCountry($this->getData($sql), 'nb')
        );
    }

    public function showTopCountry($login)
    {
        $sql = 'SELECT player_nation as nation, COUNT(*) as nb'
            . ' FROM exp_players'
            . ' GROUP BY player_nation'
            . ' ORDER BY nb DESC'
            . ' LIMIT 0, 1000';

        $this->showStats(
            $login,
            'Country with most players',
            array('Country', 'nb Players'),
            array(null, 'nation', 'nb'),
            array(null, Country::getInstance(), null),
            $this->groupByCountry($this->getData($sql), 'nb')
        );
    }

    private function showStats($login, $title, $labels, $keys, $formatters, $datas)
    {
        if (!isset($labels[0]) || !isset($labels[1])) {
            return;
        }
        $this->statsWindow->setParam("h0", $this->statsWindow->addLang($labels[0]));
        $this->statsWindow->setParam("h1", $this->statsWindow->addLang($labels[1]));

        $items = array();
        $data  = array();
        $x     = 0;
        foreach ($datas as $row) {
            $cells = array();
            for ($c = 0; $c < 3; $c++) {
                if (!array_key_exists($c, $keys)) {
                    $cells[] = "";
                    continue;
                }
                if ($keys[$c] === null) {
                    $cells[] = $x + 1;
                    continue;
                }
                $value = "";
                if (isset($row[$keys[$c]])) {
                    $value = $row[$keys[$c]];
                    if (isset($formatters[$c]) && $formatters[$c] !== null) {
                        $value = $formatters[$c]->format($value);
                    }
                }
                $cells[] = $value;
            }
            $items[$x] = $cells;
            $data[$x]  = array(-1, -1, -1, -1);
            $x++;
        }

        $this->statsWindow->setTitle($title);
        $this->statsWindow->setParam("statsItems", $items);
        $this->statsWindow->setParam("statsData", $data);
        $this->statsWindow->show($login);
    }

    private function groupByCountry($datas, $sumKey)
    {
        /** @var Country $formatter */
        $formatter = Country::getInstance();

        $newData = array();
        foreach ($datas as $row) {
            if (!isset($row['nation'])) {
                continue;
            }
            $country = $formatter->format($row['nation']);
            if ($country == "") {
                continue;
            }
            if (isset($newData[$country])) {
                $newData[$country][$sumKey] += $row[$sumKey];
            } else {
                $newData[$country] = $row;
            }
        }

        $sums = array();
        foreach ($newData as $country => $row) {
            $sums[$country] = isset($row[$sumKey]) ? $row[$sumKey] : 0;
        }
        arsort($sums);

        $sorted = array();
        foreach ($sums as $country => $sum) {
            $sorted[] = $newData[$country];
        }

        return $sorted;
    }

    public function getData($sql)
    {
        $dbData = $this->db->execute($sql);

        if ($dbData->recordCount() == 0) {
            return array();
        }


        $i = 0;
        $datas = array();
        while ($data = $dbData->fetchArray()) {
            $datas[$i] = $data;
            array_unshift($datas[$i], $i + 1);
            $i++;
        }

        return $datas;
    }

    public function eXpOnUnload()
    {
        if ($this->statsWindow instanceof Window) {
            $this->statsWindow->erase();
        }
        $this->statsWindow = null;
    }
}
