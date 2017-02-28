<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Csrf_model extends CI_Model 
{
    function token_check($token) {
        $query = "select token from csrf_token where token='$token'"; 
        $result = $this->db->query($query);
        return $result->row();
    }

    function delete_token($token) {
        $this->db->delete('csrf_token', array('token' => $token));
    }
}