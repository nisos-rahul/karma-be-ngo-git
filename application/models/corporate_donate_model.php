<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Corporate_donate_model extends CI_Model 
{   
    public function add_corporate_donate_details($insert)
    {
        $this->db->insert('corporate_donate_emails', $insert); 
        return $this->db->insert_id();
    }

    public function insert_projects($insert)
    {
        $this->db->insert('corporate_donate_projects', $insert); 
        return $this->db->insert_id();
    }

    public function corporate_donate_details($id)
    {
        $this->db->select('*');
        $this->db->from('corporate_donate_emails');
        $this->db->where('id', $id);
        $result = $this->db->get();
        return $result->row();
    }

    public function get_corporate_donate_projects($corporate_donate_id)
    {
        $this->db->select('*');
        $this->db->from('corporate_donate_projects');
        $this->db->where('corporate_donor_id', $corporate_donate_id);
        $result = $this->db->get();
        return $result->result();
    }

    public function get_all_donate_contact_count()
    {
        $query = "SELECT count(*) as num from corporate_donate_emails";
        $result = $this->db->query($query);
        return $result->row()->num;
    }

    public function get_active_organisations()
    {
        $query = "SELECT * from organisation where is_active=1 and is_deleted=0 and is_archive=0";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function get_corporate_donate_details_by_organisation($ngo_id)
    {
        $query = "SELECT * FROM corporate_donate_emails where ngo_id=$ngo_id ORDER BY id desc";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function get_corporate_donate_project_names($corporate_donor_id)
    {
        $query = "SELECT project.title FROM corporate_donate_projects join project on project.id=corporate_donate_projects.project_id where corporate_donor_id=$corporate_donor_id";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function set_is_submitted_to_true()
    {
        $query = "UPDATE corporate_donate_emails SET is_submitted=1";
        return $this->db->query($query);
    }
}