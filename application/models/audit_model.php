<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH.'libraries/aws-autoloader.php';
require './vendor/autoload.php';

use Aws\S3\S3Client;
use JonnyW\PhantomJs\Client;

class Audit_model extends CI_Model 
{
    public function save_design_page($audit_info)
    {
        $insert_audit['user_id'] = $audit_info['user_id'];
        $insert_audit['role_id'] = $audit_info['role_id'];
        $insert_audit['organisation_id'] = $audit_info['org_id'];
        $insert_audit['datetime'] = date('Y-m-d H:i:s');
        $insert_audit['entity'] = $audit_info['entity'];
        $insert_audit['entity_id'] = $audit_info['entity_id'];
        $insert_audit['action'] = $audit_info['action'];
        $insert_audit['is_active'] = 1;

        $this->db->insert('audits', $insert_audit);
        return mysql_insert_id();
    } 

    public function publish_design_page($audit_info, $branding_url)
    {
        $insert_audit['datetime'] = date('Y-m-d H:i:s');
        
        $name = "image".date('Y-m-d H:i:s').".jpg";

        $client = Client::getInstance();
        
        $width  = 1200;
        $height = 1200;
        $delay = 60;
        $request = $client->getMessageFactory()->createCaptureRequest($branding_url, 'GET');
        $request->setOutputFile("design_images/".$name);
        $request->setViewportSize($width, $height);
        $request->setDelay($delay);
        $response = $client->getMessageFactory()->createResponse();
        $client->send($request, $response);

        $myfile = fopen("design_images/".$name, "r") or die("Unable to open file!");
        $filedata = fread($myfile, filesize("design_images/".$name));
        fclose($myfile);
        unlink("design_images/".$name);

        $this->load->library('s3');
        $this->config->load('s3', true);
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $this->config->item('aws_s3_region', 's3'),
            'credentials' => [
                'key'    => $this->config->item('access_key', 's3'),
                'secret' => $this->config->item('secret_key', 's3')
            ]
        ]);

        $bucket = $this->config->item('bucket_name', 's3');

        $key = "design_pages/$name";
        $res = $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'Body'   => $filedata
        ]);
        $link = 'https://'.$bucket.'.s3.amazonaws.com/'.$key;

        $old_url_data = $this->get_last_screenshot_url($audit_info['org_id']);
        if(!empty($old_url_data))
        {
            $old_url = $old_url_data->publishUrl;
            $old_data = array('page' => $old_url);
        }
        else
            $old_data = array();
        $new_data = array('page' => $link);

        $insert_template['publishUrl'] = $link;
        $insert_template['ngo_id'] = $audit_info['org_id'];
        $insert_template['created_at'] = date('Y-m-d H:i:s');
        $insert_template['updated_at'] = date('Y-m-d H:i:s');
        $this->store_template_data($insert_template);

        $insert_audit['user_id'] = $audit_info['user_id'];
        $insert_audit['role_id'] = $audit_info['role_id'];
        $insert_audit['organisation_id'] = $audit_info['org_id'];
        $insert_audit['entity'] = $audit_info['entity'];
        $insert_audit['entity_id'] = $audit_info['entity_id'];
        $insert_audit['action'] = $audit_info['action'];
        $insert_audit['old_data'] = json_encode($old_data);
        $insert_audit['new_data'] = json_encode($new_data);
        $insert_audit['is_active'] = 1;

        $this->db->insert('audits', $insert_audit);
        return mysql_insert_id();
    } 

    public function store_template_data($insert) 
    {
        $this->db->insert('publishdata', $insert);
        return $this->db->insert_id();
    }

    public function get_last_screenshot_url($ngo_id)
    {
        $this->db->select('publishUrl');
        $this->db->from('publishdata');
        $this->db->where('ngo_id',$ngo_id);
        $this->db->order_by('id', 'desc');
        $result = $this->db->get();
        return $result->row(); 
    }

    public function check_diff($old_data,$new_data)
    {
        $CI = get_instance();
        $CI->load->helper('array_diff');
        $return['old_diff'] = arrayRecursiveDiff($old_data, $new_data);
        $return['new_diff'] = arrayRecursiveDiff($new_data, $old_data);
        return $return;
    }

    public function get_keys($entity)
    {
        $query = "select * from audit_keys where entity='$entity'";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function get_ui_key($key,$entity)
    {
        $query = "select ui_key from audit_keys where backend_key='$key' and entity='$entity' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function change_keys($old_changes,$entity)
    {
        $keys_data = $this->get_keys($entity);

        $old_changes_with_keys = array();
        if(!empty($old_changes))
        {
            foreach($old_changes as $key => $value)
            {
                if(is_array($old_changes[$key]))
                {
                    $entity1 = $entity;
                    if(is_int($key)==false)
                        $entity1=$key;
                    $data = $this->change_keys($old_changes[$key],$entity1);
                    
                    $array_key = $this->get_ui_key($key,$entity);
                    if(!empty($array_key))
                        $old_changes_with_keys[$array_key->ui_key] = $data;
                    elseif(is_int($key)==true)
                        $old_changes_with_keys[$key] = $data;
                    continue;
                }
                foreach ($keys_data as $value2) 
                {
                    if($key==$value2->backend_key)
                    {
                        $old_changes_with_keys[$value2->ui_key] = $value;
                    }
                }
            }
        }
        return $old_changes_with_keys;
    }

    //ngo, project, activity, donor update
    public function update_audit($old_data,$new_data,$audit_info)
    {
        $old_data = json_decode(json_encode($old_data),true);
        $new_data = json_decode(json_encode($new_data),true);
        $data = $this->check_diff($old_data,$new_data);
        $result = array_merge($data['old_diff'], $data['new_diff']);
        $old_changes = array_intersect_key($old_data, $result);
        $new_changes = array_intersect_key($new_data, $result);
        $entity = $audit_info['entity'];
        $old_changes = $this->change_keys($old_changes,$entity);
        $new_changes = $this->change_keys($new_changes,$entity);

        if(!empty($old_changes) || !empty($new_changes)) 
        {
            $insert_audit['user_id'] = $audit_info['user_id'];
            $insert_audit['role_id'] = $audit_info['role_id'];
            $insert_audit['organisation_id'] = $audit_info['org_id'];
            $insert_audit['datetime'] = date('Y-m-d H:i:s');
            $insert_audit['entity'] = $audit_info['entity'];
            $insert_audit['entity_id'] = $audit_info['entity_id'];
            $insert_audit['action'] = $audit_info['action'];
            $insert_audit['old_data'] = json_encode($old_changes);
            $insert_audit['new_data'] = json_encode($new_changes);

            $this->db->insert('audits', $insert_audit);
            return mysql_insert_id();
        }
        return 'false';
    }

    //project, user status update
    public function update_audit_2($old_data,$new_data,$audit_info) 
    {
        $old_data = json_decode(json_encode($old_data),true);
        $new_data = json_decode(json_encode($new_data),true);
        $data = $this->check_diff($old_data,$new_data);
        $old_changes = array_intersect_key($old_data, $data['new_diff']);
        $new_changes = array_intersect_key($new_data, $data['new_diff']);
        
        $entity = $audit_info['entity'];
        $old_changes = $this->change_keys($old_changes,$entity);
        $new_changes = $this->change_keys($new_changes,$entity);

        if(!empty($old_changes))
        {
            $insert_audit['user_id'] = $audit_info['user_id'];
            $insert_audit['role_id'] = $audit_info['role_id'];
            $insert_audit['organisation_id'] = $audit_info['org_id'];
            $insert_audit['datetime'] = date('Y-m-d H:i:s');
            $insert_audit['entity'] = $audit_info['entity'];
            $insert_audit['entity_id'] = $audit_info['entity_id'];
            $insert_audit['action'] = $audit_info['action'];
            
            $insert_audit['old_data'] = json_encode($old_changes); 
            $insert_audit['new_data'] = json_encode($new_changes); 
            
            $this->db->insert('audits', $insert_audit);
            return mysql_insert_id();
        }
        return 'false';
    }

    //media caption update
    public function update_audit_3($old_data,$new_data,$audit_info)
    {
        $old_data = json_decode(json_encode($old_data),true);
        $new_data = json_decode(json_encode($new_data),true);
        $data = $this->check_diff($old_data,$new_data);
        
        if(!empty($data['old_diff']))
        {
            $entity = $audit_info['entity'];
            $old_changes = $this->change_keys($old_data,$entity);
            $new_changes = $this->change_keys($new_data,$entity);
            
            $insert_audit['user_id'] = $audit_info['user_id'];
            $insert_audit['role_id'] = $audit_info['role_id'];
            $insert_audit['organisation_id'] = $audit_info['org_id'];
            $insert_audit['datetime'] = date('Y-m-d H:i:s');
            $insert_audit['entity'] = $audit_info['entity'];
            $insert_audit['entity_id'] = $audit_info['entity_id'];
            $insert_audit['action'] = $audit_info['action'];
            $insert_audit['old_data'] = json_encode($old_changes); 
            $insert_audit['new_data'] = json_encode($new_changes); 
            
            $this->db->insert('audits', $insert_audit);
            return mysql_insert_id();
        }
        return 'false';
    }

    //----------------------------------------------------
    //create project, activity,activity media, donor
    public function create_audit($new_data,$audit_info)
    {
        $entity = $audit_info['entity'];
        $changes = $this->change_keys($new_data,$entity);
        $new_changes = $changes;

        $insert_audit['user_id'] = $audit_info['user_id'];
        $insert_audit['role_id'] = $audit_info['role_id'];
        $insert_audit['organisation_id'] = $audit_info['org_id'];
        $insert_audit['datetime'] = date('Y-m-d H:i:s');
        $insert_audit['entity'] = $audit_info['entity'];
        $insert_audit['entity_id'] = $audit_info['entity_id'];
        $insert_audit['action'] = $audit_info['action'];
        $insert_audit['old_data'] = "";
        $insert_audit['new_data'] = json_encode($new_changes);
        $insert_audit['is_active'] = 1;
        
        $this->db->insert('audits', $insert_audit);
        return mysql_insert_id();
    }

    //--------------------------------------------------------
    //delete outcome, activity,activity media, donor
    public function delete_audit($old_data,$audit_info)
    {
        $old_data = json_decode(json_encode($old_data),true);
        $entity = $audit_info['entity'];
        $changes = $this->change_keys($old_data,$entity);
        
        $insert_audit['user_id'] = $audit_info['user_id'];
        $insert_audit['role_id'] = $audit_info['role_id'];
        $insert_audit['organisation_id'] = $audit_info['org_id'];
        $insert_audit['datetime'] = date('Y-m-d H:i:s');
        $insert_audit['entity'] = $audit_info['entity'];
        $insert_audit['entity_id'] = $audit_info['entity_id'];
        $insert_audit['action'] = $audit_info['action'];
        $insert_audit['old_data'] = json_encode($changes); 
        $insert_audit['new_data'] = ''; 
        
        $this->db->insert('audits', $insert_audit);
        return mysql_insert_id();
    }

    //---------------------------------------------------
    public function login($response)
    {
        if(!empty($response))
        {
            $username = $response->username;

            $user_info = $this->user_info_by_username($username);
            $user_id = $user_info->id;
            $role_id = $user_info->role_id;

            $last_activity = $this->check_last_login_activity($user_id);
            if(!empty($last_activity))
            {
                if($last_activity->entity=='login')
                {
                    $new_date = date('Y-m-d H:i:s');
                    $date = $this->get_login_date($user_id);
                    $old_date = $last_activity->datetime;;

                    $sec = strtotime($new_date) - strtotime($old_date);
                
                    $insert_audit['user_id'] = $user_id;
                    $insert_audit['role_id'] = $role_id;
                    $insert_audit['organisation_id'] = '';
                    $insert_audit['datetime'] = date('Y-m-d H:i:s');
                    $insert_audit['entity'] = 'logout';
                    $insert_audit['entity_id'] = '';
                    $insert_audit['action'] = 'logout';
                    $insert_audit['logout_session'] = $sec;
                    $insert_audit['is_active'] = '1';
                    
                    $this->db->insert('audits', $insert_audit);
                }
            }

            $insert_audit['user_id'] = $user_id;
            $insert_audit['role_id'] = $role_id;
            $insert_audit['organisation_id'] = '';
            $insert_audit['datetime'] = date('Y-m-d H:i:s');
            $insert_audit['entity'] = 'login';
            $insert_audit['entity_id'] = '';
            $insert_audit['action'] = 'login';
            $insert_audit['logout_session'] = '';
            $insert_audit['is_active'] = '1';
            
            $this->db->insert('audits', $insert_audit);
            return mysql_insert_id();
        }
    }

    public function logout($valid_auth_token, $ngo_id)
    {
        $user_id = $valid_auth_token->user_id;
        $role_id = $valid_auth_token->role_id;

        $new_date = date('Y-m-d H:i:s');
        $date = $this->get_login_date($user_id);
        if(!empty($date))
        {
            $old_date = $date->datetime;
            $sec = strtotime($new_date) - strtotime($old_date);
        }
        else
            $sec='';
    
        $insert_audit['user_id'] = $user_id;
        $insert_audit['role_id'] = $role_id;
        $insert_audit['organisation_id'] = $ngo_id;
        $insert_audit['datetime'] = date('Y-m-d H:i:s');
        $insert_audit['entity'] = 'logout';
        $insert_audit['entity_id'] = '';
        $insert_audit['action'] = 'logout';
        $insert_audit['logout_session'] = $sec;
        $insert_audit['is_active'] = '1';
        
        $this->db->insert('audits', $insert_audit);
    }

    public function activate_audit($id)
    {
        $query = "update audits set is_active='1' where id=$id";
        $result = $this->db->query($query);
        return;
    }

    public function get_audits($offset,$limit) {
        $query="select * from audits where is_active=1 ORDER BY id desc limit $offset,$limit";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function count_audits() {
        $query="select count(*) as num from audits where is_active='1'";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function user_info($id)
    {
        $query = "select * from user where id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function org_info($ngo_id)
    {
        $query = "select * from organisation where id=$ngo_id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function project_details($id)
    {
        $query = "select * from project where id='$id' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function outcome_details($id)
    {
        $query = "select * from goals where id='$id' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function activity_details($activity_id)
    {
        $query = "select * from project_report where id='$activity_id' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function activity_image_details($id)
    {
        $query="select * from project_report_image where id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function activity_video_details($id)
    {
        $query="select * from project_report_video where id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function check_last_login_activity($user_id)
    {
        $query="select entity,datetime from audits where user_id=$user_id and (entity='login' || entity='logout') order by id desc limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function user_info_by_username($username)
    {
        $query = "select * from user where username='$username' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }


    public function get_login_date($user_id)
    {
        $query="select datetime from audits where user_id=$user_id and entity='login' order by id desc limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function donor_details($id)
    {
        $query = "select donor.*, organisation.name as organisation_name from donor 
        join organisation on organisation.id=donor.organisation_id
        where donor.id='$id' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
}
