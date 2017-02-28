<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Routing_model extends CI_Model {
    // public function search_for_country($search)
    // {
    //     $this->db->select('country.id');
    //     $this->db->where('country.name', $search);
    //     $this->db->limit(1);
    //     $result = $this->db->get('country');
    //     return $result->row();
    // }
    // public function search_for_pillar($search)
    // {
    //     $this->db->select('categories.id');
    //     $this->db->where('categories.category', $search);
    //     $this->db->limit(1);
    //     $result = $this->db->get('categories');
    //     return $result->row();
    // }
    // public function search_for_project($search)
    // {
    //     $this->db->select('project.id');
    //     $this->db->where('project.title', $search);
    //     $this->db->limit(1);
    //     $result = $this->db->get('project');
    //     return $result->row();
    // }
    // public function search_for_ngo($search)
    // {
    //     $this->db->select('organisation.id');
    //     $this->db->where('organisation.name', $search);
    //     $this->db->limit(1);
    //     $result = $this->db->get('organisation');
    //     return $result->row();
    // }
    // public function search_for_ngo_project($search, $ngo_id)
    // {
    //     $this->db->select('project.id');
    //     $this->db->where('project.title', $search);
    //     $this->db->where('project.ngo_id', $ngo_id);
    //     $this->db->limit(1);
    //     $result = $this->db->get('project');
    //     return $result->row();
    // }
    
    public function delete_ngo_branding_url_routes($ngo_id)
    {
        $this->db->delete('url_routing', array('entity_id' => $ngo_id, 'entity_name' => 'ngo_branding_change')); 
    }

    public function get_url_slug_data($url_slug) 
    {
        $this->db->select('url_routing.*, url_routing_page_type.page_name as page_type');
        $this->db->from('url_routing');
        $this->db->join('url_routing_page_type', 'url_routing_page_type.id = url_routing.page_id');
        $this->db->where('url_slug', $url_slug);
        $result = $this->db->get();
        return $result->row();
    }

    public function get_urls($where='') 
    {
        $this->db->select('url_routing.*, url_routing_page_type.page_name as page_type, url_routing_page_type.priority as priority, url_routing_page_type.changefreq as changefreq');
        $this->db->from('url_routing');
        $this->db->join('url_routing_page_type', 'url_routing_page_type.id = url_routing.page_id');
        if($where!='')
            $this->db->where($where);

        $result = $this->db->get();
        return $result->result();
    }

    public function insert_url($insert)
    {
        $this->db->insert('url_routing', $insert);
        return;
    }

    public function update_url($update, $where)
    {
        $this->db->update('url_routing', $update, $where);
        return;
    }

    public function delete_urls($where)
    {
        $this->db->delete('url_routing', $where); 
    }
}