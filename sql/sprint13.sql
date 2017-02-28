ALTER TABLE `donor`
DROP COLUMN `first_name`,
DROP COLUMN `last_name`,
ADD `name` varchar(255) DEFAULT NULL;