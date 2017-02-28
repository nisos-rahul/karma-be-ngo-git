<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Dashboard extends Rest 
{
    public function __construct()
    {
        parent::__construct();      
        //check auth token conditional
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
        $this->load->model('Dashboard_model');
        $this->load->model('Project_model');
    }
    public function target_location()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $ngo_id = login_ngo_details($auth_token);   
        $count = $this->Dashboard_model->target_location_count($ngo_id);
        $data['error'] = false;
        $data['resp']['targetLocations'] = $count->num;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//target_location
    public function completed_projects()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $ngo_id = login_ngo_details($auth_token);   
        $count = $this->Dashboard_model->project_completed_count($ngo_id);
        $data['error'] = false;
        $data['resp']['completedProjects'] = $count->num;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }
    public function active_projects()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $ngo_id = login_ngo_details($auth_token);   
        $count = $this->Dashboard_model->project_active_count($ngo_id);
        $data['error'] = false;
        $data['resp']['activeProjects'] = $count->num;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//active_projects
    public function category_list()
    {
        if($this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $ngo_id = login_ngo_details($auth_token);
        }
        else {
            $ngo_id = $this->input->get('ngoId');
            if($ngo_id=="")
            {
                header('HTTP/1.1 404 Not Found');
                exit;
            }
        }   
        $category_list = $this->Dashboard_model->project_category_list($ngo_id);
        $i = 0;
        $categories = array();
        if(!empty($category_list))
        {
            foreach($category_list as $category)
            {
                $categories[$i]['id'] = $catid = $category->id;
                $categories[$i]['description'] = $category->description;
                $categories[$i]['imageUrl'] = $category->image_url;
                $categories[$i]['category'] = $category->category;
                $categories[$i]['subCategory'] = $category->subcategory;
                $categories[$i]['lastUpdated'] = $category->last_updated;
                $categories[$i]['deletedAt'] = $category->deleted_at;
                $categories[$i]['dateCreated'] = $category->date_created;
                $where = array('project.ngo_id'=>$ngo_id,
                    'goals.categories_id'=>$catid,
                    'project.is_active'=>true,
                    'goals.is_deleted'=>false);
                $categories[$i]['projectCount'] = $this->Dashboard_model->project_count($where)->num;
                $i++;
            }
        }
        $data['error']=false;
        $data['resp']['count']=$i;
        $data['resp']['category']=$categories;
        $data['resp']['totalProjectCount']  = $this->Project_model->project_list_count($ngo_id, '', true)->num;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//category_list
    //project wise fund amount
    public function project_fund_details()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $ngo_id = login_ngo_details($auth_token);   
        $project_fund = $this->Dashboard_model->project_fund_list($ngo_id);
        $project_data = array();
        $i=0;
        $data['error'] = false;
        if(!empty($project_fund))
        {
            foreach($project_fund as $fund_details)
            {
                $project_data[$i]['id'] = $fund_details->id;
                $project_data[$i]['name'] = $fund_details->title;
                $project_data[$i]['fund'] = $fund_details->current_amount;
                $i++;
            }//foreach($project_fund as $fund_details)
        }//if(!empty($project_fund))
        $data['resp']['count'] = $i;
        $data['resp']['project'] =  $project_data;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//project_fund_details
    //monthwise activity count of active projects 
    public function project_activity()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $ngo_id = login_ngo_details($auth_token);   
        $year = ($this->input->get('year'))?$this->input->get('year'):'';
        $month = ($this->input->get('month'))?$this->input->get('month'):'';
        if($year=="")
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please select year";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }       
        if($month=="")
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please select month";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $project_activity = $this->Dashboard_model->project_activity_year($ngo_id, $year, $month);
        $project_data = array();
        $i=0;
        $data['error'] = false;
        if(!empty($project_activity))
        {
            foreach($project_activity as $activity_count)
            {               
                $project_data[$i]['projectName'] = $activity_count->title;              
                $project_data[$i]['activityCount'] = $activity_count->num;
                $i++;
            }//foreach($project_activity as $activity_count)
        }//if(!empty($project_activity))
        $data['resp']['count'] = $i;
        $data['resp']['project'] =  $project_data;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }
}//end dashboard
/* End of file dashboard.php */
/* Location: ./application/controllers/dashboard.php */