<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model {
    public function user_info($id)
    {
        $query = "select * from user where id=$id and is_active=1 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function user_info_admin($id)
    {
        $query = "select * from user where id=$id  limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function user_duplicate($email)
    {
        $query = "select * from user where email='$email' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function role_info($id)
    {
        $query = "select * from role where id='$id' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function user_profile_info($id)
    {
        $query = "select profile.*,country.name,country.code from profile left join country on
        profile.country_id=country.id where user_id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function profile_info($id)
    {
        $query = "select * from profile where user_id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function profile_create($insert)
    {
        $this->db->insert('profile', $insert); 
        return;
    }
    public function profile_update($update,$id)
    {
        $this->db->update('profile', $update, array('id' => $id));
        return true;
    }
    public function update_user($update,$id)
    {
        $this->db->update('user', $update, array('id' => $id));
        return true;
    }
    public function send_invitation($insert)
    {
        $this->db->insert('invitation', $insert); 
        return;
    }
    public function mail_format($mail_for)
    {
        $query = "select * from mail_format where mail_for='$mail_for' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function update_user_email_duplicate($email,$id)
    {
        $query = "select * from user where email='$email' and id!=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//update_user_email_duplicate
    public function update_username_duplicate($username,$id)
    {
        $query = "select * from user where username='$username' and id!=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//update_username_duplicate
    public function user_sharing_details($user_id)
    {
        $query = "select id from social_user where user_id=$user_id and our_project_activities=true 
        limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//user_sharing_details
    public function get_info_by_username($username)
    {
        $query = "select * from user where username='$username' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
}//end of class
/* End of file user_model.php */
/* Location: ./application/models/user_model.php */