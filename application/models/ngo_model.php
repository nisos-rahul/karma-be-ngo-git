<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ngo_model extends CI_Model {
    public function organization_details($id, $status='')
    {
        $this->db->select('*');
        if($status=='')
            $this->db->where(array('id'=>$id,'is_active'=>true,'is_deleted'=>false));
        else
            $this->db->where(array('id'=>$id));
        $result = $this->db->get('organisation');
        return $result->row();
    }//organization_details
    //organization details for ngo admin
    public function organization_exist($user_id)
    {
        $query = "select * from organisation where user_id=$user_id and is_active=1 
        and is_deleted=0 and is_verified=1 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//organization_exist
    public function organization_videos($organisation_id)
    {
        $query = "select * from video where organisation_id=$organisation_id and 
        is_deleted=0 limit 4";
        $result = $this->db->query($query);
        return $result->result();
    }//organization_videos
    public function organization_video_count($organisation_id)
    {
        $query = "select count(*) as num from video where organisation_id=$organisation_id and 
        is_deleted=0";
        $result = $this->db->query($query);
        return $result->row();
    }//organization_video_count
    //organization details for ngo member
    public function organization_member_details($user_id)
    {
        $query = "select * from organisation where id in 
        (select ngo_id from ngo_user where user_id=$user_id)
        and is_active=1 
        and is_deleted=0 and is_verified=1 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function insert_ngo($insert)
    {
        $this->db->insert('organisation', $insert); 
        $id = $this->db->insert_id();       
        return $id;
    }
    public function insert_video($insert)
    {
        $this->db->insert('video', $insert); 
        $id = $this->db->insert_id();       
        return $id; }
    public function insert_category_ngo($insert)
    {
        $this->db->insert('categories_ngo', $insert); 
        return;
    }
    public function category_ngo($ngo_id)
    {
        $query = "select * from categories_ngo where ngo_id=$ngo_id ";
        $result = $this->db->query($query);
        return $result->result();
    }
    //check video exists
    public function get_video($id, $ngo_id='')
    {
        $query = "select * from video where id=$id and is_deleted=0 ";
        if($ngo_id!='')
        {
            $query.=" and organisation_id=$ngo_id";
        }
        $query.=" limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function update_video($update, $id)
    {
        $this->db->update('video', $update, array('id' => $id));
        return true;
    }
    //update ngo
    public function update_ngo($update, $id)
    {
        $this->db->update('organisation', $update, array('id' => $id));
        return true;
    }
    //delete ngo categories
    public function delete_category_ngo($ngo_id)
    {
        $this->db->delete('categories_ngo', array('ngo_id' => $ngo_id)); 
    }
    public function ngo_user_count($ngo_id, $search, $status)
    {
        $query = "select count(*) as num from ngo_user join user
        on ngo_user.user_id = user.id
        where ngo_user.ngo_id=$ngo_id  ";
        if($search!='')
        {
            $query.=" and (concat_ws(' ',user.first_name,user.last_name) like '%$search%' or email like '%$search%' 
        or username like '%$search%')";
        }           
        if(!empty($status))
            $query.=" and is_active=$status";
        
        $result = $this->db->query($query);
        return $result->row();
    }//ngo_user_count
    public function ngo_user_list($ngo_id, $search, $status, $offset, $limit)
    {
        $query = "select user.*,profile.id as profile_id,profile.image_url as profile_image from ngo_user join user
        on ngo_user.user_id = user.id
        left join profile on profile.user_id = user.id
        where ngo_user.ngo_id=$ngo_id  ";
        if($search!='')
        {
            $query.=" and (concat_ws(' ',user.first_name,user.last_name) like '%$search%'  or email like '%$search%'
        or username like '%$search%')";
        }
            
        if(!empty($status))
            $query.=" and is_active=$status";
        $query.=" ORDER BY user.id DESC";
        $query.=" limit $offset,$limit";
        $result = $this->db->query($query);
        return $result->result();
    }
    
    public function ngo_admin_data($ngo_id, $search)
    {
        $query = "select user.*,profile.id as profile_id,profile.image_url as profile_image from organisation join user
        on organisation.user_id = user.id
        left join profile on profile.user_id = user.id
        where organisation.id=$ngo_id  ";
        if($search!='')
        {
            $query.=" and (concat_ws(' ',user.first_name,user.last_name) like '%$search%'  or email like '%$search%'
        or username like '%$search%')";
        }
        // if(!empty($status))
        //  $query.=" and is_active=$status";
        $result = $this->db->query($query);
        return $result->result();
    }

    public function ngo_admin_check($ngo_id, $admin_id)
    {
        $query = "select * from organisation where id=$ngo_id and user_id=$admin_id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }

    public function ngo_user_check($ngo_id, $user_id)
    {
        $query = "select * from ngo_user where ngo_id=$ngo_id and user_id=$user_id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function registration_unique($registration_no, $ngo_id='')
    {
        $query = "select * from organisation where  
        registration_no='$registration_no' ";
        if($ngo_id!='')
        {
            //in case of update added same registration_no
            $query.=" and id<>$ngo_id ";
        }
        $query.="limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function organization_docs($organisation_id)
    {
        $query = "select id,link as url,title as name from documents where organisation_id=$organisation_id and 
        is_deleted=0 ";
        $result = $this->db->query($query);
        return $result->result();
    }//organization_docs
    public function insert_docs($insert)
    {
        $this->db->insert('documents', $insert); 
        $id = $this->db->insert_id();       
    }
    public function list_org($search='', $status='', $searchby='', $offset, $limit)
    {
        $query = "select id,name from organisation where name like '%$search%' ";
        if($searchby=="ngo")
        {
            $query.=" and ngo_type is not null ";
        }
        if($searchby=="company")
        {
            $query.=" and company_type is not null ";
        }
        if($status!="")
        {
            $query.=" and is_active=$status";
        }
        $query.=" limit $offset,$limit";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function list_org_count($search='', $status='', $searchby)
    {
        $query = "select count(*) as num from organisation where name like '%$search%'  ";
        if($searchby=="ngo")
        {
            $query.=" and ngo_type is not null ";
        }
        if($searchby=="company")
        {
            $query.=" and company_type is not null ";
        }
        if($status!="")
        {
            $query.=" and is_active=$status";
        }
        $result = $this->db->query($query);
        return $result->row();
    }
    public function organisation_gloabal_details($where)
    {
        $this->db->select('id');
        $this->db->where($where);
        $this->db->where('is_active',1);
        $this->db->where('is_archive',0);
        $result = $this->db->get('organisation');
        return $result->row();
    }
    public function org_is_accepted_terms($ngo_id)
    {
        $this->db->select('is_accepted_terms_and_conditions');
        $this->db->where('id', $ngo_id);
        $result = $this->db->get('organisation');
        return $result->row();
    }
    // public function organisation_list()
    // {
    //     $this->db->select('*');
    //     $this->db->where('is_deleted',0);
    //     $result = $this->db->get('organisation');
    //     return $result->result();
    // }
    public function organisation_list($limit, $offset, $query, $category_id, $country_id)
    {
        if($category_id!='')
        {
            $this->db->select('organisation.*');
            $this->db->distinct();
            $this->db->from('organisation');

            $this->db->join('categories_ngo', 'categories_ngo.ngo_id = organisation.id');
            $this->db->where('categories_ngo.categories_id', $category_id);

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));
            $this->db->get();
            $query1 = $this->db->last_query();

            $this->db->select('organisation.*');
            $this->db->distinct();
            $this->db->from('organisation');

            $this->db->join('project', 'project.ngo_id = organisation.id');
            $this->db->join('goals', 'goals.project_id = project.id');
            $this->db->where('goals.categories_id', $category_id);
            $this->db->where('goals.is_deleted', 0);
            $this->db->where('project.is_active', 1);

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));
            $this->db->get();
            $query2 = $this->db->last_query();

            $query3 = $this->db->query($query1." UNION ".$query2 . " order by last_updated desc limit ". $offset . "," . $limit);
            $result = $query3->result();
            return $result;
        }
        elseif($country_id!='')
        {
            $this->db->select('organisation.*');
            $this->db->distinct();
            $this->db->from('organisation');

            $this->db->where('organisation.country_id', $country_id);

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));
            $this->db->get();
            $query1 = $this->db->last_query();

            $this->db->select('organisation.*');
            $this->db->distinct();
            $this->db->from('organisation');

            $this->db->join('project', 'project.ngo_id = organisation.id');
            $this->db->join('project_country_state', 'project_country_state.project_id = project.id');
            $this->db->where('project_country_state.country_id', $country_id);
            $this->db->where('project.is_active', 1);

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));
            $this->db->get();
            $query2 = $this->db->last_query();
            
            $query3 = $this->db->query($query1." UNION ".$query2 . " order by last_updated desc limit ". $offset . "," . $limit);
            $result = $query3->result();
            return $result;
        }
        else
        {
            $this->db->select('organisation.*');
            $this->db->distinct();
            $this->db->from('organisation');

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));

            $this->db->order_by('organisation.last_updated', 'desc');
            $this->db->limit($limit,$offset);

            $result = $this->db->get();
            $result = $result->result();
            return $result;
        }
    }

    public function organisation_list_count($query, $category_id, $country_id)
    {
        if($category_id!='')
        {
            $this->db->select('organisation.id');
            $this->db->distinct();
            $this->db->from('organisation');

            $this->db->join('categories_ngo', 'categories_ngo.ngo_id = organisation.id');
            $this->db->where('categories_ngo.categories_id', $category_id);

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));
            $this->db->get();
            $query1 = $this->db->last_query();

            $this->db->select('organisation.id');
            $this->db->distinct();
            $this->db->from('organisation');

            $this->db->join('project', 'project.ngo_id = organisation.id');
            $this->db->join('goals', 'goals.project_id = project.id');
            $this->db->where('goals.categories_id', $category_id);
            $this->db->where('goals.is_deleted', 0);
            $this->db->where('project.is_active', 1);

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));
            $this->db->get();
            $query2 = $this->db->last_query();

            $query3 = $this->db->query($query1." UNION ".$query2);
            $result = $query3->result();
            return count($result);
        }
        elseif($country_id!='')
        {
            $this->db->select('organisation.id');
            $this->db->distinct();
            $this->db->from('organisation');

            $this->db->where('organisation.country_id', $country_id);

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));
            $this->db->get();
            $query1 = $this->db->last_query();

            $this->db->select('organisation.id');
            $this->db->distinct();
            $this->db->from('organisation');

            $this->db->join('project', 'project.ngo_id = organisation.id');
            $this->db->join('project_country_state', 'project_country_state.project_id = project.id');
            $this->db->where('project_country_state.country_id', $country_id);
            $this->db->where('project.is_active', 1);

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));
            $this->db->get();
            $query2 = $this->db->last_query();
            
            $query3 = $this->db->query($query1." UNION ".$query2);
            $result = $query3->result();
            return count($result);
        }
        else
        {
            $this->db->select('count(organisation.id) as num ');
            $this->db->distinct();
            $this->db->from('organisation');

            if($query!='')
                $this->db->like('organisation.name', $query);

            $this->db->where(array('organisation.is_active'=>1,'organisation.is_deleted'=>0,'organisation.is_archive'=>0));
            $result = $this->db->get();
            $count = $result->row()->num;
            return (int)$count;
        }
    }

    
    public function get_organisation_list($where)
    {
        $this->db->select('*');
        $this->db->where($where);
        $result = $this->db->get('organisation');
        return $result->result();
    }
}//end of class
/* End of file ngo_model.php */
/* Location: ./application/models/ngo_model.php */