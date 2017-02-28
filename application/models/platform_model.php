<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Platform_model extends CI_Model 
{

    public function getinvolve_list($search, $offset, $limit)
    {
        // if($search != ''){

        // }
        $this->db->select('getinvolve.*'); 
        $this->db->from('getinvolve');
        $this->db->order_by("getinvolve.datetime", "desc");
        $this->db->limit($limit,$offset);

        $query = $this->db->get();
        $result = $query->result();
        
        return $result;
    }

    public function getinvolve_count($search)
    {

        $this->db->select('count(*) as num'); 
        $this->db->from('getinvolve');

        $query = $this->db->get();
        $result = $query->row();
        return $result->num;
    }

}