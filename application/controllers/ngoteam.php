<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Ngoteam extends Rest 
{
    public function __construct()
    {
        parent::__construct();      
        $this->load->model('Verification_model');
        $this->load->model('User_model');
        $this->load->model('Ngo_model');
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
    //list of ngo users
    public function ngo_user($org_id)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;   
        $status = ($this->input->get('status'))?$this->input->get('status'):'';
        if(!empty($status))
        {
            if($status!='true' && $status!='false')
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Invalid Status.";
                header('HTTP/1.1 400 Validation Error');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }   
        }       
        //check valid auth_token and is ngo admin
        $valid_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_token))
        {
            header('HTTP/1.1 401 Unauthorized User');
            return;
        }
        $user_id = $valid_token->user_id;
        $user_data = $this->User_model->user_info($user_id);
        //list all ngo user
        $role_id = $user_data->role_id;
        //organisation details
        //if ngo admin then check directly organization
        if($role_id==4)
            $organisation = $this->Ngo_model->organization_exist($user_id);
        elseif($role_id==5)
            //get organisation_id from ngo_user
            $organisation = $this->Ngo_model->organization_member_details($user_id);
        else
        {
            $supportNgos = support_ngos($user_id);
            
            $supportCount = 0;
            foreach($supportNgos as $supportNgo)
            {
                if($supportNgo['id']==$org_id)  
                    $supportCount++;
            }

            if($supportCount==0)
            {
                header('HTTP/1.1 401 Unauthorized User');
                return;
            }
            $organisation = $this->Ngo_model->organization_details($org_id);
        }
        if(!empty($organisation))
        {
            $ngo_id = $organisation->id;
            
            if($role_id==4 || $role_id==5)
            {
                if($org_id!=$ngo_id)
                {
                    header('HTTP/1.1 401 Unauthorized User');
                    return;
                }   
            }
            //fetch users from ngo_user
            $data['error'] = false;
            $count = $this->Ngo_model->ngo_user_count($ngo_id, $query, $status);
            $data['resp']['count'] = $count->num;
            if($role_id==4 || $role_id==5)
            {
                $ngo_user_list = $this->Ngo_model->ngo_user_list($ngo_id, $query, $status, $offset, $limit);
            }
            else
            {
                if($page==1)
                {
                    if($status=='false')
                        $ngo_user_list = $this->Ngo_model->ngo_user_list($ngo_id, $query, $status, $offset, $limit);
                    else
                    {
                        $data['resp']['count'] = $count->num+1;
                        $ngo_admin_data = $this->Ngo_model->ngo_admin_data($ngo_id, $query);
                        $limit = $limit-1;
                        $ngo_member_list = $this->Ngo_model->ngo_user_list($ngo_id, $query, $status, $offset, $limit);
                        $ngo_user_list = array_merge($ngo_admin_data, $ngo_member_list);
                    }
                }
                else
                {
                    $offset = $offset-1;    
                    $ngo_user_list = $this->Ngo_model->ngo_user_list($ngo_id, $query, $status, $offset, $limit);
                }
            }
            
            $user_list = array();
            if(!empty($ngo_user_list))
            {
                $i = 0;
                foreach($ngo_user_list as $ngo_users)
                {
                    $user_list[$i]['id'] = (float)$ngo_users->id;
                    $user_list[$i]['firstName'] = $ngo_users->first_name;
                    $user_list[$i]['lastName'] = $ngo_users->last_name;
                    $user_list[$i]['title'] = $ngo_users->title;
                    $user_list[$i]['email'] = $ngo_users->email;
                    $user_list[$i]['username'] = $ngo_users->username;
                    $user_list[$i]['roleAuthority'] = $ngo_users->role_authority;
                    $user_list[$i]['dateCreated'] = $ngo_users->date_created;
                    if($ngo_users->role_authority=="ROLE_NGO_ADMIN")
                        $roleDisplay = 'Ngo Admin';
                    else
                    $roleDisplay = 'Ngo Member';    
                    $user_list[$i]['roleDisplay'] = $roleDisplay;
                    $user_list[$i]['roleId'] = $ngo_users->role_id;
                    $status = $ngo_users->is_active;            
                    if (ord($status)==1 || $status==1)
                        $user_list[$i]['status'] = true;
                    else
                        $user_list[$i]['status'] = false;
                    $user_list[$i]['profile']['id'] = $ngo_users->profile_id;
                    $user_list[$i]['profile']['imageUrl'] = $ngo_users->profile_image;
                    $i++;
                    
                }//end foreach
            }   
            $data['resp']['user'] = $user_list;
            $data = json_encode($data,JSON_NUMERIC_CHECK);
            echo $data;
        }
        else
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
    }//ngo_user
    //invite user
    public function invite_user($org_id=0)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        //check valid auth_token and is ngo member
        $valid_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_token))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "You are not authorized user.";
            header('HTTP/1.1 401 Unauthorized User');
            return;
        }
        //list all ngo user
        
        $role_id = $valid_token->role_id;
        $user_id = $valid_token->user_id;
        if($role_id==4)
        {
            $organisation = $this->Ngo_model->organization_exist($user_id);
        }
        else
        {
            //get organisation_id from ngo_user
            $organisation = $this->Ngo_model->organization_member_details($user_id);
        }
        if(!empty($organisation))
        {
            $ngo_id = $organisation->id;
            if($org_id!=$ngo_id)
            {
                header('HTTP/1.1 401 Unauthorized User');
                return;
            }   
            //blank validation
            $jsonArray = json_decode(file_get_contents('php://input'),true); 
            $insert['first_name'] = $first_name = isset($jsonArray['firstName'])?$jsonArray['firstName']:'';
            $insert['last_name'] = $last_name = isset($jsonArray['lastName'])?$jsonArray['lastName']:'';
            $insert['email'] = $email = isset($jsonArray['email'])?$jsonArray['email']:'';
            if(empty($insert['email']))
            {
                //validation error
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Please enter email.";           
                header('HTTP/1.1 400 Validation Error');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }   
            else{
                //check duplicate user 
                $dup_user = $this->User_model->user_duplicate($insert['email']);
                if(!empty($dup_user))
                {
                    $data['error'] = true;
                    $data['status'] = 409;
                    $data['message'] = "Email already exists.";         
                    header('HTTP/1.1 409 Conflict');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    return;
                }
                // don't let user kill the script by hitting the stop button 
                ignore_user_abort(true);                    
                // don't let the script time out 
                set_time_limit(0);                  
                // start output buffering
                ob_start();  
                usleep(1500);
                $insert['ngo_id'] = $ngo_id;
                $insert['role_id'] = $role_id = 5;
                $insert['random_code'] = $random_code = md5(uniqid(rand(),true));
                $insert['date_created'] = $insert['last_updated'] = date('Y-m-d H:i:s');
                $insert['status'] = 'Pending';
                //insert into invitation
                $this->User_model->send_invitation($insert);
                $data['error'] = false;
                $data['status'] = 200;
                $data['message'] = "Invitation has been sent successfully";                 
                echo json_encode($data,JSON_NUMERIC_CHECK);
                
                $size = ob_get_length(); 
                header("Content-Length: $size"); 
                header('Connection: close'); 
                ob_end_flush(); 
                ob_flush(); 
                flush(); // yes, you need to call all 3 flushes!
                //send invitation email     
                $this->invitationEmail($role_id,$email,$random_code,$first_name,$last_name);
                return;
            }
        }
        else
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
    }//invite_user
    protected function invitationEmail($role_id, $email, $random_code, $first_name, $last_name)
    {
        
        $this->config->load('email_setting');
        $config = array(
              'protocol' => $this->config->item('protocol'),
              'smtp_host' => $this->config->item('smtp_host'),
              'smtp_port' => $this->config->item('smtp_port'),
              'smtp_user' => $this->config->item('smtp_user'),
              'smtp_pass' => $this->config->item('smtp_pass'),
              'charset' => $this->config->item('charset'),
              'mailtype' => $this->config->item('mailtype')
          );
        //get mail format for Invitation
        $mail_format_data = $this->User_model->mail_format('Invitation');
        $link = $this->config->item('admin_invitation_url')."?roleId=".$role_id."&email=".$email."&token=".$random_code."&firstName=".$first_name."&lastName=".$last_name;
        if(!empty($mail_format_data))
        {
            $mail_subject = $mail_format_data->mail_subject;
            $body = $mail_format_data->body;
            $message1 = str_replace('$email',$email,$body);
            $message = str_replace('$link',$link,$message1);
        }   
        else
        {
            $mail_subject = 'Invitation to Join Karma World Wide';
            $message = "Hi, </br> We are pleased to invite you to join  Karma World Wide! </br>Please 
            <a href='".$link."'>Click Here</a> to accept the invitation";
        }   
        
        $this->load->library('email',$config);    
        $this->email->set_newline("\r\n");   
        $this->email->from('dnr@rkgtechllc.com');
        $this->email->to($email);           
        $this->email->subject($mail_subject);
        $this->email->message($message);    
        $this->email->set_mailtype("html");
        if($this->email->send()) {
           return true;
          } else {
           return false;
          } 
    }
}//end ngo

/* End of file ngo.php */
/* Location: ./application/controllers/ngo.php */