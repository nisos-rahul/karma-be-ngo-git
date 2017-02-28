<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Company_model extends CI_Model 
{
    public function company_count($search)
    {
        $query = "select count(*) as num from organisation where company_type is not null 
        and is_deleted=0 and is_active=1 ";
        if(!empty($search))
            $query.=" and name like '%$search%'";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function company_list($search, $offset, $limit)
    {
        $query = "select id,name from organisation where company_type is not null 
        and is_deleted=0 and is_active=1 ";
        if(!empty($search))
            $query.=" and name like '%$search%'";
        $query.=" limit $offset,$limit";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function project_company_details($company_id, $ngo_id, $project_id)
    {
        $query = "select * from company_ngo where company_id=$company_id and ngo_id=$ngo_id and project_id=$project_id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function project_company_details_by_id($id)
    {
        $query = "select * from company_ngo where id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function update_company_ngo($update, $company_id, $ngo_id, $project_id)
    {
        $this->db->update('company_ngo',$update,array('company_id'=>$company_id,'ngo_id'=>$ngo_id,'project_id'=>$project_id));
        return;
    }
    public function insert_company_ngo($insert)
    {
        $this->db->insert('company_ngo',$insert);
        return;
    }
    public function organisation_name($id)
    {
        $query = "SELECT organisation.name,user.email 
        FROM `organisation` join user on 
         organisation.`user_id`=user.id WHERE organisation.`id`=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function active_ngo_project_details($id, $ngo_id)
    {
        $query = "select * from project where id=$id and ngo_id=$ngo_id and is_deleted=0 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    //check group for project exists or not
    public function check_group_exists($project_id)
    {
        $query = "select * from project_group where project_id=$project_id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//check_group_exists
    //connect company to group
    public function add_group_company($insert)
    {
        $this->db->insert('project_company_group',$insert);
        true;
    }
    public function create_group($insert)
    {
        $this->db->insert('project_group',$insert);
        $id = $this->db->insert_id();       
        return $id;
    }
    public function list_group_company($ngo_id, $search, $offset, $limit)
    {
        $query = "SELECT `project_group`.`name`,`project_group`.`id`,`project_group`.notification
        FROM `project_group` join project on project.id=project_group.project_id
        where project.is_deleted=0 and project.ngo_id=$ngo_id ";
        if(!empty($search))
            $query.= "and `project_group`.`name` like '%$search%'";
        $query.= " limit $offset,$limit";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function list_group_company_count($ngo_id, $search)
    {
        $query = "SELECT count(*) as num
        FROM `project_group` join project on project.id=project_group.project_id
        where project.is_deleted=0 and project.ngo_id=$ngo_id ";
        if(!empty($search))
            $query.= " and `project_group`.`name` like '%$search%'";
        
        $result = $this->db->query($query);
        return $result->row();
    }
    public function list_project_group_member($group_id)
    {
        $query = "select organisation.name,organisation.id from project_company_group join organisation
        on organisation.id=project_company_group.company_id
        where project_group_id=$group_id";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function group_project_details($group_id)
    {
        $query="select project_group.notification,project.title
        from `project_group` join project on project.id=project_group.project_id
        where project_group.id=$group_id and project.is_active=1 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function group_member_details($company_id)
    {
        $query="select project_group.notification,project.title
        from `project_group` join project on project.id=project_group.project_id
        where project_group.id=$group_id and project.is_active=1 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function company_email($company_id)
    {
        $query="select user.email from user join organisation 
        on organisation.user_id=user.id 
        where organisation.id=$company_id and organisation.is_deleted=0 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function update_group($update, $group_id)
    {
        $this->db->update('project_group',$update,array('id'=>$group_id));
    }
}//end of class
/* End of file company_model.php */
/* Location: ./application/models/company_model.php */