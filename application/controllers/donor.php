<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Donor extends Rest 
{
    public function __construct()
    {
        parent::__construct();      
        if($this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $ngo_id = login_ngo_details($auth_token);
            if(empty($ngo_id))
            {
                $data['error'] = true;
                $data['status'] = 401;
                $data['message'] = "Unauthorized User.";
                header('HTTP/1.1 401 Unauthorized User');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
        }
        $this->load->model('Donor_model');
        $this->load->model('Project_model');
        $this->load->model('Audit_model');
    }

    public function update_details($id)
    {
        if($this->input->server('REQUEST_METHOD')=="GET")
        {
            $data = $this->donor_details($id);
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        else
        {
            $method = $this->input->get('method');

            if($method=="PUT")
            {
                $data = $this->update_donor($id);
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }
            else
            {
                header('HTTP/1.1 404 Not Found');
                return;
            }   
        }
    }

    public function add_list()
    {
        if($this->input->server('REQUEST_METHOD')=="GET")
        {
            $data = $this->list_donors();
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        else
        {
            $data = $this->add();
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
    }

    protected function add()
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
            exit;
        }

        $role_id = $valid_auth_token->role_id;
        $user_id = $valid_auth_token->user_id;

        if($role_id==5)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        else
            $ngo_id = login_ngo_details($auth_token);

        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $insert['organisation_id'] = $ngo_id;
        $insert['date_created'] = $insert['last_updated'] = date('Y-m-d H:i:s');
        $status = isset($jsonArray['status'])?$jsonArray['status']:'';
        if($status==true)
            $insert['is_active'] = 1;
        else
            $insert['is_active'] = 0;
        $insert['is_deleted'] = 0;
        $insert['name'] = isset($jsonArray['name'])?$jsonArray['name']:'';
        $insert['image_url'] = isset($jsonArray['imageUrl'])?$jsonArray['imageUrl']:'';
        $insert['donor_url'] = isset($jsonArray['donorUrl'])?$jsonArray['donorUrl']:'';
        
        //xss clean
        $insert = $this->security->xss_clean($insert);
        $donor_id = $this->Donor_model->add_donor($insert);

        //audit add_donor
        $audit_info['user_id'] = $user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'donor';
        $audit_info['entity_id'] = $donor_id;
        $audit_info['action'] = 'created';
        $audit_id = $this->Audit_model->create_audit($jsonArray, $audit_info);
        //audit add_donor
        return $this->donor_details($donor_id);
    }

    protected function donor_details($id)
    {
        $donor_info = $this->Donor_model->donor_details($id);
        if(empty($donor_info))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find donor using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            return $data;
        }
        else
        {
            $donor_data = array();
            $donor_data['id'] = $donor_id = $donor_info->id;
            $donor_data['name'] = $donor_info->name;
            $donor_data['imageUrl'] = $donor_info->image_url;
            $donor_data['donorUrl'] = $donor_info->donor_url;
            $donor_data['organisationId'] = $donor_info->organisation_id;
            $donor_data['organisationName'] = $donor_info->organisation_name;
            $status = $donor_info->is_active;
            if($status==1)
                $donor_data['status'] = true;
            else
                $donor_data['status'] = false;
            $is_deleted = $donor_info->is_deleted;
            if($is_deleted==1)
                $donor_data['isDeleted'] = true;
            else
                $donor_data['isDeleted'] = false;
            
            $projects = $this->Donor_model->get_projects_by_donor_id($donor_id);
            $projects_data = array();
            $k = 0;
            if(!empty($projects))
            {
                foreach ($projects as $key => $project) 
                {
                    $projects_data[$k]['id'] = $project->id;
                    $projects_data[$k]['title'] = $project->title;
                    $k++;
                }
            }

            $donor_data['project'] = $projects_data;
            $data['error'] = false;
            $data['resp'] = $donor_data;
            return $data;
        }
    }

    protected function list_donors()
    {
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;           
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $status = ($this->input->get('status'))?$this->input->get('status'):'';
        
        if(!empty($status))
        {           
            if($status!='true' && $status!='false')
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Invalid Status.";
                header('HTTP/1.1 400 Validation Error');
                return $data;
            }               
        }
        if($this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            if($this->input->get('ngoId'))
            {
                $ngo_id = $this->input->get('ngoId');
            }
        }
        else
            $ngo_id = $this->input->get('ngoId');
        if(empty($ngo_id))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "ngo id not given.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        
        $donor_list = $this->Donor_model->donor_list($ngo_id, $query, $status, $offset, $limit);
        $data['error'] = false;
        $data['resp']['count']  = $this->Donor_model->donor_count($ngo_id, $query, $status);
        $donor_final_data = array();
        $p = 0;
        foreach($donor_list as $donor)
        {
            $id = $donor->id;
            $donor_info = $this->Donor_model->donor_details($id);
            if(!empty($donor_info))
            {
                $donor_data = array();
                $donor_data['id'] = $donor_id =$donor_info->id;
                $donor_data['name'] = $donor_info->name;
                $donor_data['imageUrl'] = $donor_info->image_url;
                $donor_data['donorUrl'] = $donor_info->donor_url;
                $donor_data['organisationId'] = $donor_info->organisation_id;
                $donor_data['organisationName'] = $donor_info->organisation_name;

                $status = $donor_info->is_active;
                if($status==1)
                    $donor_data['status'] = true;
                else
                    $donor_data['status'] = false;
                $is_deleted = $donor_info->is_deleted;
                if($is_deleted==1)
                    $donor_data['isDeleted'] = true;
                else
                    $donor_data['isDeleted'] = false;

                $projects = $this->Donor_model->get_projects_by_donor_id($donor_id);
                $projects_data = array();
                $k = 0;
                if(!empty($projects))
                {
                    foreach ($projects as $key => $project) 
                    {
                        $projects_data[$k]['id'] = $project->id;
                        $projects_data[$k]['title'] = $project->title;
                        $k++;
                    }
                }
                $donor_data['project'] = $projects_data;

                $donor_final_data[$p] = $donor_data;
                $p++;
            }
        }
        $data['resp']['donor'] = $donor_final_data;
        return $data;
    }

    protected function update_donor($id)
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
            exit;
        }

        $role_id = $valid_auth_token->role_id;
        $user_id = $valid_auth_token->user_id;

        if($role_id==5)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        else
            $ngo_id = login_ngo_details($auth_token);

        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $insert['organisation_id'] = $ngo_id;
        $insert['last_updated'] = date('Y-m-d H:i:s');
        $status = isset($jsonArray['status'])?$jsonArray['status']:'';
        if($status==true)
            $insert['is_active'] = 1;
        else
            $insert['is_active'] = 0;
        $insert['is_deleted'] = 0;
        $insert['name'] = isset($jsonArray['name'])?$jsonArray['name']:'';
        $insert['image_url'] = isset($jsonArray['imageUrl'])?$jsonArray['imageUrl']:'';
        $insert['donor_url'] = isset($jsonArray['donorUrl'])?$jsonArray['donorUrl']:'';
        $projects = isset($jsonArray['project'])?$jsonArray['project']:'';
        foreach ($projects as $key => $project) {
            $projects[$key]['title'] = (string)$project['title'];
        }
        //audit update_donor
        $audit_info['user_id'] = $user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'donor';
        $audit_info['entity_id'] = $id;
        $audit_info['action'] = 'updated';
        $old_data = $this->donor_details($id);
        if($old_data['error']==true)
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find donor using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $audit_id = $this->Audit_model->update_audit($old_data['resp'], $jsonArray, $audit_info);
        //audit update_donor

        //xss clean
        $insert = $this->security->xss_clean($insert);
        $projects = $this->security->xss_clean($projects);

        //update project
        $this->Donor_model->update_donor($insert, $id);

        $this->Donor_model->delete_donor_projects($id);
        foreach($projects as $project_data)
        {
            $insert_project = array();
            $insert_project['donor_id'] = $id;
            $insert_project['project_id'] = $project_data['id'];
            $this->Donor_model->add_donor_projects($insert_project);
        }

        if($audit_id!='false')          
            $this->Audit_model->activate_audit($audit_id);

        $data = $this->donor_details($id);
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function delete_donor($id)
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
            exit;
        }

        $role_id = $valid_auth_token->role_id;
        $user_id = $valid_auth_token->user_id;

        if($role_id==5)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        else
            $ngo_id = login_ngo_details($auth_token);
        
        $donor_data = $this->Donor_model->donor_details($id);
        if(empty($donor_data))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find donor using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $is_deleted = isset($jsonArray['isDeleted'])?$jsonArray['isDeleted']:'';
        if($is_deleted==true)
        {
            $insert['is_deleted'] = 1;
            $insert['deleted_at'] = date('Y-m-d H:i:s');
        }
        else
            $insert['is_deleted'] = 0;

        //audit delete_donor
        $old_data = $this->donor_details($id);
        //update donor
        $this->Donor_model->update_donor($insert, $id);
        $audit_info['user_id'] = $user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'donor';
        $audit_info['entity_id'] = $id;
        $audit_info['action'] = 'deleted';
        $audit_id = $this->Audit_model->delete_audit($old_data['resp'], $audit_info);
        //audit delete_donor

        if(isset($audit_id))                
                $this->Audit_model->activate_audit($audit_id);

        $data['error'] = false;
        $data['message'] = "Successfully deleted.";
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function get_active_project()
    {
        $ngo_id = $this->input->get('ngoId');
        $list = $this->Project_model->project_list($ngo_id, '', 'true', 0, 5000);
        if(empty($list))
        {
            $data['error'] = false;
            $data['resp']['project'] = array();
            echo json_encode($data);
            return;
        }
        $p=0;
        $project_final_data = array();
        if(!empty($list))
        {
            foreach($list as $project)
            {
                $project_data['id'] = (int)$project->id;
                $project_data['title'] = $project->title;
                $project_final_data[$p] = $project_data;
                $p++;
            }
        }
        $data['error'] = false;
        $data['resp']['project'] = $project_final_data;
        echo json_encode($data);
        return;
    }

    public function get_all_donors()
    {

        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;           
        $search = ($this->input->get('query'))?$this->input->get('query'):'';

        $donors_list = $this->Donor_model->all_donors_list($search, $offset, $limit);
        $donors_count = $this->Donor_model->all_donors_count($search);
        $donor_final_data = array();
        $p = 0;
        if(!empty($donors_list))
        {
            foreach ($donors_list as $donor) {
                $id = $donor->id;
                $donor_info = $this->Donor_model->donor_details($id);
                if(!empty($donor_info))
                {
                    $donor_data = array();
                    $donor_data['id'] = $donor_id =$donor_info->id;
                    $donor_data['name'] = $donor_info->name;
                    $donor_data['imageUrl'] = $donor_info->image_url;
                    $donor_data['donorUrl'] = $donor_info->donor_url;
                    $donor_data['organisationId'] = $donor_info->organisation_id;
                    $donor_data['organisationName'] = $donor_info->organisation_name;

                    // $status = $donor_info->is_active;
                    // if($status==1)
                    //     $donor_data['status'] = true;
                    // else
                    //     $donor_data['status'] = false;
                    $is_deleted = $donor_info->is_deleted;
                    if($is_deleted==1)
                        $donor_data['isDeleted'] = true;
                    else
                        $donor_data['isDeleted'] = false;

                    $projects = $this->Donor_model->get_projects_by_donor_id($donor_id);
                    $projects_data = array();
                    $k = 0;
                    if(!empty($projects))
                    {
                        foreach ($projects as $key => $project) 
                        {
                            $projects_data[$k]['id'] = $project->id;
                            $projects_data[$k]['title'] = $project->title;
                            $k++;
                        }
                    }
                    $donor_data['project'] = $projects_data;

                    $donor_final_data[$p] = $donor_data;
                    $p++;
                }
            }
        }

        $data['error'] = false;
        $data['resp']['count'] = (int)$donors_count;
        $data['resp']['donor'] = $donor_final_data;
        echo json_encode($data);
        return;
    }
}