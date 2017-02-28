<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Category extends Rest 
{
    public function __construct()
    {
        parent::__construct();              
        if($this->input->server('HTTP_X_AUTH_TOKEN'))
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
        }
        $this->load->model('Category_model');
        $this->load->model('Routing_model');
        $this->load->helper('url_routing');
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
        
        $list = $this->Category_model->category_list($query, $offset, $limit);
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
                $data['resp']['category'][$i]['category'] = $category_details->category;
                if($this->input->server('HTTP_X_AUTH_TOKEN'))
                {
                    $data['resp']['category'][$i]['description'] = $category_details->description;
                    $data['resp']['category'][$i]['imageUrl'] = $category_details->image_url;
                    $data['resp']['category'][$i]['subCategory'] = $category_details->subcategory;
                    $data['resp']['category'][$i]['lastUpdated'] = $category_details->last_updated;
                    $data['resp']['category'][$i]['deletedAt'] = $category_details->deleted_at;
                    $data['resp']['category'][$i]['dateCreated'] = $category_details->date_created;
                }
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
        if(!$this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
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

    public function get_category_id_by_name()
    {
        $cat_name = $this->input->get('name');
        $cat_name = str_replace('-', ' ', $cat_name);
        if($cat_name===false)
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        $cat_details = $this->Category_model->get_category_info(array('category'=>$cat_name));
        if(empty($cat_details))
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }

        $data['error'] = false;
        $data['resp']['id'] = (float)$cat_details->id;
        $data['resp']['category'] = $cat_details->category;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function get_category_info($id)
    {
        $category_details = $this->Category_model->get_category_info(array('id'=>$id));
        if(empty($category_details))
            return;
        $data['error'] = false;
        $data['resp']['id'] = $category_details->id;
        $data['resp']['description'] = $category_details->description;
        $data['resp']['imageUrl'] = $category_details->image_url;
        $data['resp']['name'] = $category_details->category;
        $data['resp']['subCategory'] = $category_details->subcategory;
        $data['resp']['lastUpdated'] = $category_details->last_updated;
        $data['resp']['deletedAt'] = $category_details->deleted_at;
        $data['resp']['dateCreated'] = $category_details->date_created;
        return $data;
    }

    public function add_category()
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
        
        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $insert['category'] = $pillar = isset($jsonArray['name'])?$jsonArray['name']:'';
        $insert['description'] = isset($jsonArray['description'])?$jsonArray['description']:'';
        $insert['subcategory'] = $sub_category = isset($jsonArray['subCategory'])?$jsonArray['subCategory']:'';
        if($pillar=='')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Pillar name required.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($sub_category=='')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Pillar subCategory required.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $pillar_check = $this->Category_model->get_category_info(array('category'=>$pillar));
        if(!empty($pillar_check))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Pillar already exists.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        //routes
        $category_routing_urls = array();
        $category_name_slug = str_replace(' ', '-', $pillar);
        $category_name_slug1 = 'non-profits/'.rawurlencode($category_name_slug); 
        array_push($category_routing_urls, $category_name_slug1);

        $category_name_slug2 = 'projects/'.rawurlencode($category_name_slug); 
        array_push($category_routing_urls, $category_name_slug2);

        $category_name_slug3 = 'donors/corporates/'.rawurlencode($category_name_slug); 
        array_push($category_routing_urls, $category_name_slug3);

        $error = 0;
        foreach($category_routing_urls  as $url)
        {
            $url_slug_data = $this->Routing_model->get_url_slug_data($url);
            if(!empty($url_slug_data))
                $error++;
        }
        if($error!=0)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Url routing unique constraint failed, Please change pillar name.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $insert['date_created'] = date('Y-m-d H:i:s');
        $insert['is_deleted'] = false;
        $insert['last_updated'] = date('Y-m-d H:i:s');
        //routes

        $pillar_id = $this->Category_model->add_pillar($insert);

        //routes
        $main_array = array();
        $array = array();
        $array['page_id'] = 14;
        $array['entity_name'] = 'category_name';
        $array['entity_id'] = $pillar_id;
        $array['url_slug'] = $category_name_slug1;
        array_push($main_array, $array);

        $array = array();
        $array['page_id'] = 15;
        $array['entity_name'] = 'category_name';
        $array['entity_id'] = $pillar_id;
        $array['url_slug'] = $category_name_slug2;
        array_push($main_array, $array);

        $array = array();
        $array['page_id'] = 16;
        $array['entity_name'] = 'category_name';
        $array['entity_id'] = $pillar_id;
        $array['url_slug'] = $category_name_slug3;
        array_push($main_array, $array);

        foreach ($main_array as $value) {
            $this->Routing_model->insert_url($value);
        }
        //routes

        $data = $this->get_category_info($pillar_id);
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function update_category($id)
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

        $pillar_data = $this->Category_model->get_category_info(array('id'=>$id, 'is_deleted'=>false));
        if(empty($pillar_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Pillar not found.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $update['category'] = $pillar = isset($jsonArray['name'])?$jsonArray['name']:'';
        $update['description'] = isset($jsonArray['description'])?$jsonArray['description']:'';
        $update['subcategory'] = $sub_category = isset($jsonArray['subCategory'])?$jsonArray['subCategory']:'';
        $update['last_updated'] = date('Y-m-d H:i:s');
        if($pillar=='')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Pillar name required.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($sub_category=='')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Pillar subCategory required.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $pillar_check = $this->Category_model->get_category_info(array('category'=>$pillar));
        if(!empty($pillar_check))
        {
            if($pillar_check->id!=$id)
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Pillar already exists.";
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
        }

        // routes
        if($pillar!=$pillar_data->category)
        {
            $category_routing_urls = array();
            $category_name_slug = str_replace(' ', '-', $pillar); 
            $category_name_slug1 = 'non-profits/'.rawurlencode($category_name_slug); 
            array_push($category_routing_urls, $category_name_slug1);

            $category_name_slug2 = 'projects/'.rawurlencode($category_name_slug); 
            array_push($category_routing_urls, $category_name_slug2);

            $category_name_slug3 = 'donors/corporates/'.rawurlencode($category_name_slug); 
            array_push($category_routing_urls, $category_name_slug3);

            $error = 0;
            foreach($category_routing_urls  as $url)
            {
                $url_slug_data = $this->Routing_model->get_url_slug_data($url);
                if(!empty($url_slug_data))
                {
                    if($url_slug_data->entity_id!=$id)
                        $error++;
                }
            }
            if($error!=0)
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Url routing unique constraint failed, Please change pillar name.";
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
        }
        // routes

        $this->Category_model->update_category($update, $id);

        //routes
        if($pillar!=$pillar_data->category)
        {
            $main_array = array();
            $array = array();
            $array['page_id'] = 14;
            $array['url_slug'] = $category_name_slug1;
            array_push($main_array, $array);

            $array = array();
            $array['page_id'] = 15;
            $array['url_slug'] = $category_name_slug2;
            array_push($main_array, $array);

            $array = array();
            $array['page_id'] = 16;
            $array['url_slug'] = $category_name_slug3;
            array_push($main_array, $array);

            foreach ($main_array as $value) {
                $update_url['url_slug'] = $value['url_slug'];
                $page_id = $value['page_id'];
                $where = array('page_id'=>$page_id, 'entity_name'=>'category_name', 'entity_id'=>$id);
                $this->Routing_model->update_url($update_url, $where);
            }
        }
        // routes

        $data = $this->get_category_info($id);
        return $data;
    }

    public function delete_category($id)
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

        $pillar_data = $this->Category_model->get_category_info(array('id'=>$id, 'is_deleted'=>false));
        if(empty($pillar_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Pillar not found.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $update['is_deleted'] = true;
        $update['deleted_at'] = date('Y-m-d H:i:s');

        $this->Category_model->update_category($update, $id);

        //routes
        $this->Routing_model->delete_urls(array('entity_name'=>'category_name', 'entity_id'=>$id));
        //routes

        $data['error'] = false;
        $data['resp'] = 'Successfully deleted';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function update_delete_category($id)
    {
        $method = $this->input->get('method');
        if($method=='PUT')
        {
            $data = $this->update_category($id);
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        elseif($method=='DELETE')
        {
            $this->delete_category($id);
        }
        else
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
    }
}//Category
/* End of file category.php */
/* Location: ./application/controllers/category.php */  