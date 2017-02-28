<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class User extends Rest 
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
        $user_id = $valid_auth_token->user_id;
        if($role_id==4 || $role_id==5)
            $ngo_id = login_ngo_details($auth_token);
        else
            $ngo_id = support_ngos($user_id);
        if(empty($ngo_id))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;           
        }
        $this->load->model('Verification_model');
        $this->load->model('User_model');
        $this->load->model('Country_model');
        $this->load->model('Ngo_model');
        $this->load->model('Category_model');
        $this->load->model('Support_model');
        $this->load->model('Audit_model');
        $this->load->model('Social_media_sharing_model');
    }
    //presigned url for file upload
    public function signedurl() 
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token))
        {
            header('HTTP/1.1 401 Unauthorized.');
            exit;
        }
        $domain = ($this->input->get('domain'))?$this->input->get('domain'):'';
        $id = ($this->input->get('id'))?$this->input->get('id'):'';
        $file_name = ($this->input->get('name'))?$this->input->get('name'):'';
        $contentType = ($this->input->get('contentType'))?$this->input->get('contentType'):'';
        if(empty($file_name) || empty($contentType) || empty($domain) || empty($id))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Validation Error.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }   
        
        //call login api of grails      
        $url = $this->config->item('admin_url')."v1/file/signedurl?name=".$file_name."&contentType=".$contentType."&domain=ngo&id=".$id; 
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        try{
            
            $ch = curl_init();
            $postdata = array(
            );
            $data_string = json_encode($postdata); 
            $ch = curl_init(); 
            $ret = curl_setopt($ch, CURLOPT_URL, $url);                                                                     
            $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                     
                                                                         
            $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                          
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                'Content-Type: application/json',                                                                                
                'X-Auth-Token: ' . $auth_token)                                                                       
            );      
            echo $ret = curl_exec($ch);
            
            $info = curl_getinfo($ch);
        }//end try
        catch(Exception $e)
        {
            
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }//end catch
        
                
    }//end presigned url for file upload
    //get logged in user details
    public function current_user()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');        
        $user_id = login_user_details($auth_token);
        if(empty($user_id))
        {
            header('HTTP/1.1 401 Unauthorized');
            return; 
        }
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        //role_check
        $role_id = $valid_auth_token->role_id;
        if($role_id==4 || $role_id==5)
        {
            $ngo_id = login_ngo_details($auth_token);   
        }
        $user_data = $this->User_model->user_info($user_id);
        if(!empty($user_data))
        {
            $data['error'] = false;
            //user details
            $data['resp']['user']['id'] = $user_data->id;
            $data['resp']['user']['email'] = $user_data->email;
            $data['resp']['user']['username'] = $user_data->username;
            $data['resp']['user']['roleName'] = $user_data->role_authority;
            if($user_data->role_authority=="ROLE_NGO_ADMIN") {
                $roleDisplay = 'NPO Admin';
            }
            elseif($user_data->role_authority=="ROLE_NGO_MEMBER") {
                $roleDisplay = 'NPO Member';
            }
            else {
                $roleDisplay = 'NPO Support';
            }
            $data['resp']['user']['roleDisplay'] = $roleDisplay;
            $role_id = $data['resp']['user']['roleId'] = $user_data->role_id;
            $status = $user_data->is_active;
            $data['resp']['user']['status'] = (ord($status)==1 || $status==1)?true:false;   
            
            $data['resp']['user']['firstName'] = $user_data->first_name;
            $data['resp']['user']['lastName'] = $user_data->last_name;
            $data['resp']['user']['dateAdded'] = $user_data->date_created;
            $data['resp']['user']['projectAssignedDate'] = $user_data->project_assigned_on;
            $data['resp']['user']['token'] = $user_data->token_value;
            $data['resp']['user']['registrationStatus'] = $user_data->registration_status;
            $data['resp']['user']['title'] = $user_data->title;
            $role_data = $this->User_model->role_info($role_id);
            $data['resp']['role']['class'] = 'com.karmaworldwide.account.Role';
            $data['resp']['role']['id'] = $role_data->id;
            $data['resp']['role']['authority'] = $role_data->authority;
            $data['resp']['role']['displayAuthority'] = $role_data->display_authority;
            $profile = $this->User_model->user_profile_info($user_id);
            if(!empty($profile))
            {
                $data['resp']['profile']['id'] = $profile->id;
                $data['resp']['profile']['description'] = $profile->description;
                $data['resp']['profile']['imageUrl'] = $profile->image_url;
                
                $data['resp']['profile']['country'] = $profile->name;
                $data['resp']['profile']['countryCode'] = $profile->code;
                if(!empty($profile->state_id))
                {
                    $state_details = $this->Country_model->state_info($profile->state_id);
                    $state = $state_details->name;
                }
                else
                    $state = null;  
                $data['resp']['profile']['state'] = $state;
                if(!empty($profile->city_id))
                {
                    $city_details = $this->Country_model->city_info($profile->city_id);
                    $city = $city_details->name;
                }
                else
                    $city = null;   
                $data['resp']['profile']['city'] = $city;       
                $data['resp']['profile']['dob'] = $profile->dob;
                $data['resp']['profile']['gender'] = $profile->gender;
                $data['resp']['profile']['mobile'] = $profile->mobile;
            }
            else
            {
                $data['resp']['profile']['error'] = true;
                $data['resp']['profile']['status'] = 404;
                $data['resp']['profile']['message'] = "profile was not found";                  
            }

            if($role_data->authority=='ROLE_SUPPORT')
            {
                $ngos_data = $this->Support_model->get_ngos_by_support($user_data->id);
                if(empty($ngos_data))
                {
                    $data['organisation']['error'] = true;
                    $data['organisation']['status'] = 404;
                    $data['organisation']['message'] = "Operation failed to find ngo details using id mentioned.";
                    header('HTTP/1.1 404 Not Found');
                }
                else
                {
                    $data['error'] = false;
                    foreach($ngos_data as $ngo_data)
                    {
                        $social_media_info = $this->Social_media_sharing_model->get_ngo_status($ngo_data->ngo_id);
                        if(!empty($social_media_info))
                        {
                            if($social_media_info->facebook==null || $social_media_info->facebook=='')
                                $facebook_connect_status = false;   
                            else
                                $facebook_connect_status = true;

                            if($social_media_info->twitter==null || $social_media_info->twitter=='')
                                $twitter_connect_status = false;    
                            else
                                $twitter_connect_status = true;
                        }
                        else
                        {
                            $facebook_connect_status = false;
                            $twitter_connect_status = false;        
                        }
                        $a = array(
                            'id' => $ngo_data->ngo_id,
                            'name' => $ngo_data->name,
                            'brandingUrl' => $ngo_data->branding_url,
                            'facebook_connect_status' => $facebook_connect_status,
                            'twitter_connect_status' => $twitter_connect_status,
                        );
                        $data['resp']['organisation'][] = $a;
                    }
                }
            }
            else
            {
                $data['resp']['organisation']['id'] = $ngo_id;
                $ngo_data = $this->Ngo_model->organization_details($ngo_id);
                $data['resp']['organisation']['name'] = $ngo_data->name;
                $data['resp']['organisation']['brandingUrl'] = $ngo_data->branding_url;

                $social_media_info = $this->Social_media_sharing_model->get_ngo_status($ngo_id);
                if(!empty($social_media_info))
                {
                    if($social_media_info->facebook==null || $social_media_info->facebook=='')
                        $data['resp']['organisation']['facebook_connect_status'] = false;   
                    else
                        $data['resp']['organisation']['facebook_connect_status'] = true;

                    if($social_media_info->twitter==null || $social_media_info->twitter=='')
                        $data['resp']['organisation']['twitter_connect_status'] = false;    
                    else
                        $data['resp']['organisation']['twitter_connect_status'] = true;
                }
                else
                {
                    $data['resp']['organisation']['facebook_connect_status'] = false;
                    $data['resp']['organisation']['twitter_connect_status'] = false;        
                }
            }
        }//if(!empty($user_data))
        else
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find user using id mentioned.";
            header('HTTP/1.1 404 Not Found');
        }
        $data = json_encode($data,JSON_NUMERIC_CHECK);
        echo $data;
        return;
    }//current_user
    //get the user profile information
    public function profile($id=0)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        if(empty($auth_token))
        {
            header('HTTP/1.1 401 Unauthorized');
            return;
        }//if(empty($auth_token))
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token))
        {
            header('HTTP/1.1 401 Unauthorized');
            return;
        }   
        if($_SERVER['REQUEST_METHOD']=='GET')
        {
            $method = $this->input->get('method');
            if($method=="DELETE")
            {
                $data = $this->delete_profile($id);
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }
            $data = $this->get_profile($id);
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        elseif($_SERVER['REQUEST_METHOD']=='POST')
        {
            $method = $this->input->get('method');
            if($method=="PUT")
            {
                $data = $this->update_user($id);
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }//
            else
            {
                header('HTTP/1.1 404 Not Found');
                return;
            }
        }
        else{           
            header('HTTP/1.1 404 Not Found');
            return;
        }
                    
    }
    //update profile
    protected function update_user($id)
    {
        $id = (int) $id;
        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $update['first_name'] = isset($jsonArray['firstName'])?$jsonArray['firstName']:'';
        $update['last_name'] = isset($jsonArray['lastName'])?$jsonArray['lastName']:'';
        $update['title'] = isset($jsonArray['title'])?$jsonArray['title']:'';
        $email = isset($jsonArray['email'])?$jsonArray['email']:'';
        $username = isset($jsonArray['email'])?$jsonArray['username']:'';
        $insert_profile['image_url'] = isset($jsonArray['imageUrl'])?$jsonArray['imageUrl']:'';
        $insert_profile['description'] = isset($jsonArray['description'])?$jsonArray['description']:'';
        $user_data = $this->User_model->user_info_admin($id);
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        //if ngo admin then can update any user otherwise can update profile of himself
        $valid_ngo_admin = $this->Verification_model->valid_ngo_admin($auth_token);
         
        if(empty($valid_ngo_admin))
        {
            //if not ngo admin then id should match with id from token
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            if(!empty($valid_auth_token))
            {
                // 
                $role_id = $valid_auth_token->role_id;
                if($role_id==5)
                {
                    $user_id_token =  $valid_auth_token->user_id;
                    if($user_id_token!=$id)
                    {
                        header('HTTP/1.1 401 Unauthorized');
                        exit;
                    }
                }
                elseif($role_id==7)
                {
                    $ngoId = $this->input->get('ngoId');

                    $user_id =  $valid_auth_token->user_id;
                    $supportNgos = support_ngos($user_id);
                    $supportCount = 0;
                    foreach($supportNgos as $supportNgo)
                    {
                        if($supportNgo['id']==$ngoId)   
                            $supportCount++;
                    }

                    if($supportCount==0)
                    {
                        header('HTTP/1.1 401 Unauthorized User');
                        return;
                    }

                    $valid_ngo_user = $this->Ngo_model->ngo_user_check($ngoId, $id);
                    $valid_admin = $this->Ngo_model->ngo_admin_check($ngoId, $id);
                    if(empty($valid_ngo_user) && empty($valid_admin))
                    {
                        header('HTTP/1.1 401 Unauthorized');
                        exit;
                    }
                }
                // 
            }
            else
            {
                header('HTTP/1.1 401 Unauthorized');
                exit;
            }           
        }
        else{
            //only update user of his ngo
            $admin_id = $valid_ngo_admin->user_id;
            if($admin_id!=$id)
            {
                $organisation = $this->Ngo_model->organization_exist($admin_id);
                if(!empty($organisation))
                {
                    $ngo_id = $organisation->id;
                    //check user belongs to ngo
                    $valid_id = $this->Ngo_model->ngo_user_check($ngo_id, $id);
                    if(empty($valid_id))
                    {
                        header('HTTP/1.1 401 Unauthorized');
                        exit;
                    }
                }
                else
                {
                    header('HTTP/1.1 401 Unauthorized');
                    exit;
                }
            }   
                
        }
        if(empty($update['first_name']) || empty($update['last_name']) || empty($email) || empty($username))
        {
            //validation error
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Validation Error.";         
            header('HTTP/1.1 400 Validation Error');
            return $data;
        }
        else{           
            if(!empty($user_data))
            {   
                
                //check duplicate email
                $dupEmail = $this->User_model->update_user_email_duplicate($email, $id);
                if(!empty($dupEmail))
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Email Already Exists.";         
                    header('HTTP/1.1 400 Validation Error');
                    return $data;
                }
                //check duplicate username
                $dupUsername = $this->User_model->update_username_duplicate($username, $id);
                if(!empty($dupUsername))
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Username Already Exists.";          
                    header('HTTP/1.1 400 Validation Error');
                    return $data;
                }
                $update['username'] = $username;
                $update['email'] = $email;
                $update['salt'] = $email;
                $this->User_model->update_user($update, $id);

                //check entry in profile
                $profile_data = $this->User_model->profile_info($id);
                if(empty($profile_data))
                {
                    //insert into profile
                    $insert_profile['date_created']=$insert_profile['last_updated'] = date('Y-m-d H:i:s');
                    $insert_profile['user_id'] = $id;
                    $this->User_model->profile_create($insert_profile);
                }
                else
                {
                    //update profile
                    $profile_id = $profile_data->id;
                    $insert_profile['last_updated'] = date('Y-m-d H:i:s');
                    $this->User_model->profile_update($insert_profile, $profile_id);
                }   
                $data = $this->get_profile($id);
                return $data;
            }
            else{
                $data['error'] = true;
                $data['status'] = 404;
                $data['message'] = "Operation failed to find user using id mentioned.";
                header('HTTP/1.1 404 Not Found');
                return $data;
            }
        }
    }
    //get_profile
    protected function get_profile($id)
    {
        $id = (int) $id;
        
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_token))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "You are not authorized user.";
            header('HTTP/1.1 401 Unauthorized User');
            return;
        }       
        $user_data = $this->User_model->user_info_admin($id);
        if(!empty($user_data))
        {
            $data['error'] = false;
            $profile = $this->User_model->profile_info($id);
            $data['resp']['id'] = (float)$user_data->id;
            $data['resp']['email'] = $user_data->email;
            $data['resp']['username'] = $user_data->username;
            $data['resp']['roleName'] = $user_data->role_authority;
            if($user_data->role_authority=="ROLE_NGO_ADMIN")
                    $roleDisplay = 'Ngo Admin';
                else
                    $roleDisplay = 'Ngo Member';    
            $data['resp']['roleDisplay'] = $roleDisplay;
            $data['resp']['roleId'] = $user_data->role_id;
            $status = $user_data->is_active;            
            if (ord($status)==1 || $status==1)
                $data['resp']['status'] = true;
            else
                $data['resp']['status'] = false;
            $data['resp']['firstName'] = $user_data->first_name;
            $data['resp']['lastName'] = $user_data->last_name;
            $data['resp']['dateAdded'] = $user_data->date_created;
            $data['resp']['title'] = $user_data->title;             
            $data['resp']['dateCreated'] = $user_data->date_created;
            $data['resp']['projectAssignedDate'] = $user_data->project_assigned_on;
            $data['resp']['token'] = $user_data->token_value;
            if(!empty($profile))
            {
                $data['resp']['imageUrl'] = $profile->image_url;
                $data['resp']['description'] = $profile->description;
            }
            else
            {
                $data['resp']['imageUrl'] = null;
                $data['resp']['description'] = null;
            }
            return $data;
        }//if(!empty($user_data))
        else
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find user using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            return $data;
        }
    }
    
    //delete profile    
    public function delete_profile($id)
    {
        $id = (int) $id;
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        //if ngo admin then can delete any user
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token))
        {       
            header('HTTP/1.1 401 Unauthorized');
            return;     
        }
        //check ngo from token
        $role_id = $valid_auth_token->role_id;

        if($role_id==4)
        {
            //only delete user of his ngo
            $admin_id = $valid_auth_token->user_id;
            //admin can not delete own profile
            if($admin_id==$id)
            {
                header('HTTP/1.1 401 Unauthorized');
                return;
            }   
            $organisation = $this->Ngo_model->organization_exist($admin_id);
            if(empty($organisation))
            {
                header('HTTP/1.1 401 Unauthorized');
                exit;
            }
            $ngo_id = $organisation->id;
        }
        // 
        elseif($role_id==7)
        {
            $ngoId = $this->input->get('ngoId');
            
            $user_id = $valid_auth_token->user_id;
            $supportNgos = support_ngos($user_id);
            $supportCount = 0;
            foreach($supportNgos as $supportNgo)
            {
                if($supportNgo['id']==$ngoId) {
                    $supportCount++;
                    $ngo_id=$ngoId;
                }
            }
            if($supportCount==0)
            {
                header('HTTP/1.1 401 Unauthorized User');
                return;
            }
        }
        // 
        else
        {
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        //check user belongs to ngo
        $valid_id = $this->Ngo_model->ngo_user_check($ngo_id, $id);
        if(empty($valid_id))
        {
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        else
        {
            //delete profile
            $jsonArray = json_decode(file_get_contents('php://input'),true); 
            $status = isset($jsonArray['status'])?$jsonArray['status']:false;               
            $status1 = $status ? 'true' : 'false'; //will output false              
            if($status1=="false")
            {                       
                $update['is_active'] = 0;
                $update['enabled'] = 0;
                $update['account_expired'] = 1;
                $update['account_locked'] = 1;
                $update['deleted_at'] = $update['last_updated'] = date('Y-m-d H:i:s');
            }
            else
            {                       
                $update['is_active'] = 1;
                $update['enabled'] = 1;
                $update['account_expired'] = 0;
                $update['account_locked'] = 0;
                $update['last_updated'] = date('Y-m-d H:i:s');
            }   

            //audit update_user_status
            $audit_info['user_id'] = $valid_auth_token->user_id;
            $audit_info['role_id'] = $role_id;
            $audit_info['org_id'] = $ngo_id;
            $audit_info['entity'] = 'member status';
            $audit_info['entity_id'] = $id;
            $audit_info['action'] = 'updated';
            $audit_info['target_user_id'] = $id;
            $user_data = $this->get_profile($id);
            $audit_id = $this->Audit_model->update_audit_2($user_data['resp'], $jsonArray, $audit_info);
            
            $this->User_model->update_user($update, $id);

            if(isset($audit_id))                
            $this->Audit_model->activate_audit($audit_id);
            //audit update_user_status

            $data = $this->get_profile($id);
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }           
    }

}//end user

/* End of file user.php */
/* Location: ./application/controllers/user.php */