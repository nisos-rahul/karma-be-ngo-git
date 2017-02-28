<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Country extends Rest 
{   
    public function __construct()
    {
        parent::__construct();      
        $this->load->model('Country_model');
        if($this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            if(empty($valid_auth_token))
            {
                $data['error'] = true;
                $data['status'] = 401;
                $data['message'] = "Unauthorized User.";
                header('HTTP/1.1 401 Unauthorized User');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
            $role_id = $valid_auth_token->role_id;      
            //if ngo admin or member then only check for ngo
            if($role_id==1 || $role_id==4 || $role_id==5 || $role_id==7)
            {
                if($role_id==4 || $role_id==5)
                {
                    $ngo_id = login_ngo_details($auth_token);       
                    if(empty($ngo_id))
                    {
                        $data['error'] = true;
                        $data['status'] = 401;
                        $data['message'] = "Unauthorized User.";
                        header('HTTP/1.1 401 Unauthorized User');
                        echo json_encode($data,JSON_NUMERIC_CHECK);
                        exit;
                    }
                }
            }
            else
            {
                $data['error'] = true;
                $data['status'] = 401;
                $data['message'] = "Unauthorized User.";
                header('HTTP/1.1 401 Unauthorized User');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
        }
    }
    //fetch country list
    public function list_all()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;           
        $list = $this->Country_model->country_list($query, $offset, $limit);
        $data['error'] = false;  
        $data['resp'] = array();
        $count = $this->Country_model->country_count($query);
        $data['resp']['count'] = $count->num;
        if(!empty($list))
        {
            $i =0;
            foreach($list as $country_details)
            {
                $data['resp']['country'][$i]['id'] = (float)$country_details->id;
                $data['resp']['country'][$i]['country'] = $country_details->name;
                $data['resp']['country'][$i]['countryCode'] = $country_details->code;
                $data['resp']['country'][$i]['lastUpdated'] = $country_details->last_updated;
                $data['resp']['country'][$i]['dateCreated'] = $country_details->date_created;
                $i++;
            }
        }   
        else
        {
            $data['resp']['country'] = array();
        }
        $data = json_encode($data,JSON_NUMERIC_CHECK);
        echo $data;
        return; 

    }//list_all
    //fetch country details
    public function get_country($id=0)
    {
        if(!$this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $country_details = $this->Country_model->country_info($id);
        if(!empty($country_details))
        {
            $data['error'] = false;
            $data['resp']['id'] = (float)$country_details->id;
            $data['resp']['name'] = $country_details->name;
            $data['resp']['code'] = $country_details->code;
        }
        else{
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find country using id mentioned.";
            header('HTTP/1.1 404 Not Found');
        }
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//get_country
    //fetch state details
    public function get_state($id=0)
    {
        if(!$this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $state_details = $this->Country_model->state_info($id);
        if(!empty($state_details))
        {
            $data['error'] = false;
            $data['resp']['id'] = (float)$state_details->id;
            $data['resp']['name'] = $state_details->name;
        }
        else{
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find state using id mentioned.";
            header('HTTP/1.1 404 Not Found');
        }
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//get_state
    //fetch city details
    public function get_city($id=0)
    {
        if(!$this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $city_details = $this->Country_model->city_info($id);
        if(!empty($city_details))
        {
            $data['error'] = false;
            $data['resp']['id'] = (float)$city_details->id;
            $data['resp']['name'] = $city_details->name;
        }
        else{
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find city using id mentioned.";
            header('HTTP/1.1 404 Not Found');
        }
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }//get_state

    public function get_country_id_by_name()
    {
        $country_name = $this->input->get('name');
        $country_name = str_replace('-', ' ', $country_name);
        if($country_name===false)
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        $country_details = $this->Country_model->get_country_info(array('name'=>$country_name));
        if(empty($country_details))
        {
            header('HTTP/1.1 404 Not Found');
            return;
        }
        $data['error'] = false;
        $data['resp']['id'] = (float)$country_details->id;
        $data['resp']['category'] = $country_details->name;
        $data['resp']['countryCode'] = $country_details->code;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function insert_country_flag()
    {
        if(!$this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $base_url = $this->config->item('customer_url');
        if($base_url=='')
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "url not found.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        } 

        $query1 = "select * from country";
        $result = $this->db->query($query1);
        $countries = $result->result();
        foreach($countries as $country)
        {
            $id = $country->id;
            $code = $country->code;
            $url = $base_url."flags/$code.png";
            $url = strtolower($url);
            $ch = @curl_init();
            @curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            @curl_setopt($ch, CURLOPT_URL, $url);
            @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = @curl_exec($ch);
            $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_errors = curl_error($ch);
            @curl_close($ch);
            if($status_code==200)
            {
                $update['flag_url'] = $url;
                $this->db->update('country', $update, array('id' => $id));
            }
        }
        $data['error'] = false;
        $data['resp'] = 'Urls added successfully.';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }
}//end Country
/* End of file country.php */
/* Location: ./application/controllers/country.php */