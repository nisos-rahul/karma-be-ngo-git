ALTER TABLE user_social
ADD twitter_screen_name varchar(255) DEFAULT NULL;

#Command to be run on the server
cd /home/devuser/projects/karma-be-ngo/
mkdir temp_media
sudo chown -R www-data:www-data temp_media

update categories set image_url = url;

ALTER TABLE categories
DROP COLUMN url;

CREATE TABLE IF NOT EXISTS `kraken_logs` (
 `id` bigint(20) NOT NULL AUTO_INCREMENT,
 `ngo_id` int(11) DEFAULT NULL,
 `status` varchar(255) DEFAULT 'Not Done',
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE kraken_logs
ADD details text DEFAULT NULL;