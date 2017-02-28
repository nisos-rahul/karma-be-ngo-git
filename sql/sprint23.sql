ALTER TABLE organisation
ADD programs_net_spend int DEFAULT 0;

INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'programsNetSpend', 'Spend_Programs', 'NPO profile');

DELETE FROM audit_keys
WHERE backend_key = 'websiteUrl' and ui_key = 'Statement_of_intent';

ALTER TABLE project
ADD fundings_to_date int DEFAULT 0;

INSERT INTO `audit_keys` (`backend_key`, `ui_key`, `entity`) VALUES( 'fundingsToDate', 'To_Date_Amount', 'project');
