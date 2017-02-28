<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Support_model extends CI_Model 
{
    public function support_role_details($user_id)
    {
        $query = "select * from organisation where id in 
        (select ngo_id from support_user where user_id=$user_id)
        and is_active=1 
        and is_deleted=0 and is_verified=1";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function get_ngos_by_support($user_id)
    {
        $query = "select support_user.*,organisation.name,organisation.branding_url from support_user join 
        organisation on support_user.ngo_id where support_user.user_id=$user_id and organisation.id=support_user.ngo_id and organisation.is_active=1 and organisation.is_archive=0";
        $result = $this->db->query($query);
        return $result->result();
    }
}