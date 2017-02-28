ALTER TABLE categories
ADD url varchar(255) DEFAULT NULL;

ALTER TABLE country
ADD flag_url varchar(255) DEFAULT NULL;
/* url- https://{{host}}/karma-be-ngo/country/flag/insert */


/* 
To delete deleted goals media
url- https://{{host}}/karma-be-ngo/delete/goal/media
*/
