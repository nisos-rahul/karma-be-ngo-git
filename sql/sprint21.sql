CREATE TABLE IF NOT EXISTS `corporate_donate_emails` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `email` varchar(255) DEFAULT NULL,
 `first_name` varchar(255) DEFAULT NULL,
 `last_name` varchar(255) DEFAULT NULL,
 `corporate_name` varchar(255) DEFAULT NULL,
 `amount` int(11) DEFAULT NULL,
 `ngo_id` int(11) DEFAULT NULL,
 `willing_to_partner` int(11) DEFAULT NULL,
 `date` varchar(255) DEFAULT NULL,
 `date_created` datetime DEFAULT NULL,
 `last_updated` datetime DEFAULT NULL,
 `deleted_at` datetime DEFAULT NULL,
 `is_submitted` int(11) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `corporate_donate_projects` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `corporate_donar_id` int(11) DEFAULT NULL,
 `project_id` int(11) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'categoryname', 'Pillar', 'outcome');

INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'newProgress', 'New_Progress', 'activity');

ALTER TABLE corporate_donate_emails MODIFY amount bigint(20);
