-- TODO, make this into a migration-based system so i never have to use `find . -type f -name "*.sql"` ever again!!!
-- has permissions as far as i can tell.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `log_content` text,
  `log_context` varchar(512) DEFAULT 'admin',
  `logged_in_user` int(11) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ban_reports`
--

CREATE TABLE `ban_reports` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL DEFAULT '0',
  `length` int(11) DEFAULT '0',
  `message` text NOT NULL,
  `teamspeak` tinyint(4) NOT NULL DEFAULT '0',
  `ingame` tinyint(4) NOT NULL DEFAULT '0',
  `website` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `permenant` tinyint(4) NOT NULL DEFAULT '0',
  `manual_expired` tinyint(4) NOT NULL DEFAULT '0',
  `player` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `case_logs`
--

CREATE TABLE `case_logs` (
  `id` int(11) NOT NULL,
  `lead_staff` text,
  `other_staff` text,
  `type_of_report` varchar(512) NOT NULL DEFAULT 'Other',
  `description_of_events` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `case_players`
--

CREATE TABLE `case_players` (
  `id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `type` varchar(512) DEFAULT NULL,
  `name` varchar(512) DEFAULT NULL,
  `guid` varchar(512) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `errorlog`
--

CREATE TABLE `errorlog` (
  `id` int(11) NOT NULL,
  `errorinfopdo` text NOT NULL,
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `guides`
--

CREATE TABLE `guides` (
  `id` int(11) NOT NULL,
  `title` varchar(512) NOT NULL,
  `body` text NOT NULL,
  `author` varchar(256) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `effective` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `login_tokens`
--

CREATE TABLE `login_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `user_id` varchar(512) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `slt` tinyint(1) NOT NULL DEFAULT '0',
  `pd` tinyint(1) NOT NULL DEFAULT '0',
  `ems` tinyint(1) NOT NULL DEFAULT '0',
  `staff` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_comments`
--

CREATE TABLE `meeting_comments` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `author` varchar(128) NOT NULL,
  `pointID` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_points`
--

CREATE TABLE `meeting_points` (
  `id` int(11) NOT NULL,
  `name` varchar(512) NOT NULL,
  `description` text NOT NULL,
  `votes_up` varchar(128) DEFAULT NULL,
  `votes_down` varchar(128) DEFAULT NULL,
  `comments` text NOT NULL,
  `author` varchar(256) NOT NULL,
  `meetingID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `title` text NOT NULL,
  `creator_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `punishment_reports`
--

CREATE TABLE `punishment_reports` (
  `id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT '0',
  `points` int(11) DEFAULT '0',
  `rules` text NOT NULL,
  `comments` text NOT NULL,
  `player` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rank_groups`
--

CREATE TABLE `rank_groups` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `permissions` varchar(4096) NOT NULL DEFAULT '[]',
  `position` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dumping data for table gamepanel.rank_groups: ~4 rows (approximately)
INSERT INTO `rank_groups` (`id`, `name`, `permissions`, `position`) VALUES
	(6, 'BASIC', '["VIEW_GENERAL","VIEW_CASE","VIEW_SEARCH"]', 20),
	(100, 'SMT', '["VIEW_GENERAL","VIEW_SLT","SPECIAL_DEVELOPER"]', 40),
	(500, 'GOD', '["*"]', 10);

-- --------------------------------------------------------

--
-- Table structure for table `rollcall`
--

CREATE TABLE `rollcall` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `rank` text NOT NULL,
  `team` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `staffMessages`
--

CREATE TABLE `staffMessages` (
  `id` int(11) NOT NULL,
  `user` int(11) DEFAULT NULL,
  `content` text,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `staff_interviews`
--

CREATE TABLE `staff_interviews` (
  `id` int(11) NOT NULL,
  `previous_experience` text,
  `ever_banned_reason` text,
  `how_much_time` text,
  `time_away_from_server` text,
  `work_flexibly` text,
  `passed` tinyint(4) DEFAULT '0',
  `processed` tinyint(4) DEFAULT '0',
  `applicant_name` varchar(50) DEFAULT NULL,
  `applicant_region` varchar(50) DEFAULT NULL,
  `interviewer_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `suggestions`
--

CREATE TABLE `suggestions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `suggestion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(200) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `rank_groups` varchar(4096) NOT NULL DEFAULT '[]',
  `rank_lvl` tinyint(4) NOT NULL DEFAULT 0,
  `rank` varchar(200) NOT NULL DEFAULT 'Guest',
  `staff_team` int(11) DEFAULT NULL,
  `isServerOwner` tinyint(1) NOT NULL DEFAULT 0,
  `isStaff` tinyint(1) DEFAULT 0,
  `isCommand` tinyint(4) NOT NULL DEFAULT 0,
  `isPD` tinyint(4) NOT NULL DEFAULT 0,
  `isEMS` tinyint(4) NOT NULL DEFAULT 0,
  `SLT` tinyint(1) DEFAULT NULL,
  `Developer` tinyint(1) DEFAULT NULL,
  `timezone` text DEFAULT NULL,
  `steamid` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `strikes` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `essentialNotification` text DEFAULT NULL,
  `readEssentialNotification` tinyint(4) DEFAULT 0,
  `region` varchar(50) DEFAULT NULL,
  `loa` text DEFAULT NULL,
  `suspended` tinyint(4) DEFAULT 0,
  `lastPromotion` date DEFAULT NULL,
  `createdAt` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table gamepanel.users: ~3 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password`, `rank_groups`, `rank_lvl`, `rank`, `staff_team`, `isServerOwner`, `isStaff`, `isCommand`, `isPD`, `isEMS`, `SLT`, `Developer`, `timezone`, `steamid`, `active`, `strikes`, `notes`, `essentialNotification`, `readEssentialNotification`, `region`, `loa`, `suspended`, `lastPromotion`, `createdAt`) VALUES
	(1, 'gamepanel', 'master', 'gamepanelmaster', 'gamepanel@arma-life.com', '$2y$10$YMbh4o4nF.g5OdMnlBA/fObzHEJUF1FVQID0Hj35d7dcqRgwWz6wK', '[500]', 100, 'GOD', 1, 1, 1, 1, 1, 1, 1, 1, NULL, '76561199242507277', 1, 0, NULL, NULL, 0, 'EU', NULL, 0, NULL, '2023-01-23 07:10:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ban_reports`
--
ALTER TABLE `ban_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`);

--
-- Indexes for table `case_logs`
--
ALTER TABLE `case_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `case_players`
--
ALTER TABLE `case_players`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `name` (`name`);

--
-- Indexes for table `errorlog`
--
ALTER TABLE `errorlog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guides`
--
ALTER TABLE `guides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_tokens`
--
ALTER TABLE `login_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meeting_comments`
--
ALTER TABLE `meeting_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meeting_points`
--
ALTER TABLE `meeting_points`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `punishment_reports`
--
ALTER TABLE `punishment_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`);

--
-- Indexes for table `rank_groups`
--
ALTER TABLE `rank_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rollcall`
--
ALTER TABLE `rollcall`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staffMessages`
--
ALTER TABLE `staffMessages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_interviews`
--
ALTER TABLE `staff_interviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suggestions`
--
ALTER TABLE `suggestions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ban_reports`
--
ALTER TABLE `ban_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `case_logs`
--
ALTER TABLE `case_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `case_players`
--
ALTER TABLE `case_players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `errorlog`
--
ALTER TABLE `errorlog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guides`
--
ALTER TABLE `guides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_tokens`
--
ALTER TABLE `login_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meeting_comments`
--
ALTER TABLE `meeting_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meeting_points`
--
ALTER TABLE `meeting_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `punishment_reports`
--
ALTER TABLE `punishment_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rank_groups`
--
ALTER TABLE `rank_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rollcall`
--
ALTER TABLE `rollcall`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staffMessages`
--
ALTER TABLE `staffMessages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_interviews`
--
ALTER TABLE `staff_interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suggestions`
--
ALTER TABLE `suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
