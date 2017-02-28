<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Category_model extends CI_Model 
{
    public function category_list($search='',$offset='',$limit='')
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
}//end of class
/* End of file category_model.php */
/* Location: ./application/models/category_model.php */