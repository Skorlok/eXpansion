<?php

set_time_limit(0);
error_reporting(E_ALL ^ E_DEPRECATED);

if (!function_exists('mysql_connect')) {
	function mysql_connect($server,$user,$password="",$newlink=false,$flags=0) {
		global $mysqli;
		$mysqli = new mysqli($server, $user, $password);
		return $mysqli->set_charset('utf8mb4');
	}
}
if (!function_exists('mysql_select_db')) {
	function mysql_select_db($database_name, $link=false) {
		global $mysqli;
		return $mysqli->select_db($database_name);
	}
}
if (!function_exists('mysql_close')) {
	function mysql_close($ident = null){
		global $mysqli;
		return $mysqli->close();
	}
}

if (!function_exists('mysql_error')) {
	function mysql_error($ident = null){
		global $mysqli;
		return $mysqli->error;
	}
}

if (!function_exists('mysql_escape_string')) {
	function mysql_escape_string($string){
		global $mysqli;
		return $mysqli->real_escape_string($string);
	}
}

if (!function_exists('mysql_real_escape_string')) {
	function mysql_real_escape_string($string){
		global $mysqli;
		return $mysqli->real_escape_string($string);
	}
}

if (!function_exists('mysql_fetch_assoc')) {
	function mysql_fetch_assoc($result){
		return $result->fetch_assoc();
	}
}

if (!function_exists('mysql_fetch_object')) {
	function mysql_fetch_object($result){
		return $result->fetch_object();
	}
}
if (!function_exists('mysql_query')) {
	function mysql_query($query, $res=null){
		global $mysqli;
		return $mysqli->query($query);
	}
}
if (!function_exists('mysql_set_charset')) {
	function mysql_set_charset($cname, $res=null){
		global $mysqli;
		return $mysqli->set_charset($cname);
	}
}

// launch process
$Uimporter = new Uimporter();

class Uimporter
{

    private $config;
    private $conn;

    public function __construct()
    {
        $this->welcome();
        $this->readconfig();
        $this->connectdb();
        $this->dothejob();
        $this->disconnect();
        $this->theEnd();
    }

    protected function welcome()
    {
        $this->hr();
        $this->c("from Uaseco to eXpansion", true);
        $this->hr();
    }

    protected function readconfig()
    {
        if (!file_exists("uaseco_migration.ini")) {
            die("Cannot locate main configuration file: uaseco_migration.ini.");
        }
        $this->config = parse_ini_file("uaseco_migration.ini");
        if ($this->config === false) {
            die("# Fatal error reading configuration file. Check .ini syntax");
        }
    }

    protected function connectdb()
    {
        $this->c(" Connecting to database.... hold on...");
        $this->conn = mysql_connect(
            $this->config['host'] . ':' . $this->config['port'],
            $this->config['login'],
            $this->config['password']
        );
        if (!$this->conn) {
            die('Could not connect: ' . mysql_error());
        }
        mysql_set_charset("utf8", $this->conn);
        $this->c(' Connected successfully');
    }

    protected function disconnect()
    {
        mysql_close($this->conn);
        $this->hr();
        $this->c("* All done! * ", true);
    }

    protected function dothejob()
    {
        $this->hr();
        $this->c("* By now you are aware that this is ONE TIME operation * ", true);
        $this->c("* Once completed, do not run again! * ", true);
        $this->c("* WARNING * ", true);
        $this->c("* Only way to rollback is to start new eXpansion database * ", true);
        $this->c("* Once started, do not interupt the process *", true);
        $this->hr();
        print "Type \"DO IT\" and press enter to continue: ";
        $input = fgets(STDIN);
        if (trim($input) != "DO IT") {
            die();
        }

        $this->hr();
        $this->c("DO NOT INTERRUPT THE PROCESS", true);
        $this->hr();


        $this->query("USE " . $this->config['uaseco_db'] . ";", $this->conn);

        $records = $this->query(
            "Select
p.Login as record_playerlogin, c.Uid as record_challengeuid,
r.Score as record_score, UNIX_TIMESTAMP(r.Date) as record_date,
r.Checkpoints as record_checkpoints,
r.Score as record_avgScore
FROM " . $this->config['uaseco_prefix'] . "maps c, " . $this->config['uaseco_prefix'] . "players p, " . $this->config['uaseco_prefix'] . "records r where r.PlayerId = p.PlayerId and r.MapId = c.MapId;",
            $this->conn
        );
        while ($uaseco_recs[] = mysql_fetch_object($records));
        unset($records);
        $this->query("USE " . $this->config['exp_db'] . ";", $this->conn);


        $mplayers = $this->query("SELECT * FROM exp_players p;", $this->conn);
        while ($exp_players[] = mysql_fetch_assoc($mplayers));

        $total = count($uaseco_recs);
        $x = 1;
        $y = 0;
        $this->c("* Migrating localrecords *", true);
        $buffer = "";
        foreach ($uaseco_recs as $data) {
            if (empty($data)) {
                continue;
            }
            // do query every 50 values
            if ($y % 100 == 0 && $y > 0) {
                $buffer = trim($buffer, ",");
                $this->query(
                    "INSERT INTO exp_records (`record_challengeuid`, `record_playerlogin`, `record_nbLaps`,
 `record_score`, `record_nbFinish`, `record_avgScore`, `record_checkpoints`, `record_date`) VALUES $buffer;",
                    $this->conn
                );
                $buffer = "";
                // for pretty output to user :)
                $percentage = round(($x / $total) * 100, 0);
                $this->c($percentage . "%...");
            }
            $buffer .= "('" . mysql_escape_string($data->record_challengeuid)
                . "','" . mysql_escape_string($data->record_playerlogin) . "','1', '"
                . mysql_escape_string($data->record_score) . "','1','" . mysql_escape_string($data->record_score)
                . "','" . mysql_escape_string($data->record_checkpoints)
                . "','" . mysql_escape_string($data->record_date) . "' ),";

            $x++;
            $y++;
        }
        // if buffer had some values, write them..
        $buffer = trim($buffer, ",");
        $this->query(
            "INSERT INTO exp_records (`record_challengeuid`, `record_playerlogin`, `record_nbLaps`, `record_score`,
 `record_nbFinish`, `record_avgScore`, `record_checkpoints`, `record_date`) VALUES $buffer;",
            $this->conn
        );
        $buffer = "";
        $this->c("Done!");

        unset($uaseco_recs);

        $this->query("USE " . $this->config['uaseco_db'] . ";", $this->conn);


        $xplayers = $this->query(
            "SELECT Login as player_login, Nickname as player_nickname, UNIX_TIMESTAMP(LastVisit) as player_updated,
 Wins as player_wins, TimePlayed as player_timeplayed FROM " . $this->config['uaseco_prefix'] . "players p;",
            $this->conn
        );

        while ($uaseco_players[] = mysql_fetch_object($xplayers));
        unset($xplayer);

        $this->query("USE " . $this->config['exp_db'] . ";", $this->conn);

        $this->c("* Migrating Players *", true);
        $total = count($uaseco_players);
        $x = 1;
        $y = 0;
        $buffer = "";
        // do the players sort
        foreach ($uaseco_players as $data) {
            if (empty($data)) {
                continue;
            }
            if ($y % 100 == 0 && $y > 0) {
                $buffer = trim($buffer, ",");
                $this->query(
                    "INSERT INTO exp_players (`player_login`, `player_nickname`, `player_updated`, `player_wins`,
 `player_timeplayed`) VALUES $buffer;",
                    $this->conn
                );
                $buffer = "";
                // for pretty output to user :)
                $percentage = round(($x / $total) * 100, 0);
                $this->c($percentage . "%...");
            }
            $buffer .= "('" . mysql_real_escape_string($data->player_login, $this->conn)
                . "','" . mysql_real_escape_string($data->player_nickname, $this->conn)
                . "', '" . $data->player_updated . "','" . $data->player_wins . "','"
                . $data->player_timeplayed . "'),";

            $x++;
            $y++;
        } // outer foreach

        $buffer = trim($buffer, ",");
        $this->query(
            "INSERT INTO exp_players (`player_login`, `player_nickname`, `player_updated`, `player_wins`,
 `player_timeplayed`) VALUES $buffer;",
            $this->conn
        );
        $buffer = "";
        $this->c($percentage . "Done.");



        $this->query("USE " . $this->config['uaseco_db'] . ";", $this->conn);

        $xkarma = $this->query(
            "SELECT p.Login as login, Score as rating, c.Uid as uid FROM " . $this->config['uaseco_prefix'] . "maps c, " . $this->config['uaseco_prefix'] . "players p, " . $this->config['uaseco_prefix'] . "ratings r 
where r.PlayerId = p.PlayerId and r.MapId = c.MapId;",
            $this->conn
        );
        while ($uaseco_karma[] = mysql_fetch_object($xkarma));
        unset($xkarma);
        $this->query("USE " . $this->config['exp_db'] . ";", $this->conn);

        $total = count($uaseco_karma);
        $x = 1;
        $y = 0;
        $buffer = "";
        $this->c("* Migrating karma *", true);
        foreach ($uaseco_karma as $data) {
            if (empty($data)) {
                continue;
            }

            if ($y % 100 == 0 && $y > 0) {
                $buffer = trim($buffer, ",");
                $this->query("INSERT INTO exp_ratings (`login`, `uid`, `rating`) VALUES $buffer;", $this->conn);
                $buffer = "";
                // for pretty output to user :)
                $percentage = round(($x / $total) * 100, 0);
                $this->c($percentage . "%...");
            }

            $karma = 3;
            switch ($data->rating) {
                case -3:
                    $karma = 0;
                    break;
                case -2:
                    $karma = 1;
                    break;
                case -1:
                    $karma = 2;
                    break;
                case 1:
                    $karma = 3;
                    break;
                case 2:
                    $karma = 4;
                    break;
                case 3:
                    $karma = 5;
                    break;
            }
            $buffer .= "('" . mysql_escape_string($data->login) . "','"
                . mysql_escape_string($data->uid) . "','" . mysql_escape_string($karma) . "'),";
            $x++;
            $y++;
        }
        $buffer = trim($buffer, ",");
        $this->query("INSERT INTO exp_ratings (`login`, `uid`, `rating`) VALUES $buffer;", $this->conn);
        $buffer = "";
        $this->c("done.");


        ///////////////////////////
        /// DONATIONS MIGRATION ///
        ///////////////////////////
        $this->query("USE " . $this->config['uaseco_db'] . ";", $this->conn);
        $xdons = $this->query(
            "SELECT p.Login AS transaction_fromLogin, Donations AS transaction_amount FROM " . $this->config['uaseco_prefix'] . "players p WHERE Donations > 0;",
            $this->conn
        );
        while ($uaseco_dons[] = mysql_fetch_object($xdons));
        unset($xdons);
        $this->query("USE " . $this->config['exp_db'] . ";", $this->conn);
        $total = count($uaseco_dons);
        $x = 1;
        $y = 0;
        $buffer = "";
        $this->c("* Migrating donations *", true);
        foreach ($uaseco_dons as $data) {
            if (empty($data)) {
                continue;
            }

            if ($y % 100 == 0 && $y > 0) {
                $buffer = trim($buffer, ",");
                $this->query(
                    "INSERT INTO exp_planet_transaction (`transaction_fromLogin`, `transaction_toLogin`, `transaction_plugin`, `transaction_subject`, `transaction_amount`) VALUES $buffer;",
                    $this->con
                );
                $buffer = "";
                // for pretty output to user :)
                $percentage = round(($x / $total) * 100, 0);
                $this->c($percentage . "%...");
            }
            $buffer .= "('" . mysql_escape_string($data->transaction_fromLogin)
                . "','" . mysql_escape_string($this->config['transaction_toLogin'])
                . "','eXpansion\DonatePanel','server_donation','"
                . mysql_escape_string($data->transaction_amount) . "'),";

            $x++;
            $y++;
        }
        $buffer = trim($buffer, ",");
        $this->query(
            "INSERT INTO exp_planet_transaction (`transaction_fromLogin`, `transaction_toLogin`, `transaction_plugin`, 
`transaction_subject`, `transaction_amount`) VALUES $buffer;",
            $this->conn
        );
        $buffer = "";
        $this->c("done.");
    }

    protected function query($query, $link)
    {
        $result = mysql_query($query, $link);
        if (!$result) {
            $message = 'Invalid query: ' . mysql_error() . "\n";
            $message .= 'Query:' . $query;
            die($message);
        }

        return $result;
    }

    protected function theEnd()
    {
        $this->hr();
    }

    protected function hr()
    {
        for ($x = 0; $x < 79; $x++) {
            print "#";
        }
        print "\n";
    }

    protected function c($string, $center = false)
    {
        if ($center) {
            $len = (80 / 2) - (strlen($string) / 2);
            for ($x = 0; $x < $len; $x++) {
                print " ";
            }
            print $string . "\n";
        } else {
            print $string . "\n";
        }
    }
}
