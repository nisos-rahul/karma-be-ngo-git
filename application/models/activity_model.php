<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Activity_model extends CI_Model 
{
    //insert activity   
    public function create_activity($insert)
    {
        $this->db->insert('project_report', $insert); 
        $id = $this->db->insert_id();
        return $id;
    }//create_activity
    public function add_report_image($image_insert)
    {
        $this->db->insert('project_report_image', $image_insert); 
        $id = $this->db->insert_id();       
        return $id;
    }//add_report_image
    public function add_report_video($video_insert)
    {
        $this->db->insert('project_report_video', $video_insert); 
        $id = $this->db->insert_id();       
        return $id;
    }//add_report_image
    public function previous_activity_outcome_details($outcome_id,$id)
    {
        $query = "select max(current_goal) as current_goal from project_report where 
        goal_id=$outcome_id and is_deleted=0 and id<>$id
        limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//previous_activity_outcome_details
    public function newer_activity_details($outcome_id,$id)
    {
        $query = "select * from project_report where 
        goal_id=$outcome_id and is_deleted=0 and id>$id";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function activity_details($id)
    {
        $query = "SELECT project_report.*,project.title,project.is_active,project.status_name,project.is_deleted,goals.goal,goals.is_deleted as isdeleted,user.first_name,user.last_name, 
        user.id as user_id,goals.id as goal_id,goals.goal_target
        FROM `project_report` join project on project_report.`project_id`=project.id 
        join goals on project_report.goal_id=goals.id 
        join user on project_report.user_id=user.id 
        where project_report.id=$id limit 1";       
        $result = $this->db->query($query);
        return $result->row();
    }

    public function activity_outcome_details($id)
    {
        $query = "SELECT project_report.*,goals.goal,goals.is_deleted as isdeleted, goals.id as goal_id,goals.goal_target
        FROM `project_report` 
        join goals on project_report.goal_id=goals.id 
        where project_report.id=$id limit 1";       
        $result = $this->db->query($query);
        return $result->row();
    }

    public function activity_highlight_details($id)
    {
        $query = "SELECT project_report.*,project.title,project.is_active,project.status_name,project.is_deleted,user.first_name,user.last_name, user.id as user_id
        FROM `project_report` join project on project_report.`project_id`=project.id 
        join user on project_report.user_id=user.id 
        where project_report.id=$id limit 1";       
        $result = $this->db->query($query);
        return $result->row();
    }

    public function report_image_entry($url)
    {
        $query = "select id from project_report_image where url='$url' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function report_video_entry($url)
    {
        $query = "select id from project_report_video where url='$url' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function list_report_videos($activity_id)
    {
        $query = "select * from project_report_video where project_report_id='$activity_id'
        and is_deleted=0 limit 4";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function list_report_images($activity_id)
    {
        $query = "select * from project_report_image where project_report_id='$activity_id'
        and is_deleted=0 limit 4";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function list_activity($search='',$offset,$limit,$ngo_id,$user_id='',$status=true)
    {
        $query="SELECT project_report.*,project.title,user.first_name,user.last_name,
        project.id as project_id,project.is_active
        FROM `project_report` join project on project_report.`project_id`=project.id 
        join user on project_report.user_id=user.id 
        WHERE project.ngo_id=$ngo_id and project_report.is_deleted!=$status ";
        if($user_id!='')
        {
            $query.=" and project_report.user_id=$user_id ";
        }
        if($search!='')
        {
            $query.=" and (project_report.report like '%$search%' or 
            project.title like '%$search%' or           
            concat_ws(' ',user.first_name,user.last_name) like '%$search%'          
            ) ";
        }
        $query.="  ORDER BY project_report.last_updated desc limit $offset,$limit";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function activity_count($search='',$offset,$limit,$ngo_id,$user_id='',$status=true)
    {
        $query="SELECT count(*) as num FROM `project_report` join project on project_report.`project_id`=project.id 
        join user on project_report.user_id=user.id 
        WHERE project.ngo_id=$ngo_id and project_report.is_deleted!=$status ";
        if($user_id!='')
        {
            $query.=" and project_report.user_id=$user_id  ";
        }
        if($search!='')
        {
            $query.=" and (project_report.report like '%$search%' or 
            project.title like '%$search%' or 
            concat_ws(' ',user.first_name,user.last_name) like '%$search%') ";
        }       
        $result = $this->db->query($query);
        return $result->row();
    }

    public function update_activity($update,$activity_id)
    {
        $this->db->update('project_report', $update, array('id' => $activity_id));
        return;
    }
    public function active_activity_details($activity_id)
    {
        $query="select * from project_report where id=$activity_id and is_deleted=0 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function activity_video_details($activity_id,$id)
    {
        $query="select * from project_report_video 
        where project_report_id=$activity_id and id=$id and is_deleted=0 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function update_report_video($update,$id)
    {
        $this->db->update('project_report_video', $update,array('id'=>$id)); 
        return;
    }
    public function activity_image_details($activity_id,$id)
    {
        $query="select * from project_report_image where project_report_id=$activity_id and id=$id and is_deleted=0 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function update_report_image($update,$id)
    {
        $this->db->update('project_report_image', $update,array('id'=>$id)); 
        return;
    }
    public function active_video_count($activity_id)
    {
        $query="select count(*) as num from project_report_video where project_report_id=$activity_id and is_deleted=0";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function active_image_count($activity_id)
    {
        $query="select count(*) as num from project_report_image where project_report_id=$activity_id and is_deleted=0";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function project_activity_media_count($id)
    {
        $this->db->select('count(project_report_image.id) as num ');
        $this->db->from('project_report_image');
        $this->db->join('project_report','project_report.id = project_report_image.project_report_id');
        $this->db->where(array('project_report.project_id'=>$id,'project_report.is_deleted'=>false,'project_report_image.is_active'=>true,'project_report_image.is_deleted'=>false));
        $result = $this->db->get();
        $count1 = $result->row()->num;

        $this->db->select('count(project_report_video.id) as num ');
        $this->db->from('project_report_video');
        $this->db->join('project_report','project_report.id = project_report_video.project_report_id');
        $this->db->where(array('project_report.project_id'=>$id,'project_report.is_deleted'=>false,'project_report_video.is_active'=>true,'project_report_video.is_deleted'=>false));
        $result2 = $this->db->get();
        $count2 = $result2->row()->num;
        return $count1 + $count2;
    }
    public function project_activity_media($id,$offset,$limit)
    {
        $this->db->select("project_report_image.id,project_report_image.last_updated,project_report_image.date_created,project_report_image.url,project_report_image.caption,'image' as type,project_report_image.thumb_url as thumbUrl ",false);
        $this->db->from('project_report_image');
        $this->db->join('project_report','project_report.id = project_report_image.project_report_id');
        $this->db->where(array('project_report.project_id'=>$id,'project_report.is_deleted'=>false,'project_report_image.is_active'=>true,'project_report_image.is_deleted'=>false));
        $this->db->get(); 
        $query1 =  $this->db->last_query();

        $this->db->select("project_report_video.id,project_report_video.last_updated,project_report_video.date_created,project_report_video.url,project_report_video.caption,'video' as type,project_report_video.thumb_url as thumbUrl ",false);
        $this->db->from('project_report_video');
        $this->db->join('project_report','project_report.id = project_report_video.project_report_id');
        $this->db->where(array('project_report.project_id'=>$id,'project_report.is_deleted'=>false,'project_report_video.is_active'=>true,'project_report_video.is_deleted'=>false));
        $this->db->get(); 
        $query2 = $this->db->last_query();

        $query3 = $this->db->query($query1." UNION ".$query2 . " order by last_updated desc limit ". $offset . "," . $limit);   
        return $query3->result();
    }

    public function ngo_update_media_count($id)
    {
        $this->db->select('count(project_report_image.id) as num ');
        $this->db->from('project_report_image');
        $this->db->join('project_report','project_report.id = project_report_image.project_report_id');
        $this->db->join('project','project.id = 
            project_report.project_id');
        $this->db->where(array('project.ngo_id'=>$id,'project.is_active'=>true,'project_report.is_deleted'=>false,'project_report_image.is_active'=>true,'project_report_image.is_deleted'=>false));
        $result = $this->db->get();
        $count1 = $result->row()->num;

        $this->db->select('count(project_report_video.id) as num ');
        $this->db->from('project_report_video');
        $this->db->join('project_report','project_report.id = project_report_video.project_report_id');
        $this->db->join('project','project.id = 
            project_report.project_id');
        $this->db->where(array('project.ngo_id'=>$id,'project.is_active'=>true,'project_report.is_deleted'=>false,'project_report_video.is_active'=>true,'project_report_video.is_deleted'=>false));
        $result2 = $this->db->get();
        $count2 = $result2->row()->num;
        return $count1 + $count2;
    }   

    public function ngo_update_media($id,$offset,$limit)
    {
        $this->db->select("project_report_image.id,project_report_image.last_updated,project_report_image.date_created,project_report_image.url,project_report_image.caption,'image' as type,project_report_image.thumb_url as thumbUrl,project.title as project, project_report_image.project_report_id ",false);
        $this->db->from('project_report_image');
        $this->db->join('project_report','project_report.id = project_report_image.project_report_id');
        $this->db->join('project','project.id = 
            project_report.project_id');
        $this->db->where(array('project.ngo_id'=>$id,'project.is_active'=>true,'project_report.is_deleted'=>false,'project_report_image.is_active'=>true,'project_report_image.is_deleted'=>false));
        $this->db->get(); 
        $query1 =  $this->db->last_query();

        $this->db->select("project_report_video.id,project_report_video.last_updated,project_report_video.date_created,project_report_video.url,project_report_video.caption,'video' as type,project_report_video.thumb_url as thumbUrl,project.title as project, project_report_video.project_report_id ",false);
        $this->db->from('project_report_video');
        $this->db->join('project_report','project_report.id = project_report_video.project_report_id');
        $this->db->join('project','project.id = 
            project_report.project_id');
        $this->db->where(array('project.ngo_id'=>$id,'project.is_active'=>true,'project_report.is_deleted'=>false,'project_report_video.is_active'=>true,'project_report_video.is_deleted'=>false));
        $this->db->get();
        $query2 = $this->db->last_query();

        $query3 = $this->db->query($query1." UNION ".$query2 . " order by date_created desc limit ". $offset . "," . $limit);   
        return $query3->result();
    }   

    public function project_activity($project_id,$offset,$limit)
    {
        $query1="select project_report.* from project_report where project_report.project_id=$project_id and project_report.is_deleted=0 and project_report.project_report_type='Project Highlight'";

        $query2="select project_report.* from project_report join goals on goals.id = project_report.goal_id where project_report.project_id=$project_id and project_report.is_deleted=0 and goals.is_deleted=0 and project_report.project_report_type='Progress Update'";

        $query3 = $this->db->query($query1." UNION ".$query2 . " order by date_created desc limit $offset,$limit");   
        return $query3->result();
    }

    public function project_activity_count($project_id)
    {
        $query1="select count(*) as num from project_report where project_report.project_id=$project_id and project_report.is_deleted=0 and project_report.project_report_type='Project Highlight'";
        $result = $this->db->query($query1);
        $count1 = $result->row()->num;

        $query2="select count(*) as num from project_report join goals on goals.id = project_report.goal_id where project_report.project_id=$project_id and project_report.is_deleted=0 and goals.is_deleted=0 and project_report.project_report_type='Progress Update'";
        $result2 = $this->db->query($query2);
        $count2 = $result2->row()->num;

        return $count1 + $count2;
    }

    public function outcome_details($id)
    {
        $query="select * from goals where id=$id";
        $result = $this->db->query($query);
        return $result->row();
    }   

    public function activity_media($activity_id)
    {
        $this->db->select("project_report_image.id,project_report_image.last_updated,project_report_image.date_created,project_report_image.url,project_report_image.caption,'image' as type,project_report_image.thumb_url as thumbUrl ",false);
        $this->db->from('project_report_image');
        $this->db->join('project_report','project_report.id = project_report_image.project_report_id');
        $this->db->where(array('project_report.id'=>$activity_id,'project_report_image.is_active'=>true,'project_report_image.is_deleted'=>false));
        $this->db->get(); 
        $query1 =  $this->db->last_query();

        $this->db->select("project_report_video.id,project_report_video.last_updated,project_report_video.date_created,project_report_video.url,project_report_video.caption,'video' as type,project_report_video.thumb_url as thumbUrl ",false);
        $this->db->from('project_report_video');
        $this->db->join('project_report','project_report.id = project_report_video.project_report_id');
        $this->db->where(array('project_report.id'=>$activity_id,'project_report_video.is_active'=>true,'project_report_video.is_deleted'=>false));
        $this->db->get(); 
        $query2 = $this->db->last_query();

        $query3 = $this->db->query($query1." UNION ".$query2 . " order by last_updated desc");   
        return $query3->result();
    }   

    public function get_activity_media($activity_id)
    {
        $this->db->select("project_report_image.id,project_report_image.last_updated,project_report_image.date_created,project_report_image.url,project_report_image.caption,'image' as type,project_report_image.thumb_url as thumbUrl,project_report.report as project_report_title",false);
        $this->db->from('project_report_image');
        $this->db->join('project_report','project_report.id = project_report_image.project_report_id');
        $this->db->where(array('project_report.id'=>$activity_id,'project_report_image.is_active'=>true,'project_report_image.is_deleted'=>false));
        $this->db->get(); 
        $query1 =  $this->db->last_query();

        $this->db->select("project_report_video.id,project_report_video.last_updated,project_report_video.date_created,project_report_video.url,project_report_video.caption,'video' as type,project_report_video.thumb_url as thumbUrl,project_report.report as project_report_title",false);
        $this->db->from('project_report_video');
        $this->db->join('project_report','project_report.id = project_report_video.project_report_id');
        $this->db->where(array('project_report.id'=>$activity_id,'project_report_video.is_active'=>true,'project_report_video.is_deleted'=>false));
        $this->db->get(); 
        $query2 = $this->db->last_query();

        $query3 = $this->db->query($query1." UNION ".$query2 . " order by date_created");   
        return $query3->result();
    }

    public function get_project_report_type($id)
    {
        $this->db->select("project_report_type");
        $this->db->from('project_report');
        $this->db->where('id',$id);
        $result = $this->db->get();
        return $result->row();
    }

    public function get_latest_project_activity($project_id)
    {
        $query1="select project_report.* from project_report where project_report.project_id=$project_id and project_report.is_deleted=0 and project_report.project_report_type='Project Highlight'";

        $query2="select project_report.* from project_report join goals on goals.id = project_report.goal_id where project_report.project_id=$project_id and project_report.is_deleted=0 and goals.is_deleted=0 and project_report.project_report_type='Progress Update'";

        $query3 = $this->db->query($query1." UNION ".$query2 . " order by last_updated desc");   
        return $query3->row();
    }

    public function delete_updates_media_by_outcome_id($id)
    {
        $deleted_at  = date('Y-m-d H:i:s');
        $query = "update project_report_image join project_report on project_report.id = project_report_image.project_report_id set project_report_image.is_deleted=1,project_report_image.deleted_at='$deleted_at' where project_report.goal_id=$id";
        $this->db->query($query);

        $query2 = "update project_report_video join project_report on project_report.id = project_report_video.project_report_id set project_report_video.is_deleted=1,project_report_video.deleted_at='$deleted_at' where project_report.goal_id=$id";
        $this->db->query($query2);
        
        return true;
    }

    public function project_activity_count_between_dates($project_id, $first_date, $second_date)
    {
        // $query1="select count(*) as num from project_report where project_report.project_id=$project_id and project_report.is_deleted=0 and project_report.project_report_type='Project Highlight'";
        // $result = $this->db->query($query1);
        // $count1 = $result->row()->num;

        // $query2="select count(*) as num from project_report join goals on goals.id = project_report.goal_id where project_report.project_id=$project_id and project_report.is_deleted=0 and goals.is_deleted=0 and project_report.project_report_type='Progress Update'";
        // $result2 = $this->db->query($query2);
        // $count2 = $result2->row()->num;

        // return $count1 + $count2;

        // $first_date = '2016-06-15';
        // $second_date = '2016-07-01';

        $this->db->select('project_report.*');
        $this->db->from('project_report');
        $this->db->where(array('project_report.project_id'=>$project_id,'project_report.is_deleted'=>0,'project_report.project_report_type'=>'Project Highlight'));
        $this->db->where('project_report.date_created >=', date('Y-m-d', strtotime($first_date)));
        $this->db->where('project_report.date_created <', date('Y-m-d', strtotime($second_date)));
        $this->db->get(); 
        $query1 =  $this->db->last_query();

        $this->db->select('project_report.*');
        $this->db->from('project_report');
        $this->db->join('goals','goals.id = project_report.goal_id');
        $this->db->where(array('project_report.project_id'=>$project_id, 'project_report.is_deleted'=>0, 'project_report.project_report_type'=>'Progress Update', 'goals.is_deleted'=>0));
        $this->db->where('project_report.date_created >=', date('Y-m-d', strtotime($first_date)));
        $this->db->where('project_report.date_created <', date('Y-m-d', strtotime($second_date)));
        $this->db->get(); 
        $query2 = $this->db->last_query();

        $query3 = $this->db->query($query1." UNION ".$query2 );  
        // return $query3->result();
        $result = $query3->result();
        return count($result);
    }
}//end of class
/* End of file ngo_model.php */
/* Location: ./application/models/ngo_model.php */