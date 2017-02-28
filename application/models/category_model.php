<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Category_model extends CI_Model 
{
    public function category_list($search='', $offset='', $limit='')
    {
        $query = "select * from categories where is_deleted=0 ";
        if($search!="")
            $query.=" and category like '%$search%'";
        if($offset>=0 && trim($offset)!='')
            $query.=" limit $offset, $limit";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function category_count($search)
    {
        $query = "select count(*) as num from categories where is_deleted=0 ";
        if($search!="")
            $query.=" and category like '%$search%'";           
        $result = $this->db->query($query);
        return $result->row();
    }
    public function category_info($id)
    {
        $query = "select * from categories where id=$id and is_deleted=0 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function match_category($catname)
    {
        $query = "select id from categories where category='$catname' and is_deleted=0 limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }
    public function get_category_info($where)
    {
        $this->db->select('*');
        $this->db->from('categories');
        $this->db->where($where);
        $this->db->where('is_deleted', 0);
        $result = $this->db->get();
        return $result->row();
    }
    public function add_pillar($insert)
    {
        $this->db->insert('categories', $insert);
        return $this->db->insert_id();
    }
    public function update_category($update, $id)
    {
        $this->db->update('categories', $update, array('id'=>$id));
        return;
    }
}//end of class
/* End of file category_model.php */
/* Location: ./application/models/category_model.php */