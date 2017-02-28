<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rest extends CI_Controller 
{
    public function __construct()
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method, X-Auth-Token, x-access-token, x-auth-token, apporigin, X-XSRF-TOKEN");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE,PATCH, HEAD");
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-type: application/json');
        $method = $_SERVER['REQUEST_METHOD'];
        if($method == "OPTIONS") {
            die();
        }
        parent::__construct();
    }
}
/* End of file rest.php */
/* Location: ./application/controllers/rest.php */  