<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Support extends Rest 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Support_model');
    }

    public function get_ngos($support_id) {
        
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_ngo_support = $this->Verification_model->valid_ngo_support($auth_token);

        if(empty($valid_ngo_support))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $supportData = support_ngos($support_id);

        $data['error'] = false;
        $data['resp'] = $supportData;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }
}