<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Routing extends Rest 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Routing_model');
        $this->load->helper('url_routing');
    }

    public function create_slug_url()
    {
        // $string = "- _ . ! ~ * ' ( )";
        $string = "* ' ( )";
        // $slug = url_slug($string);
        $asd = rawurlencode($string);
        var_dump($asd);
        // die();
        $string = urlencode($string);
        var_dump($string);
        // var_dump($slug); 
        die();
    }

    // public function get_page_type()
    // {
    //     $jsonArray = json_decode(file_get_contents('php://input'),true);
    //     $url = $jsonArray['url'];
    //     $url = str_replace('/#!', '', $url); 
    //     $url = str_replace("'", '%27', $url); 
    //     $url = str_replace("!", '%21', $url); 
    //     $url = str_replace("*", '%2A', $url); 
    //     $url = str_replace("(", '%28', $url); 
    //     $url = str_replace(")", '%29', $url); 
    //     $host = parse_url($url, PHP_URL_PATH);
    //     $host = ltrim($host, '/');
    //     $host = rtrim($host, '/');
        
    //     if($host=='')
    //         $page_type = 'base_page';
    //     else
    //     {
    //         $url_slug_data = $this->Routing_model->get_url_slug_data($host);
    //         if(empty($url_slug_data))
    //         {
    //             header('HTTP/1.1 404 Not Found');
    //             exit;
    //         }
    //         $page_type = $url_slug_data->page_type;
    //     }
        
    //     $data['error'] = false;
    //     $data['resp'] = $page_type;
    //     echo json_encode($data,JSON_NUMERIC_CHECK);
    //     return;
    // }

    public function delete_ngo_routes_delete($ngo_id)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id!=1) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $this->Routing_model->delete_urls(array('entity_name'=>'ngo_branding_change', 'entity_id'=>$ngo_id));

        $data['error'] = false;
        $data['resp'] = 'Successfully deleted';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }
}