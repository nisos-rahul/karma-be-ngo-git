ALTER TABLE `project_report`
ADD `project_report_type` varchar(255) DEFAULT NULL;

UPDATE project_report
SET `project_report_type`='Progress Update';

ALTER TABLE project_report
DROP FOREIGN KEY project_report_ibfk_1;