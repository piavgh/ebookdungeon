CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(45) NOT NULL,
  `email` varchar(45) NOT NULL,
  `password` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `admin` VALUES (1,'admin','piavghoang@gmail.com','7c4a8d09ca3762af61e59520943dc26494f8941b');

CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `verification_hash` varchar(255) DEFAULT NULL,
  `reset_password_hash` varchar(255),
  `email` varchar(254) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `expiration` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `extra_time` int(11) NOT NULL,
  `status` enum('inactive','active','pending') DEFAULT 'pending',
  `maximum` bigint default 52428800,
  `used` bigint default 0,
  `available` bigint default 52428800,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=latin1;

INSERT INTO `users` VALUES (1,'guest', '7c4a8d09ca3762af61e59520943dc26494f8941b','85471dab3195db4c4c00','','guest@gmail.com','Guest','Guest','01259118112','2014-04-01 09:52:06',0,'active',52428800,0,52428800);

CREATE TABLE `contents` (
  `content_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned NOT NULL,
  `file_type` varchar(40) NOT NULL,
  `path` varchar(255) NOT NULL,
  `information` varchar(255) NOT NULL,
  `content_name` varchar(40) NOT NULL,
  `content_size` int(11) NOT NULL,
  `content_extension` varchar(5) DEFAULT NULL,
  `status` enum('private','shared','public') DEFAULT 'private',
  `created` int(11) DEFAULT '0',
  `uploaded` datetime NOT NULL,
  PRIMARY KEY (`content_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `contents_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=413 DEFAULT CHARSET=latin1;

CREATE TABLE `groups` (
  `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned NOT NULL,
  `group_name` varchar(255) NOT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_name` (`group_name`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

CREATE TABLE `group_member` (
  `group_id` int(10) unsigned NOT NULL,
  `member_id` int(10) unsigned NOT NULL DEFAULT '0',
  `member_status` enum('rejected','active','pending') DEFAULT 'pending',
  `action_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`group_id`,`member_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `group_member_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`),
  CONSTRAINT `group_member_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `shared_contents` (
  `content_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `content_status` enum('private','shared') DEFAULT 'private',
  PRIMARY KEY (`content_id`,`group_id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `shared_contents_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`content_id`),
  CONSTRAINT `shared_contents_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `conversions` (
  `convert_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL,
  `convert_path` varchar(255) DEFAULT NULL,
  `convert_mode` enum('schedule','instant') DEFAULT NULL,
  `convert_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `convert_status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`convert_id`),
  KEY `content_id` (`content_id`),
  CONSTRAINT `conversion_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `contents` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;