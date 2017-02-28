<?php defined('BASEPATH') OR exit('No direct script access allowed');

include 'environment.php';
$path="/home/devuser/projects/karma-configuration/{$_SERVER['ENV']}.backend.json";
$string = file_get_contents($path);
$envjson = json_decode($string, true);

/*
|--------------------------------------------------------------------------
| Access Key
|--------------------------------------------------------------------------
|
| Your Amazon S3 access key.
|
*/

$config['access_key'] = $envjson['access_key'];

/*
|--------------------------------------------------------------------------
| Secret Key
|--------------------------------------------------------------------------
|
| Your Amazon S3 Secret Key.
|
*/

$config['secret_key'] = $envjson['secret_key'];


/*
|--------------------------------------------------------------------------
| Bucket Name
|--------------------------------------------------------------------------
|
| Your Amazon S3 Bucket Name.
|
*/
$config['bucket_name'] =  $envjson['bucket_name'];


$config['aws_s3_region'] =  $envjson['aws_s3_region'];
