<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


include 'environment.php';
// $path="/home/devuser/projects/karma-configuration/{$_SERVER['ENV']}.backend.json";
$path="../karma-configuration/{$_SERVER['ENV']}.backend.json";
$string = file_get_contents($path);
$envjson = json_decode($string, true);

$config['protocol']	= 'smtp';
$config['smtp_host'] = $envjson['mailHost'];
$config['smtp_port'] = $envjson['mailPort'];
$config['smtp_user'] =  $envjson['mailUsername'];
$config['smtp_pass'] =  $envjson['mailPassword'];
$config['charset']	= 'utf-8';
$config['mailtype']	= 'html';

$config['project_invitation_mail_subject'] = 'You are invited to join $projectname';
$config['project_invitation_body'] = 'Hello $email, <br/> $ngoname has invited you to join  
$projectname on Karma World Wide! <br/>Please 
	<a href="$link">Login</a> to your account to accept/reject the invitation.';
/* End of file email_setting.php */
/* Location: ./application/config/email_setting.php */
