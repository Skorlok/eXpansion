CREATE TABLE `exp_planet_transaction` (
  `transaction_id` mediumint(9) NOT NULL,
  `transaction_fromLogin` varchar(200) NOT NULL,
  `transaction_toLogin` varchar(200) NOT NULL,
  `transaction_plugin` varchar(200) NOT NULL DEFAULT 'unknown',
  `transaction_subject` varchar(200) NOT NULL DEFAULT 'unknown',
  `transaction_amount` mediumint(4) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `exp_players` (
  `player_login` varchar(50) NOT NULL,
  `player_nickname` varchar(100) NOT NULL,
  `player_nicknameStripped` varchar(100) NOT NULL DEFAULT "",
  `player_updated` int(12) NOT NULL DEFAULT 0,
  `player_wins` mediumint(9) NOT NULL DEFAULT 0,
  `player_timeplayed` int(12) NOT NULL DEFAULT 0,
  `player_onlinerights` varchar(10) NOT NULL DEFAULT "",
  `player_ip` varchar(50) DEFAULT NULL,
  `player_nation` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `exp_ratings` (
  `id` int(11) NOT NULL,
  `uid` varchar(27) NOT NULL,
  `login` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


CREATE TABLE `exp_records` (
  `record_id` mediumint(9) NOT NULL,
  `record_challengeuid` varchar(27) NOT NULL DEFAULT '0',
  `record_playerlogin` varchar(30) NOT NULL DEFAULT '0',
  `record_nbLaps` int(3) NOT NULL,
  `record_score` mediumint(9) DEFAULT 0,
  `record_nbFinish` mediumint(4) DEFAULT 0,
  `record_avgScore` mediumint(9) DEFAULT 0,
  `record_checkpoints` text DEFAULT NULL,
  `record_date` int(12) NOT NULL,
  `score_type` varchar(10) DEFAULT 'time'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;


ALTER TABLE `exp_planet_transaction`
  ADD PRIMARY KEY (`transaction_id`);

ALTER TABLE `exp_players`
  ADD PRIMARY KEY (`player_login`);

ALTER TABLE `exp_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid` (`uid`,`rating`);

ALTER TABLE `exp_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `record_challengeuid` (`record_challengeuid`,`record_playerlogin`,`record_nbLaps`);



ALTER TABLE `exp_planet_transaction`
  MODIFY `transaction_id` mediumint(9) NOT NULL AUTO_INCREMENT;

ALTER TABLE `exp_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `exp_records`
  MODIFY `record_id` mediumint(9) NOT NULL AUTO_INCREMENT;
