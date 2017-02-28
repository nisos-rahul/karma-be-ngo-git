<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Media_model extends CI_Model 
{
    public function insert_upload_log($insert)
    {
        $this->db->insert('media_upload_logs', $insert); 
        return;
    }
}