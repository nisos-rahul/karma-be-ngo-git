ALTER TABLE project MODIFY long_description text;

ALTER TABLE project_report
ADD new_progress int DEFAULT NULL