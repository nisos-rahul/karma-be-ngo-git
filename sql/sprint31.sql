ALTER TABLE organisation
ADD ngo_url_suffix varchar(255) DEFAULT NULL;
/*
run api from karma-be-development to enter url suffix for exiting ngo
http://{{server}}:{{port}}/karma-development/insert/urlsuffix
*/


