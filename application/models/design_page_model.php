<?php if(!defined('BASEPATH')) exit('No direct script access allowed.');

class Design_page_model extends CI_Model 
{
    public function add_template($insert) {

        $this->db->insert('ngo_template', $insert);
        return $this->db->insert_id();
    }

    public function update_template($update, $id) {

        $this->db->update('ngo_template', $update, array('id'=>$id));
        return;
    }

    public function check_version($ngo_id, $version) {

        $this->db->select('*');
        $this->db->from('ngo_template');
        $this->db->where('ngo_id', $ngo_id);
        $this->db->where('version', $version);
        $result = $this->db->get();
        return $result->row();
    }

    public function get_details($ngo_id, $version) {

        $this->db->select('*');
        $this->db->from('ngo_template');
        $this->db->where('version', $version);
        $this->db->where('ngo_id', $ngo_id);
        $result = $this->db->get();
        return $result->row();
    }

    public function get_active_version($ngo_id) {

        $this->db->select('*');
        $this->db->from('ngo_template');
        $this->db->where('ispublished', 1);
        $this->db->where('ngo_id', $ngo_id);
        $result = $this->db->get();
        return $result->row();
    }

    public function all_template_data($ngo_id, $offset, $limit) {

        $this->db->select('*');
        $this->db->from('ngo_template');
        $this->db->where('ngo_id', $ngo_id);
        $this->db->limit($limit, $offset);
        $this->db->order_by('version', 'desc');
        $result = $this->db->get();
        return $result->result();
    }

    public function all_template_count($ngo_id) {

        $this->db->select('count(*) as num');
        $this->db->from('ngo_template');
        $this->db->where('ngo_id', $ngo_id);
        $result = $this->db->get();
        return $result->row();
    }

    public function get_last_version($ngo_id) {

        $this->db->select('version');
        $this->db->from('ngo_template');
        $this->db->where('ngo_id', $ngo_id);
        $this->db->order_by('id', 'desc');
        $result = $this->db->get();
        return $result->row();
    }

    public function set_is_published_false($ngo_id) {

        $this->db->update('ngo_template', array('ispublished'=>0), array('ngo_id'=>$ngo_id));
        return;
    }
}