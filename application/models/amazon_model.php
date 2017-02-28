<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Amazon_model extends CI_Model 
{
    public function get_activity_videos($ngo_id)
    {
        $query = "select project_report_video.url from project_report_video 
        join project_report on project_report_video.project_report_id=project_report.id 
        join project on project_report.project_id=project.id 
        where project.ngo_id=$ngo_id";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function get_activity_images($ngo_id)
    {
        $query = "select project_report_image.url from project_report_image 
        join project_report on project_report_image.project_report_id=project_report.id 
        join project on project_report.project_id=project.id 
        where project.ngo_id=$ngo_id";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function ngo_profile_image($ngo_id)
    {
        $query = "select organisation.image_url as ngo_logo from organisation where organisation.id=$ngo_id and organisation.image_url!=''";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function ngo_profile_icon($ngo_id)
    {
        $query = "select organisation.favicon_url as ngo_icon from organisation where organisation.id=$ngo_id and organisation.favicon_url!=''";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function project_profile_images($ngo_id)
    {
        $query = "select project.image_url as project_profile_images from project where project.ngo_id=$ngo_id and project.image_url!=''";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function get_admin_profile_image($ngo_id)
    {
        $query = "select profile.image_url from profile join organisation on profile.user_id=organisation.user_id where organisation.id=$ngo_id and profile.image_url!=''";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function get_member_profile_images($ngo_id)
    {
        $query = "select profile.image_url from profile join ngo_user on profile.user_id=ngo_user.user_id where ngo_user.ngo_id=$ngo_id and profile.image_url!=''";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function get_donor_images($ngo_id)
    {
        $query = "select image_url from donor where organisation_id=$ngo_id and image_url!=''";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function get_template_data($ngo_id)
    {
        $query = "select json from ngo_template where ngo_id=$ngo_id";
        $result = $this->db->query($query);
        return $result->result();
    }
}