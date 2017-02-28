<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';
// require APPPATH.'libraries/aws-autoloader.php';

class Ngo extends Rest 
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
        $this->load->model('Country_model');        
        $this->load->model('Category_model');
        $this->load->model('Hashtag_model');
        $this->load->model('Audit_model');
        $this->load->model('Activity_model');
        $this->load->model('Ngo_model');
        $this->load->model('Social_media_sharing_model');
    }

    protected function ngo_details($id='')
    {
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

            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            //role_check
            $role_id = $valid_auth_token->role_id;
            if($role_id==7)
            {
                $ngo_id = $this->input->get('ngoId');
                if(!empty($id))
                    $ngo_id = $id;
            }
            else
            {
                $ngo_id = login_ngo_details($auth_token);
            }
            $id = $ngo_id;
        }
        else
        {
            $id = $this->input->get('ngoId');
            if($id=="")
            {
                header('HTTP/1.1 404 Not Found');
                exit;
            }
        }

        $organisation = $this->Ngo_model->organization_details($id);
        if(!empty($organisation))
        {
            $data = ngo_profile_details($id);
            // $where1 = array('ngo_id'=>$id);
            // $ngo_social_data = $this->Social_media_sharing_model->get_user_data($where1);
            // if(!empty($ngo_social_data))
            // {
            //     if($ngo_social_data->facebook_is_post_on_pages==0)
            //     {
            //         if($ngo_social_data->manual_unlink==1)
            //         {
            //             $data['resp']['manualUnlink'] = true;
            //             $data['resp']['facebook_connect_status'] = false;
            //         }
            //         else
            //         {
            //             if($ngo_social_data->fb_extended_access_token_expires!=null || $ngo_social_data->fb_extended_access_token_expires!='')
            //             {
            //                 $expiresAt = $ngo_social_data->fb_extended_access_token_expires;
            //                 $current_date = date('Y-m-d H:i:s');

            //                 if($expiresAt<$current_date)
            //                 {
            //                     $data['resp']['manualUnlink'] = true;
            //                     $data['resp']['facebook_connect_status'] = false;
                                
            //                     $update['manual_unlink'] = 1;
            //                     $update['fb_extended_access_token'] = null;
            //                     $update['fb_extended_access_token_expires'] = null;
            //                     $social_id = $ngo_social_data->id;
            //                     $this->Social_media_sharing_model->update_user_data($update,array('id'=>$social_id));
            //                 }
            //                 else
            //                 {
            //                     $data['resp']['manualUnlink'] = false;
            //                 }
            //             }
            //             else
            //             {
            //                 $data['resp']['manualUnlink'] = false;
            //             }
            //         }
            //     }
            //     else
            //     {
            //         $data['resp']['manualUnlink'] = false;
            //     }
            // }
            // else
            //     $data['resp']['manualUnlink'] = false;
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 404;
            header('HTTP/1.1 404 Not Found');
            $data['message'] = "Organisation was not found.";
        }           
        
        return $data;
    }
    public function get_ngo_details($id='')
    {
        $data = $this->ngo_details($id);
        $data = json_encode($data,JSON_NUMERIC_CHECK);
        echo $data;
        return;
    }
    //update ngo rofile
    public function update_ngo_profile($id)
    {
        $id = $id;
        if($_SERVER['REQUEST_METHOD']=='GET')
        {
            $data = $this->ngo_details($id);
            $data = json_encode($data,JSON_NUMERIC_CHECK);
            echo $data;
            return;
        }
        $method = $this->input->get('method');
        if($method!="PUT")
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');

        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);   
        if(empty($valid_auth_token))
        {

            header('HTTP/1.1 401 Unauthorized');
            return;
        }       
        //list all ngo user
        //role_check
        $role_id = $valid_auth_token->role_id;
        if($role_id==5)
        {
            header('HTTP/1.1 401 Unauthorized');
            return;
        }

        $user_id = $valid_auth_token->user_id;
        if($role_id==4)
        {
            $insert['user_id'] = $user_id;
        }
        //role_check

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
        $donation_status = isset($jsonArray['donationStatus'])?$jsonArray['donationStatus']:'';
        if($donation_status==true)
            $insert['donation_status'] = 1;
        else
            $insert['donation_status'] = 0;
        $insert['donation_url'] = isset($jsonArray['donationUrl'])?$jsonArray['donationUrl']:'';
        $insert['description'] = isset($jsonArray['description'])?$jsonArray['description']:''; 
        $insert['programs_net_spend'] = isset($jsonArray['programsNetSpend'])?$jsonArray['programsNetSpend']:'';            
        $insert['image_url'] = isset($jsonArray['imageUrl'])?$jsonArray['imageUrl']:'';
        $insert['ngo_type'] = isset($jsonArray['ngoType'])?$jsonArray['ngoType']:'';            
        $insert['annual_revenue'] = isset($jsonArray['annualRevenue'])?$jsonArray['annualRevenue']:'';      
        $insert['registration_no'] = $registration_no = isset($jsonArray['registrationNo'])?$jsonArray['registrationNo']:'';
        $insert['contact_us'] = isset($jsonArray['contactUs'])?$jsonArray['contactUs']:'';
        $insert['copyright'] = isset($jsonArray['copyright'])?$jsonArray['copyright']:'';
        $insert['thumb_url'] = isset($jsonArray['thumbUrl'])?$jsonArray['thumbUrl']:'';
        //Update ngo favicon neha
        $insert['favicon_url'] = isset($jsonArray['faviconUrl'])?$jsonArray['faviconUrl']:'';
        $category = isset($jsonArray['category'])?$jsonArray['category']:array();
        $category_count = count($category);         
        $insert['zip_code'] = (string)$insert['zip_code'];
        $handlename = isset($jsonArray['handles'])?$jsonArray['handles']:array();
        //facebook handles added by  
        $facebookHandles = isset($jsonArray['facebookHandles'])?$jsonArray['facebookHandles']:array();
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
            $data['message'] = "Please enter name";         
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
        /*if($insert['registration_no']=='')
        {
            
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter Registration number.";         
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        } */
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
            $where_branding = array('branding_url' => $insert['branding_url'],
                'id <> '=> $id);
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
        //commented by  
        /*
        //check registrationNo unique
        $unique_registartion_no = $this->Ngo_model->registration_unique($registration_no,$id);
        if(!empty($unique_registartion_no))
        {
            $data['error'] = true;
            $data['status'] = 409;
            $data['message'] = "Registration number already exists.";           
            header('HTTP/1.1 409 Conflict');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }//if(!empty($unique_registartion_no))*/
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
        
        //To check if same handle is added by another NGO - Unique handle name
        if(!empty($handlename))
        {
            foreach($handlename as $handlename_list)
            {
                //check handle is unique for NGO 
                $unique_handle_name = $this->Hashtag_model->check_handle_unique($handlename_list['handleName'],$id);
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
        //check facebook handle is unique
        if(!empty($facebookHandles))
        {
            foreach($facebookHandles as $facebookHandle)
            {
                //check handle is unique for NGO 
                $unique_handle_name = $this->Hashtag_model->check_facebook_handle_unique($facebookHandle['handleName'],$id);
                if(!empty($unique_handle_name))
                {
                    $data['error'] = true;
                    $data['status'] = 409;
                    $data['message'] = $facebookHandle['handleName']." - handle already exists.";           
                    header('HTTP/1.1 409 Conflict');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    return;
                }//if(!empty($unique_handle_name))
            }
        }
        //check ngo profile created or not

        //role_check
        if($role_id==4)
        {   
            $organization_exists = $this->Ngo_model->organization_exist($user_id);
        }
        elseif($role_id==7)
        {
            $supportNgos = support_ngos($user_id);
            $supportCount = 0;
            foreach($supportNgos as $supportNgo)
            {
                if($supportNgo['id']==$id)  
                    $supportCount++;
            }

            if($supportCount==0)
            {

                header('HTTP/1.1 401 Unauthorized User');
                return;
            }
            
            $organization_exists = $this->Ngo_model->organization_details($id);
        }
        //role_check

        if(!empty($organization_exists))
        {
            $ngo_id = $organization_exists->id;     
            //check update for same organisation
            if($ngo_id==$id)
            {
                //xss clean
                $insert = $this->security->xss_clean($insert);

                //audit ngo_profile
                $audit_info['user_id'] = $user_id;
                $audit_info['role_id'] = $role_id;
                $audit_info['org_id'] = $id;
                $audit_info['entity'] = 'NPO profile';
                $audit_info['entity_id'] = $id;
                $audit_info['action'] = 'updated';
                $old_data = ngo_profile_details($ngo_id);
                $old_data = $old_data['resp'];
                $audit_id = $this->Audit_model->update_audit($old_data,$jsonArray,$audit_info);

                $insert['last_updated'] = date('Y-m-d H:i:s');
                $this->Ngo_model->update_ngo($insert,$id);
                
                if($audit_id!='false')              
                    $this->Audit_model->activate_audit($audit_id);
                //audit ngo_profile
                
                //delete all existing categories and insert new
                $this->Ngo_model->delete_category_ngo($id);
                //insert categories_ngo
                $insert_category['ngo_id'] = $ngo_id;       
                for($i=0;$i<$category_count;$i++)
                {
                    $insert_category['categories_id'] = $category[$i]['id'];
                    $categoryExists = $this->Category_model->category_info($category[$i]['id']);
                    if(!empty($categoryExists))
                        $this->Ngo_model->insert_category_ngo($insert_category);
                    
                }
                //delete all existing handles and insert new 
                if(!empty($handlename))
                {
                    foreach($handlename as $handlename_list)
                    {
                        $insert_handle = array();
                        $insert_handle['date_created'] = $insert_handle['last_updated'] = date('Y-m-d H:i:s');
                        $insert_handle['handle_name'] = $handlename_list['handleName'];
                        $insert_handle['is_active'] = 1;
                        $insert_handle['is_deleted'] = 0;
                        $insert_handle['organisation_id'] = $ngo_id;
                        //get current active handle if from db and new one same dont do anything else deactivate prev and insert new
                        $where = array(
                            'organisation_id'=>$ngo_id,
                            'is_active'=>1,
                            'is_deleted'=>false);
                        $handleExisting = $this->Hashtag_model->get_handle_details('twitter_handles',$where);
                        if(empty($handleExisting))
                        {
                            //insert 
                            $this->Hashtag_model->insert_twitter_handle($insert_handle);
                        }                           
                        else
                        {
                            $update_prev = array();
                            $where_prev = array();
                            //update old value as we are saving only one handle
                            $update_prev['handle_name'] = $insert_handle['handle_name'];
                            $update_prev['is_active'] = true;
                            $update_prev['is_deleted'] = false;
                            $where_prev['id'] = $handlename_list['id'];
                            $this->Hashtag_model->update_handle('twitter_handles', $where_prev, $update_prev);  
                        }
                    }
                }

                //delete all existing facebook handles and add new
                if(!empty($facebookHandles))
                {
                    foreach($facebookHandles as $facebookHandle)
                    {
                        $insert_handle = array();
                        $insert_handle['date_created'] = $insert_handle['last_updated'] = date('Y-m-d H:i:s');
                        $insert_handle['handle_name'] = $facebookHandle['handleName'];
                        $insert_handle['is_active'] = true;
                        $insert_handle['is_deleted'] = false;
                        $insert_handle['organisation_id'] = $ngo_id;
                        //get current active handle if from db and new one same dont do anything else deactivate prev and insert new
                        $where = array(
                            'organisation_id'=>$ngo_id,
                            'is_active'=>true,
                            'is_deleted'=>false);
                        $handleExisting = $this->Hashtag_model->get_handle_details('facebook_handles',$where);
                        if(empty($handleExisting))
                        {
                            //insert
                            $this->Hashtag_model->insert_facebook_handle($insert_handle);
                        }                           
                        else
                        {
                            $update_prev = array();
                            $where_prev = array();
                            //update old value as we are saving only one handle
                            $update_prev['handle_name'] = $facebookHandle['handleName'];
                            $update_prev['is_active'] = true;
                            $update_prev['is_deleted'] = false;
                            $where_prev['id'] = $handleExisting->id;
                            $this->Hashtag_model->update_handle('facebook_handles', $where_prev, $update_prev); 
                        }
                    }
                }
                $this->get_ngo_details($id);
            }
            else
            {
                header('HTTP/1.1 401 Unauthorized User');
                return;
            }   
        }           
        else
        {
            $data['error'] = true;  
            $data['status'] = 404;
            $data['meassage'] = "Operation failed to find NGO using id mentioned."; 
            header('HTTP/1.1 404 Not Found');
            return;
        }//else
    }//ngo_profile  
    public function delete_video($ngo_id,$id)
    {
        $id = $id;
        $method = $this->input->get('method');
        
        //this action can be performed by ngo admin only
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_ngo_admin = $this->Verification_model->valid_ngo_admin($auth_token);
        //if not a ngo admin then 401
        if(empty($valid_ngo_admin))
        {           
            header('HTTP/1.1 401 Unauthorized User');
            return;
        }
        //check video by id exists or not
        $video = $this->Ngo_model->get_video($id,$ngo_id);
        if(!empty($video))
        {
            if($method=="DELETE")
            {
                $update['deleted_at'] = date('Y-m-d H:i:s');
                $update['is_deleted'] = 1;
                $this->Ngo_model->update_video($update,$id);
                $data['error'] = false;
                $data['status'] = 200;
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }
            elseif($method=="PUT")
            {
                $jsonArray = json_decode(file_get_contents('php://input'),true);            
                $update['video_url'] = isset($jsonArray['videoUrl'])?$jsonArray['videoUrl']:'';
                $update['caption'] = isset($jsonArray['caption'])?$jsonArray['caption']:'';
                $update['thumb_url'] = isset($jsonArray['thumbUrl'])?$jsonArray['thumbUrl']:'';
                $update['last_updated'] = date('Y-m-d H:i:s');
                if(empty($update['video_url']))
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Validation Error.";         
                    header('HTTP/1.1 400 Validation Error');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    return;
                }
                $this->Ngo_model->update_video($update,$id);
                $data['error'] = false;
                $data['resp']['id'] = $id;
                $data['resp']['url'] = $update['video_url'];
                $data['resp']['caption'] = $update['caption'];
                $data['resp']['thumbUrl'] =$update['thumb_url'];
                $data['resp']['lastUpdated'] =$update['last_updated'];
                echo json_encode($data,JSON_NUMERIC_CHECK);
            }   
            else
            {
                header('HTTP/1.1 404 Not Found');
                return;
            }   
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find video using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }   
    }//delete_video
    //add video while updating ngo
    public function add_video($organisation_id)
    {
        //this action can be performed by ngo admin only
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_ngo_admin = $this->Verification_model->valid_ngo_admin($auth_token);
        if(empty($valid_ngo_admin))
        {
            header('HTTP/1.1 401 Unauthorized User');
            return;
        }
        $user_id = $valid_ngo_admin->user_id;
        $organisation = $this->Ngo_model->organization_details($organisation_id);
        if(!empty($organisation))
        {
            //check logged in user admin of same organisation
            if($user_id==$organisation->user_id)
            {
                $jsonArray = json_decode(file_get_contents('php://input'),true);            
                $video_url = isset($jsonArray['videoUrl'])?$jsonArray['videoUrl']:'';
                $caption = isset($jsonArray['caption'])?$jsonArray['caption']:'';
                $thumb_url = isset($jsonArray['thumbUrl'])?$jsonArray['thumbUrl']:'';
                if(empty($video_url))
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Validation Error.";         
                    header('HTTP/1.1 400 Validation Error');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    return;
                }
                //check uploaded video not greater than 4
                $count = $this->Ngo_model->organization_video_count($organisation_id);
                $video_cnt = $count->num;
                if($video_cnt<4)
                {
                    
                    $insert_video['organisation_id'] = $organisation_id;
                    $insert_video['date_created'] = $insert_video['last_updated'] = date('Y-m-d H:i:s');
                    $insert_video['video_url'] = $video_url;
                    $insert_video['caption'] = $caption;
                    $insert_video['thumb_url'] = $thumb_url;
                    $id = $this->Ngo_model->insert_video($insert_video);
                    $data['error'] = false;
                    $data['resp']['id'] = (float)$id;
                    $data['resp']['url'] = $video_url;
                    $data['resp']['caption'] = $caption;
                    $data['resp']['thumbUrl'] = $thumb_url;
                    echo json_encode($data,JSON_NUMERIC_CHECK);

                    return;
                }
                else
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "You can not upload more than 4 videos.";
                    header('HTTP/1.1 400 Validation Error');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    return;
                }   
            }
            else{
                header('HTTP/1.1 401 Unauthorized user');
                return;
            }
        }   
        else
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find organisation using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }   
    }//add_video
    public function ngo_by_website()
    {
        $headers = getallheaders();
        $branding_url = $headers['apporigin'];
        if(empty($branding_url))
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        $ngo_details = $this->Ngo_model->organisation_gloabal_details(array('branding_url' => $branding_url));
        if(empty($ngo_details))
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        $id = $ngo_details->id;
        $data = ngo_profile_details($id);

        $data = json_encode($data,JSON_NUMERIC_CHECK);
        echo $data;
        
        return;
    }//ngo_by_website

    public function is_accepted_terms_and_conditions($ngo_id)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        $role_id = $valid_auth_token->role_id;
        if($role_id!=4)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }   

        $ngoId = login_ngo_details($auth_token); 
        if($ngo_id!=$ngoId)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = 'Unauthorized User.';
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $ngo_data = $this->Ngo_model->org_is_accepted_terms($ngo_id);
        $is_accepted = $ngo_data->is_accepted_terms_and_conditions;
        $data['error'] = false;
        if($is_accepted==0)
            $data['is_accepted'] = false;
        else
            $data['is_accepted'] = true;
        echo json_encode($data, JSON_NUMERIC_CHECK);
        exit;
    }

    public function accept_terms_and_conditions($ngo_id)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        $role_id = $valid_auth_token->role_id;
        if($role_id!=4)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }   

        $ngoId = login_ngo_details($auth_token); 
        if($ngo_id!=$ngoId)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = 'Unauthorized User.';
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $update['is_accepted_terms_and_conditions'] = 1;
        $this->Ngo_model->update_ngo($update,$ngo_id);
        $data['error'] = false;
        $data['message'] = 'NPO profile successfully accepted.';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function ngo_media($id)
    {
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;

        $ngo_info = $this->Ngo_model->organization_details($id);    
        if(empty($ngo_info)){
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find npo using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            return $data;
        }
        $data['error'] = false;
        $data['resp']['count'] = $this->Activity_model->ngo_update_media_count($id);
        $data['resp']['media'] = $this->Activity_model->ngo_update_media($id,$offset,$limit);
        echo json_encode($data,JSON_NUMERIC_CHECK);
    }
}//end ngo

/* End of file ngo.php */
/* Location: ./application/controllers/ngo.php */