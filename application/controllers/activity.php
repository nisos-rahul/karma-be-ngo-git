<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Activity extends Rest 
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
        $this->load->model('Project_model');
        $this->load->model('Activity_model');
        $this->load->model('Audit_model');
        $this->load->helper('social');
    }
    public function list_outcomes($project_id)
    {       
        $project_id = (int) $project_id;        
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $offset=($page-1)*$limit;   
        $outcome_data = $this->Project_model->outcome_list($project_id,$query,$offset,$limit);
        $count = $this->Project_model->active_outcome_count($project_id,$query);
        $data['error'] = false;
        $data['resp']['count'] = $count->num;
        $outcome = array();
        $g = 0;
        foreach($outcome_data as $outcome_details)
        {
            $outcome[$g]['id'] = $outcome_details->id;
            $outcome[$g]['outcome'] = $outcome_details->goal;
            $outcome[$g]['currentOutcome'] = $outcome_details->goal_achieved;
            $outcome[$g]['goalOutcome'] = $outcome_details->goal_target;
            $outcome[$g]['description'] = $outcome_details->description;
            $g++;
        }//foreach
        $data['resp']['outcome'] = $outcome;
        $data = json_encode($data,JSON_NUMERIC_CHECK);
        echo $data;
        return;
    }//list_outcome
    public function outcome_details($outcome_id)
    {
        $outcome_id = (int) $outcome_id;
        $outcome_details = $this->Project_model->outcome_details($outcome_id);
        if(!empty($outcome_details))
        {
            $data['error'] = false;
            $data['resp']['id'] = $outcome_details->id;
            $data['resp']['outcome'] = $outcome_details->goal;
            $data['resp']['currentOutcome'] = $outcome_details->goal_achieved;
            $data['resp']['goalOutcome'] = $outcome_details->goal_target;
            $data['resp']['description'] = $outcome_details->description;
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find outcome using id mentioned.";
        }   
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;     
    }
    public function details_update($activity_id)
    {
        $method = $this->input->get('method');
        if($this->input->server('REQUEST_METHOD')=="POST")
        {
            if($method=="PUT")
            {
                //update activity call
                $data = $this->update_activity($activity_id);
                if(isset($data['resp']['id']))
                {
                    $activityId = $data['resp']['id'];              
                    $projectId = $data['resp']['projectId'];
                    echo json_encode($data,JSON_NUMERIC_CHECK);
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
                    $user_id = $valid_auth_token->user_id;
                    $this->postActivityFacebook($activityId,$projectId,$user_id);
                    //list of companies of projects with social details
                    if($valid_auth_token->role_id==7)
                        $ngo_id = $this->input->get('ngoId');
                    else
                        $ngo_id = login_ngo_details($auth_token);
                    $company_list = $this->Project_model->company_project_list_approved($ngo_id,$projectId);
                    if(!empty($company_list))
                    {
                        foreach($company_list as $company)
                        {
                            $user_id = $company->user_id;
                            $this->postActivityFacebook($activityId,$projectId,$user_id);
                        }
                    }
                }
                else
                {
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                }
                return;
            }
            else
            {
                header('HTTP/1.1 404 Not Found');
                return;
            }       
        }
        else
        {   
            //details of activity
            $data = $this->activity_details($activity_id);
            $data = json_encode($data,JSON_NUMERIC_CHECK);
            echo $data;
            return;
        }
    }//details_update
    public function add_list()
    {
        //check call for create activity or for list of activities
        if($this->input->server('REQUEST_METHOD')=="GET")
        {
            $data = $this->list_activity();
            $data = json_encode($data,JSON_NUMERIC_CHECK);
            echo $data;
            return;
        }
        else
        {
            $data = $this->add();   
            if(isset($data['resp']['id']))
            {
                $activityId = $data['resp']['id'];              
                $projectId = $data['resp']['projectId'];
                echo json_encode($data,JSON_NUMERIC_CHECK);
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
                $user_id = $valid_auth_token->user_id;
                $this->postActivityFacebook($activityId,$projectId,$user_id);
                //list of companies of projects with social details
                if($valid_auth_token->role_id==7)
                    $ngo_id = $this->input->get('ngoId');
                else
                    $ngo_id = login_ngo_details($auth_token);
                $company_list = $this->Project_model->company_project_list_approved($ngo_id,$projectId);
                if(!empty($company_list))
                {
                    foreach($company_list as $company)
                    {
                        $user_id = $company->user_id;
                        $this->postActivityFacebook($activityId,$projectId,$user_id);
                    }
                }
            }
            else
            {
                echo json_encode($data,JSON_NUMERIC_CHECK);
            }       
            return;
        }
    }
    protected function add()
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

        $insert['user_id'] = $user_id = $valid_auth_token->user_id;
        $role_id = $valid_auth_token->role_id;
        if($role_id==7)
        {
            $ngo_id = $this->input->get('ngoId');
        }
        else
        {
            $ngo_id = login_ngo_details($auth_token);
        }

        $user_data = $this->User_model->user_info($user_id);
        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $project_report_type = $insert['project_report_type'] = isset($jsonArray['projectReportType'])?$jsonArray['projectReportType']:'';

        $project_id = $insert['project_id'] = isset($jsonArray['projectId'])?$jsonArray['projectId']:'';
        $description = $insert['description'] = isset($jsonArray['description'])?$jsonArray['description']:'';
        $title =  $insert['report'] = isset($jsonArray['title'])?$jsonArray['title']:'';
        $facebook_status = isset($jsonArray['facebookStatus'])?$jsonArray['facebookStatus']:'';
        $twitter_status = isset($jsonArray['twitterStatus'])?$jsonArray['twitterStatus']:'';
        
        if(empty($project_id))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please select Project.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if($title=='')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter update title.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }   
        $project_details = $this->Project_model->project_ngo($ngo_id,$project_id);
        if(empty($project_details))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Given project id does not belong to this user.";
            header('HTTP/1.1 404 Not Found');
            return $data;
        }

        if($project_report_type=='Progress Update')
        {
            $outcome_id = $insert['goal_id'] = isset($jsonArray['outcomeId'])?$jsonArray['outcomeId']:'';
            $current_outcome = $insert['current_goal'] = isset($jsonArray['newOutcome'])?$jsonArray['newOutcome']:'';
            $goal_outcome = isset($jsonArray['goalOutcome'])?$jsonArray['goalOutcome']:'';
            $insert['new_progress'] = isset($jsonArray['newProgress'])?$jsonArray['newProgress']:'';
            if(empty($outcome_id))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Please select Outcome.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($current_outcome=='')
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Please enter current outcome.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($current_outcome>$goal_outcome)
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "New total should not be Greater than Goal.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($current_outcome<0)
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "New total should not be negative value.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            $outcome_details = $this->Project_model->project_goal_check($outcome_id,$project_id);
            if(empty($outcome_details))
            {
                $data['error'] = true;
                $data['status'] = 404;
                $data['message'] = "Operation failed to find outcome using id mentioned.";
                header('HTTP/1.1 404 Not Found');
                return $data;
            }
            //update current goal in project table also
            $project_goal_update['goal_achieved'] = $current_outcome;
            $this->Project_model->update_outcomes($project_goal_update,$outcome_id);
        }
        elseif($project_report_type=='Project Highlight')
        {
            $outcome_id = $insert['goal_id'] = '';
            $current_outcome = $insert['current_goal'] = '';
            $goal_outcome = '';
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Invalid update type.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }

        //xss clean
        $insert = $this->security->xss_clean($insert);

        $imageUrls =  isset($jsonArray['imageUrls'])?$jsonArray['imageUrls']:array();
        $videoUrls =  isset($jsonArray['videoUrls'])?$jsonArray['videoUrls']:array();
        $imageUrls = $this->security->xss_clean($imageUrls);
        $videoUrls = $this->security->xss_clean($videoUrls);
        if(count($imageUrls)>4)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "You can not upload more than 4 images.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if(count($videoUrls)>4)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "You can not upload more than 4 videos.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }

        $insert['date_created'] = $insert['last_updated'] = date('Y-m-d H:i:s');

        $activity_id = $this->Activity_model->create_activity($insert);

        //check image uploaded or not
        $image_insert['date_created'] = $image_insert['last_updated'] = date('Y-m-d H:i:s');
        $image_insert['is_active'] = 1;
        $image_insert['project_report_id'] = $activity_id;
        foreach($imageUrls as $image_Urls)
        {
            $image_insert['url'] = $url = $image_Urls['url'];
            $image_insert['caption'] = isset($image_Urls['caption'])?$image_Urls['caption']:"";
            $image_insert['thumb_url'] = isset($image_Urls['thumbUrl'])?$image_Urls['thumbUrl']:"";
            $this->Activity_model->add_report_image($image_insert);
        }//image save
        //check video uploaded or not
        $video_insert['date_created'] = $video_insert['last_updated'] = date('Y-m-d H:i:s');
        $video_insert['is_active'] = 1;
        $video_insert['project_report_id'] = $activity_id;
        foreach($videoUrls as $video_urls)
        {
            $video_insert['url'] = $url = $video_urls['url'];
            $video_insert['caption'] = $url = isset($video_urls['caption'])?$video_urls['caption']:"";
            $video_insert['thumb_url'] = isset($video_urls['thumbUrl'])?$video_urls['thumbUrl']:"";
            $this->Activity_model->add_report_video($video_insert);
        }//image save   
        $data = $this->activity_details($activity_id);  
        //audit create_activity
        $audit_info['user_id'] = $user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'activity';
        $audit_info['entity_id'] = $activity_id;
        $audit_info['action'] = 'created';
        $audit_id = $this->Audit_model->create_audit($data['resp'], $audit_info);
        //audit create_activity
        return $data;
    }//add
    protected function activity_details($activity_id)
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
        if($role_id==7)
            $ngo_id = $this->input->get('ngoId');
        else
            $ngo_id = login_ngo_details($auth_token);

        $activity_type = $this->Activity_model->get_project_report_type($activity_id);
        if(empty($activity_type))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find update using id mentioned.";   
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $activity_type = $activity_type->project_report_type;
        if($activity_type=='Progress Update')
        {
            $activity_data = $this->Activity_model->activity_details($activity_id);
            $data['error'] = false;
            $data['resp']['id'] = $activity_data->id;
            $data['resp']['projectReportType'] = $activity_data->project_report_type;
            $data['resp']['projectId'] = $project_id = $activity_data->project_id;
            $data['resp']['projectName'] = $activity_data->title;
            $data['resp']['outcomeId'] = $activity_data->goal_id;
            $data['resp']['outcomeName'] = $activity_data->goal;
            $data['resp']['userId'] = $activity_data->user_id;
            $data['resp']['newOutcome'] = $activity_data->current_goal;
            $data['resp']['currentOutcome'] = (int)$activity_data->current_goal-(int)$activity_data->new_progress;
            $data['resp']['newProgress'] = $activity_data->new_progress;
            $data['resp']['goalOutcome'] = $activity_data->goal_target;
            $data['resp']['title'] = $activity_data->report;
            $data['resp']['description'] = $activity_data->description;
            $is_active = $activity_data->is_deleted;
            if (ord($is_active)==1 || $is_active==1)
                $data['resp']['status'] = false;  //it is project status 
            else
                $data['resp']['status'] = true;  //it is project status 
            
        }
        elseif($activity_type=='Project Highlight')
        {
            $activity_data = $this->Activity_model->activity_highlight_details($activity_id);
            $data['error'] = false;
            $data['resp']['id'] = $activity_data->id;
            $data['resp']['projectReportType'] = $activity_data->project_report_type;
            $data['resp']['projectId'] = $project_id = $activity_data->project_id;
            $data['resp']['projectName'] = $activity_data->title;
            $data['resp']['userId'] = $activity_data->user_id;
            $data['resp']['title'] = $activity_data->report;
            $data['resp']['description'] = $activity_data->description;
            $is_active = $activity_data->is_deleted;
            if (ord($is_active)==1 || $is_active==1)
                $data['resp']['status'] = false;  //it is project status 
            else
                $data['resp']['status'] = true;  //it is project status 
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Invalid update type.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }

        $project_ngo_check = $this->Project_model->project_ngo($ngo_id,$project_id);
        if(empty($project_ngo_check))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $image_data = $this->Activity_model->list_report_images($activity_id);
        $image_urls = array();
        $i=0;
        if(!empty($image_data))
        {
            foreach($image_data as $images)
            {
                $image_urls[$i]['id'] = $images->id;
                $image_urls[$i]['url'] = $images->url;
                $image_urls[$i]['caption'] = $images->caption;
                $image_urls[$i]['thumbUrl'] = $images->thumb_url;
                $i++;
            }
        }
        $data['resp']['imageUrls'] = $image_urls;

        $video_data = $this->Activity_model->list_report_videos($activity_id);
        $video_urls = array();
        $k=0;
        if(!empty($video_data))
        {
            foreach($video_data as $videos)
            {
                $video_urls[$k]['id'] = $videos->id;
                $video_urls[$k]['url'] = $videos->url;
                $video_urls[$k]['caption'] = $videos->caption;
                $video_urls[$k]['thumbUrl'] = $videos->thumb_url;
                $k++;
            }
        }
        $data['resp']['videoUrls'] = $video_urls;
        return $data;
    }
    protected function list_activity()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);   
        if(empty($valid_auth_token))
        {
            header('HTTP/1.1 401 Unauthorized');
            return; 
        }       
        //list all ngo user
        $role_id = $valid_auth_token->role_id;
        
        if($role_id==4 || $role_id==5)
            $ngo_id = login_ngo_details($auth_token);
        else
            $ngo_id = $this->input->get('ngoId');
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;           
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $status = ($this->input->get('status'))?$this->input->get('status'):true;

        $total_count = $this->Activity_model->activity_count('',$offset,$limit,$ngo_id,'',$status);
        $count = $this->Activity_model->activity_count($query,$offset,$limit,$ngo_id,'',$status);
        $activity_list = $this->Activity_model->list_activity($query,$offset,$limit,$ngo_id,'',$status);
        $data['error'] = false;
        $data['resp']['total_count'] = $total_count->num;
        $data['resp']['count'] = $count->num;
        $activity_data = array();
        if(!empty($activity_list))
        {
            $i = 0;
            foreach($activity_list as $activity)
            {
                $outcomes = array();
                $activity_data[$i]['id'] = $activity->id;
                $activity_data[$i]['projectReportType'] = $activity->project_report_type;
                $activity_data[$i]['projectId'] = $activity->project_id;
                $activity_data[$i]['projectName'] = $activity->title;
                $activity_data[$i]['activityTitle'] = $activity->report;
                $activity_data[$i]['description'] = $activity->description;
                $activity_data[$i]['dateCreated'] = $activity->date_created;
                $activity_data[$i]['lastUpdated'] = $activity->last_updated;                
                $user_id = $activity->user_id;
                $member=array();
                $member[0]['id'] = $user_id;
                $member[0]['name'] = $activity->first_name." ".$activity->last_name;
                $user_profile = $this->User_model->profile_info($user_id);
                if(!empty($user_profile))
                    $member[0]['imageUrl'] = $user_profile->image_url;
                else
                    $member[0]['imageUrl'] = "";
                $activity_data[$i]['member'] = $member;
                $is_active = $activity->is_deleted;
                if(ord($is_active)==1 || $is_active==1)
                    $activity_data[$i]['status'] = false;
                else
                    $activity_data[$i]['status'] = true;        
                
                $project_is_active = $activity->is_active;
                if(ord($project_is_active)==1 || $project_is_active==1)
                    $activity_data[$i]['projectStatus'] = true;
                else
                    $activity_data[$i]['projectStatus'] = false;

                $activity_type = $this->Activity_model->get_project_report_type($activity->id);
                $activity_type = $activity_type->project_report_type;
                if($activity_type=='Progress Update')
                {
                    $activity_data[$i]['newProgress'] = $activity->new_progress;
                    $activity_goal_data = $this->Activity_model->activity_outcome_details($activity->id);
                    if(empty($activity_goal_data))
                    {
                        $activity_data[$i]['outcomeStatus'] = false;
                    }
                    else
                    {
                        $outcomes[0]['id'] = $activity_goal_data->goal_id;
                        $outcomes[0]['outcome'] = $activity_goal_data->goal;
                        $outcomes[0]['currentOutcome'] = $activity_goal_data->current_goal;
                        $outcomes[0]['goalOutcome'] = $activity_goal_data->goal_target;
                        $activity_data[$i]['outcomes'] = $outcomes;
                        $activity_data[$i]['outcomeStatus'] = true;
                    }
                }
                elseif($activity_type=='Project Highlight')
                {
                    $activity_data[$i]['outcomeStatus'] = true;
                }
                $i++;   
            }
        }
        $data['resp']['activity']  = $activity_data;
        return $data;
    }

    public function update_status($activity_id)
    {       
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        //if ngo admin then can update status
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
         //only ngo can perform this action
        $role_id = $valid_auth_token->role_id;
        $user_id = $valid_auth_token->user_id;
        if($role_id==5)
        {           
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $activity_exists = $this->Activity_model->active_activity_details($activity_id);
        if(empty($activity_exists))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find update using id mentioned.";   
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }   

        $outcome_id = $activity_exists->goal_id;
        //outcome details to compare current outcome with this activity
        $outcome_details = $this->Project_model->outcome_details($outcome_id);
        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $status = isset($jsonArray['status'])?$jsonArray['status']:false;
        $status = (bool) $status;
        if($status==false)
        {
            //check current outcome of project 
            if(!empty($outcome_details))
            {

                $newer_activities = $this->Activity_model->newer_activity_details($outcome_id,$activity_id);
                if(!empty($newer_activities))
                {
                    foreach ($newer_activities as $newer_activity) {
                        $newer_activity_id = $newer_activity->id;
                        $update_newer_activity['current_goal'] = $newer_activity->current_goal - $activity_exists->new_progress;
                        $update_newer_activity['last_updated'] = date('Y-m-d H:i:s');
                        $this->Activity_model->update_activity($update_newer_activity,$newer_activity_id);
                    }
                }

                $update_outcome['goal_achieved'] = $outcome_details->goal_achieved - $activity_exists->new_progress;
                $update_outcome['last_updated'] = date('Y-m-d H:i:s');

                $this->Project_model->update_outcomes($update_outcome,$outcome_id); 
            }//if(!empty($outcome_details))
            $update['is_deleted'] = true;
            $update['deleted_at'] = $update['last_updated'] = date('Y-m-d H:i:s');
        }
        else
        {           
            //if activate previous entry then if this activity has max current goal then update 
            //goal table
            if(!empty($outcome_details))
            {
                $currentOutcomeProject = $outcome_details->goal_achieved;
                if($currentOutcomeProject<$activity_exists->current_goal)
                {
                    $update_goal['goal_achieved'] = $activity_exists->current_goal;
                    $this->Project_model->update_outcomes($update_goal,$outcome_id);    
                }
            }//if(!empty($outcome_details))
            $update['is_deleted'] = false;
            $update['last_updated'] = date('Y-m-d H:i:s');
        }
        $this->Activity_model->update_activity($update,$activity_id);

        //audit delete_activity
        $audit_info['user_id'] = $user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $this->input->get('ngoId');
        $audit_info['entity'] = 'activity';
        $audit_info['entity_id'] = $activity_id;
        $audit_info['action'] = 'deleted';
        $old_data = $this->activity_details($activity_id);
        $audit_id = $this->Audit_model->delete_audit($old_data['resp'],$audit_info);
        if($audit_id!='false')              
            $this->Audit_model->activate_audit($audit_id);
        //audit delete_activity

        $data = $this->activity_details($activity_id);
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//update_status
    public function delete_video($activity_id,$video_id)
    {
        $method = $this->input->get('method');
        
        $videoDetails=$this->Activity_model->activity_video_details($activity_id,$video_id);
        if(empty($videoDetails))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find video using id mentioned.";    
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($method=='DELETE')
        {
            $update['is_deleted']  = true;
            $update['deleted_at']  = date('Y-m-d H:i:s');

            //audit delete_video
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            $audit_info['user_id'] = $valid_auth_token->user_id;
            $audit_info['role_id'] = $valid_auth_token->role_id;
            $audit_info['org_id'] = $this->input->get('ngoId');
            $audit_info['entity'] = 'activity video';
            $audit_info['entity_id'] = $video_id;
            $audit_info['action'] = 'deleted';
            $audit_id = $this->Audit_model->delete_audit($videoDetails,$audit_info);

            $this->Activity_model->update_report_video($update,$video_id);
        
            $this->Audit_model->activate_audit($audit_id);
            //audit delete_video
            
            $data['error'] = false;
            $data['status'] = 200;
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        elseif($method=="PUT")
        {
            $jsonArray = json_decode(file_get_contents('php://input'),true);            
            $update['url'] = isset($jsonArray['videoUrl'])?$jsonArray['videoUrl']:'';
            $update['caption'] = isset($jsonArray['caption'])?$jsonArray['caption']:'';
            $update['thumb_url'] = isset($jsonArray['thumbUrl'])?$jsonArray['thumbUrl']:'';
            $update['last_updated'] = date('Y-m-d H:i:s');

            //xss clean         
            $update = $this->security->xss_clean($update);

            if(empty($update['url']))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Validation Error.";         
                header('HTTP/1.1 400 Validation Error');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }

            //audit change_video_caption
            $old_data['id'] = $videoDetails->id;
            $old_data['videoUrl'] = $videoDetails->url;
            $old_data['url'] = $videoDetails->url;
            $old_data['caption'] = $videoDetails->caption;
            $old_data['thumbUrl'] = $videoDetails->thumb_url;
            $old_data['type'] = 'video';
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            $audit_info['user_id'] = $valid_auth_token->user_id;
            $audit_info['role_id'] = $valid_auth_token->role_id;
            $audit_info['org_id'] = $this->input->get('ngoId');
            $audit_info['entity'] = 'activity video caption';
            $audit_info['entity_id'] = $video_id;
            $audit_info['action'] = 'updated';
            $audit_id = $this->Audit_model->update_audit_3($old_data,$jsonArray,$audit_info);

            $this->Activity_model->update_report_video($update,$video_id);

            if($audit_id!='false')              
                $this->Audit_model->activate_audit($audit_id);
            //audit change_video_caption    

            $data['error'] = false;
            $data['resp']['id'] = $video_id;
            $data['resp']['url'] = $update['url'];
            $data['resp']['caption'] = $update['caption'];
            $data['resp']['thumbUrl'] = $update['thumb_url'];
            $data['resp']['lastUpdated'] = $update['last_updated'];
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        else
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }   
    }//delete_video($activity_id,$video_id)
    public function delete_image($activity_id,$image_id)
    {
        $method = $this->input->get('method');      
        $imageDetails=$this->Activity_model->activity_image_details($activity_id,$image_id);
        if(empty($imageDetails))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find image using id mentioned.";    
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        if($method=='DELETE')
        {
            $update['is_deleted']  = true;
            $update['deleted_at']  = date('Y-m-d H:i:s');

            //audit_K delete_image
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            $audit_info['user_id'] = $valid_auth_token->user_id;
            $audit_info['role_id'] = $valid_auth_token->role_id;
            $audit_info['org_id'] = $this->input->get('ngoId');
            $audit_info['entity'] = 'activity image';
            $audit_info['entity_id'] = $image_id;
            $audit_info['action'] = 'deleted';
            $audit_id = $this->Audit_model->delete_audit($imageDetails,$audit_info);

            $this->Activity_model->update_report_image($update,$image_id);
        
            $this->Audit_model->activate_audit($audit_id);
            //audit_K delete_image
            
            $data['error'] = false;
            $data['status'] = 200;
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        elseif($method=='PUT')
        {
            $jsonArray = json_decode(file_get_contents('php://input'),true);            
            $update['url'] = isset($jsonArray['imageUrl'])?$jsonArray['imageUrl']:'';
            $update['caption'] = isset($jsonArray['caption'])?$jsonArray['caption']:'';
            $update['thumb_url'] = isset($jsonArray['thumbUrl'])?$jsonArray['thumbUrl']:'';
            //xss clean         
            $update = $this->security->xss_clean($update);

            if(empty($update['url']))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Validation Error.";         
                header('HTTP/1.1 400 Validation Error');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }
            $update['last_updated']  = date('Y-m-d H:i:s');

            //audit change_image_caption
            $old_data['id'] = $imageDetails->id;
            $old_data['imageUrl'] = $imageDetails->url;
            $old_data['url'] = $imageDetails->url;
            $old_data['caption'] = $imageDetails->caption;
            $old_data['thumbUrl'] = $imageDetails->thumb_url;
            $old_data['type'] = 'image';
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            $audit_info['user_id'] = $valid_auth_token->user_id;
            $audit_info['role_id'] = $valid_auth_token->role_id;
            $audit_info['org_id'] = $this->input->get('ngoId');
            $audit_info['entity'] = 'activity image caption';
            $audit_info['entity_id'] = $image_id;
            $audit_info['action'] = 'updated';
            $audit_id = $this->Audit_model->update_audit_3($old_data,$jsonArray,$audit_info);

            $this->Activity_model->update_report_image($update,$image_id);

            if($audit_id!='false')              
                $this->Audit_model->activate_audit($audit_id);
            //audit change_image_caption    

            $data['error'] = false;
            $data['resp']['id'] = $image_id;
            $data['resp']['url'] = $update['url'];
            $data['resp']['caption'] = $update['caption'];
            $data['resp']['thumbUrl'] = $update['thumb_url'];
            $data['resp']['lastUpdated'] = $update['last_updated'];
            echo json_encode($data,JSON_NUMERIC_CHECK);
            
        }
        else
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }   
    }//delete_image($activity_id,$image_id)
    public function add_video($activity_id)
    {       
        $jsonArray = json_decode(file_get_contents('php://input'),true);            
        $video_url = isset($jsonArray['videoUrl'])?$jsonArray['videoUrl']:'';
        $caption = isset($jsonArray['caption'])?$jsonArray['caption']:'';
        $thumb_url = isset($jsonArray['thumbUrl'])?$jsonArray['thumbUrl']:'';

        if (strpos($video_url, 'http:') !== false) 
        {
            $video_url = str_replace("http","https",$video_url);
        }
        if (strpos($thumb_url, 'http:') !== false) 
        {
            $thumb_url = str_replace("http","https",$thumb_url);
        }

        if(empty($video_url))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Validation Error.";         
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $activity_details = $this->Activity_model->activity_highlight_details($activity_id);
        if(empty($activity_details))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find update using id mentioned.";   
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $count = $this->Activity_model->active_video_count($activity_id);       
        //check uploaded video not greater than 4       
        $video_cnt = $count->num;
        if($video_cnt<4)
        {           
            $insert_video['project_report_id'] = $activity_id;
            $insert_video['is_active'] = 1;
            $insert_video['date_created'] = $insert_video['last_updated'] = date('Y-m-d H:i:s');
            $insert_video['url'] = $video_url;
            $insert_video['caption'] = $caption;
            $insert_video['thumb_url'] = $thumb_url;
            $id = $this->Activity_model->add_report_video($insert_video);

            $data['error'] = false;
            $data['resp']['id'] = $id;
            $data['resp']['url'] = $video_url;
            $data['resp']['caption'] = $caption;
            $data['resp']['thumbUrl'] = $thumb_url;

            //audit add_video
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            $audit_info['user_id'] = $valid_auth_token->user_id;
            $audit_info['role_id'] = $valid_auth_token->role_id;
            $audit_info['org_id'] = $this->input->get('ngoId');
            $audit_info['entity'] = 'activity video';
            $audit_info['entity_id'] = $id;
            $audit_info['action'] = 'added';
            $audit_id = $this->Audit_model->create_audit($data['resp'], $audit_info);
            //audit add_image   

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
    }//add_video
    public function add_image($activity_id)
    {       
        $jsonArray = json_decode(file_get_contents('php://input'),true);            
        $image_url = isset($jsonArray['imageUrl'])?$jsonArray['imageUrl']:'';
        $caption = isset($jsonArray['caption'])?$jsonArray['caption']:'';
        $thumb_url = isset($jsonArray['thumbUrl'])?$jsonArray['thumbUrl']:'';

        if (strpos($image_url, 'http:') !== false) 
            $image_url = str_replace("http","https",$image_url);
        if (strpos($thumb_url, 'http:') !== false) 
            $thumb_url = str_replace("http","https",$thumb_url);

        if(empty($image_url))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Validation Error.";         
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $activity_details = $this->Activity_model->activity_highlight_details($activity_id);
        if(empty($activity_details))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find update using id mentioned.";   
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $count = $this->Activity_model->active_image_count($activity_id);       
        //check uploaded images not greater than 4      
        $image_cnt = $count->num;
        if($image_cnt<4)
        {           
            $insert_image['project_report_id'] = $activity_id;
            $insert_image['is_active'] = 1;
            $insert_image['date_created'] = $insert_image['last_updated'] = date('Y-m-d H:i:s');
            $insert_image['url'] = $image_url;
            $insert_image['caption'] = $caption;
            $insert_image['thumb_url'] = $thumb_url;
            $image_record=$this->Activity_model->report_image_entry($image_url);

            $id = $this->Activity_model->add_report_image($insert_image);

            $data['error'] = false;
            $data['resp']['id'] = $id;
            $data['resp']['url'] = $image_url;
            $data['resp']['caption'] = $caption;
            $data['resp']['thumbUrl'] = $thumb_url;

            //audit add_image
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            $audit_info['user_id'] = $valid_auth_token->user_id;
            $audit_info['role_id'] = $valid_auth_token->role_id;
            $audit_info['org_id'] = $this->input->get('ngoId');
            $audit_info['entity'] = 'activity image';
            $audit_info['entity_id'] = $id;
            $audit_info['action'] = 'added';
            $audit_id = $this->Audit_model->create_audit($data['resp'], $audit_info);
            //audit add_image   

            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "You can not upload more than 4 images.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }   
    }//add_image
    protected function update_activity($activity_id)
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
        if($role_id==7)
            $ngo_id = $this->input->get('ngoId');
        else
            $ngo_id = login_ngo_details($auth_token);

        $jsonArray = json_decode(file_get_contents('php://input'),true);        
        $project_id = $update['project_id'] = isset($jsonArray['projectId'])?$jsonArray['projectId']:'';
        $description = $update['description'] = isset($jsonArray['description'])?$jsonArray['description']:'';
        $title =  $update['report'] = isset($jsonArray['title'])?$jsonArray['title']:'';
        $facebook_status = isset($jsonArray['facebookStatus'])?$jsonArray['facebookStatus']:'';
        $twitter_status = isset($jsonArray['twitterStatus'])?$jsonArray['twitterStatus']:'';

        $activity_type = $this->Activity_model->get_project_report_type($activity_id);
        if(empty($activity_type))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find update type."; 
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        $activity_type = $activity_type->project_report_type;
        if($activity_type=='Progress Update')
        {
            $outcome_id = $update['goal_id'] = isset($jsonArray['outcomeId'])?$jsonArray['outcomeId']:'';

            $current_outcome = $update['current_goal'] = isset($jsonArray['newOutcome'])?$jsonArray['newOutcome']:'';
            $goalOutcome = isset($jsonArray['goalOutcome'])?$jsonArray['goalOutcome']:'';
            $update['new_progress'] = isset($jsonArray['newProgress'])?$jsonArray['newProgress']:'';

            $activity_details = $this->Activity_model->activity_details($activity_id);
            $project_is_active = $activity_details->is_active;
            $project_is_deleted = $activity_details->is_deleted;
            $project_status_name = $activity_details->status_name;
            $outcome_is_deleted = $activity_details->isdeleted;
        }
        elseif($activity_type=='Project Highlight')
        {
            $activity_details = $this->Activity_model->activity_highlight_details($activity_id);
            $project_is_active = $activity_details->is_active;
            $project_is_deleted = $activity_details->is_deleted;
            $project_status_name = $activity_details->status_name;
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Invalid update type.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        //xss clean
        $update = $this->security->xss_clean($update);

        if(empty($activity_details))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find update using id mentioned.";   
            header('HTTP/1.1 404 Not Found');
            
            return $data;
        }
        if(empty($project_id))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please select Project.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if($project_status_name=='Inactive')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Project of this update is Inactive, you cannot edit it!!"; 
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if($title=='')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter update title.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        $project_details = $this->Project_model->project_ngo($ngo_id,$project_id);
        if(empty($project_details))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Given project id does not belong to this user.";
            header('HTTP/1.1 404 Not Found');
            return $data;
        }
        if($activity_type=='Progress Update')
        {
            if((ord($outcome_is_deleted)==1 || $outcome_is_deleted==1)) 
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Your Outcome is deleted, you cannot edit it!!"; 
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if(empty($outcome_id))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Please select Outcome.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($current_outcome=='')
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Please enter total outcome.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($current_outcome>$goalOutcome)
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "New total should not be Greater than Goal.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($current_outcome<0)
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "New total should not be negative value.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            $outcome_details = $this->Project_model->project_goal_check($outcome_id,$project_id);
            if(empty($outcome_details))
            {
                $data['error'] = true;
                $data['status'] = 404;
                $data['message'] = "Operation failed to find outcome using id mentioned.";
                header('HTTP/1.1 404 Not Found');
                return $data;
            }   
            if($current_outcome>$outcome_details->goal_achieved)
            {
                //update current goal in project table also
                $project_goal_update['goal_achieved'] = $current_outcome;
                $this->Project_model->update_outcomes($project_goal_update,$outcome_id);
            }
        }
        
        //update activity table
        $update['last_updated'] = date('Y-m-d H:i:s');

        //audit update_activity
        $audit_info['user_id'] = $valid_auth_token->user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'activity';
        $audit_info['entity_id'] = $activity_id;
        $audit_info['action'] = 'updated';
        $old_data = $this->activity_details($activity_id);
        $audit_id = $this->Audit_model->update_audit($old_data['resp'],$jsonArray,$audit_info);

        $this->Activity_model->update_activity($update,$activity_id);
        
        if($audit_id!='false')              
            $this->Audit_model->activate_audit($audit_id);
        //audit update_activity

        $data = $this->activity_details($activity_id);
        return $data;
    }//update_activity
    public function postActivityFacebook($activityId,$projectId,$user_id)
    {
        
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');        
        //if user exists        
        $enablePosting = $this->User_model->user_sharing_details($user_id);
        //if user ticked posting activity from profile page
        if(!empty($enablePosting))
        {
            $socialId = $enablePosting->id;
            try{                                
                $url = $this->config->item('admin_social_posting_url')."?socialId=".$socialId."&activityId=".$activityId; 
                //$url = $this->config->item('admin_url')."api/v1/users/loggedInUser";
                $ch = curl_init(); 
                $ret = curl_setopt($ch, CURLOPT_URL, $url);                                                                     
                $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                                                                  
                $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                          
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                    'Content-Type: application/json',                                                                                
                    'X-Auth-Token: ' . $auth_token)                                                                       
                );      
                $ret = curl_exec($ch);                  
                $info = curl_getinfo($ch);
            }
            catch(Exception $e)
            {
            }
        }//if(!empty($enablePosting))
    
        return;
    }//postActivityFacebook

    public function get_activity_media($activity_id,$media_id,$media_type)
    {
        $data['error'] = false;
        $type = $this->Activity_model->get_project_report_type($activity_id);

        if($type->project_report_type=='Progress Update')
        {
            $activity_data = $this->Activity_model->activity_details($activity_id);
            $goal_id = $activity_data->goal_id;
            $project_id = $activity_data->project_id;
            $outcome_info = $this->Project_model->outcome_list($project_id);
            $outcome_ids = array_column(json_decode(json_encode($outcome_info), true), 'id');
            $rank = array_search($goal_id,$outcome_ids,true) + 1;
            $data['is_progress_update'] = true;
            $data['rank'] = $rank;
        }
        else
        {
            $data['is_progress_update'] = false;
        }

        $media = $this->Activity_model->get_activity_media($activity_id);

        $new_array = array();
        foreach ($media as $value) 
        {
            if($media_id==$value->id && $media_type==$value->type)
            {
                $new_array[0] = $value;
            }
        }

        foreach ($media as $value) {
            if($media_id!=$value->id || $media_type!=$value->type)
            {
                $new_array[] = $value;
            }
        }
        $data['resp'] = $new_array;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }
}//end activity
/* End of file activity.php */
/* Location: ./application/controllers/activity.php */