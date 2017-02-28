CREATE TABLE IF NOT EXISTS `user_social` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `email` varchar(255) DEFAULT NULL,
 `user_id` bigint(20) DEFAULT NULL,
 `ngo_id` bigint(20) DEFAULT NULL,
 `displayName` varchar(255) DEFAULT NULL,
 `facebook` varchar(255) DEFAULT NULL,
 `twitter` varchar(255) DEFAULT NULL,
 `created_at` datetime DEFAULT NULL,
 `updated_at` datetime DEFAULT NULL,
 `deleted_at` datetime DEFAULT NULL,
 `fb_profileurl` varchar(255) DEFAULT NULL,
 `fb_extended_access_token` varchar(255) DEFAULT NULL,
 `fb_extended_access_token_expires` varchar(255) DEFAULT NULL,
 `twitter_profileurl` varchar(255) DEFAULT NULL,
 `twitter_access_token` varchar(255) DEFAULT NULL,
 `twitter_access_token_secret` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



INSERT INTO `user_social` (user_id, twitter, created_at, updated_at, twitter_access_token, twitter_access_token_secret)  
SELECT user_id, twitter, created_at, updated_at, twitter_access_token, twitter_access_token_secret
FROM `social_user`
WHERE `user_id` = 1;

ALTER TABLE organisation
ADD is_accepted_terms_and_conditions int(11) DEFAULT 1;

INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'facebookStatus', 'Posted_On_Facebook', 'activity');

INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'twitterStatus', 'Posted_On_Twitter', 'activity');

ALTER TABLE `corporate_donate_projects` CHANGE `corporate_donar_id` `corporate_donor_id` int(11) DEFAULT NULL;

UPDATE organisation SET is_accepted_terms_and_conditions=0;