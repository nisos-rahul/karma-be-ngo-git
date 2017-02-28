update donor
set image_url = replace(image_url, 'd1z4uau6lqab2z.cloudfront.net', 'd1v6ini006zxwz.cloudfront.net') ;

update organisation set image_url = replace(image_url, 'https://d1z4uau6lqab2z.cloudfront.net/', 'https://d1v6ini006zxwz.cloudfront.net/') , favicon_url = replace(favicon_url, 'https://d1z4uau6lqab2z.cloudfront.net/', 'https://d1v6ini006zxwz.cloudfront.net/');

update project set image_url = replace(image_url, 'https://d1z4uau6lqab2z.cloudfront.net/', 'https://d1v6ini006zxwz.cloudfront.net/');

update project_report_image set url = replace(url, 'https://d1z4uau6lqab2z.cloudfront.net/', 'https://d1v6ini006zxwz.cloudfront.net/');

update project_report_video set url = replace(url, 'https://d1z4uau6lqab2z.cloudfront.net/', 'https://d1v6ini006zxwz.cloudfront.net/') , thumb_url = replace(thumb_url, 'https://d1z4uau6lqab2z.cloudfront.net/', 'https://d1v6ini006zxwz.cloudfront.net/');

update profile set image_url = replace(image_url, 'https://d1z4uau6lqab2z.cloudfront.net/', 'https://d1v6ini006zxwz.cloudfront.net/');

update ngo_template set
json = replace(json, "https://d1z4uau6lqab2z.cloudfront.net/","https://d1v6ini006zxwz.cloudfront.net/");

update categories
set image_url = replace(image_url, "https://karmaworldtest.s3.amazonaws.com/","https://d1v6ini006zxwz.cloudfront.net");
