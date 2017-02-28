<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Social_media_sharing_model extends CI_Model 
{
    public function get_user_data($where) 
    {
        $this->db->select('*');
        $this->db->from('user_social');
        $this->db->where($where);
        $result = $this->db->get();
        return $result->row();
    }

    public function store_user_data($insert)
    {
        $this->db->insert('user_social',$insert);
        return $this->db->insert_id();
    }

    public function update_user_data($update,$where)
    {
        $this->db->update('user_social', $update, $where);
        return;
    }

    public function get_ngo_status($ngo_id)
    {
        $this->db->select('facebook, twitter');
        $this->db->from('user_social');
        $this->db->where('ngo_id', $ngo_id);
        $result = $this->db->get();
        return $result->row();
    }
}