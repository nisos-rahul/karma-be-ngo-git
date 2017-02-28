update role set display_authority = "NPO Admin" where display_authority = "Ngo Admin";

update role set display_authority = "NPO Member" where display_authority = "Ngo Member";

update audits set old_data = replace(old_data, '"Budget"', '"Funding"'), new_data = replace(new_data, '"Budget"', '"Funding"') where entity = 'project';

update audits set old_data = replace(old_data, '"Goals"', '"Outcomes"'), new_data = replace(new_data,  '"Goals"', '"Outcomes"') where entity = 'project';

update audits set old_data = replace(old_data, '"Goal_Description"', '"Description"'), new_data = replace(new_data,  '"Goal_Description"', '"Description"') where entity = 'project';

update audits set old_data = replace(old_data, '"Goal_Target"', '"Goal"'), new_data = replace(new_data,  '"Goal_Target"', '"Goal"') where entity = 'project';

update audits set old_data = replace(old_data, '"Goal_Current"', '"Current"'), new_data = replace(new_data,  '"Goal_Current"', '"Current"') where entity = 'project';

update audits set old_data = replace(old_data, '"Goal_Pillar"', '"Pillar"'), new_data = replace(new_data,  '"Goal_Pillar"', '"Pillar"') where entity = 'project';

update audits set old_data = replace(old_data, '"Goal_Title"', '"Title"'), new_data = replace(new_data,  '"Goal_Title"', '"Title"') where entity = 'project';

update audits set old_data = replace(old_data, '"Target"', '"Goal"'), new_data = replace(new_data,  '"Target"', '"Goal"') where entity = 'activity';


update audits set old_data = replace(old_data, '"Ngo_Name"', '"NPO_Name"'), new_data = replace(new_data, '"Ngo_Name"', '"NPO_Name"') where entity = 'NGO profile';

update audits set entity = 'NPO profile' where entity = 'NGO profile';
