<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Verification_model extends CI_Model 
{
    public function valid_ngo_admin($token)
    {
        $query = "select authentication_token.id as authId,user.id as user_id 
        from authentication_token join user 
        on authentication_token.username=user.username 
        where authentication_token.auth_token='$token' and 
        is_active=1 and user.role_id=4 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//valid_ngo_admin
    public function valid_auth_token($token)
    {
        $query = "select authentication_token.id as authId,user.role_id,user.id as user_id
        from authentication_token join user 
        on authentication_token.username=user.username 
        where authentication_token.auth_token='$token' and 
        is_active=1 and (user.role_id=4 || user.role_id=5 || user.role_id=7 || user.role_id=1) limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//valid_auth_token

    public function valid_ngo_support($token)
    {
        $query = "select authentication_token.id as authId,user.id as user_id 
        from authentication_token join user 
        on authentication_token.username=user.username 
        where authentication_token.auth_token='$token' and 
        is_active=1 and user.role_id=7 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//valid_support_role

    public function get_csrf_token()
    {
        $token = md5(uniqid() . microtime() . rand());
        $data = array(
            'token' => $token ,
        );

        $this->db->insert('csrf_token', $data); 
        return $token;
    }

}//end of class
/* End of file verification_model.php */
/* Location: ./application/models/verification_model.php */