<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Platform extends Rest 
{
    public function __construct()
    {       
        parent::__construct();  
        $this->load->model('Platform_model');
        $this->load->model('Verification_model');
    }

    public function getinvolve_list()
    {
    	$auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
    	$valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }

        $role_id = $valid_auth_token->role_id;
        if($role_id!=1)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }

    	$page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;           
        $query = ($this->input->get('query'))?$this->input->get('query'):'';

        $this->Platform_model->getinvolve_count($query);
        $getinvolve_list = $this->Platform_model->getinvolve_list($query, $offset, $limit);

        $g = 0;
        $involve_list = array();
        foreach ($getinvolve_list as $list) 
        {
    		
            $involve_data = array();
            $involve_data['id'] = $list->id;
            $involve_data['email'] = $list->email;
            $involve_data['firstName'] = $list->first_name;
            $involve_data['lastName'] = $list->last_name;
            $involve_data['message'] = $list->message;
            $involve_data['dateTime'] = $list->datetime;

            $involve_list[$g] = $involve_data;
            $g++;
        }

        
        $data['error'] = false;
        $data['resp']['count']  = $this->Platform_model->getinvolve_count($query);
        $data['resp']['getInvovle'] = $involve_list;
        echo json_encode($data);
        return;

    }

}