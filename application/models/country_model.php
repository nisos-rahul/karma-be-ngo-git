<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Country_model extends CI_Model 
{
    public function country_list($search='',$offset='',$limit='')
    {
        $query = "select * from country ";
        if(!empty($search))
            $query.=" where name like '%$search%'";
        $query.="limit $offset, $limit";
        $result = $this->db->query($query);
        return $result->result();
    }
    public function country_count($search='')
    {
        $query = "select count(*) as num from country ";
        if(!empty($search))
            $query.=" where name like '%$search%'";

        $result = $this->db->query($query);
        return $result->row();
    }
    public function country_get_insert($country,$country_code)
    {
        $this->db->select('id');
        $this->db->from('country');
        $this->db->where('name', $country);
        $query = $this->db->get();

        if($query->num_rows() > 0)
        {
            $row=$query->row();
            $id = $row->id; 
            return $id;
        }
        else
        {
            $this->db->select('id');
            $this->db->from('country');
            $this->db->where('code', $country_code);
            $query = $this->db->get();
            if($query->num_rows() > 0)
            {
                $row=$query->row();
                $id = $row->id; 
                return $id;
            }
            $insert['name'] = $country;
            $insert['code'] = $country_code;
            $insert['date_created'] = $insert['last_updated'] = date('Y-m-d H:i:s');
            $this->db->insert('country', $insert); 
            $id = $this->db->insert_id();
            return $id;
        }   
    }//country_get_insert
    public function state_get_insert($state,$country_id)
    {
        $this->db->select('id'); 
        $this->db->from('state');
        $this->db->where('name', $state); // Annu's
        $this->db->where('country_id', $country_id);
        $query = $this->db->get();
        
        if($query->num_rows() > 0)
        {
            $row=$query->row();
            $id = $row->id; 
            return $id;
        }
        else
        {
            $insert['name'] = $state;
            $insert['country_id'] = $country_id;
            $insert['date_created'] = $insert['last_updated'] = date('Y-m-d H:i:s');
            $this->db->insert('state', $insert); 
            $id = $this->db->insert_id();
            return $id;
        }   
    }//state_get_insert
    public function city_get_insert($city,$state_id)
    {
        $query = "select id from city where name='$city' and state_id=$state_id limit 1";
        $result = $this->db->query($query);
        $row=$result->row();
        if(!empty($row))
        {
            $id = $row->id; 
            return $id;
        }
        else
        {
            $insert['name'] = $city;
            $insert['state_id'] = $state_id;
            $insert['date_created'] = $insert['last_updated'] = date('Y-m-d H:i:s');
            $this->db->insert('city', $insert); 
            $id = $this->db->insert_id();
            return $id;
        }   
    }//state_get_insert
    public function country_info($id)
    {
        $query = "select * from country where id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//country_info
    public function state_info($id)
    {
        $query = "select * from state where id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//state_info
    public function city_info($id)
    {
        $query = "select * from city where id=$id limit 1";
        $result = $this->db->query($query);
        return $result->row();
    }//city_info
}//end of class
/* End of file country_model.php */
/* Location: ./application/models/country_model.php */