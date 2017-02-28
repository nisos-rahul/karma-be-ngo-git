<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Category extends Rest 
{
    public function __construct()
    {
        parent::__construct();              
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
        if($role_id==1 || $role_id==4 || $role_id==5 || $role_id==7)
        {
            if($role_id==4 || $role_id==5)
            {
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
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }   
        $this->load->model('Category_model');
    }
    public function category_list()
    {
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $page = ($this->input->get('page'))?$this->input->get('page'):'';
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):'';
        if(empty($page) || empty($limit))
            $offset='';
        else
            $offset=($page-1)*$limit;   
        
        $list = $this->Category_model->category_list($query,$offset,$limit);
        $data['error'] = false;  
        $data['resp'] = array();
        $count = $this->Category_model->category_count($query);
        $data['resp']['count'] = $count->num;
        if(!empty($list))
        {
            $i =0;
            foreach($list as $category_details)
            {
                $data['resp']['category'][$i]['id'] = (float)$category_details->id;
                $data['resp']['category'][$i]['description'] = $category_details->description;
                $data['resp']['category'][$i]['imageUrl'] = $category_details->image_url;

                $data['resp']['category'][$i]['category'] = $category_details->category;

                $data['resp']['category'][$i]['subCategory'] = $category_details->subcategory;
                $data['resp']['category'][$i]['lastUpdated'] = $category_details->last_updated;
                $data['resp']['category'][$i]['deletedAt'] = $category_details->deleted_at;
                $data['resp']['category'][$i]['dateCreated'] = $category_details->date_created;
                $i++;
            }
        }       
        $data = json_encode($data,JSON_NUMERIC_CHECK);
        echo $data;
        return; 
    }
    //fetch category details
    public function get_category($id=0)
    {
        $category_details = $this->Category_model->category_info($id);
        if(!empty($category_details))
        {
            $data['error'] = false;
            $data['resp']['id'] = (float)$category_details->id;
            $data['resp']['description'] = $category_details->description;
            $data['resp']['imageUrl'] = $category_details->image_url;

            $data['resp']['category'] = $category_details->category;

            $data['resp']['subCategory'] = $category_details->subcategory;
            $data['resp']['lastUpdated'] = $category_details->last_updated;
            $data['resp']['deletedAt'] = $category_details->deleted_at;
            $data['resp']['dateCreated'] = $category_details->date_created;
            
        }
        else{
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find category using id mentioned.";
            header('HTTP/1.1 404 Not Found');
        }
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//get_category
}//Category
/* End of file category.php */
/* Location: ./application/controllers/category.php */  