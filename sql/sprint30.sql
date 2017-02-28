ALTER TABLE audit_keys MODIFY COLUMN id bigint(20)  auto_increment ;


CREATE TABLE IF NOT EXISTS `media_upload_logs` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `user_id` bigint(20) DEFAULT NULL,
 `url` varchar(255) DEFAULT NULL,
 `datetime` datetime DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `donation_applications` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 `ein_no` varchar(255) NOT NULL,
 `doc_url` varchar(255) NOT NULL,
 `user_id` int(11) DEFAULT NULL,
 `ngo_id` int(11) DEFAULT NULL,
 `created_at` datetime DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE donation_applications
ADD status int(11) DEFAULT NULL,
ADD updated_at datetime DEFAULT NULL;

ALTER TABLE `organisation`
ADD `first_giving_uuid_no` varchar(255) DEFAULT NULL,
ADD `ein_no` varchar(255) DEFAULT NULL,
ADD `first_giving_registered_name` varchar(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `first_giving_application_status` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `status_name` varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `first_giving_application_status` (`status_name`) VALUES('Initial Request');
INSERT INTO `first_giving_application_status` (`status_name`) VALUES('Request Submitted');
INSERT INTO `first_giving_application_status` (`status_name`) VALUES('Approved');

ALTER TABLE `organisation`
ADD use_karma_donation int(11) DEFAULT NULL;

INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'donationStatus', 'Show Donation Link', 'donation setup');
INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'donationUrl', 'Donation Url', 'donation setup');
INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'useKarmaDonation', 'Use Karma For Donations', 'donation setup');
INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'firstGivingRegisteredName', 'Selected Organisation', 'donation setup');

INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'name', 'Organisation Name', 'donation application');
INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'einNo', 'EIN No.', 'donation application');
INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'docUrl', 'Documrnt Url', 'donation application');

ALTER TABLE `donation_applications`
ADD `doc_name` varchar(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `sendgrid_template_id` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `to` varchar(255) NOT NULL,
 `event` varchar(255) NOT NULL,
 `template_id` varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) 
VALUES( 'Superadmin', 'donations activation success', '0991a202-5cd1-4d2e-bd19-56bfb23e0d90');
INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) 
VALUES( 'Superadmin', 'donations app submitted', 'dcb4a958-3381-496a-8edb-71bebab62043');
INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) 
VALUES( 'Npo admin', 'donations app submitted', '353a64ef-9132-4456-98a6-b38b7d8371f0');
INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) 
VALUES( 'Npo admin', 'donations app approved', '7dad8f94-a4bf-413a-9141-8d34a88f6245');

-- INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) 
-- VALUES( 'Superadmin', 'donations activation success', '9d918817-ef8c-4fb2-8249-f393f28bf485');
-- INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) 
-- VALUES( 'Superadmin', 'donations app submitted', '1ddce718-2248-4521-abfe-4a9be48d4871');
-- INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) 
-- VALUES( 'Npo admin', 'donations app submitted', 'b4044d72-fd7c-49b4-9ae8-c8277e850a44');
-- INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) 
-- VALUES( 'Npo admin', 'donations app approved', '8c346db4-8c85-4554-ae35-953c354bf4a0');

ALTER TABLE `email_settings`
ADD `donations_activation_success` bit(1) DEFAULT 1,
ADD `donations_app_submitted_for_superadmin` bit(1) DEFAULT 1,
ADD `donations_app_submitted_for_npo_admin` bit(1) DEFAULT 1,
ADD `donations_app_approved` bit(1) DEFAULT 1;

CREATE TABLE IF NOT EXISTS `karma_donation_notification_mail_logs` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `event` varchar(255) NOT NULL,
 `to_address` varchar(255) NOT NULL,
 `from_address` varchar(255) NOT NULL,
 `template_id` varchar(255) NOT NULL,
 `subject` varchar(255) NOT NULL,
 `html_content` text NOT NULL,
 `plain_content` text NOT NULL,
 `datetime` datetime DEFAULT NULL,
 `is_email_successfully_sent` varchar(255) NOT NULL,
 `errors` varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

update organisation set use_karma_donation = 0 where length(donation_url) > 0 and donation_status = 1

CREATE TABLE IF NOT EXISTS `first_giving_transaction_logs` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `datetime` datetime NOT NULL,
 `ngo_id` int(11) NOT NULL,
 `_fg_popup_transaction_id` varchar(255) NOT NULL,
 `_fg_popup_date` varchar(255) NOT NULL,
 `_fg_popup_organization_name` varchar(255) NOT NULL,
 `_fg_popup_organization_id` varchar(255) NOT NULL,
 `_fg_popup_attribution` varchar(255) NOT NULL,
 `_fg_popup_amount` varchar(255) NOT NULL,
 `_fg_popup_donor_name` varchar(255) NOT NULL,
 `_fgp_email` varchar(255) NOT NULL,
 `_fgp_address` varchar(255) NOT NULL,
 `_fgp_city` varchar(255) NOT NULL,
 `_fgp_state` varchar(255) NOT NULL,
 `_fgp_zip` varchar(255) NOT NULL,
 `_fgp_country` varchar(255) NOT NULL,
 `_fg_popup_donationId` varchar(255) NOT NULL,
 `_fg_popup_campaignName` varchar(255) NOT NULL,
 `_fg_popup_pledgeId` varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `first_giving_transaction_logs`
ADD `_fg_popup_ccType` varchar(255) DEFAULT NULL;

ALTER TABLE first_giving_transaction_logs MODIFY _fg_popup_donationId varchar(255) DEFAULT NULL;
ALTER TABLE first_giving_transaction_logs MODIFY _fg_popup_campaignName varchar(255) DEFAULT NULL;
ALTER TABLE first_giving_transaction_logs MODIFY _fg_popup_pledgeId varchar(255) DEFAULT NULL;

RENAME TABLE `first_giving_transaction_logs` TO `donor_transactions`;

ALTER TABLE `donor_transactions`
ADD `currency_code` varchar(255) DEFAULT NULL;

ALTER TABLE `donor_transactions` ADD payment_gateway varchar(255) NOT NULL AFTER ngo_id;

ALTER TABLE `donor_transactions`
DROP `datetime`;

ALTER TABLE donor_transactions
CHANGE _fg_popup_transaction_id transaction_id varchar(255),
CHANGE _fg_popup_date transaction_datetime varchar(255),
CHANGE _fg_popup_organization_name payment_gateway_organization_name varchar(255) DEFAULT NULL,
CHANGE _fg_popup_organization_id payment_gateway_organization_id varchar(255) DEFAULT NULL,
CHANGE _fg_popup_attribution payment_gateway_attribution varchar(255) DEFAULT NULL,
CHANGE _fg_popup_amount amount varchar(255) DEFAULT NULL,
CHANGE _fg_popup_donor_name donor_name varchar(255) DEFAULT NULL,
CHANGE _fgp_email donor_email varchar(255) DEFAULT NULL,
CHANGE _fgp_address donor_address varchar(255) DEFAULT NULL,
CHANGE _fgp_city donor_city varchar(255) DEFAULT NULL,
CHANGE _fgp_state donor_state varchar(255) DEFAULT NULL,
CHANGE _fgp_zip donor_zip varchar(255) DEFAULT NULL,
CHANGE _fgp_country donor_country varchar(255) DEFAULT NULL,
CHANGE _fg_popup_donationId payment_gateway_donation_id varchar(255) DEFAULT NULL,
CHANGE _fg_popup_campaignName payment_gateway_campaign_name varchar(255) DEFAULT NULL,
CHANGE _fg_popup_pledgeId payment_gateway_pledge_id varchar(255) DEFAULT NULL,
CHANGE _fg_popup_ccType credit_card_type varchar(255) DEFAULT NULL;


ALTER TABLE donor_transactions
MODIFY transaction_id varchar(255) NOT NULL,
MODIFY transaction_datetime varchar(255) NOT NULL,
MODIFY amount varchar(255) NOT NULL,
MODIFY currency_code varchar(255) NOT NULL;

ALTER TABLE donation_applications
ADD is_active tinyint(4) DEFAULT 1;


ALTER TABLE `user_social`
ADD `manual_unlink` tinyint(4) DEFAULT 0;

ALTER TABLE `donor_transactions`
ADD `refund_status` tinyint(4) DEFAULT 0;


ALTER TABLE `donor_transactions`
ADD `last_refund_error` text DEFAULT NULL;

ALTER TABLE `donor_transactions`
ADD `status` varchar(255) DEFAULT 'Awaiting Authorization';

/*
add following line to crontab - 
5 0 1,15 * *  php /home/devuser/projects/karma-be-ngo/index.php cronjob nightlyreport
*/