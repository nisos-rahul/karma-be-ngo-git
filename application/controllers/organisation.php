<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Organisation extends Rest 
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
        $this->load->model('Ngo_model');
        $this->load->model('Country_model');
        $this->load->model('Category_model');
        $this->load->model('Hashtag_model');
    }
    public function types()
    {       
        $ngo_type_array = ngo_types();
        $data['error'] = false;
        $data['resp'] = $ngo_type_array;
        $data = json_encode($data,JSON_NUMERIC_CHECK);
        echo $data;
        return;
    }   
    //create ngo rofile
    public function create_ngo_profile()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        //only admin can create organisation
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        $role_id = $valid_auth_token->role_id;
        if($role_id!=1)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }   
        //input data
        //insert into organization
        $jsonArray = json_decode(file_get_contents('php://input'),true);            
        $insert['name'] = $name = isset($jsonArray['name'])?$jsonArray['name']:'';
        $insert['addres_line1'] = isset($jsonArray['address1'])?$jsonArray['address1']:'';
        $insert['addres_line2'] = isset($jsonArray['address2'])?$jsonArray['address2']:'';
        $city = isset($jsonArray['city'])?$jsonArray['city']:'';    
        $state = isset($jsonArray['state'])?$jsonArray['state']:'';
        $insert['zip_code'] = isset($jsonArray['zip'])?$jsonArray['zip']:'';    
        $country = isset($jsonArray['country'])?$jsonArray['country']:'';
        $country_code = isset($jsonArray['countryCode'])?$jsonArray['countryCode']:'';
        $insert['website_url'] = isset($jsonArray['websiteUrl'])?$jsonArray['websiteUrl']:'';
        $insert['branding_url'] = isset($jsonArray['brandingUrl'])?$jsonArray['brandingUrl']:'';
        $insert['description'] = isset($jsonArray['description'])?$jsonArray['description']:''; 
        $video_url = isset($jsonArray['videoUrls'])?$jsonArray['videoUrls']:array();
        $insert['image_url'] = isset($jsonArray['imageUrl'])?$jsonArray['imageUrl']:'';
        $insert['ngo_type'] = isset($jsonArray['ngoType'])?$jsonArray['ngoType']:'';    
        $insert['annual_revenue'] = isset($jsonArray['annualRevenue'])?$jsonArray['annualRevenue']:'';      
        $insert['registration_no'] = $registration_no = isset($jsonArray['registrationNo'])?$jsonArray['registrationNo']:'';
        $insert['contact_us'] = isset($jsonArray['contactUs'])?$jsonArray['contactUs']:'';
        $insert['copyright'] = isset($jsonArray['copyright'])?$jsonArray['copyright']:'';
        $insert['thumb_url'] = isset($jsonArray['thumbUrl'])?$jsonArray['thumbUrl']:'';
        $category = isset($jsonArray['category'])?$jsonArray['category']:array();
        $document_urls = isset($jsonArray['documentUrls'])?$jsonArray['documentUrls']:array();  
        $category_count = count($category);     
        $video_count = count($video_url);   
        $document_count = count($document_urls);
        $insert['zip_code'] = (string)$insert['zip_code'];
        $handlename = isset($jsonArray['handles'])?$jsonArray['handles']:array();
        $decimal_num= strlen(substr(strrchr($insert['annual_revenue'], "."), 1));
        
        if($insert['zip_code']=='')
        {
            $insert['zip_code'] = NULL;
        }
        if($name=='')
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter NGO name.";            
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($insert['addres_line1']=='')
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter address";          
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($city=='')
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter city.";            
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($state=='')
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please select state.";          
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($country=='')
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please select country.";            
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($country_code=='')
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter country code.";            
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($insert['ngo_type']=='') 
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please select NGO type.";           
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        //commented by  
        /*
        if($insert['registration_no']=='')
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter Registration number.";         
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        } 
        */
        if($insert['annual_revenue']<0 )
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter Annual Revenue.";          
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if(is_float($insert['annual_revenue']) && $decimal_num>2)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Annual Revenue allowed only upto two decimals.";            
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($video_count>4)
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "You can not upload more than 4 videos.";            
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($document_count>15)
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "You can not upload more than 15 documents.";            
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if(!is_array($category))
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please select category.";           
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $ngo_type_array = ngo_types_arr();      
        if(!in_array($insert['ngo_type'],$ngo_type_array))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Invalid NgoType.";          
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if(!empty($insert['branding_url']))
        {
            //check unique branding_url
            $where_branding = array('branding_url' => $insert['branding_url']);
            $brandingData = $this->Ngo_model->organisation_gloabal_details($where_branding);
            if(!empty($brandingData))
            {
                $data['error'] = true;
                $data['status'] = 409;
                $data['message'] = "Branding Url already exists.";          
                header('HTTP/1.1 409 Validation Error');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }
        }
        
        //check registrationNo unique
        $unique_registartion_no = $this->Ngo_model->registration_unique($registration_no,'');
        if(!empty($unique_registartion_no))
        {
            $data['error'] = true;
            $data['status'] = 409;
            $data['message'] = "Registration number already exists.";           
            header('HTTP/1.1 409 Conflict');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }//if(!empty($unique_registartion_no))
        
        if(!empty($handlename))
        {
            foreach($handlename as $handlename_list)
            {
                //check handle is unique for NGO 
                $unique_handle_name = $this->Hashtag_model->check_handle_unique($handlename_list['handleName'],'');
                if(!empty($unique_handle_name))
                {
                    $data['error'] = true;
                    $data['status'] = 409;
                    $data['message'] = $handlename_list['handleName']." - handle already exists.";          
                    header('HTTP/1.1 409 Conflict');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    return;
                }//if(!empty($unique_handle_name))
            }
        }
        
        //check entry of country if exists then fetch id otherwise insert
        if(!empty($country))
        {
            $insert['country_id'] = $this->Country_model->country_get_insert($country,$country_code);
            //check entry of state if exists then fetch id otherwise insert
            if(!empty($state))
            {
                $insert['state_id'] = $this->Country_model->state_get_insert($state,$insert['country_id']);
                //check entry of city if exists then fetch id otherwise insert
                $insert['city_id'] = $this->Country_model->city_get_insert($city,$insert['state_id']);
            }//if(!empty($state))       
        }//if(!empty($country))
        
        $insert['status'] = 'Registered';   
        $insert['is_deleted'] = 0;
        $insert['is_active'] = 0;
        $insert['is_verified'] = 1;
        $insert['class'] = 'com.karmaworldwide.organisation.Ngo';
        $insert['date_created'] = $insert['last_updated'] = date('Y-m-d H:i:s');
        //insert organization data
        $organisation_id = $this->Ngo_model->insert_ngo($insert);
        
        //insert videos
        $insert_video['organisation_id'] = $organisation_id;
        $insert_video['date_created'] = $insert_video['last_updated'] = date('Y-m-d H:i:s');
        for($i=0;$i<$video_count;$i++)
        {
            $insert_video['video_url'] = isset($video_url[$i]['url'])?$video_url[$i]['url']:'';
            $insert_video['caption'] = isset($video_url[$i]['caption'])?$video_url[$i]['caption']:'';
            $insert_video['thumb_url'] = isset($video_url[$i]['thumbUrl'])?$video_url[$i]['thumbUrl']:'';
            if($insert_video['video_url']!="")
            $this->Ngo_model->insert_video($insert_video);
        }
        //insert categories_ngo
        $insert_category['ngo_id'] = $organisation_id;      
        for($i=0;$i<$category_count;$i++)
        {
            $insert_category['categories_id'] = $category[$i]['id'];
            $categoryExists = $this->Category_model->category_info($category[$i]['id']);
            if(!empty($categoryExists))
                $this->Ngo_model->insert_category_ngo($insert_category);
        }
        //insert documents
        for($i=0;$i<$document_count;$i++)
        {
            $insert_document['organisation_id'] = $organisation_id; 
            $insert_document['created_by_id'] = $valid_auth_token->user_id;
            $insert_document['is_public'] = $document_urls[$i]['isPublic'];
            $insert_document['link'] = $document_urls[$i]['url'];
            $insert_document['title'] = $document_urls[$i]['name'];
            $insert_document['description'] = isset($document_urls[$i]['description'])?$document_urls[$i]['description']:'';
            $insert_document['date_created'] = $insert_document['last_updated'] = date('Y-m-d H:i:s');
            $this->Ngo_model->insert_docs($insert_document);
        }
        //insert handles to twitter_handle table
        if(!empty($handlename))
        {
            foreach($handlename as $handlename_list)
            {
                // $insert_handle['version'] = 0;
                $insert_handle['date_created'] = $insert_handle['last_updated'] = date('Y-m-d H:i:s');
                $insert_handle['handle_name'] = $handlename_list['handleName'];
                $insert_handle['is_active'] = 1;
                $insert_handle['is_deleted'] = 0;
                $insert_handle['organisation_id'] = $organisation_id;
                //chk duplicate handles exists in db table
                $chk_duplicate_hand = $this->Hashtag_model->check_duplicate_handle($handlename_list['handleName'], $organisation_id);
                if(empty($chk_duplicate_hand))
                $this->Hashtag_model->insert_twitter_handle($insert_handle);    
            }
        }
        $data = ngo_profile_details($organisation_id,'all');
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//create_ngo_profile
}//end Organisation
/* End of file organisation.php */
/* Location: ./application/controllers/organisation.php */