<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Project_model extends CI_Model 
{
    //insert project    
    public function create_project($insert)
    {
        $this->db->insert('project', $insert); 
        $id = $this->db->insert_id();       
        return $id;
    }
    public function project_country($insert_country)
    {
        $this->db->insert('project_country', $insert_country); 
        return;
    }//project_country
    //insert project goal   
    public function insert_outcome($insert)
    {
        $this->db->insert('goals', $insert); 
        $id = $this->db->insert_id();       
        return $id;
    }

    public function update_outcomes($insert,$id)
    {
        $this->db->update('goals', $insert,array('id'=>$id)); 
        return;
    }
    public function outcome_details($id)
    {
        $query = "select * from goals where id='$id' and is_deleted=0 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function project_details($id)
    {
        $query = "select * from project where id='$id' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function outcome_list($id,$search='',$offset='',$limit='')
    {
        $query = "select * from goals where project_id = $id and is_deleted=0";
        if($search!="")
        {
            $query.=" and goal like '%$search%'";
        }
        if($offset!='' && $limit!='')
        {
            $query.= " limit $offset,$limit";
        }
        $result = $this->db->query($query);
        return $result->result();
    }
    public function project_country_details($id)
    {
        $query = "SELECT `country`.* FROM `country` join `project_country` on `project_country`.`country_id`=`country`.`id` 
        WHERE `project_country_id`=$id";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function company_project_list_approved($ngo_id,$project_id)
    {
        $query = "SELECT organisation.*,company_ngo.relationship_status FROM `organisation` join company_ngo 
        on company_ngo.company_id = organisation.id 
        where company_ngo.ngo_id=$ngo_id and company_ngo.project_id=$project_id and relationship_status='Approved'";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function company_project_list($ngo_id,$project_id)
    {
        $query = "SELECT organisation.*,company_ngo.relationship_status FROM `organisation` join company_ngo on company_ngo.company_id = organisation.id 
        where company_ngo.ngo_id=$ngo_id and company_ngo.project_id=$project_id ";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function company_project_fund($comp_id,$project_id)
    {
        $query = "select funds from project_funding where company_id=$comp_id and project_id=$project_id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function project_list($ngo_id,$search,$status,$offset,$limit)
    {
        $this->db->select('*'); 
        $this->db->from('project');
        $this->db->where('ngo_id', $ngo_id);
        if($search!='')
        {
            $this->db->like('title', $search);
        }
        if(!empty($status))
        {   

            if($status=='true' || $status=='false')
            {
                if($status=='true')
                    $status = 1;
                else
                    $status = 0;
                $this->db->where('is_active', $status);
            }
            elseif($status=='active')
            {
                $this->db->where("(status_name='Fundraising' OR status_name='In Progress')", NULL, false);
            }
            else
                $this->db->where('status_name', $status);
            
        }
        $this->db->order_by("project.last_updated", "desc");
        $this->db->limit($limit,$offset);

        $query = $this->db->get();
        $result = $query->result();
        return $result;
    }
    public function project_list_count($ngo_id,$search,$status)
    {
        $this->db->select('count(*) as num'); 
        $this->db->from('project');
        $this->db->where('ngo_id', $ngo_id);
        if($search!='')
            $this->db->like('title', $search);
        if(!empty($status))
        {
            if($status=='true' || $status=='false')
            {
                if($status=='true')
                    $status = 1;
                else
                    $status = 0;
                $this->db->where('is_active', $status);
            }
            elseif($status=='active')
            {
                $this->db->where("(status_name='Fundraising' OR status_name='In Progress')", NULL, false);
            }
            else
                $this->db->where('status_name', $status);
        }

        $query = $this->db->get();
        $result = $query->row();
        return $result;
    }

    public function active_project_count($ngo_id,$project_req)
    {
        $query = "select count(*) as num from project where ngo_id=$ngo_id ";
        if($project_req=='active')
        {           
            $query.=" and is_active=true";
        }
            
        $result = $this->db->query($query);
        return $result->row();
    }

    public function update_project($update,$id)
    {
        $this->db->update('project', $update, array('id' => $id));
        return true;
    }
    public function project_ngo($ngo_id,$id)
    {
        $query = "select id from project where ngo_id=$ngo_id and id=$id";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function project_country_delete($id)
    {
        $this->db->delete('project_country', array('project_country_id' => $id));
        
    }
    public function project_country_entry($id)
    {
        $query = "select * from project_country where project_country_id=$id";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function check_project_funding($companyId,$id)
    {
        $query = "select * from project_funding where company_id=$companyId and project_id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function update_project_funding($update,$id)
    {
        $this->db->update('project_funding',$update,array('id'=>$id));
    }
    public function insert_project_funding($insert)
    {
        $this->db->insert('project_funding',$insert);
        return;
    }
    public function active_outcome_count($id,$search='')
    {
        $query = "select count(*) as num from goals where project_id = $id and is_deleted=0";
        if($search!="")
        {
            $query.=" and goal like '%$search%'";
        }
        $result = $this->db->query($query);
        return $result->row();
    }
    public function project_goal_check($id,$project_id)
    {
        $query = "select * from goals where id='$id' and project_id=$project_id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function project_group_exists($project_id)
    {
        $query = "select * from project_group where project_id=$project_id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//project_group_exists
    public function list_project_members($project_id)
    {
        $query = "select user.*,profile.image_url from user join project_report
        on user.id=project_report.user_id left join profile on user.id=profile.user_id
        where project_report.project_id=$project_id and project_report.is_deleted=0 
        group by project_report.user_id";
        $result = $this->db->query($query);
        return $result->result();
    }//list_project_members
    //calculate total no of beneficiaries
    public function total_beneficiaries($ngo_id)
    {
        $query = "select sum(total_benefeciaries) as no_benefeciaries from project where ngo_id=$ngo_id ";
        $result = $this->db->query($query);
        return $result->row();
    }
    //update beneficiaries of ngo
    public function update_organisation($update,$ngo_id)
    {
        $this->db->update('organisation',$update,array('id'=>$ngo_id));
        return;
    }
    //calculate total beneficiaries of company
    public function total_beneficiaries_company($company_id)
    {
        $query = "select sum(total_benefeciaries) as no_benefeciaries from project 
        join project_funding on project.id=project_funding.project_id       
        where project_funding.company_id=$company_id and funds>0 ";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function project_country_region($insert_country)
    {
        $this->db->insert('project_country_state', $insert_country); 
        return;
    }//project_country
    public function project_country_regions($id)
    {
        $this->db->select('*');     
        $this->db->where(array('project_id'=>$id));
        $result = $this->db->get('project_country_state');
        return $result->result();
    }
    public function project_country_regions_delete($id)
    {
        $this->db->delete('project_country_state', array('project_id' => $id));
    }
    public function get_project_donors($id)
    {
        $this->db->select('donor.*');
        $this->db->join('donor', 'donor.id = donor_projects.donor_id');
        $this->db->where('donor_projects.project_id',$id);
        $this->db->where('donor.is_deleted',0);
        $result = $this->db->get('donor_projects');
        return $result->result();
    }

    public function is_new_highlight($project_id)
    {
        $this->db->select('id,date_created');
        $this->db->where('project_report.project_id',$project_id);
        $this->db->where('project_report.project_report_type','Project Highlight');
        $this->db->order_by("project_report.id", "desc");
        $this->db->limit(1);
        $result = $this->db->get('project_report');
        return $result->row();
    }

    public function get_deleted_goals()
    {
        $query = "select * from goals where is_deleted=1";
        $result = $this->db->query($query);
        return $result->result();
    }



    public function get_total_active_beneficiaries($ngo_id)
    {
        $query = "select sum(total_benefeciaries) as no_of_benefeciaries from project where ngo_id=$ngo_id and is_active=1";
        $result = $this->db->query($query);
        return $result->row();
    }
    
    public function get_project_list($where)
    {
        $this->db->select('*');
        $this->db->from('project');
        $this->db->where($where);
        $result = $this->db->get();
        return $result->result();
    }
    public function get_project_outcomes($project_id)
    {
        $query = "select * from goals where project_id = $project_id and is_deleted=0";
        $result = $this->db->query($query);
        return $result->result();
    }
}//end of class
/* End of file ngo_model.php */
/* Location: ./application/models/ngo_model.php */