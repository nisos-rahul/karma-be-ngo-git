<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dashboard_model extends CI_Model 
{
    public function target_location_count($ngo_id)
    {
        $query = "SELECT count(distinct(country_id)) as num FROM `project_country` 
        join project on project_country.project_country_id=project.id 
        where project.is_deleted=0 and project.ngo_id=$ngo_id";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function project_completed_count($ngo_id)
    {
        //project completed means progress 100% 
        //calculated from overall goal = sum of goal achieved and greater than 0
        $query = "select count(*) as num from 
        (SELECT project.over_all_goal FROM `project` left join goals 
        on goals.project_id=project.id 
        WHERE ngo_id=$ngo_id and goals.is_deleted=0 and project.over_all_goal>0 
        and project.is_deleted=0 group by goals.project_id 
        having project.`over_all_goal`= sum(goals.goal_achieved)) project_completed";
        $result = $this->db->query($query);
        return $result->row();
    }//project_completed_count
    public function project_active_count($ngo_id)
    {
        $query = "select count(*) as num from project where ngo_id=$ngo_id
        and is_deleted=0";
        $result = $this->db->query($query);
        return $result->row();
    }//project_active_count
    public function project_category_list($ngo_id)
    {
        $query = "SELECT categories.* FROM `project` join goals on goals.project_id=project.id
        join categories on goals.categories_id=categories.id
        WHERE project.ngo_id='$ngo_id' and goals.is_deleted=0 and project.is_deleted=0 group by goals.categories_id";
        
        $result = $this->db->query($query);
        return $result->result();
    }
    public function project_fund_details($ngo_id)
    {
        $query = "SELECT * FROM `project` 
        WHERE ngo_id=$ngo_id ";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function project_fund_list($ngo_id)
    {
        $query = "SELECT * FROM `project` 
        WHERE ngo_id=$ngo_id and is_deleted=0 and current_amount>0";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function project_activity_year($ngo_id, $year, $month)
    {
        $query="SELECT COUNT(*) as num,  
        project.title
        from project_report join project 
        on project.id=project_report.project_id 
        where project.is_deleted=0 and project_report.is_deleted=0
        and YEAR(project_report.date_created)='$year' 
        and MONTH(project_report.date_created)='$month'
        and project.ngo_id = $ngo_id Group By MONTH(project_report.date_created),project.id";
        $result = $this->db->query($query);
        return $result->result();
    }//project_activity_year
    public function project_count($where)
    {
        $this->db->select('count(project.id) as num');
        $this->db->from('project');
        $this->db->join('goals','project.id=goals.project_id');
        $this->db->where($where);
        $result = $this->db->get();
        return $result->row();
    }
}//end of class
/* End of file verification_model.php */
/* Location: ./application/models/verification_model.php */