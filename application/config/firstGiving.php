<?php defined('BASEPATH') OR exit('No direct script access allowed');

include 'environment.php';
// $path="/home/devuser/projects/karma-configuration/{$_SERVER['ENV']}.backend.json";
$path="../karma-configuration/{$_SERVER['ENV']}.backend.json";
$string = file_get_contents($path);
$envjson = json_decode($string, true);






$config['use_first_giving_production_api'] = $envjson['useFirstGivingProductionAPI'];
//use_first_giving_production_url


$config['first_giving_application_key_production'] = $envjson['firstGivingApplicationKey_Production'];



$config['first_giving_security_token_production'] = $envjson['firstGivingSecurityToken_Production'];



$config['first_giving_api_url_production'] = $envjson['firstGivingAPIUrl_Production'];
//first_giving_production_url


$config['first_giving_api_url_staging'] = $envjson['firstGivingAPIUrl_Staging'];
//first_giving_sandbox_url


$config['first_giving_application_key_staging'] = $envjson['firstGivingApplicationKey_Staging'];
//first_giving_application_key


$config['first_giving_security_token_staging'] = $envjson['firstGivingSecurityToken_Staging'];
//first_giving_security_token






$config['use_first_giving_production_donation_url'] = $envjson['useFirstGivingProductionDonationUrl'];



$config['first_giving_donation_url_production'] = $envjson['firstGivingDonationUrl_Production'];
//first_giving_production_donation_url


$config['first_giving_affiliate_id_production'] = $envjson['firstGivingAffiliateId_Production'];



$config['first_giving_donation_url_staging'] = $envjson['firstGivingDonationUrl_Staging'];
//first_giving_staging_url



$config['first_giving_affiliate_id_staging'] = $envjson['firstGivingAffiliateId_Staging'];
//first_giving_affiliate_id



$config['first_giving_style_sheet_url'] = $envjson['firstGivingStyleSheetUrl'];




$config['sendgrid_superadmin_from_email_address'] = $envjson['replyTo'];



$config['sendgrid_donation_notification_email'] = $envjson['superadminDonationNotificationEmail'];