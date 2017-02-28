<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Group extends Rest 
{
    public function __construct()
    {
        parent::__construct();      
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
        $this->load->model('Project_model');
        $this->load->model('Company_model');
        
        
    }
    public function list_groups()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');        
        $ngo_id = login_ngo_details($auth_token);
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;           
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $count = $this->Company_model->list_group_company_count($ngo_id, $query);
        $data['error'] = false;
        $group = array();
        $group_list = $this->Company_model->list_group_company($ngo_id, $query, $offset, $limit);
        if(!empty($group_list))
        {
            $g = 0;
            foreach($group_list as $group_details)
            {
                $group[$g]['id'] = $group_id = $group_details->id;
                $group[$g]['name'] = $group_details->name;
                $group[$g]['notification'] = $group_details->notification;
                //the list of members
                $member = array();
                $group_member = $this->Company_model->list_project_group_member($group_id);
                if(!empty($group_member))
                {
                    $m = 0;
                    foreach($group_member as $member_details)
                    {
                        $member[$m]['id'] = $member_details->id;
                        $member[$m]['name'] = $member_details->name;
                        $m++;
                    }
                }//if(!empty($group_member))
                $group[$g]['member'] = $member;
                $g++;
            }   //foreach($group_list as $group_details)        
        }   
        $data['resp']['count'] = $count->num;
        $data['resp']['group'] = $group;
        echo json_encode($data,JSON_NUMERIC_CHECK); 
        return;
    }
    public function send_notification($group_id)
    {
        //only performed by admin
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_ngo_admin = $this->Verification_model->valid_ngo_admin($auth_token);      
        if(empty($valid_ngo_admin))
        {
            header('HTTP/1.1 401 Unauthorized User');
            exit;
        }
        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $message = isset($jsonArray['notification'])?$jsonArray['notification']:'';
        if(empty($message))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter message.";
            header('HTTP/1.1 400 Validation Error.');
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
        //check project of group is active or not
        $project_details = $this->Company_model->group_project_details($group_id);
        if(empty($project_details))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "The project of this group is inactive.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $project_name = $project_details->title;
        
        $data['error'] = false;
        $data['status'] = 200;
        $data['message'] = "Notification has been sent successfully";
        echo json_encode($data,JSON_NUMERIC_CHECK);
        $size = ob_get_length(); 
        header("Content-Length: $size"); 
        header('Connection: close'); 
        ob_end_flush(); 
        ob_flush(); 
        flush(); // yes, you need to call all 3 flushes!
        //update group table with current message
        $update['notification'] = $message;
        $this->Company_model->update_group($update, $group_id);
        //send this message to all members  
        $this->send_notification_mail($group_id, $message, $project_name);
        return;
    }
    protected function send_notification_mail($group_id, $message,
        $project_name)
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
        $mail_subject = $project_name." : Notification";            
        //list of members
        $group_member = $this->Company_model->list_project_group_member($group_id);
        if(!empty($group_member))
        {
            foreach($group_member as $member)
            {
                
                $company_id = $member->id;
                //company email
                $company_details = $this->Company_model->company_email($company_id);
                if(!empty($company_details))
                {
                    $email = $company_details->email;
                    $this->load->library('email',$config);    
                    $this->email->set_newline("\r\n");   
                    $this->email->from('dnr@rkgtechllc.com');       
                    $this->email->subject($mail_subject);                   
                    $this->email->message($message);    
                    $this->email->set_mailtype("html");
                    $this->email->to($email);
                    $this->email->send();                   
                }//if(!empty($company_details))
            }//foreach($group_member as $member)
        }//if(!empty($group_member))    

        return; 
    }//send_notification_mail($group_id)
}//end group
/* End of file group.php */
/* Location: ./application/controllers/group.php */