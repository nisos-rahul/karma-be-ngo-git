<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Kraken_model extends CI_Model 
{
    public function insert_kraken_log($insert)
    {
        $this->db->insert('kraken_logs', $insert); 
        $id = $this->db->insert_id();
        return $id;
    }

    public function update_kraken_log($update,$where)
    {
    	$this->db->update('kraken_logs', $update,$where); 
        return;
    }
}
