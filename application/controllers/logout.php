<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Logout extends Rest 
{
    public function __construct()
    {
        parent::__construct();  
        $this->load->model('Audit_model');
    }// __construct
    public function clear_auth()
    {
        //call logout api of grails
        $auth_token = trim($this->input->server('HTTP_X_AUTH_TOKEN'));
        $url = $this->config->item('admin_url')."v1/logout"; 
        
        if(empty($auth_token))
        {
            header('HTTP/1.1 401 Unauthorized User');
            return;
        }
        try{
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            $csrf_token = $this->Verification_model->get_csrf_token();

            $ch = curl_init();
            $ch = curl_init(); 
            $ret = curl_setopt($ch, CURLOPT_URL, $url);                                                                     
            $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                                                                                       
            $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            $header = array();
            $header[] = 'Content-Type: application/json';
            $header[] = 'X-Auth-Token: '.$auth_token;
            $header[] = 'X-XSRF-TOKEN: '.$csrf_token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
            $ret = curl_exec($ch);
            $info = curl_getinfo($ch);
            $http_code = $info['http_code'];
            //header('HTTP/1.1 404 Not Found');
            //audit logout
            if(!empty($valid_auth_token))
            {
                $ngo_id = $this->input->get('ngoId');
                $this->Audit_model->logout($valid_auth_token, $ngo_id);
            }
            //audit logout
            echo $ret;
            return;
        }//end try
        catch(Exception $e)
        {
            header("Internal Server Error", true, 500);
            return;
        }//end catch    
    }//logout
}//Logout
/* End of file logout.php */
/* Location: ./application/controllers/logout.php */    