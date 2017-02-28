ALTER TABLE user_social
ADD facebook_is_post_on_pages int(11) DEFAULT 0,
ADD facebook_page_id varchar(255) DEFAULT NULL,
ADD facebook_page_access_token varchar(255) DEFAULT NULL;