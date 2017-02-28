<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Company extends Rest 
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
    public function company_list()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $page = ($this->input->get('page'))?$this->input->get('page'):'';
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):'';
        $offset=($page-1)*$limit;   
        $list = $this->Company_model->company_list($query, $offset, $limit);
        $data['error'] = false;  
        $data['resp'] = array();
        $count = $this->Company_model->company_count($query);
        $data['resp']['count'] = $count->num;
        $company = array();
        if(!empty($list))
        {
            $i =0;
            foreach($list as $company_details)
            {
                $company[$i]['id'] = $company_details->id;
                $company[$i]['name'] = $company_details->name;
                $i++;
            }
        }
        $data['resp']['company'] = $company;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
        
    }
    public function invitation_list()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;           
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $user_id = login_user_details($auth_token);
        $user_data = $this->User_model->user_info($user_id);
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
        if($valid_auth_token->role_id==7)
            $ngo_id = $this->input->get('ngoId');
        else
            $ngo_id = login_ngo_details($auth_token);
        if(empty($ngo_id))
        {
            header('HTTP/1.1 401 Unauthorized User');
            exit;
        }
        //fetch all projects of ngo
        $project_list = $this->Project_model->project_list($ngo_id, $query, 1, $offset, $limit);
        $data['resp']['count']  = $this->Project_model->project_list_count($ngo_id, $query, 1)->num;
        $project_data = array();
        if(!empty($project_list))
        {
            $p = 0;
            foreach($project_list as $projects)
            {
                $project_data[$p]['id']  = $project_id = $projects->id;
                $project_data[$p]['name']  = $projects->title;
                //company list
                $company_list = $this->Project_model->company_project_list($ngo_id, $project_id);
                $temp = array();
                $temp2 = array();
                $temp3 = array();
                if(!empty($company_list))
                {
                    $t=0;
                    foreach($company_list as $company)
                    {
                        $relationship_status = $company->relationship_status;
                        if($relationship_status=="Approved")
                        {
                            $temp[$t]['id'] = $company->id;
                            $temp[$t]['name'] = $company->name;
                        }   
                        else
                        if($relationship_status=="Requested")
                        {
                            $temp2[$t]['id'] = $company->id;
                            $temp2[$t]['name'] = $company->name;
                        }
                        else
                        if($relationship_status=="Declined")
                        {
                            $temp3[$t]['id'] = $company->id;
                            $temp3[$t]['name'] = $company->name;
                        }
                        $t++;
                    }//company list
                }//if company blank
                
                $project_data[$p]['accepted'] = $temp;
                $project_data[$p]['requested'] = $temp2;
                $project_data[$p]['declined'] = $temp3;
                $p++;
            }//foreach project
        }       
        $data['resp']['project'] = $project_data;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//invitation_list  
    //send invitation to company
    public function invite_company($project_id)
    {
        //this action can be performed only by ngo admin
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        //if ngo admin then can update status
        $valid_ngo_admin = $this->Verification_model->valid_ngo_admin($auth_token);
         //only ngo can perform this action
        if(empty($valid_ngo_admin))
        {           
            header('HTTP/1.1 401 Unauthorized');
            return;     
        }
        //check ngo from token
        $admin_id = $valid_ngo_admin->user_id;
        $organisation = $this->Ngo_model->organization_exist($admin_id);
        if(!empty($organisation))
        {
            $ngo_id = $organisation->id;
        }
        else{
            header('HTTP/1.1 401 Unauthorized');
            return; 
        }
        //check active project or not
        $project_exists = $this->Company_model->active_ngo_project_details($project_id, $ngo_id);
        if(empty($project_exists))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find Project using id mentioned.";
            header('HTTP/1.1 404 Not found.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $company = isset($jsonArray['company'])?$jsonArray['company']:array();
        if(count($company)==0)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please select company.";
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
        $final_company_id = array();
        for($k=0;$k<count($company);$k++)
        {
            $company_id  = $company[$k];
            if(!empty($company_id))
            {
                //check entry in company_ngo
                $comp_ngo  = $this->Company_model->project_company_details($company_id, $ngo_id, $project_id);
                if(!empty($comp_ngo))
                {
                    $relationship_status = $comp_ngo->relationship_status;
                    if($relationship_status!='Approved')
                    {
                        $final_company_id[] = $company_id;
                        //update status to requested
                        $update['date_created'] = $update['last_updated'] = date('Y-m-d H:i:s');
                        $update['relationship_status'] = 'Requested';
                        $this->Company_model->update_company_ngo($update, $company_id, $ngo_id, $project_id);
                    }//if already connected do not send request
                }//if not empty then update
                else{
                    $final_company_id[] = $insert['company_id'] = $company_id;
                    $insert['ngo_id'] = $ngo_id;
                    $insert['project_id'] = $project_id;
                    $insert['relationship_status'] = 'Requested';
                    $insert['date_created'] = $insert['last_updated'] = date('Y-m-d H:i:s');
                    $this->Company_model->insert_company_ngo($insert);
                }
            }           
        }//foreach
        
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
        
        $final_company = implode(',',$final_company_id);
        $this->send_invitation_user($project_id,$ngo_id,$final_company);
        return;
    }//
    protected function send_invitation_user($project_id, $ngo_id, $final_company)
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
        $mail_format_data = $this->User_model->mail_format('ProjectInvitationMailToCompany');
        if(!empty($mail_format_data))
        {
            $mail_subject = $mail_format_data->mail_subject;
            $mail_body = $mail_format_data->body;
        }
        else
        {
            $mail_subject = $this->config->item('project_invitation_mail_subject');
            $mail_body = $this->config->item('project_invitation_body');
        }       
        $final_company = explode(',',$final_company);
        for($i=0;$i<count($final_company);$i++)
        {
            //fetch id from dba_close
            $company_id=$final_company[$i];
            $comapny_ngo = $this->Company_model->project_company_details($company_id, $ngo_id, $project_id);
            $id = $comapny_ngo->id;
            $link = $this->config->item('corporate_url');           
            $ngo_name = $this->Company_model->organisation_name($ngo_id)->name;
            $email = $this->Company_model->organisation_name($company_id)->email;
            $project_name = $this->Project_model->project_details($project_id)->title;
            $email_name = strstr($email, '@', true); 
            $message1 = str_replace('$ngoname',$ngo_name,$mail_body);
            $message2 = str_replace('$projectname',$project_name,$message1);
            $message3 = str_replace('$email',$email_name,$message2);
            $message = str_replace('$link',$link,$message3);
            $subject = str_replace('$projectname',$project_name,$mail_subject);
            $this->load->library('email',$config);    
            $this->email->set_newline("\r\n");   
            $this->email->from('dnr@rkgtechllc.com');
            $this->email->to($email);           
            $this->email->subject($subject);
            $this->email->message($message);    
            $this->email->set_mailtype("html");
            $this->email->send();
        }//for          
    }//send_invitation_user
}//end project
/* End of file project.php */
/* Location: ./application/controllers/project.php */