<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Hashtag_model extends CI_Model
{
    public function all_default_hashtags($search='',$offset='',$limit='')
    {
        $query = "select id, hash_tag as hashTag from hash_tags where is_active=1";
        if(!empty($search))
            $query.=" and  hash_tag like '%$search%'";
        $query.=" ORDER BY date_created desc limit $offset, $limit";
        $result = $this->db->query($query);
        return $result->result();
    }
    
    public function hashtag_count($search)
    {
        $query = "select count(*) as num from hash_tags where is_active=1 ";
        if($search!="")
            $query.=" and hash_tag like '%$search%'";           
        $result = $this->db->query($query);
        return $result->row();
    }
    
    public function insert_hashtag($insert)
    {
        $this->db->insert('hash_tags', $insert); 
        $id = $this->db->insert_id();       
        return $id;
    }
    
    public function check_duplicate_hashtag($hashTag)
    {
        $query = "select * from hash_tags where hash_tag='$hashTag' limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    
    public function check_duplicate_handle($handlename, $id)
    {
        $query = "select * from twitter_handles where handle_name='$handlename' and organisation_id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    
    public function insert_twitter_handle($insert)
    {
        $this->db->insert('twitter_handles',$insert);
        return;
    }
    
    public function hashtag_info($id)
    {
        $query = "select id, hash_tag as hashTag from hash_tags where id=$id and is_active=1 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//hashtag_info
    
    public function get_organization_handles($id)
    {
        $query = "select id, handle_name as handleName from twitter_handles where organisation_id=$id and is_active=1";
        $result = $this->db->query($query);
        return $result->result();
    }//get_organization_handles
    
    public function check_handle_unique($handleName,$organization_id='')
    {
        $query = "select * from twitter_handles where  
        handle_name='$handleName' ";
        if($organization_id!='')
        {
            //in case of update added same organization
            $query.=" and organisation_id<>$organization_id ";
        }
        $query.="limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    
    public function delete_twitter_handle($org_id)
    {
        $this->db->delete('twitter_handles', array('organisation_id' => $org_id)); 
    }
    
    public function update_twitter_handle($row_id, $handle)
    {
        $this->db->where('id', $row_id);
        $this->db->update('twitter_handles', ['handle_name' => $handle]);
    }
    public function insert_project_hashtags($insert)
    {
        $this->db->insert('project_hash_tags',$insert);
        return;
    }
    
    public function get_project_hashtag($id)
    {
        $query = "select * from project_hash_tags where project_hash_tags_id=$id";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function delete_project_hashtag($proj_id, $hashtag_id)
    {
        $this->db->delete('project_hash_tags', array('project_hash_tags_id' => $proj_id, 'hash_tags_id' => $hashtag_id)); 
    }
    
    public function organization_hashtag_count($hashtag_id)
    {
        $query = "select count(*) as num from hash_tags_organisation where hash_tags_id=$hashtag_id ";
        $result = $this->db->query($query);
        return $result->row();
    }
    
    public function check_proj_hashtag_exists($proj_id, $hashtag_id)
    {
        $query = "select count(*) as num from project_hash_tags where project_hash_tags_id=$proj_id and hash_tags_id=$hashtag_id";
        $result = $this->db->query($query);
        return $result->row();
    }
    
    public function project_hashtag_count($hashtag_id)
    {
        $query = "select count(*) as num from project_hash_tags where hash_tags_id=$hashtag_id ";
        $result = $this->db->query($query);
        return $result->row();
    }
    
    public function check_hashtagid_exist($hashtag_id)
    {
        $query = "select count(*) as num from hash_tags where id=$hashtag_id";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function check_facebook_handle_unique($handleName,$organization_id='')
    {
        $query = "select * from facebook_handles where  
        handle_name='$handleName' ";
        if($organization_id!='')
        {
            //in case of update added same organization
            $query.=" and organisation_id<>$organization_id ";
        }
        $query.="limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function insert_facebook_handle($insert)
    {
        $this->db->insert('facebook_handles',$insert);
        return;
    }
    public function delete_facebook_handle($org_id)
    {
        $this->db->delete('facebook_handles', array('organisation_id' => $org_id)); 
    }
    public function update_facebook_handle($row_id, $handle)
    {
        $this->db->where('id', $row_id);
        $this->db->update('facebook_handles', ['handle_name' => $handle]);
    }
    public function get_organization_fb_handles($id)
    {
        $query = "select id, handle_name as handleName from facebook_handles where organisation_id=$id and is_active=1";
        $result = $this->db->query($query);
        return $result->result();
    }//get_organization_handles
    public function get_handle_details($table,$where)
    {
        $this->db->select('*');
        $this->db->where($where);       
        $result = $this->db->get($table);
        return $result->row();
    }
    public function update_handle($table, $where, $update)
    {

        $this->db->update($table, $update, $where);
        
    }
}//end of class
/* End of file hashtag_model.php */
/* Location: ./application/models/hashtag_model.php */