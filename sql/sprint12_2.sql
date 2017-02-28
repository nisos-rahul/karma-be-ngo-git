ALTER TABLE `project`
ADD `start_date` varchar(255) DEFAULT NULL,
ADD `end_date` varchar(255) DEFAULT NULL,
ADD `status_name` varchar(255) DEFAULT NULL;

ALTER TABLE `organisation`
ADD `donation_status` int(10) unsigned DEFAULT NULL,
ADD `donation_url` varchar(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `donor` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `organisation_id` int(11) DEFAULT NULL,
 `image_url` varchar(255) DEFAULT NULL,
 `donor_url` varchar(255) DEFAULT NULL,
 `date_created` datetime DEFAULT NULL,
 `deleted_at` datetime DEFAULT NULL,
 `last_updated` datetime DEFAULT NULL,
 `is_active` int(11),
 `is_deleted` int(11),
 `first_name` varchar(255) DEFAULT NULL,
 `last_name` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `donor_projects` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `donor_id` int(11) DEFAULT NULL,
 `project_id` int(11) DEFAULT NULL,
 `is_donated` int(11) DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
