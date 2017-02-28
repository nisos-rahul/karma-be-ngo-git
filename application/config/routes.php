<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = "";
$route['404_override'] = '';
$route['login'] = 'login/check_credential';
$route['logout'] = 'logout/clear_auth'; 
$route['ngo'] = 'organisation/create_ngo_profile';
$route['ngotype'] = 'organisation/types';
$route['ngo/(:num)'] = 'ngo/update_ngo_profile/$1';
$route['ngo/loggedInUser'] = 'ngo/get_ngo_details';
$route['ngo/(:num)/video/(:num)'] = 'ngo/delete_video/$1/$2';
$route['ngo/(:num)/video'] = 'ngo/add_video/$1';
$route['user/(:num)'] = 'user/profile/$1';
$route['user/(:num)/status'] = 'user/delete_profile/$1';
$route['user/loggedInUser'] = 'user/current_user';
$route['file/signedurl'] = 'user/signedurl';
$route['country/(:num)'] = 'country/get_country/$1';
$route['state/(:num)'] = 'country/get_state/$1';
$route['city/(:num)'] = 'country/get_city/$1';
$route['category'] = 'category/category_list';
$route['category/(:num)'] = 'category/get_category/$1';
$route['ngo/(:num)/users'] = 'ngoteam/ngo_user/$1';
$route['ngo/(:num)/inviteuser'] = 'ngoteam/invite_user/$1';
$route['country'] = 'country/list_all';
$route['project'] = 'project/add_list';
$route['project/outcome/(:num)'] = 'project/delete_outcome/$1';
$route['project/(:num)'] = 'project/update_details/$1';
$route['project/(:num)/status'] = 'project/update_status/$1';
$route['invitation'] = 'company/invitation_list';
$route['company'] = 'company/company_list';
$route['project/(:num)/invitecompany'] = 'company/invite_company/$1';
$route['invitation/accept'] = 'invitation/accept_invitation';
$route['invitation/reject'] = 'invitation/reject_invitation';
$route['group'] = 'group/list_groups';
$route['group/(:num)/notification'] = 'group/send_notification/$1';
$route['outcomes/(:num)'] = 'activity/list_outcomes/$1';
$route['outcome/(:num)'] = 'activity/outcome_details/$1';
$route['activity'] = 'activity/add_list';
$route['activity/(:num)'] = 'activity/details_update/$1';
$route['activity/(:num)/status'] = 'activity/update_status/$1';
$route['activity/(:num)/video'] = 'activity/add_video/$1';
$route['activity/(:num)/image'] = 'activity/add_image/$1';
$route['activity/(:num)/video/(:num)'] = 'activity/delete_video/$1/$2';
$route['activity/(:num)/image/(:num)'] = 'activity/delete_image/$1/$2';
$route['dashboard/locations'] = 'dashboard/target_location';
$route['dashboard/projects/completed'] = 'dashboard/completed_projects';
$route['dashboard/projects/active'] = 'dashboard/active_projects';
$route['dashboard/category'] = 'dashboard/category_list';
$route['dashboard/project/fund'] = 'dashboard/project_fund_details';
$route['dashboard/project/activity'] = 'dashboard/project_activity';
$route['hashtag/default'] = 'ngoDefaultHashtags/get_default_hashtags';
$route['project/(:num)/hashtag/(:num)'] = 'project/delete_proj_hashtag/$1/$2';
$route['ngo/website'] = 'ngo/ngo_by_website';
$route['project/(:num)/media'] = 'project/project_media/$1';
$route['project/(:num)/updates'] = 'project/project_latest_updates/$1';
$route['ngo/(:num)/tweets'] = 'tweets/ngo_tweets/$1';

$route['support/(:num)/ngo'] = 'support/get_ngos/$1';

$route['audits'] = 'audit/get_audits';
$route['store/audit'] = 'audit/store_audit';
$route['media/archive/(:num)'] = 'amazon/move_archive_ngo_media/$1';
$route['media/unarchive/(:num)'] = 'amazon/unarchive_ngo_media/$1';
$route['media/delete/(:num)'] = 'amazon/delete_ngo_media/$1';
// $route['check/bucket'] = 'amazon/check_bucket';
// $route['put/object'] = 'amazon/put_object';
$route['get/csrf'] = 'login/get_csrf_token';

$route['project/status'] = 'project/get_status';
$route['donor'] = 'donor/add_list';
$route['donor/(:num)'] = 'donor/update_details/$1';
$route['donor/(:num)/status'] = 'donor/delete_donor/$1';
$route['donor/project'] = 'donor/get_active_project';

$route['corporate/donor'] = 'corporateDonate/add_corporate_donate_details';
$route['corporate/donor/(:num)'] = 'corporateDonate/corporate_deatails/$1';
$route['corporate/donor/excel'] = 'corporateDonate/export_excel';

$route['facebook'] = 'socialMediaSharing/facebook';
$route['twitter'] = 'socialMediaSharing/twitter';
$route['unlink/(:any)'] = 'socialMediaSharing/unlink/$1';
$route['social/status'] = 'socialMediaSharing/check_superadmin_status';
$route['post/facebook'] = 'socialMediaSharing/post_activity_on_facebook';
$route['post/twitter'] = 'socialMediaSharing/post_activity_on_twitter';
$route['facebook/pages'] = 'socialMediaSharing/get_user_pages';
$route['facebook/page/set'] = 'socialMediaSharing/set_post_on_page';

$route['ngo/(:num)/terms'] = 'ngo/is_accepted_terms_and_conditions/$1';
$route['ngo/(:num)/terms/accept'] = 'ngo/accept_terms_and_conditions/$1';

$route['template/add'] = 'designPage/save_template';
$route['template/publish'] = 'designPage/publish_template';
$route['ngo/(:num)/template/(:any)'] = 'designPage/template_data/$1/$2';
$route['template'] = 'designPage/template_list';
$route['template/publish/audit'] = 'audit/publish_design_page';

$route['media/upload'] = 'media/upload_media';
$route['media/upload/optimize'] = 'media/upload_optimized_media';
$route['ngo/(:num)/media'] = 'ngo/ngo_media/$1';
$route['country/flag/insert'] = 'country/insert_country_flag';
$route['delete/goal/media'] = 'project/delete_goals_media';
$route['activity/(:num)/media/(:num)/(:any)'] = 'activity/get_activity_media/$1/$2/$3';
$route['twitter/screenname/(:any)'] = 'socialMediaSharing/get_screen_name/$1';

// $route['kraken'] = 'krakenApi/upload_media';

$route['ngo/donation'] = 'firstGiving/get_ngo_donation_status';
$route['ngo/donation/update'] = 'firstGiving/update_ngo_donation_status';

$route['firstgiving/transaction'] = 'firstGiving/make_transaction';
$route['firstgiving/transacations'] = 'firstGiving/list_transactions';
$route['firstgiving/transaction/(:any)'] = 'firstGiving/get_transaction/$1';
$route['firstgiving/application'] = 'firstGiving/add_list';
$route['firstgiving/application/(:num)'] = 'firstGiving/application_details/$1';
$route['firstgiving/application/update/(:num)'] = 'firstGiving/update_application/$1';
$route['firstgiving/application/status/(:num)'] = 'firstGiving/change_application_status/$1';
$route['firstgiving/log'] = 'firstGiving/store_transaction_log';
$route['firstgiving/delete'] = 'firstGiving/delete_selected_firstgiving_entity';
$route['firstgiving/transaction/refund'] = 'firstGiving/refund_transaction';
$route['firstgiving/refund/ngo'] = 'firstGiving/get_ngo_list_for_refund';
$route['transaction/list/refresh'] = 'firstGiving/refresh_transaction_list';
$route['firstgiving/transaction/refresh'] = 'firstGiving/refresh_transaction';


$route['facebook/expiration'] = 'socialMediaSharing/check_facebook_expiration';

$route['ngo/details/name'] = 'ngo/get_ngo_data_by_branding';
$route['category/details/name'] = 'category/get_category_id_by_name';
$route['country/details/name'] = 'country/get_country_id_by_name';
$route['ngo/list'] = 'ngo/list_all_ngo';
$route['project/list'] = 'project/get_all_projects';
$route['donors/list'] = 'donor/get_all_donors';

$route['route/page'] = 'routing/get_page_type';
$route['ngo/(:num)/delete/routes'] = 'routing/delete_ngo_routes_delete/$1';
$route['route/slug'] = 'routing/create_slug_url';

$route['pillar'] = 'category/add_category';
$route['pillar/(:num)'] = 'category/update_delete_category/$1';
$route['platform/getinvolvelist'] = 'platform/getinvolve_list';

// $route['ngo/(:num)/details'] = 'ngo/get_ngo_data_by_id/$1';

/* End of file routes.php */
/* Location: ./application/config/routes.php */