<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Invitation extends Rest 
{
    public function __construct()
    {
        parent::__construct();      
        
        $this->load->model('Verification_model');
        $this->load->model('User_model');
        $this->load->model('Project_model');
        $this->load->model('Company_model');
        $this->load->model('Ngo_model');
    }
    public function accept_invitation()
    {
        //this url will be called from email hence sending only status without message
        $id = ($this->input->get('id'))?$this->input->get('id'):'';         
        //not proper info
        if(empty($id))
        {           
            header('HTTP/1.1 404 Not Found.');
            return;
        }
        $invitationEntry = $this->Company_model->project_company_details_by_id($id);
        if(empty($invitationEntry))
        {           
            header('HTTP/1.1 404 Not Found.');
            return;
        }
        //if status requested then only can accept invitation
        if($invitationEntry->relationship_status=='Requested')
        {           
            $project_id = $invitationEntry->project_id;
            $company_id = $invitationEntry->company_id;
            $ngo_id = $invitationEntry->ngo_id;
            //check valid project id
            $project_details = $this->Project_model->project_details($project_id);
            if(empty($project_details))
            {
                header('HTTP/1.1 404 Not Found.');
                return;
            }   
            //valid ngo and company
            $valid_ngo = $this->Ngo_model->organization_details($ngo_id);
            if(empty($valid_ngo))
            {
                header('HTTP/1.1 404 Not Found.');
                return;
            }
            //valid ngo and company
            $valid_company = $this->Ngo_model->organization_details($company_id);
            if(empty($valid_company))
            {
                header('HTTP/1.1 404 Not Found.');
                return;
            }
            //update relationship_status to Approved
            $update['last_updated'] = date('Y-m-d H:i:s');
            $update['relationship_status'] = 'Approved';
            $this->Company_model->update_company_ngo($update,$company_id,$ngo_id,$project_id);
            $project_name = $project_details->title;
            //check group created or not if not then create otherwise add company to group
            $group_exists = $this->Company_model->check_group_exists($project_id);
            if($group_exists)
            {
                //add entry to company_group
                $insert['project_group_id'] = $group_exists->id;
                $insert['company_id'] = $company_id;
                $this->Company_model->add_group_company($insert);
            }
            else
            {
                //create group and then add to company group
                $group_insert['project_id'] = $project_id;
                $group_name = str_replace(' ','_',$project_name);
                $group_insert['name'] = $group_name."_Accepted";
                $group_insert['is_active'] = 1;
                $group_insert['date_created'] = $group_insert['last_updated'] = date('Y-m-d H:i:s');
                $insert['project_group_id'] = $this->Company_model->create_group($group_insert);
                $insert['company_id'] = $company_id;
                $this->Company_model->add_group_company($insert);
            }   
            
            return;
        }
        else
        {
            header('HTTP/1.1 404 Not Found.');
            return;
        }   
    }   
    public function reject_invitation()
    {
        $id = ($this->input->get('id'))?$this->input->get('id'):'';
        
        //not proper info
        if(empty($id))
        {
            header('HTTP/1.1 404 Not Found.');
            return;
        }
        $invitationEntry = $this->Company_model->project_company_details_by_id($id);
        if(empty($invitationEntry))
        {
            header('HTTP/1.1 404 Not Found.');
            return;
        }
        //if status requested then only can reject invitation
        if($invitationEntry->relationship_status=='Requested')
        {
            $project_id = $invitationEntry->project_id;
            $company_id = $invitationEntry->company_id;
            $ngo_id = $invitationEntry->ngo_id;
            //update relationship_status to Declined
            $update['relationship_status'] = 'Declined';
            $update['last_updated'] = date('Y-m-d H:i:s');
            $this->Company_model->update_company_ngo($update,$company_id,$ngo_id,$project_id);
            return;
        }
        else
        {
            header('HTTP/1.1 404 Not Found.');
            return;
        }   
    }//reject_invitation    
}   
    