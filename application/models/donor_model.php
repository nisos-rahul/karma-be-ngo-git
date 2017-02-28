<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Donor_model extends CI_Model 
{
    public function add_donor($insert)
    {
        $this->db->insert('donor', $insert); 
        $id = $this->db->insert_id();
        return $id;
    }

    public function add_donor_projects($insert)
    {
        $this->db->insert('donor_projects', $insert); 
        $id = $this->db->insert_id();
        return $id;
    }

    public function donor_details($id)
    {
        $query = "select donor.*, organisation.name as organisation_name from donor 
        join organisation on organisation.id=donor.organisation_id
        where donor.id='$id'and donor.is_deleted=0 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function get_projects_by_donor_id($id)
    {
        $query = "select project.id, project.title, donor_projects.is_donated from project 
        join donor_projects on donor_projects.project_id=project.id
        where donor_projects.donor_id='$id'";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function donor_list($ngo_id, $search, $status, $offset, $limit)
    {
        $this->db->select('donor.*, organisation.name as organisation_name'); 
        $this->db->from('donor');
        $this->db->join('organisation', 'organisation.id = donor.organisation_id');
        $this->db->where('donor.organisation_id', $ngo_id);
        $this->db->where('donor.is_deleted', 0);
        if(!empty($status))
        {
            if($status=='true')
                $status = 1;
            else
                $status = 0;
            $this->db->where('donor.is_active', $status);
        }
        $this->db->order_by("donor.last_updated", "desc");
        $this->db->limit($limit,$offset);

        $query = $this->db->get();
        $result = $query->result();
        return $result;
    }

    public function donor_count($ngo_id, $search, $status)
    {
        $this->db->select('count(*) as num'); 
        $this->db->from('donor');
        $this->db->join('organisation', 'organisation.id = donor.organisation_id');
        $this->db->where('donor.organisation_id', $ngo_id);
        $this->db->where('donor.is_deleted', 0);
        if(!empty($status))
        {
            if($status=='true')
                $status = 1;
            else
                $status = 0;
            $this->db->where('donor.is_active', $status);
        }
        $this->db->order_by("donor.id", "desc");

        $query = $this->db->get();
        $result = $query->row();
        return $result->num;
    }

    public function update_donor($insert, $id)
    {
        $this->db->update('donor', $insert,array('id'=>$id)); 
        return;
    }

    public function delete_donor_projects($donor_id)
    {
        $this->db->delete('donor_projects', array('donor_id' => $donor_id)); 
    }

    public function all_donors_list($search, $offset, $limit)
    {
        $this->db->select('donor.*, organisation.name as organisation_name'); 
        $this->db->from('donor');
        $this->db->join('organisation', 'organisation.id = donor.organisation_id');
        $this->db->where('donor.is_deleted', 0);
        $this->db->where('organisation.is_deleted', 0);
        $this->db->where('organisation.is_active', 1);
        $this->db->where('organisation.is_archive', 0);
        if($search!='')
            $this->db->like('donor.name', $search);

        $this->db->order_by("donor.last_updated", "desc");
        $this->db->limit($limit,$offset);

        $query = $this->db->get();
        $result = $query->result();
        return $result;
    }

    public function all_donors_count($search)
    {
        $this->db->select('count(*) as num'); 
        $this->db->from('donor');
        $this->db->join('organisation', 'organisation.id = donor.organisation_id');
        $this->db->where('donor.is_deleted', 0);
        $this->db->where('organisation.is_deleted', 0);
        $this->db->where('organisation.is_active', 1);
        $this->db->where('organisation.is_archive', 0);
        if($search!='')
            $this->db->like('donor.name', $search);

        $query = $this->db->get();
        $result = $query->row();
        return $result->num;
    }
}