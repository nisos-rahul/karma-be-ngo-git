<?php defined('BASEPATH') OR exit('No direct script access allowed');

include 'environment.php';
$path="/home/devuser/projects/karma-configuration/{$_SERVER['ENV']}.backend.json";
$string = file_get_contents($path);
$envjson = json_decode($string, true);
/**
 * Register api key: https://code.google.com/apis/console/
 *
 */  
$config['google_api_url'] = 'https://www.googleapis.com/urlshortener/v1/url';
// $config['google_api_key'] = 'AIzaSyCLHZrHDRBWaXX_KFDLDk26o_Y9OJzT0GA';
$config['google_api_key'] = $envjson['googleapikey'];

/* End of file Google_url_api.php */
/* Location: ./application/config/Google_url_api.php */

