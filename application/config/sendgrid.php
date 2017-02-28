<?php defined('BASEPATH') OR exit('No direct script access allowed');

include 'environment.php';
$path="/home/devuser/projects/karma-configuration/{$_SERVER['ENV']}.backend.json";
$string = file_get_contents($path);
$envjson = json_decode($string, true);



$config['sendgrid_api_key'] = $envjson['sendgridApiKey'];


// $config['sendgrid_template_id'] = $envjson['sendgridTemplateId'];


