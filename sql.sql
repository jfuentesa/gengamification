SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `t_gengamification_alerts` (
  `id_user` int(10) unsigned NOT NULL,
  `id_badge` int(10) unsigned DEFAULT NULL,
  `id_level` int(10) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `t_gengamification_badges` (
  `id_user` int(10) unsigned NOT NULL,
  `id_badge` int(10) unsigned NOT NULL,
  `badgescounter` int(10) unsigned NOT NULL,
  `grantdate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `t_gengamification_events` (
  `id_user` int(10) unsigned NOT NULL,
  `id_event` int(10) unsigned NOT NULL,
  `eventcounter` int(10) unsigned NOT NULL,
  `pointscounter` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `t_gengamification_log` (
  `id_user` int(10) unsigned NOT NULL,
  `id_event` int(10) unsigned DEFAULT NULL,
  `eventdate` datetime NOT NULL,
  `points` int(10) unsigned DEFAULT NULL,
  `id_badge` int(10) unsigned DEFAULT NULL,
  `id_level` int(10) unsigned DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `t_gengamification_scores` (
  `id_user` int(10) unsigned NOT NULL,
  `points` int(10) unsigned NOT NULL,
  `id_level` int(10) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `t_gengamification_alerts`
 ADD KEY `id_user` (`id_user`);

ALTER TABLE `t_gengamification_badges`
 ADD PRIMARY KEY (`id_user`,`id_badge`);

ALTER TABLE `t_gengamification_events`
 ADD PRIMARY KEY (`id_user`,`id_event`);

ALTER TABLE `t_gengamification_log`
 ADD KEY `id_user` (`id_user`);

ALTER TABLE `t_gengamification_scores`
 ADD PRIMARY KEY (`id_user`);
