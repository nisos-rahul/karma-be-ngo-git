
#Command to be run on the server
cd /home/devuser/projects/karma-be-destination/
mkdir sitemap
sudo chown -R www-data:www-data sitemap

CREATE TABLE IF NOT EXISTS `url_routing_page_type` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `page_name` varchar(255) NOT NULL,
 `page_type` varchar(255) NOT NULL,
 `priority` float(5) DEFAULT 0.5,
 `changefreq` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(1, 'non-profits', 'static', 0.5, 'weekly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(2, 'projects', 'static', 0.5, 'daily');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(3, 'donors', 'static', 0.5, 'weekly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(4, 'donors/individuals', 'static', 0.5, 'monthly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(5, 'donors/corporates', 'static', 0.5, 'monthly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(6, 'donors/corporates/projects', 'static', 0.5, 'monthly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(7, 'projects/status_name', 'dynamic', 0.5, 'weekly');

INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(8, 'ngo_branding', 'dynamic', 0.5, 'daily');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(9, 'ngo_branding/projects', 'dynamic', 0.5, 'daily');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(10, 'ngo_branding/projects/project_name', 'dynamic', 0.5, 'weekly');

INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(11, 'non-profits/country_name', 'dynamic', 0.5, 'weekly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(12, 'projects/country_name', 'dynamic', 0.5, 'weekly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(13, 'donors/corporates/country_name', 'dynamic', 0.5, 'monthly');

INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(14, 'non-profits/pillar_name', 'dynamic', 0.5, 'weekly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(15, 'projects/pillar_name', 'dynamic', 0.5, 'weekly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(16, 'donors/corporates/pillar_name', 'dynamic', 0.5, 'monthly');

INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(17, 'karmaworld', 'static', 0.5, 'daily');

INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(18, 'articleDetail/article-name', 'dynamic', 0.5, 'weekly');


INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(19, 'karmaworld_others', 'static', 0.5, 'monthly');

INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(20, 'blog', 'static', 0.5, 'weekly');
INSERT INTO `url_routing_page_type` (`id`, `page_name`, `page_type`, `priority`, `changefreq`) VALUES(21, 'blog/blog-name', 'dynamic', 0.5, 'weekly');


CREATE TABLE IF NOT EXISTS `url_routing` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `page_id` int(11) NOT NULL,
 `entity_name` varchar(255) DEFAULT NULL,
 `entity_id` int(11) DEFAULT NULL,
 `second_entity_id` int(11) DEFAULT NULL,
 `url_slug` varchar(255) NOT NULL,
 PRIMARY KEY (`id`),
 FOREIGN KEY (`page_id`) REFERENCES url_routing_page_type(`id`),
 CONSTRAINT slug_unique UNIQUE (url_slug)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 1, 'non-profits', 'non-profits');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 2, 'projects', 'projects');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 3, 'donors', 'donors');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 4, 'donors/individuals', 'donors/individuals');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 5, 'donors/corporates', 'donors/corporates');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 6, 'donors/corporates/projects', 'donors/corporates/projects');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 7, 'Fundraising', 'projects/fundraising');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 7, 'In Progress', 'projects/in-progress');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 7, 'Complete', 'projects/complete');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'Npo Login', 'ngo');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'Superadmin Login', 'admin');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'faq', 'faq');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'About Karma', 'about-karma');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'Team', 'team');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'Privacy Policy', 'privacyPolicy');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'Terms And Conditions', 'termsAndConditions');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'Donation', 'donation');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'Make Grant', 'make-grant');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'Thank You', 'thankyou');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 20, 'Blog List', 'blog');
INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'Sitemap', 'sitemap.xml');
-- INSERT INTO `url_routing` (`page_id`, `entity_name`, `url_slug`) VALUES( 19, 'demo', 'demo');

/**
 * run apis from karma-be-development to store route urls for exiting data
 * 1. http://{{server}}:{{port}}/karma-development/urlrouting/country
 * 2. http://{{server}}:{{port}}/karma-development/urlrouting/pillar 
 * 3. http://{{server}}:{{port}}/karma-development/urlrouting/ngobranding
*/

/**
 * add following line to crontab - 
 10 0 * * *  php /home/devuser/projects/karma-be-destination/index.php Sitemap_cronjob generatexml
*/



INSERT INTO `country` (`version`, `code`, `date_created`, `last_updated`, `name`, `flag_url`) VALUES
(0,	'AQ',	NOW(),	NOW(),	'Antarctica',	'https://dev.karmaworld.co/flags/aq.png');

CREATE TABLE IF NOT EXISTS `ngo_contact_us` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `datetime` datetime DEFAULT NULL,
 `ngo_id` int(11) DEFAULT NULL,
 `email` varchar(255) NOT NULL,
 `first_name` varchar(255) NOT NULL,
 `last_name` varchar(255) NOT NULL,
 `subject` varchar(255) DEFAULT NULL,
 `message` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `newsletter` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `datetime` datetime DEFAULT NULL,
 `email` varchar(255) NOT NULL,
 `first_name` varchar(255) NOT NULL,
 `last_name` varchar(255) NOT NULL,
 `is_interested_in_donors` tinyint(4) DEFAULT 0,
 `is_interested_in_organizations` tinyint(4) DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



ALTER TABLE donor_transactions
ADD is_recurring tinyint(4) DEFAULT 0,
ADD recurring_billing_frequency varchar(255) DEFAULT NULL,
ADD recurring_billing_term varchar(255) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `donor_transactions_error_logs` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `datetime` datetime DEFAULT NULL,
 `payment_gateway` varchar(255) DEFAULT NULL,
 `ngo_id` int(11) DEFAULT NULL,
 `status` varchar(255) DEFAULT NULL,
 `error` text DEFAULT NULL,
 `bill_to_first_name` varchar(255) DEFAULT NULL,
 `bill_to_last_name` varchar(255) DEFAULT NULL,
 `bill_to_address_line` varchar(255) DEFAULT NULL,
 `bill_to_city` varchar(255) DEFAULT NULL,
 `bill_to_state` varchar(255) DEFAULT NULL,
 `bill_to_zip` varchar(255) DEFAULT NULL,
 `bill_to_country` varchar(255) DEFAULT NULL,
 `bill_to_phone` varchar(255) DEFAULT NULL,
 `remote_address` varchar(255) DEFAULT NULL,
 `amount` varchar(255) DEFAULT NULL,
 `currency_code` varchar(255) DEFAULT NULL,
 `commission_rate` varchar(255) DEFAULT NULL,
 `is_recurring` varchar(255) DEFAULT 0,
 `recurring_billing_frequency` varchar(255) DEFAULT NULL,
 `recurring_billing_term` varchar(255) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


RENAME TABLE karma_donation_notification_mail_logs TO mail_logs;



CREATE TABLE IF NOT EXISTS `promotional_contact_list_logs` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `event` varchar(255) DEFAULT NULL,
 `newletter_contacts_id` bigint(20) DEFAULT NULL,
 `datetime` datetime DEFAULT NULL,
 `log` text DEFAULT NULL,
 `errors` text DEFAULT NULL,
 `status` tinyint(4) DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



CREATE TABLE IF NOT EXISTS `drupal_cache` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `last_updated` datetime NOT NULL,
 `url` varchar(255) NOT NULL,
 `response` text DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `drupal_error_log` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `datetime` datetime NOT NULL,
 `drupal_url` varchar(255) NOT NULL,
 `error` text DEFAULT NULL,
 `response_from_cache` text DEFAULT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `getinvolve` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `datetime` datetime DEFAULT NULL,
 `email` varchar(255) NOT NULL,
 `first_name` varchar(255) NOT NULL,
 `last_name` varchar(255) NOT NULL,
`message` VARCHAR(255) NOT NULL,
 `is_interested_in_donors` tinyint(4) DEFAULT 0,
 `is_interested_in_organizations` tinyint(4) DEFAULT 0,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) VALUES( 'Npo Admin', 'Contact us', '27ec6da7-d574-4342-86fa-e5fa5ef4b884');
INSERT INTO `sendgrid_template_id` (`to`, `event`, `template_id`) VALUES( 'Superadmin', 'Getinvolve', '72d7e00f-fe8d-405c-878a-9543da6581e0');