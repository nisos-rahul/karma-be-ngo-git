<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Audit extends Rest 
{   
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Audit_model');
        $this->load->model('Support_model');
        $this->load->model('User_model');
        $this->load->model('Activity_model');
        $this->load->model('Design_page_model');
    }

    public function get_audits() {

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
        if($role_id!=1)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset = ($page-1)*$limit;

        $audits = $this->Audit_model->get_audits($offset,$limit);
        $data = [];
        foreach($audits as $audit)
        {
            $a = [];
            $a['id'] = $audit->id;
            $user_id = $audit->user_id;
            $user_data = $this->Audit_model->user_info($user_id);
            if(!empty($user_data))
            {
                $a['user_id'] = $user_id;
                $a['first_name'] = $first_name = $user_data->first_name;
                $a['last_name'] = $last_name = $user_data->last_name;
                $a['role_id'] = $user_data->role_id;
                $a['role_authority'] = $user_data->role_authority;
                $a['username'] = $user_data->username;
            }
            $a['datetime'] = $audit->datetime;
            $a['entity'] = $entity = $audit->entity;
            $a['action'] = $action = $audit->action;
            
            if($entity!='login' && $entity!='logout')
            {
                $org_id = $audit->organisation_id;
                $org_data = $this->Audit_model->org_info($org_id);
                if(!empty($org_data))
                {
                    $a['organisation_id'] = $org_data->id;
                    $a['organisation_name'] = $organisation_name = $org_data->name;
                }
            }
            
            if($entity == 'NPO profile')
            {
                $a['old_data'] = $audit->old_data;
                $a['new_data'] = $audit->new_data;
                $a['Description'] = "'$organisation_name' NPO profile has been updated by $first_name $last_name";
            }
            elseif($entity == 'project' ||  $entity == 'project status')
            {
                $project_id = $audit->entity_id;
                $project_data = $this->Audit_model->project_details($project_id);
                if(!empty($project_data))
                {
                    $a['project_id'] = $project_id;
                    $a['project_name'] = $project_name =  $project_data->title;
                    $a['old_data'] = $audit->old_data;
                    $a['new_data'] = $audit->new_data;
                    if($entity != 'project status')
                        $a['Description'] = "'$project_name' project has been $action by $first_name $last_name";
                    else
                        $a['Description'] = "status for '$project_name' project has been updated by $first_name $last_name";
                }
            }
            elseif($entity == 'outcome')
            {
                
                $outcome_id = $audit->entity_id;
                $outcome_data = $this->Audit_model->outcome_details($outcome_id);
                if(!empty($outcome_data))
                {
                    $outcome_title = $outcome_data->goal;
                    $project_id = $outcome_data->project_id;
                    $project_data = $this->Audit_model->project_details($project_id);
                    if(!empty($project_data))
                    {
                        $a['project_id'] = $project_id;
                        $a['project_name'] = $project_name = $project_data->title;
                        $a['Description'] = "'$outcome_title' outcome from '$project_name' project has been deleted by $first_name $last_name";
                    }

                    $a['old_data'] = $audit->old_data;
                    $a['new_data'] = '';
                }
            }
            elseif($entity == 'member status' || $entity == 'member')
            {
                if($action == 'updated')
                {
                    $team_member_id = $audit->entity_id;
                    $team_member_data = $this->Audit_model->user_info($team_member_id);
                    if(!empty($team_member_data))
                    {
                        $a['team_member_id'] = $team_member_id;
                        $a['team_member_first_name'] = $team_member_first_name = $team_member_data->first_name;
                        $a['team_member_last_name'] = $team_member_last_name = $team_member_data->last_name;
                        $a['team_member_username'] = $team_member_data->username;
                    }
                    $a['old_data'] = $audit->old_data;
                    $a['new_data'] = $audit->new_data;

                    if($entity == 'member status')
                    {
                        $a['Description'] = "status of '$team_member_first_name $team_member_last_name' member has been updated by $first_name $last_name";
                    }
                }
                else
                {
                    $a['old_data'] = $audit->old_data;
                    $a['new_data'] = $audit->new_data;
                }
            }
            elseif($entity == 'activity' || $entity == 'activity image' || $entity == 'activity image caption' 
                    || $entity == 'activity video' || $entity == 'activity video caption')
            {
                $a['entity'] = str_replace("activity","update",$entity);
                
                if($entity == 'activity')
                    $activity_id = $audit->entity_id;
                elseif($entity == 'activity image' || $entity == 'activity image caption')
                {
                    $a['image_id'] = $img_id = $audit->entity_id;
                    $img_data = $this->Audit_model->activity_image_details($img_id);
                    if(!empty($img_data))
                        $activity_id = $img_data->project_report_id;
                }
                else
                {
                    $a['video_id'] = $video_id = $audit->entity_id;
                    $video_data = $this->Audit_model->activity_video_details($video_id);
                    if(!empty($video_data))
                        $activity_id = $video_data->project_report_id;
                }

                if(isset($activity_id))
                {
                    $activity_type = $this->Activity_model->get_project_report_type($activity_id);
                    $activity_type = $activity_type->project_report_type;

                    $activity_data = $this->Audit_model->activity_details($activity_id);
                    if(!empty($activity_data))
                    {
                        $a['activity_id'] = $activity_id;
                        $a['activity_name'] = $activity_name = $activity_data->report;
                        $a['activity_type'] = $activity_type;

                        $project_id = $activity_data->project_id;
                        $project_data = $this->Audit_model->project_details($project_id);
                        if(!empty($project_data))
                        {
                            $a['project_id'] = $project_id;
                            $a['project_name'] = $project_data->title;
                        }

                        if($activity_type=='Progress Update')
                        {
                            $outcome_id = $activity_data->goal_id;
                            $outcome_data = $this->Audit_model->outcome_details($outcome_id);
                            if(!empty($outcome_data))
                            {
                                $a['outcome_id'] = $outcome_id;
                                $a['outcome'] = $outcome_data->goal;
                            }
                        }

                        $a['old_data'] = $audit->old_data;
                        $a['new_data'] = $audit->new_data;
                        if($entity == 'activity')
                        {
                            $a['Description'] = "'$activity_name' update has been $action by $first_name $last_name";
                        }
                        elseif($action == 'added')
                        {
                            $entity = str_replace("activity ","",$entity);
                            $a['Description'] = "new $entity has been $action to '$activity_name' update by $first_name $last_name";
                        }
                        else
                        {
                            $entity = str_replace("activity ","",$entity);
                            $a['Description'] = "$entity from '$activity_name' update has been $action by $first_name $last_name";
                        }
                    }
                }
            }
            elseif($entity == 'login')
            {
                $a['entity'] = 'logged in';
                $a['action'] = '';
            }
            elseif($entity == 'logout')
            {
                $a['entity'] = 'logged out';
                $a['action'] = '';
                $a['Logout_session'] = $audit->logout_session;
            }
            elseif($entity == 'Design Page')
            {
                $a['entity'] = strtolower($entity);
                $a['action'] = strtolower($action);
                $a['version'] = $audit->entity_id;
                $a['old_data'] = $audit->old_data;
                $a['new_data'] = $audit->new_data;
            }
            elseif($entity == 'donor')
            {
                $a['old_data'] = $audit->old_data;
                $a['new_data'] = $audit->new_data;

                $donor_id = $audit->entity_id;
                $donor_data = $this->Audit_model->donor_details($donor_id);
                if(!empty($donor_data))
                {
                    $donor_name = $donor_data->name;
                    if($action=='created')
                        $a['Description'] = "new donor '$donor_name' for '$organisation_name' NPO has been $action by $first_name $last_name";
                    else
                        $a['Description'] = "'$donor_name' donor of '$organisation_name' NPO has been $action by $first_name $last_name";
                }
            }
            elseif($entity == 'donation setup')
            {
                $a['old_data'] = $audit->old_data;
                $a['new_data'] = $audit->new_data;
                $a['Description'] = "Donation setup for '$organisation_name' NPO has been $action by $first_name $last_name";
            }
            elseif($entity == 'donation application')
            {
                $a['old_data'] = $audit->old_data;
                $a['new_data'] = $audit->new_data;
                if($action=='added')
                    $a['Description'] = "New donation application for '$organisation_name' NPO has been added by $first_name $last_name";
                else
                    $a['Description'] = "Donation application for '$organisation_name' NPO has been updated by $first_name $last_name";
            }
            array_push($data,$a);
        }
        $count = $this->Audit_model->count_audits();
        $data1['resp']['count'] = $count->num;
        $data1['resp']['audits'] = $data;
        echo json_encode($data1,JSON_NUMERIC_CHECK);
        return;
    }

    public function store_audit() 
    {
        $jsonArray = json_decode(file_get_contents('php://input'),true);
        $user_id = isset($jsonArray['user_id'])?$jsonArray['user_id']:'';
        $ngo_id = isset($jsonArray['ngo_id'])?$jsonArray['ngo_id']:'';
        $entity = isset($jsonArray['entity'])?$jsonArray['entity']:'';
        $entity_id = isset($jsonArray['entity_id'])?$jsonArray['entity_id']:'';
        $action = isset($jsonArray['action'])?$jsonArray['action']:'';
        $old_data = isset($jsonArray['old_data'])?$jsonArray['old_data']:'';
        $new_data = isset($jsonArray['new_data'])?$jsonArray['new_data']:'';

        if($user_id == '')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "user_id field requered.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($ngo_id == '')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "ngo_id field requered.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }

        $user_data = $this->Audit_model->user_info((int)$user_id);

        $insert_audit['user_id'] = $user_id;
        $insert_audit['role_id'] = (int)$user_data->role_id;
        $insert_audit['organisation_id'] = $ngo_id;
        $insert_audit['datetime'] = date('Y-m-d H:i:s');
        $insert_audit['entity'] = $entity;
        $insert_audit['entity_id'] = $entity_id;
        $insert_audit['action'] = $action;

        $insert_audit['old_data'] = $old_data; 
        $insert_audit['new_data'] = $new_data;
        $insert_audit['is_active'] = "1";
    
        $this->db->insert('audits', $insert_audit);

        $data['error'] = false;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function publish_design_page() 
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
        $user_id = $valid_auth_token->user_id;
        if($role_id==7)
            $ngo_id = $this->input->get('ngoId');
        else
            $ngo_id = login_ngo_details($auth_token);
        
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();  
        usleep(1500);       
        $data['error'] = false;
        $data['message'] = "Processing.";
        echo json_encode($data,JSON_NUMERIC_CHECK);
        $size = ob_get_length(); 
        header("Content-Length: $size"); 
        header('Connection: close'); 
        ob_end_flush(); 
        ob_flush(); 
        flush();

        $jsonArray = json_decode(file_get_contents('php://input'), true);
        // $ngo_id = isset($jsonArray['ngoId'])?$jsonArray['ngoId']:'';
        // $user_id = isset($jsonArray['user_id'])?$jsonArray['user_id']:'';

        $ngo_data = $this->Audit_model->org_info($ngo_id);
        $branding_url = $ngo_data->branding_url;
        if($branding_url=='' ||$branding_url==null)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please set branding url.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }

        $version = isset($jsonArray['version'])?$jsonArray['version']:'';

        $check = $this->Design_page_model->check_version($ngo_id, $version);
        if(empty($check)) 
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Version Not Found.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        
        $user_data = $this->Audit_model->user_info((int)$user_id);
        $audit_info['user_id'] = $user_id;
        $audit_info['role_id'] = (int)$user_data->role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'Design Page';
        $audit_info['entity_id'] = $version;
        $audit_info['action'] = 'published';
        $this->Audit_model->publish_design_page($audit_info, $branding_url);

        $data['error'] = false;
        $data['status'] = 200;
        $data['message'] = "Successful.";
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }
}