<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Project extends Rest 
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
        $this->load->model('Project_model');
        $this->load->model('Company_model');
        $this->load->model('Hashtag_model');
        $this->load->model('Activity_model');
        $this->load->model('Audit_model');
        $this->load->helper('url_routing');
        $this->load->model('Routing_model');
    }
    public function update_details($id)
    {
        //check call for update project or for details of projects
        if($this->input->server('REQUEST_METHOD')=="GET")
        {
            $project_info = $this->Project_model->project_details($id);
            if(!empty($project_info))
            {
                if(ord($project_info->is_active)==0)
                {
                    header('HTTP/1.1 404 Not Found');
                    exit;
                }
            }
            $data = $this->project_details($id);
            $data = json_encode($data,JSON_NUMERIC_CHECK);
            echo $data;
            return;
        }
        else
        {
            $method = $this->input->get('method');

            if($method=="PUT")
            {
                $data = $this->update_project($id);
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }
            else
            {
                header('HTTP/1.1 404 Not Found');
                return;
            }   
        }
    }
    public function add_list()
    {
        //check call for create project or for list of projects
        if($this->input->server('REQUEST_METHOD')=="GET")
        {
            $data = $this->list_project();

            $data = json_encode($data,JSON_NUMERIC_CHECK);
            echo $data;

        }
        else
        {
            $data = $this->add();
            echo json_encode($data,JSON_NUMERIC_CHECK);
        }
    }
    protected function add()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        //this action performed only by ngo admin

        //if ngo admin then can add project
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

        if($role_id==5)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        else
            $insert['ngo_id'] = $ngo_id = login_ngo_details($auth_token);

        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $insert['goal_amount'] = isset($jsonArray['targetAmount'])?$jsonArray['targetAmount']:'';
        $insert['fundings_to_date'] = isset($jsonArray['fundingsToDate'])?$jsonArray['fundingsToDate']:'';
        $insert['total_benefeciaries'] = isset($jsonArray['totalBenefeciaries'])?$jsonArray['totalBenefeciaries']:'';
        $status = isset($jsonArray['status'])?$jsonArray['status']:'';

        if($status=="Fundraising" || $status=="In Progress" || $status=="Complete")
        {
            $insert['is_active'] = true;
            $insert['status_name'] = $status;
        }
        elseif($status=="Inactive")
        {
            $insert['is_active'] = false;
            $insert['status_name'] = $status;
            $insert['is_deleted'] = true;
            $insert['deleted_at'] = date('Y-m-d H:i:s'); 
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Invalid project status.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }

        if($status!='Inactive')
        {
            $count  = $this->Project_model->project_list_count($ngo_id, '', 'true')->num;
            if($count >= 12)
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "You are at the maximum of 12 active projects. Set another project to inactive to continue.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
        }

        $countryRegion = isset($jsonArray['countryRegion'])?$jsonArray['countryRegion']:array();
        $insert['image_url'] = isset($jsonArray['imageUrl'])?$jsonArray['imageUrl']:'';
        $insert['is_crowd_sourced'] = isset($jsonArray['isCrowdFunded'])?$jsonArray['isCrowdFunded']:false;
        $insert['title'] = isset($jsonArray['title'])?$jsonArray['title']:'';

        $insert['short_description'] = isset($jsonArray['shortDescription'])?$jsonArray['shortDescription']:'';
        $insert['start_date'] = $start_date = isset($jsonArray['startDate'])?$jsonArray['startDate']:'';
        $insert['end_date'] = $end_date = isset($jsonArray['endDate'])?$jsonArray['endDate']:'';
        $insert['micro_site'] = isset($jsonArray['microSite'])?$jsonArray['microSite']:'';
        $outcomes = isset($jsonArray['outcomes'])?$jsonArray['outcomes']:array();
        $insert['date_created'] = $insert['last_updated'] = date('Y-m-d H:i:s');
        $hashtag = isset($jsonArray['hashtags'])?$jsonArray['hashtags']:array();
        
        if($start_date!='' && $end_date!='')
        {
            if(strtotime($start_date)>strtotime($end_date))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "End date should be greater than or equal to start date.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
        }

        // xss clean
        $insert = $this->security->xss_clean($insert);

        $insert['long_description'] = isset($jsonArray['longDescription'])?$jsonArray['longDescription']:'';

        // xss clean
        $outcomes = $this->security->xss_clean($outcomes);  

        //validation error_get_last
        if($insert['title']=='') 
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter name.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if($insert['goal_amount']===null) 
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter funding target.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if($insert['goal_amount']<0) 
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Funding target should not be negative value.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if($insert['goal_amount']<$insert['fundings_to_date'])
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "To date amount should not be greater than Goal.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if($insert['fundings_to_date']<0)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "To date amount should not be less than 0.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if($insert['total_benefeciaries']=='')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please enter target beneficiaries.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if($insert['total_benefeciaries']<0) 
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Target beneficiaries should not be negative value.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if(count($outcomes)==0)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Project should have at least one outcome.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        //goals validation
        $insert['over_all_goal'] = 0;  //goal and target in insert array are changed to outcome and goal respectively
        if(!empty($outcomes))
        {
            foreach($outcomes as $outcome_list)
            {
                if(empty($outcome_list['outcome']))
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Please enter outcome.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                if($outcome_list['goalOutcome']!='' && $outcome_list['goalOutcome']<0)
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Goal should not be negative value.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                if($outcome_list['currentOutcome']!='' && $outcome_list['currentOutcome']<0)
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Current should not be negative value.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                if($outcome_list['goalOutcome']<$outcome_list['currentOutcome'])
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Goal should be greater than Current.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                if(empty($outcome_list['categoryId']))
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Please select category.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                $insert['over_all_goal']+=$outcome_list['goalOutcome']; //goal and target in insert array are changed to outcome and goal respectively
            }
        }

        $insert['track_goal'] = 1;

        $project_status = $this->Project_model->get_table_status('project'); 
        $auto_increment_value = $project_status->Auto_increment;

        //routes
        $ngo_details = $this->Ngo_model->organization_details($ngo_id, 'any');
        $ngo_url_suffix = $ngo_details->ngo_url_suffix;
        $url1 = str_replace(' ', '-', $insert['title']); 
        // $url1 = 'projects/'.rawurlencode($url1).'-'.$auto_increment_value;
        // $check1 = $this->Routing_model->get_url_slug_data($url1);
        // if(!empty($check1))
        // {
        //     $data['error'] = true;
        //     $data['status'] = 400;
        //     $data['message'] = "Url routing unique constraint failed, Please change project name.";
        //     header('HTTP/1.1 400 Validation Error.');
        //     echo json_encode($data,JSON_NUMERIC_CHECK);
        //     exit;
        // }
        if($ngo_url_suffix!=null && $ngo_url_suffix!='')
        {
            $url2 = rawurlencode($ngo_url_suffix).'/projects/'.rawurlencode($url1).'-'.$auto_increment_value;
            $check2 = $this->Routing_model->get_url_slug_data($url2);
            if(!empty($check2))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Url routing unique constraint failed, Please change project name.";
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
        }
        //routes

        $outcome_insert['project_id'] = $insert_country['project_id'] = $projectId = $this->Project_model->create_project($insert);
        //insert project

        //routes
        $insert_array = array();
        if($ngo_url_suffix!=null && $ngo_url_suffix!='')
        {
            $array = array();
            $array['page_id'] = 10;
            $array['entity_name'] = 'ngo_branding_change';
            $array['entity_id'] = $ngo_id;
            $array['second_entity_id'] = $projectId;
            $array['url_slug'] = $url2;
            array_push($insert_array, $array);
        }

        foreach($insert_array as $array)
            $this->Routing_model->insert_url($array);
        //routes

        $outcome_insert['date_created'] = $outcome_insert['last_updated'] = date('Y-m-d H:i:s');
        if(!empty($countryRegion))
        {
            foreach($countryRegion as $regions)
            {
                $country = $regions['country'];
                $countryCode = $regions['countryCode'];
                //country details
                if(!empty($country) && !empty($countryCode) )
                {
                    $state = isset($regions['state'])?$regions['state']:'';
                    $insert_country['country_id'] = $this->Country_model->country_get_insert($country, $countryCode);
                    if(!empty($state))
                        $insert_country['state_id'] = $this->Country_model->state_get_insert($state, $insert_country['country_id']);
                    else
                        $insert_country['state_id'] = NULL;
                    $this->Project_model->project_country_region($insert_country);
                }
            }
            
        }
        
        if(!empty($outcomes))
        {
            foreach($outcomes as $outcome_list)
            {
                $outcome_insert['goal_target'] = $outcome_list['goalOutcome']; //goal and target in insert array are changed to outcome and goal respectively
                $outcome_insert['goal_achieved'] = $outcome_list['currentOutcome']; //goal and target in insert array are changed to outcome and goal respectively
                $outcome_insert['goal'] = $outcome_list['outcome']; //goal and target in insert array are changed to outcome and goal respectively
                $outcome_insert['description'] = $outcome_list['description']; //goal and target in insert array are changed to outcome and goal respectively
                $outcome_insert['categories_id'] = $outcome_list['categoryId']; //goal and target in insert array are changed to outcome and goal respectively
                $this->Project_model->insert_outcome($outcome_insert);
            }
        }
        
        //insert hashtags into hashtag table if new and entry in mapping table
        if(!empty($hashtag))
        {
            foreach($hashtag as $hashtag_list)
            {
                $hashtag_id = $hashtag_list['id'];
                //insert into hash_tags table
                if(empty($hashtag_id))
                {
                    $insert_hashtag['version'] = 0;
                    $insert_hashtag['date_created'] = $insert_hashtag['last_updated'] = date('Y-m-d H:i:s');
                    $insert_hashtag['hash_tag'] = $hashtag_list['hashTag'];
                    $insert_hashtag['is_active'] = 1;
                    $insert_hashtag['is_deleted'] = 0;
                    //chk duplicate hashtag exists in db table
                    $chk_duplicate = $this->Hashtag_model->check_duplicate_hashtag($hashtag_list['hashTag']);
                    $default = isset($chk_duplicate->is_default)?$chk_duplicate->is_default:0;
                    //if this is new hashtag then add this to hashtag table 
                    if (ord($default)==0 || $default==0)
                    {
                        if(empty($chk_duplicate))
                            $hashtag_id = $this->Hashtag_model->insert_hashtag($insert_hashtag);
                        else
                            $hashtag_id = $chk_duplicate->id;
                    }
                }
                
                $chk_hashtagid = $this->Hashtag_model->check_hashtagid_exist($hashtag_id)->num;
                if($chk_hashtagid!=0)
                {
                    // add all hashtag entries in to project_hash_tags
                    $insert_proj_hash['project_hash_tags_id'] = $projectId;
                    $insert_proj_hash['hash_tags_id'] = $hashtag_id;
                    $this->Hashtag_model->insert_project_hashtags($insert_proj_hash);
                }
            }//foreach
        }
        //update total_no_of_benefeciaries of NGO
        $this->organisation_beneficiaries($insert['ngo_id']);
        //update total_no_of_benefeciaries of NGO ends
        
        $data = $this->project_details($projectId);
        //audit create_project
        $audit_info['user_id'] = $user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'project';
        $audit_info['entity_id'] = $projectId;
        $audit_info['action'] = 'created';
        $audit_id = $this->Audit_model->create_audit($data['resp'], $audit_info);
        //audit create_project
        return $data;
    }

    protected function project_details($id)
    {
        $project_info = $this->Project_model->project_details($id);
        if(!empty($project_info))
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
                $role_id = $valid_auth_token->role_id;
                $ngo_id = login_ngo_details($auth_token);
            }
            else
            {
                $ngo_id = $this->input->get('ngoId');
            }
            //check if this project belongs to this ngo
            if($ngo_id!=false)
            {
                $project_ngo_check = $this->Project_model->project_ngo($ngo_id, $id);
                if(empty($project_ngo_check))
                {
                    $data['error'] = true;
                    $data['status'] = 401;
                    $data['message'] = "Unauthorized User.";
                    header('HTTP/1.1 401 Unauthorized User');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    exit;
                }
            }

            $data['error'] = false;
            $project_data = array();
            $project_data['id'] = $project_info->id;
            $project_data['longDescription'] = $project_info->long_description;
            $project_data['shortDescription'] = $project_info->short_description;
            $project_data['imageUrl'] = $project_info->image_url;
            $project_data['videoUrl'] = $project_info->video_url;
            $project_data['title'] = $project_info->title;
            $project_data['totalBenefeciaries'] = $project_info->total_benefeciaries;
            $project_data['status'] = $status_name = $project_info->status_name;
            $project_data['microSite'] = $project_info->micro_site;
            if(!$this->input->server('HTTP_X_AUTH_TOKEN'))
            {
                $project_data['startDate'] = $start_date = $project_info->start_date;
                $project_data['endDate'] = $end_date = $project_info->end_date;
                if($start_date!='')
                {
                    $project_data['startDate'] = $start_date = strtoupper(date('F d, Y',strtotime($start_date)));
                }
                if($end_date!='')
                {
                    $project_data['endDate'] = $end_date = strtoupper(date('F d, Y',strtotime($end_date)));
                }
                
                if($start_date!='' && $end_date!='')
                {
                    $duration = strtotime($end_date) - strtotime($start_date);
                    $days = floor($duration/(24*60*60));
                    $project_data['duration'] = $days;
                }
            }
            else
            {
                $project_data['startDate'] = $start_date = $project_info->start_date;
                $project_data['endDate'] = $end_date = $project_info->end_date;
            }
            $project_data['overAllOutcome'] = $project_info->over_all_goal;
            $project_data['targetAmount'] = $project_info->goal_amount;
            $project_data['fundingsToDate'] = $project_info->fundings_to_date;
            $project_data['currentAmount'] = $project_info->current_amount;
            $project_data['noOfPeopleInvolved'] = $project_info->no_of_people_involved;
            $project_data['lastUpdated'] = $project_info->last_updated;
            $project_data['dateCreated'] = $project_info->date_created;
            $project_data['deletedAt'] = $project_info->deleted_at;
            $project_data['isCrowdFunded'] = $project_info->is_crowd_sourced;
            if (ord($project_data['isCrowdFunded'])==1 || $project_data['isCrowdFunded']==1)
                $project_data['isCrowdFunded'] = true;
            else
                $project_data['isCrowdFunded'] = false;
            $project_data['isFeaturedProject'] = (ord($project_info->is_featured_project)==1 || $status_name!='Inactive')?true:false;
            $project_data['ngoId'] = $ngo_id = $project_info->ngo_id;

            //country details
            $country_regions = $this->Project_model->project_country_regions($id);
            $final_country = array();
            $cr = 0;
            if(!empty($country_regions))
            {
                foreach ($country_regions as $country_region)
                {
                    $countryCode = "";
                    $country = "";
                    $state = "";
                    $country_id = $country_region->country_id;
                    $state_id = $country_region->state_id;
                    if(!empty($country_id))
                    {
                        $country_info = $this->Country_model->country_info($country_id);
                        if(!empty($country_info))
                        {
                            $country = $country_info->name;
                            $countryCode = $country_info->code;
                            $countryUrl = $country_info->flag_url;
                        }
                        if(!empty($state_id))
                        {
                            $state_info = $this->Country_model->state_info($state_id);
                            if(!empty($state_info))
                            {
                                $state = $state_info->name;                             
                            }
                        }
                    }
                    $final_country[$cr]['country']  = $country;
                    $final_country[$cr]['countryCode']  = $countryCode;
                    $final_country[$cr]['countryUrl']   = $countryUrl;
                    $final_country[$cr]['state']    = $state;
                    $cr++;
                }           
            }   
            
            //hashtag details
            $hashtag_list = $this->Hashtag_model->get_project_hashtag($id);
            $hashtag_data = array();
            if(!empty($hashtag_list))
            {
                foreach($hashtag_list as $hashtag)
                {
                    $hash_tag_id = $hashtag->hash_tags_id;                  
                    $hashtag_info = $this->Hashtag_model->hashtag_info($hash_tag_id);
                    if(!empty($hashtag_info))
                    {
                        $hashtag_data[] = $hashtag_info;
                    }
                }
            }
            $project_data['hashtags'] = $hashtag_data;
            //goal list
            $achievedOutcome = 0;
            $outcome_data = array();
            $outcome_info = $this->Project_model->outcome_list($id);
            if(!empty($outcome_info))
            {
                $i=0;
                foreach($outcome_info as $outcome)
                {
                    $outcome_data[$i]['id'] = $outcome->id;
                    $outcome_data[$i]['outcome'] = $outcome->goal;
                    $outcome_data[$i]['currentOutcome'] = $outcome->goal_achieved;
                    $outcome_data[$i]['goalOutcome'] = $outcome->goal_target;
                    $outcome_data[$i]['description'] = $outcome->description;
                    $categories_id = $outcome->categories_id;
                    $achievedOutcome += $outcome_data[$i]['currentOutcome'];
                    //get tha data of categories associated with goal
                    if(!empty($categories_id))
                    {
                        $cat_info = $this->Category_model->category_info($categories_id);
                        $cat_data = array();
                        if(!empty($cat_info))
                        {
                            //...
                            $outcome_data[$i]['categoryId'] = $cat_info->id;
                            $outcome_data[$i]['categoryname'] = $cat_info->category;
                            //...
                            $cat_data['id'] = $cat_info->id;
                            $cat_data['category'] = $cat_info->category;
                            $cat_data['subcategory'] = $cat_info->subcategory;
                            $cat_data['logoUrl'] = $cat_info->image_url;
                        }
                        $outcome_data[$i]['category'] = $cat_data;
                    }
                    $i++;
                }
            }
            
            //member list
            $company_data = array();
            $company_list = $this->Project_model->company_project_list_approved($ngo_id, $id);
            if(!empty($company_list))
            {
                $i = 0;
                foreach($company_list as $company_info)
                {
                    $company_data[$i]['companyId'] = $comp_id = $company_info->id;
                    $company_data[$i]['name'] = $company_info->name;
                    //fund amount
                    $fund_info = $this->Project_model->company_project_fund($comp_id, $id);
                    if(!empty($fund_info))
                        $company_data[$i]['funds'] = $fund_info->funds;
                    else
                        $company_data[$i]['funds'] = 0;
                    $i++;
                }
            }   

            $donors = $this->Project_model->get_project_donors($id);
            $donors_data = array();
            if(!empty($donors))
            {
                $i=0;
                foreach ($donors as $donor) {
                    $donors_data[$i]['id'] = $donor->id;
                    $donors_data[$i]['imageUrl'] = $donor->image_url;
                    $donors_data[$i]['donorUrl'] = $donor->donor_url;
                    $donors_data[$i]['name'] = $donor->name;
                    $i++;
                }
            }
            $data['resp'] = $project_data;
            $data['resp']['outcomes'] = $outcome_data;
            $data['resp']['achievedOutcome'] = $achievedOutcome;
            $data['resp']['members'] = $company_data;
            $data['resp']['countryRegion'] = $final_country;
            $data['resp']['donor'] = $donors_data;
            return $data;
        }//project Info
        else{
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find project using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            return $data;
        }
        
    }//project_details($projectId)

    protected function list_project()
    {
            
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;           
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $status = ($this->input->get('status'))?$this->input->get('status'):'';
        
        if(!empty($status))
        {           
            if($status!='Fundraising' && $status!='In Progress' && $status!='Complete' && $status!='Inactive' && $status!='true' && $status!='false' && $status!='active')
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Invalid Status.";
                header('HTTP/1.1 400 Validation Error');
                return $data;
            }               
        }
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
            $ngo_id = login_ngo_details($auth_token);
        }
        else
        {
            $ngo_id = $this->input->get('ngoId');
        }   
        if(empty($ngo_id))
        {
            header('HTTP/1.1 401 Unauthorized Error.');
            exit;
        }
        
        $list = $this->Project_model->project_list($ngo_id, $query, $status, $offset, $limit);
        $data['error'] = false;
        $project_final_data = array();
        $p = 0;
        foreach($list as $project)
        {
            $id = $project->id;
            $project_info = $this->Project_model->project_details($id);
            $data['error'] = false;
            $project_data = array();
            $project_data['id'] = $project_info->id;
            $project_data['longDescription'] = $project_info->long_description;
            $project_data['imageUrl'] = $project_info->image_url;
            $project_data['videoUrl'] = $project_info->video_url;
            $project_data['title'] = $project_info->title;
            $project_data['totalBenefeciaries'] = $project_info->total_benefeciaries;
            $project_data['status'] = $project_info->status_name;
            $project_data['microSite'] = $project_info->micro_site;
            $start_date = $end_date = '';
            $project_data['startDate'] = $start_date = $project_info->start_date;
            $project_data['endDate'] = $end_date = $project_info->end_date;
            if($start_date!='')
            {
                $project_data['startDate'] = $start_date = strtoupper(date('F d, Y',strtotime($start_date)));
            }
            if($end_date!='')
            {
                $project_data['endDate'] = $end_date = strtoupper(date('F d, Y',strtotime($end_date)));
            }
            if($start_date!='' && $end_date!='')
            {
                $duration = strtotime($end_date) - strtotime($start_date);
                $days = floor($duration/(24*60*60));
                $project_data['duration'] = $days;
            }
            $project_data['overAllOutcome'] = $project_info->over_all_goal;
            $project_data['targetAmount'] = $project_info->goal_amount;
            $project_data['fundingsToDate'] = $project_info->fundings_to_date;
            $project_data['currentAmount'] = $project_info->current_amount;
            $project_data['noOfPeopleInvolved'] = $project_info->no_of_people_involved;
            $project_data['lastUpdated'] = $project_info->last_updated;
            $project_data['dateCreated'] = $project_info->date_created;
            $latest_update = $this->Activity_model->get_latest_project_activity($id);
            $string = null;
            if(!empty($latest_update))
                $updatedAt = $latest_update->last_updated;
            else
                $updatedAt = $project_info->last_updated;

            $current_date = date('Y-m-d H:i:s');
            $date_diff = strtotime($current_date) - strtotime($updatedAt);

            $seconds = array( 365 * 24 * 60 * 60  =>  'year',
                         30 * 24 * 60 * 60  =>  'month',
                              24 * 60 * 60  =>  'day',
                                   60 * 60  =>  'hour',
                                        60  =>  'minute',
                                         1  =>  'second'
                        );
            $seconds_plural = array( 'year'   => 'years',
                               'month'  => 'months',
                               'day'    => 'days',
                               'hour'   => 'hours',
                               'minute' => 'minutes',
                               'second' => 'seconds'
                        );
            foreach ($seconds as $secs => $str)
            {
                $d = $date_diff / $secs;
                if ($d >= 1)
                {
                    $r = round($d);
                    $string = $r . ' ' . ($r > 1 ? $seconds_plural[$str] : $str) . ' ago';
                    break;
                }
            }
            $project_data['updatedAt'] = $string;
            $project_data['completedAt'] = $updatedAt;

            $project_data['deletedAt'] = $project_info->deleted_at;         
            $project_data['isCrowdFunded'] = $project_info->is_crowd_sourced;
            if (ord($project_data['isCrowdFunded'])==1 || $project_data['isCrowdFunded']==1)
                $project_data['isCrowdFunded'] = true;
            else
                $project_data['isCrowdFunded'] = false;
            if (ord($project_info->is_featured_project)==1 || $project_info->is_featured_project==1)
                $project_data['isFeaturedProject'] = true;
            else
                $project_data['isFeaturedProject'] = false;
            $project_data['ngoId'] = $ngo_id = $project_info->ngo_id;
            $ngo_details = $this->Ngo_model->organization_details($ngo_id, 'any');
            $project_data['ngoName'] = $ngo_details->name;
            //country details
            $country_info = $this->Project_model->project_country_details($id);

            if(!empty($country_info))
            {
                
                $project_data['country'] = $country_info->name;
                $project_data['countryCode'] = $country_info->code;
            }else
            {
                $project_data['country'] = "";
                $project_data['countryCode'] = "";
            }

            //outcome list
            $outcome_data = array();
            $outcome_info = $this->Project_model->outcome_list($id);
            $achievedOutcome=0;
            if(!empty($outcome_info))
            {
                $i = 0;
                foreach($outcome_info as $outcome)
                {
                    $outcome_data[$i]['id'] = $outcome->id;
                    $outcome_data[$i]['outcome'] = $outcome->goal;
                    $outcome_data[$i]['currentOutcome'] = $outcome->goal_achieved;
                    $outcome_data[$i]['goalOutcome'] = $outcome->goal_target;
                    $outcome_data[$i]['description'] = $outcome->description;
                    $achievedOutcome+=$outcome_data[$i]['currentOutcome'];
                    
                    $categories_id = $outcome->categories_id;
                    
                    //get tha data of categories associated with goal
                    if(!empty($categories_id))
                    {
                        $cat_info = $this->Category_model->category_info($categories_id);
                        $cat_data = array();
                        if(!empty($cat_info))
                        {
                            $cat_data['id'] = $cat_info->id;
                            $cat_data['category'] = $cat_info->category;
                            $cat_data['subcategory'] = $cat_info->subcategory;
                            $cat_data['logoUrl'] = $cat_info->image_url;
                        }
                        $outcome_data[$i]['category'] = $cat_data;
                    }
                    $i++;
                }
            }
            $project_data['achievedOutcome'] = $achievedOutcome;
            //member list
            $member_data = array();
            $member_list = $this->Project_model->list_project_members($id);
            if(!empty($member_list))
            {
                $k = 0;
                foreach($member_list as $member_info)
                {
                    $member_data[$k]['id'] = $member_info->id;
                    $member_data[$k]['name'] = $member_info->first_name." ".$member_info->last_name;
                    $member_data[$k]['imageUrl'] = $member_info->image_url;
                    $k++;
                }
            }   
            //country details
            $country_regions = $this->Project_model->project_country_regions($id);
            $final_country = array();
            $cr = 0;
            if(!empty($country_regions))
            {
                foreach ($country_regions as $country_region)
                {
                    $countryCode = "";
                    $country = "";
                    $state = "";
                    $country_id = $country_region->country_id;
                    $state_id = $country_region->state_id;
                    if(!empty($country_id))
                    {
                        $country_info = $this->Country_model->country_info($country_id);
                        if(!empty($country_info))
                        {
                            $country = $country_info->name;
                            $countryCode = $country_info->code;
                            $countryUrl = $country_info->flag_url;
                        }
                        if(!empty($state_id))
                        {
                            $state_info = $this->Country_model->state_info($state_id);
                            if(!empty($state_info))
                            {
                                $state = $state_info->name;                             
                            }
                        }
                    }
                    $final_country[$cr]['country']  = $country;
                    $final_country[$cr]['countryCode']  = $countryCode;
                    $final_country[$cr]['countryUrl']   = $countryUrl;
                    $final_country[$cr]['state']    = $state;
                    $cr++;
                }           
            }    

            $donors = $this->Project_model->get_project_donors($id);
            $donors_data = array();
            if(!empty($donors))
            {
                $i=0;
                foreach ($donors as $donor) {
                    $donors_data[$i]['id'] = $donor->id;
                    $donors_data[$i]['imageUrl'] = $donor->image_url;
                    $donors_data[$i]['donorUrl'] = $donor->donor_url;
                    $donors_data[$i]['name'] = $donor->name;
                    $i++;
                }
            }

            $project_final_data[$p] = $project_data;
            $project_final_data[$p]['outcomes'] = $outcome_data;
            $project_final_data[$p]['members'] = $member_data;  
            $project_final_data[$p]['countryRegion'] = $final_country;
            $project_final_data[$p]['donor'] = $donors_data;    

            $is_new_highlight = $this->Project_model->is_new_highlight($id);
            if(empty($is_new_highlight))
                $project_final_data[$p]['is_new_highlight'] = false;
            else
            {
                $update_date = $is_new_highlight->date_created;
                $current_date = date('Y-m-d H:i:s');
                $diff  = (strtotime($current_date) - strtotime($update_date))/3600;
                if($diff<=48)
                    $project_final_data[$p]['is_new_highlight'] = true;
                else
                    $project_final_data[$p]['is_new_highlight'] = false;
            }
            $p++;
        }

        if($status=='active')
        {
            usort($project_final_data, array($this,'cmp'));
            $project_final_data = array_reverse($project_final_data);
        }
        
        $data['resp']['count']  = $this->Project_model->project_list_count($ngo_id, $query, $status)->num;
        $data['resp']['total_count']  = $this->Project_model->project_list_count($ngo_id, '', '')->num;
        $data['resp']['active_project_count'] = $this->Project_model->active_project_count($ngo_id, 'active')->num;
        $data['resp']['project'] = $project_final_data;
        return $data;
    }       

    public function cmp($a, $b)
    {
        if ($a["completedAt"] == $b["completedAt"]) {
            return 0;
        }
        return ($a["completedAt"] < $b["completedAt"]) ? -1 : 1;
    }

    public function update_status($id)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        //if ngo admin then can update status
         //only ngo can perform this action

        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token))
        {
            header('HTTP/1.1 401 Unauthorized');
            return;     
        }
        $user_id = $valid_auth_token->user_id;
        $role_id = $valid_auth_token->role_id;
        if($role_id==5)
        {
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        else
            $ngo_id = login_ngo_details($auth_token);

        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $status = isset($jsonArray['status'])?$jsonArray['status']:false;

        $project_ngo = $this->Project_model->project_ngo($ngo_id, $id);
        if(empty($project_ngo))
        {
            header('HTTP/1.1 401 Unauthorized');
            return; 
        }

        $projectDetails = $this->Project_model->project_details($id);
        if($status!='Inactive')
        {
            $previous_status = $projectDetails->status_name;
            if($previous_status=='Inactive')
            {
                $count  = $this->Project_model->project_list_count($ngo_id, '', 'true')->num;
                if($count >= 12)
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "You are at the maximum of 12 active projects. Set another project to inactive to continue.";
                    header('HTTP/1.1 400 Validation Error.');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    exit;
                }
            }
        }
        //role_check
        //check project from same ngo
        $project_ngo = $this->Project_model->project_ngo($ngo_id, $id);
        if(empty($project_ngo))
        {
            echo "Asd";
            header('HTTP/1.1 401 Unauthorized');
            return; 
        }   
        
        if($status=="Fundraising" || $status=="In Progress" || $status=="Complete")
        {
            $update['is_active'] = true;
            $update['status_name'] = $status;
        }
        elseif($status=="Inactive")
        {
            $update['is_active'] = false;
            $update['status_name'] = $status;
            $update['is_deleted'] = true;
            $update['deleted_at'] = date('Y-m-d H:i:s'); 
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Invalid project status.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
    
        //audit project_status
        $audit_info['user_id'] = $user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'project status';
        $audit_info['entity_id'] = $id;
        $audit_info['action'] = 'updated';
        $old_data = $this->project_details($id);
        $audit_id = $this->Audit_model->update_audit_2($old_data['resp'], $jsonArray, $audit_info);
        // $audit_id = $this->Audit_model->update_project_status($audit_info, $status);
        
        $this->Project_model->update_project($update, $id);
        
        if($audit_id!='false')              
            $this->Audit_model->activate_audit($audit_id);
        //audit project_status

        $this->organisation_beneficiaries($ngo_id);
        $data = $this->project_details($id);
        $data = json_encode($data,JSON_NUMERIC_CHECK);
        echo $data;
        return;
    }//update_status
    protected function update_project($id)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token))
        {
            header('HTTP/1.1 401 Unauthorized.');
            exit;
        }
        $jsonArray = json_decode(file_get_contents('php://input'),true); 

        $outcomes = isset($jsonArray['outcomes'])?$jsonArray['outcomes']:array();
        $hashtag = isset($jsonArray['hashtags'])?$jsonArray['hashtags']:array();

        $outcomes = $this->security->xss_clean($outcomes);  
    
        //project details
        $projectDetails = $this->Project_model->project_details($id);
        if(empty($projectDetails))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find project using id mentioned.";
            return $data;
        }
        $role_id = $valid_auth_token->role_id;
        $ngo_id = login_ngo_details($auth_token);

        $project_ngo_check = $this->Project_model->project_ngo($ngo_id, $id);
        if(empty($project_ngo_check))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        //member and admin both can update outcomes
        //outcomes validation
        $insert['over_all_goal'] = 0;
        if(count($outcomes)==0)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Project should have at least one outcome.";
            header('HTTP/1.1 400 Validation Error.');
            return $data;
        }
        if(!empty($outcomes))
        {
            foreach($outcomes as $outcome_list)
            {
                if(empty($outcome_list['outcome']))
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Please enter outcome.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                if($outcome_list['goalOutcome']!='' && $outcome_list['goalOutcome']<0)
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Goal should not be negative value.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                if($outcome_list['currentOutcome']!='' && $outcome_list['currentOutcome']<0)
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Current should not be negative value.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                if($outcome_list['goalOutcome']<$outcome_list['currentOutcome'])
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Goal should be greater than Current.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                if(empty($outcome_list['categoryId']))
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Please select category.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
                $insert['over_all_goal']+=$outcome_list['goalOutcome'];
            }
        }
        if($role_id==4 || $role_id==7)
        {
            //only ngo admin can update project details         
            $insert['goal_amount'] = isset($jsonArray['targetAmount'])?$jsonArray['targetAmount']:'';
            $insert['fundings_to_date'] = isset($jsonArray['fundingsToDate'])?$jsonArray['fundingsToDate']:'';
            $insert['total_benefeciaries'] = isset($jsonArray['totalBenefeciaries'])?$jsonArray['totalBenefeciaries']:'';
            $status = isset($jsonArray['status'])?$jsonArray['status']:'';

            if($status!='Inactive')
            {
                $previous_status = $projectDetails->status_name;
                if($previous_status=='Inactive')
                {
                    $count  = $this->Project_model->project_list_count($ngo_id, '', 'true')->num;
                    if($count >= 12)
                    {
                        $data['error'] = true;
                        $data['status'] = 400;
                        $data['message'] = "You are at the maximum of 12 active projects. Set another project to inactive to continue.";
                        header('HTTP/1.1 400 Validation Error.');
                        echo json_encode($data,JSON_NUMERIC_CHECK);
                        exit;
                    }
                }
            }
            if($status=="Fundraising" || $status=="In Progress" || $status=="Complete")
            {
                $insert['is_active'] = true;
                $insert['status_name'] = $status;
            }
            elseif($status=="Inactive")
            {
                $insert['is_active'] = false;
                $insert['status_name'] = $status;
                $insert['is_deleted'] = true;
                $insert['deleted_at'] = date('Y-m-d H:i:s'); 
            }
            else
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Invalid Status.";
                header('HTTP/1.1 400 Validation Error');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
    
            $countryRegion = isset($jsonArray['countryRegion'])?$jsonArray['countryRegion']:''; 
            $insert['image_url'] = isset($jsonArray['imageUrl'])?$jsonArray['imageUrl']:'';
            $insert['is_crowd_sourced'] = isset($jsonArray['isCrowdFunded'])?$jsonArray['isCrowdFunded']:false;
            $insert['title'] = $project_name = isset($jsonArray['title'])?$jsonArray['title']:'';
            $insert['short_description'] = isset($jsonArray['shortDescription'])?$jsonArray['shortDescription']:'';
            $insert['start_date'] = $start_date = isset($jsonArray['startDate'])?$jsonArray['startDate']:'';
            $insert['end_date'] = $end_date = isset($jsonArray['endDate'])?$jsonArray['endDate']:'';
            $insert['micro_site'] = isset($jsonArray['microSite'])?$jsonArray['microSite']:'';
            $members = isset($jsonArray['members'])?$jsonArray['members']:array();
            $insert['last_updated'] = date('Y-m-d H:i:s');
            //validation error_get_last
            if($start_date!='' && $end_date!='')
            {
                if(strtotime($start_date)>strtotime($end_date))
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "End date should be greater than or equal to start date.";
                    header('HTTP/1.1 400 Validation Error.');
                    return $data;
                }
            }

            if($insert['title']=='') 
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Please enter name.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($insert['goal_amount']===null) 
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Please enter funding target.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($insert['goal_amount']<0) 
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Funding target should not be negative value.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($insert['goal_amount']<$insert['fundings_to_date'])
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "To date amount should not be greater than Goal.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($insert['fundings_to_date']<0)
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "To date amount should not be less than 0.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($insert['total_benefeciaries']=='')
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Please enter target beneficiaries.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            if($insert['total_benefeciaries']<0) 
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Target beneficiaries should not be negative value.";
                header('HTTP/1.1 400 Validation Error.');
                return $data;
            }
            //members list

            if(!empty($members))
            {
                
                //update fund details
                foreach($members as $member)
                {
                    
                    if($member['funds']!='' && $member['funds']<0)
                    {
                        $data['error'] = true;
                        $data['status'] = 400;
                        $data['message'] = "Amount should not be negative value.";
                        header('HTTP/1.1 400 Validation Error.');
                        return $data;   
                    }                       
                }
                
            }//if(!empty($members)) 

            $audit_info['user_id'] = $valid_auth_token->user_id;
            $audit_info['role_id'] = $role_id;
            $audit_info['org_id'] = $ngo_id;
            $audit_info['entity'] = 'project';
            $audit_info['entity_id'] = $id;
            $audit_info['action'] = 'updated';
            $new_data = $jsonArray;
            $old_data = $this->project_details($id);
            $old_data = $old_data['resp'];
            $audit_id = $this->Audit_model->update_audit($old_data, $new_data, $audit_info);
            
            $currentAmount = 0;
            //members list
            if(!empty($members))
            {
                
                //update fund details
                foreach($members as $member)
                {
                    $currentAmount+= $update_fund['funds'] = $member['funds'];
                    $update_fund['project_id'] = $id;
                    $update_fund['company_id'] = $companyId = $member['companyId'];
                    //check its a valid member for project
                    
                    //check fund entry
                    $entry = $this->Project_model->check_project_funding($companyId, $id);
                    if(!empty($entry))
                    {
                        $update_fund['last_updated'] = date('Y-m-d H:i:s');
                        $fund_id = $entry->id;
                        //update entry
                        $this->Project_model->update_project_funding($update_fund, $fund_id);
                    }
                    else
                    {                       
                        $update_fund['last_updated'] = $update_fund['date_created'] = date('Y-m-d H:i:s');
                        $this->Project_model->insert_project_funding($update_fund);
                    }   
                }
            }

            $insert['current_amount'] = $currentAmount; 
            //xss clean
            $insert = $this->security->xss_clean($insert);
            $insert['long_description'] = isset($jsonArray['longDescription'])?$jsonArray['longDescription']:'';

            $this->Project_model->project_country_regions_delete($id);
            //delete all mapping of this project before insert
            if(!empty($countryRegion))
            {
                $insert_country['project_id'] = $id;
                foreach($countryRegion as $regions)
                {
                    $country = $regions['country'];
                    $countryCode = $regions['countryCode'];
                    //country details
                    if(!empty($country) && !empty($countryCode) )
                    {
                        $state = isset($regions['state'])?$regions['state']:'';
                        $insert_country['country_id'] = $this->Country_model->country_get_insert($country, $countryCode);
                        if(!empty($state))
                            $insert_country['state_id'] = $this->Country_model->state_get_insert($state, $insert_country['country_id']);
                        else
                            $insert_country['state_id'] = NULL;
                        $this->Project_model->project_country_region($insert_country);
                    }
                }
            }
            //check group for this project created or not
            $project_group_exists = $this->Project_model->project_group_exists($id);
            if(!empty($project_group_exists))
            {
                //update project group name
                $group_id = $project_group_exists->id;
                $group_name = str_replace(' ','_',$project_name);
                $update_group['name'] =  $group_name."_Accepted";
                $update_group['last_updated'] = date('Y-m-d H:i:s');
                $this->Company_model->update_group($update_group,$group_id);
            }
            
        }

        //update project
        $insert['track_goal'] = 1;
        
        //routes
        $ngo_details = $this->Ngo_model->organization_details($ngo_id, 'any');
        $ngo_name = $ngo_details->ngo_url_suffix;
        $ngo_url_suffix = $ngo_details->ngo_url_suffix;
        $url1 = str_replace(' ', '-', $project_name); 
        // $url1 = 'projects/'.rawurlencode($url1).'-'.$id;
        // $check1 = $this->Routing_model->get_url_slug_data($url1);
        // if(!empty($check1))
        // {
        //     if($check1->entity_id!=$id)
        //     {
        //         $data['error'] = true;
        //         $data['status'] = 400;
        //         $data['message'] = "Url routing unique constraint failed, Please change project name.";
        //         header('HTTP/1.1 400 Validation Error.');
        //         echo json_encode($data,JSON_NUMERIC_CHECK);
        //         exit;
        //     }
        // }
        if($ngo_url_suffix!=null && $ngo_url_suffix!='')
        {
            $url2 = rawurlencode($ngo_url_suffix).'/projects/'.rawurlencode($url1).'-'.$id;
            $check2 = $this->Routing_model->get_url_slug_data($url2);
            if(!empty($check2))
            {
                if($check2->second_entity_id!=$id)
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "Url routing unique constraint failed, Please change project name.";
                    header('HTTP/1.1 400 Validation Error.');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    exit;
                }
            }
        }
        //routes

        $this->Project_model->update_project($insert, $id);

        //routes
        if($projectDetails->title!=$project_name)
        {
            // $update_url1['url_slug'] = $url1;
            // $this->Routing_model->update_url($update_url1, array('page_id'=>11, 'entity_id'=>$id));

            if($ngo_url_suffix!=null && $ngo_url_suffix!='')
            {
                $update_url2['url_slug'] = $url2;
                $this->Routing_model->update_url($update_url2, array('page_id'=>10, 'entity_id'=>$ngo_id, 'second_entity_id'=>$id));
            }
        }
        // routes

        $this->organisation_beneficiaries($ngo_id);
        //update company beneficiaries      
        if(!empty($members))
        {               
            //update fund details
            foreach($members as $member)
            {
                $companyId = $member['companyId'];
                //get totalBenefeciaries of this company
                $totalBenefeciariesCompany = $this->Project_model->total_beneficiaries_company($companyId);
                if(!empty($totalBenefeciariesCompany))
                {
                    $update_comp_benef['total_no_of_benefeciaries'] = $totalBenefeciariesCompany->no_benefeciaries;
                    $this->Project_model->update_organisation($update_comp_benef, $companyId);
                }
            }
            
        }//if(!empty($members)) 
        //update company beneficiaries ends
        //update outcomes
        if(!empty($outcomes))
        {
            $outcome_insert['last_updated'] = date('Y-m-d H:i:s');
            foreach($outcomes as $outcome_list)
            {
                $outcome_id = $outcome_list['id'];
                $outcome_insert['goal_target'] = $outcome_list['goalOutcome'];
                $outcome_insert['goal_achieved'] = $outcome_list['currentOutcome'];
                $outcome_insert['goal'] = $outcome_list['outcome'];
                $outcome_insert['description'] = $outcome_list['description'];
                $outcome_insert['categories_id'] = $outcome_list['categoryId'];
                if(empty($outcome_id))
                {
                    $outcome_insert['date_created'] = date('Y-m-d H:i:s');
                    $outcome_insert['project_id'] = $id;
                    $this->Project_model->insert_outcome($outcome_insert);
                }
                else
                {                   
                    //update outcomes
                    $this->Project_model->update_outcomes($outcome_insert, $outcome_id);
                }
                
            }//foreach
        }//if
        
        if($audit_id!='false')              
            $this->Audit_model->activate_audit($audit_id);
        //audit update_project

        //update hashtags
        if(!empty($hashtag))
        {
            foreach($hashtag as $hashtag_list)
            {
                $hashtag_id = $hashtag_list['id'];
                //insert into hash_tags table
                if(empty($hashtag_id))
                {
                    $insert_hashtag['version'] = 0;
                    $insert_hashtag['date_created'] = $insert_hashtag['last_updated'] = date('Y-m-d H:i:s');
                    $insert_hashtag['hash_tag'] = $hashtag_list['hashTag'];
                    $insert_hashtag['is_active'] = 1;
                    $insert_hashtag['is_deleted'] = 0;
                    $insert_hashtag['is_default'] = 0;
                    //chk duplicate hashtag exists in db table
                    $chk_duplicate = $this->Hashtag_model->check_duplicate_hashtag($hashtag_list['hashTag']);
                    $default = isset($chk_duplicate->is_default)?$chk_duplicate->is_default:0;
                    //if this is new hashtag then add this to hashtag table
                    if (ord($default)==0 || $default==0)
                    {
                        if(empty($chk_duplicate))
                            $hashtag_id = $this->Hashtag_model->insert_hashtag($insert_hashtag);
                        else
                            $hashtag_id = $chk_duplicate->id;
                    }
                }
                
                // add hashtag entries in to project_hash_tags which are not present
                $proj_hashtag_id_cnt = $this->Hashtag_model->check_proj_hashtag_exists($id, $hashtag_id)->num;
                $chk_hashtagid = $this->Hashtag_model->check_hashtagid_exist($hashtag_id)->num;
                if($proj_hashtag_id_cnt == 0 && $chk_hashtagid!=0)
                {
                    $insert_proj_hash['project_hash_tags_id'] = $id; 
                    $insert_proj_hash['hash_tags_id'] = $hashtag_id;
                    $this->Hashtag_model->insert_project_hashtags($insert_proj_hash);
                }
            }//foreach
        }
    
        $data = $this->project_details($id);
        return $data;
    }//function
    public function delete_outcome($id)
    {
        $id = (int) $id;
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

        $id = (int) $id;
        $method = $this->input->get('method');
        if($method!="DELETE")
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        
        //check outcome by id exists or not
        $outcome = $this->Project_model->outcome_details($id);
        if(!empty($outcome))
        {
            //check at least 1 outcome should be there for a project
            $project_id = $outcome->project_id;
            $outcome_count = $this->Project_model->active_outcome_count($project_id);
            $num = $outcome_count->num;
            if($num==1)
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Project should have at least one outcome.";
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data);
                return;
            }

            $update['deleted_at'] = date('Y-m-d H:i:s');
            $update['is_deleted'] = 1;

            //audit delete_outcome
            $audit_info['user_id'] = $valid_auth_token->user_id;
            $audit_info['role_id'] = $valid_auth_token->role_id;
            $audit_info['org_id'] = $this->input->get('ngoId');
            $audit_info['entity'] = 'outcome';
            $audit_info['entity_id'] = $id;
            $audit_info['action'] = 'deleted';

            $audit_id = $this->Audit_model->delete_audit($outcome, $audit_info);
            
            $this->Project_model->update_outcomes($update, $id);
            
            if(isset($audit_id))                
                $this->Audit_model->activate_audit($audit_id);
            //audit delete_outcome

            $updates = $this->Activity_model->delete_updates_media_by_outcome_id($id);

            $data['error'] = false;
            $data['status'] = 200;
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find outcome using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
    }//delete_outcome

    protected function organisation_beneficiaries($ngo_id)  
    {
        $organisationData = $this->Project_model->total_beneficiaries($ngo_id);
        if(!empty($organisationData))
        {
            $update_org['total_no_of_benefeciaries'] = $organisationData->no_benefeciaries;
            $this->Project_model->update_organisation($update_org, $ngo_id);
        }
        return;
    }//organisation_beneficiaries
    
    // function to delete hashtag mapping with the project. Also if mapping not exists for hashtag set the status to false in hash_tags
    public function delete_proj_hashtag($proj_id, $tag_id)
    {
        $this->Hashtag_model->delete_project_hashtag($proj_id, $tag_id);
        $data['error'] = false;
        $data['status'] = 200;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }
    public function project_media($id)
    {
        //list images and videos added on a project activity
        $project_info = $this->Project_model->project_details($id);
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;   
        if(empty($project_info)){
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find project using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            return $data;
        }
        $data['error'] = false;
        $data['resp']['count'] = $this->Activity_model->project_activity_media_count($id);
        $data['resp']['media'] = $this->Activity_model->project_activity_media($id, $offset, $limit);
        echo json_encode($data,JSON_NUMERIC_CHECK);
    }//public function project_media($id)
    public function project_latest_updates($id)
    {
        $project_info = $this->Project_model->project_details($id);
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;   
        if(empty($project_info)){
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find project using id mentioned.";
            header('HTTP/1.1 404 Not Found');
            return $data;
        }
        $data['error'] = false;
        $outcome_info = $this->Project_model->outcome_list($id);
        $outcome_ids = array_column(json_decode(json_encode($outcome_info), true), 'id');

        $activity_list = $this->Activity_model->project_activity($id, $offset, $limit);
        $i=0;
        $activity_data = array();
        if(!empty($activity_list))
        {
            foreach ($activity_list as $activity) {
                $activity_data[$i]['project_report_id'] = $activity->id;
                $activity_data[$i]['title'] = $activity->report;
                $activity_data[$i]['last_updated'] = $activity->last_updated;
                $activity_data[$i]['date_created'] = $activity->date_created;
                $activity_data[$i]['project_report_type'] = $project_report_type = $activity->project_report_type;
                if($project_report_type == 'Progress Update')
                {
                    $outcome_id = $activity->goal_id;
                    $rank = array_search($outcome_id,$outcome_ids,true) + 1;
                    $outcome_details = $this->Activity_model->outcome_details($outcome_id);
                    $activity_data[$i]['outcome'] = $outcome_details->goal;
                    $activity_data[$i]['outcomeRank'] = $rank;
                    $activity_data[$i]['new_progress'] = $activity->new_progress;
                    $activity_data[$i]['current_outcome'] = $activity->current_goal;
                    $activity_data[$i]['goal'] = $outcome_details->goal_target;
                }
                $media = $this->Activity_model->activity_media($activity->id);
                $activity_data[$i]['media'] = $media;
                $i++;
            }
        }
        $data['resp']['count'] = $this->Activity_model->project_activity_count($id, $offset, $limit);
        $data['resp']['updates'] = $activity_data;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function get_status()
    {
        $status = array(
            0=> array(
                'id'=>1,
                'name'=>'Fundraising',
            ),
            1=> array(
                'id'=>2,
                'name'=>'In Progress',
            ),
            2=> array(
                'id'=>3,
                'name'=>'Complete',
            ),
            3=> array(
                'id'=>4,
                'name'=>'Inactive',
            ),
        );
        echo json_encode($status);
        return;
    }

    public function delete_goals_media()
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

        $deleted_goals = $this->Project_model->get_deleted_goals();
        $deleted_goals = array_column(json_decode(json_encode($deleted_goals),true), 'id');

        foreach ($deleted_goals as  $value) {
            $this->Activity_model->delete_updates_media_by_outcome_id((int)$value);
        }

        $data['error'] = false;
        $data['message'] = "Successfully deleted.";
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function get_all_projects()
    {
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;           
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $status = ($this->input->get('status'))?$this->input->get('status'):'';
        $countryId = ($this->input->get('countryId'))?$this->input->get('countryId'):'';
        $category_id = ($this->input->get('categoryId'))?$this->input->get('categoryId'):'';
        $ngoId = ($this->input->get('ngoId'))?$this->input->get('ngoId'):'';

        if($status!='')
        {
            $status = strtolower($status);
            $status = str_replace('-', ' ', $status);
        }

        $project_count = $this->Project_model->get_project_list_count($query, $status, $countryId, $category_id, $ngoId);
        $project_list = $this->Project_model->get_project_list($limit, $offset, $query, $status, $countryId, $category_id, $ngoId);
        $project_final_data = array();
        $p = 0;
        foreach($project_list as $project)
        {
            $id = $project->id;
            $project_info = $this->Project_model->project_details($id);
            $project_data = array();
            $project_data['id'] = $project_info->id;
            $project_data['longDescription'] = $project_info->long_description;
            $project_data['shortDescription'] = $project_info->short_description;
            $project_data['imageUrl'] = $project_info->image_url;
            $project_data['videoUrl'] = $project_info->video_url;
            $project_data['title'] = $project_info->title;
            $project_data['totalBenefeciaries'] = $project_info->total_benefeciaries;
            $project_data['status'] = $project_info->status_name;
            $project_data['microSite'] = $project_info->micro_site;
            $start_date = $end_date = '';
            $project_data['startDate'] = $start_date = $project_info->start_date;
            $project_data['endDate'] = $end_date = $project_info->end_date;
            if($start_date!='')
            {
                $project_data['startDate'] = $start_date = strtoupper(date('F d, Y',strtotime($start_date)));
            }
            if($end_date!='')
            {
                $project_data['endDate'] = $end_date = strtoupper(date('F d, Y',strtotime($end_date)));
            }
            if($start_date!='' && $end_date!='')
            {
                $duration = strtotime($end_date) - strtotime($start_date);
                $days = floor($duration/(24*60*60));
                $project_data['duration'] = $days;
            }
            $project_data['overAllOutcome'] = $project_info->over_all_goal;
            $project_data['targetAmount'] = $project_info->goal_amount;
            $project_data['fundingsToDate'] = $project_info->fundings_to_date;
            $project_data['currentAmount'] = $project_info->current_amount;
            $project_data['noOfPeopleInvolved'] = $project_info->no_of_people_involved;
            $project_data['lastUpdated'] = $project_info->last_updated;
            $project_data['dateCreated'] = $project_info->date_created;
            $latest_update = $this->Activity_model->get_latest_project_activity($id);
            $string = null;
            if(!empty($latest_update))
                $updatedAt = $latest_update->last_updated;
            else
                $updatedAt = $project_info->last_updated;

            $current_date = date('Y-m-d H:i:s');
            $date_diff = strtotime($current_date) - strtotime($updatedAt);

            $seconds = array( 365 * 24 * 60 * 60  =>  'year',
                         30 * 24 * 60 * 60  =>  'month',
                              24 * 60 * 60  =>  'day',
                                   60 * 60  =>  'hour',
                                        60  =>  'minute',
                                         1  =>  'second'
                        );
            $seconds_plural = array( 'year'   => 'years',
                               'month'  => 'months',
                               'day'    => 'days',
                               'hour'   => 'hours',
                               'minute' => 'minutes',
                               'second' => 'seconds'
                        );
            foreach ($seconds as $secs => $str)
            {
                $d = $date_diff / $secs;
                if ($d >= 1)
                {
                    $r = round($d);
                    $string = $r . ' ' . ($r > 1 ? $seconds_plural[$str] : $str) . ' ago';
                    break;
                }
            }
            $project_data['updatedAt'] = $string;
            $project_data['completedAt'] = $updatedAt;

            $project_data['deletedAt'] = $project_info->deleted_at;         
            $project_data['isCrowdFunded'] = $project_info->is_crowd_sourced;
            if (ord($project_data['isCrowdFunded'])==1 || $project_data['isCrowdFunded']==1)
                $project_data['isCrowdFunded'] = true;
            else
                $project_data['isCrowdFunded'] = false;
            if (ord($project_info->is_featured_project)==1 || $project_info->is_featured_project==1)
                $project_data['isFeaturedProject'] = true;
            else
                $project_data['isFeaturedProject'] = false;
            $project_data['ngoId'] = $ngo_id = $project_info->ngo_id;
            $ngo_details = $this->Ngo_model->organization_details($ngo_id, 'any');
            $project_data['ngoName'] = $ngo_details->name;
            $project_data['brandingUrlView'] = $ngo_details->ngo_url_suffix;
            //country details
            $country_info = $this->Project_model->project_country_details($id);

            if(!empty($country_info))
            {
                
                $project_data['country'] = $country_info->name;
                $project_data['countryCode'] = $country_info->code;
            }else
            {
                $project_data['country'] = "";
                $project_data['countryCode'] = "";
            }

            //outcome list
            $outcome_data = array();
            $outcome_info = $this->Project_model->outcome_list($id);
            $achievedOutcome=0;
            if(!empty($outcome_info))
            {
                $i = 0;
                foreach($outcome_info as $outcome)
                {
                    $outcome_data[$i]['id'] = $outcome->id;
                    $outcome_data[$i]['outcome'] = $outcome->goal;
                    $outcome_data[$i]['currentOutcome'] = $outcome->goal_achieved;
                    $outcome_data[$i]['goalOutcome'] = $outcome->goal_target;
                    $outcome_data[$i]['description'] = $outcome->description;
                    $achievedOutcome+=$outcome_data[$i]['currentOutcome'];
                    
                    $categories_id = $outcome->categories_id;
                    
                    //get tha data of categories associated with goal
                    if(!empty($categories_id))
                    {
                        $cat_info = $this->Category_model->category_info($categories_id);
                        $cat_data = array();
                        if(!empty($cat_info))
                        {
                            $cat_data['id'] = $cat_info->id;
                            $cat_data['category'] = $cat_info->category;
                            $cat_data['subcategory'] = $cat_info->subcategory;
                            $cat_data['logoUrl'] = $cat_info->image_url;
                        }
                        $outcome_data[$i]['category'] = $cat_data;
                    }
                    $i++;
                }
            }
            $project_data['achievedOutcome'] = $achievedOutcome;
            //member list
            $member_data = array();
            $member_list = $this->Project_model->list_project_members($id);
            if(!empty($member_list))
            {
                $k = 0;
                foreach($member_list as $member_info)
                {
                    $member_data[$k]['id'] = $member_info->id;
                    $member_data[$k]['name'] = $member_info->first_name." ".$member_info->last_name;
                    $member_data[$k]['imageUrl'] = $member_info->image_url;
                    $k++;
                }
            }   
            //country details
            $country_regions = $this->Project_model->project_country_regions($id);
            $final_country = array();
            $cr = 0;
            if(!empty($country_regions))
            {
                foreach ($country_regions as $country_region)
                {
                    $countryCode = "";
                    $country = "";
                    $state = "";
                    $country_id = $country_region->country_id;
                    $state_id = $country_region->state_id;
                    if(!empty($country_id))
                    {
                        $country_info = $this->Country_model->country_info($country_id);
                        if(!empty($country_info))
                        {
                            $country = $country_info->name;
                            $countryCode = $country_info->code;
                            $countryUrl = $country_info->flag_url;
                        }
                        if(!empty($state_id))
                        {
                            $state_info = $this->Country_model->state_info($state_id);
                            if(!empty($state_info))
                            {
                                $state = $state_info->name;                             
                            }
                        }
                    }
                    $final_country[$cr]['country']  = $country;
                    $final_country[$cr]['countryCode']  = $countryCode;
                    $final_country[$cr]['countryUrl']   = $countryUrl;
                    $final_country[$cr]['state']    = $state;
                    $cr++;
                }           
            }    

            $donors = $this->Project_model->get_project_donors($id);
            $donors_data = array();
            if(!empty($donors))
            {
                $i=0;
                foreach ($donors as $donor) {
                    $donors_data[$i]['id'] = $donor->id;
                    $donors_data[$i]['imageUrl'] = $donor->image_url;
                    $donors_data[$i]['donorUrl'] = $donor->donor_url;
                    $donors_data[$i]['name'] = $donor->name;
                    $i++;
                }
            }

            $project_final_data[$p] = $project_data;
            $project_final_data[$p]['outcomes'] = $outcome_data;
            $project_final_data[$p]['members'] = $member_data;  
            $project_final_data[$p]['countryRegion'] = $final_country;
            $project_final_data[$p]['donor'] = $donors_data;    

            $is_new_highlight = $this->Project_model->is_new_highlight($id);
            if(empty($is_new_highlight))
                $project_final_data[$p]['is_new_highlight'] = false;
            else
            {
                $update_date = $is_new_highlight->date_created;
                $current_date = date('Y-m-d H:i:s');
                $diff  = (strtotime($current_date) - strtotime($update_date))/3600;
                if($diff<=48)
                    $project_final_data[$p]['is_new_highlight'] = true;
                else
                    $project_final_data[$p]['is_new_highlight'] = false;
            }
            $p++;
        }
        $data['error'] = false;
        $data['resp']['count'] = $project_count;
        $data['resp']['project'] = $project_final_data;
        echo json_encode($data, JSON_NUMERIC_CHECK);
        return;
    }
}//end project

/* End of file project.php */
/* Location: ./application/controllers/project.php */