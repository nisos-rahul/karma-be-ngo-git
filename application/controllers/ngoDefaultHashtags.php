<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class NgoDefaultHashtags extends Rest 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hashtag_model');
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        //if not a super admin then 401
        if(empty($valid_auth_token))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
    }
    
    /* This function is to get the default hashtags for NGO admin */
    function get_default_hashtags()
    {
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $page = ($this->input->get('page'))?$this->input->get('page'):'';
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):'';
        if(empty($page) || empty($limit))
            $offset='';
        else
            $offset=($page-1)*$limit;
            
        $hashtag_list = $this->Hashtag_model->all_default_hashtags($query,$offset,$limit);
        $data['error'] = false;  
        $data['resp'] = array();
        if(!empty($hashtag_list))
        {
            $count = $this->Hashtag_model->hashtag_count($query);
            $data['resp']['count'] = $count->num;
            $data['resp']['hashtags'] = $hashtag_list;
        }
        else
        {
            $data['error'] = false;
            $data['resp']['count'] = array();
            $data['resp']['hashtags'] = array();
            $data['status'] = 200;
        }           
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }
}//end ngo

/* End of file ngo_default_hashtags.php */
/* Location: ./application/controllers/ngo_default_hashtags.php */