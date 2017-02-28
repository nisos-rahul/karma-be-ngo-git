<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Login extends Rest 
{
    public function __construct()
    {       
        parent::__construct();  
        $this->load->model('Audit_model');  
    }
    public function check_credential()
    {           
        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $usernameoremail = isset($jsonArray['usernameoremail'])?$jsonArray['usernameoremail']:'';
        $password = isset($jsonArray['password'])?$jsonArray['password']:'';
        if(empty($usernameoremail) || empty($password))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Validation error.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }//if blank 
        else
        {
            //call login api of grails

            $url = $this->config->item('admin_url')."login";
            try{
                
                $ch = curl_init();
                $postdata = array(
                    'usernameoremail' => $usernameoremail,
                    'password' => $password,
                );
                $data_string = json_encode($postdata); 
                $ch = curl_init(); 
                $ret = curl_setopt($ch, CURLOPT_URL, $url);                                                                     
                $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
                $ret = curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
                $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                          
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                    'Content-Type: application/json',                                                                                
                    'Content-Length: ' . strlen($data_string))                                                                       
                );      
                $ret = curl_exec($ch);
                $info = curl_getinfo($ch);
            }//end try
            catch(Exception $e)
            {
                
                header('HTTP/1.1 500 Internal Server Error');
                return;
            }//end catch
            if($info['http_code']=='401')
            {
                $data['error'] = true;
                $data['status'] = 401;
                $data['message'] = "Unauthorized User.";
                $data['name'] = "login";
                header('HTTP/1.1 401 Unauthorized User');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }//if 401
            else
            if($info['http_code']=='400')
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Validation error.";

                header('HTTP/1.1 400 Validation Error');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                return;
            }    
            else
            {               
                $response = json_decode($ret);
                if(isset($response->roles[0]))
                {
                    //logged in but not NGO User
                    $role = $response->roles[0];
                    if($role=='ROLE_NGO_ADMIN' || $role=='ROLE_NGO_MEMBER' || $role=='ROLE_SUPPORT')
                    {
                        //check valid ngo
                        $jsonArray = json_decode($ret); 
                        $auth_token = isset($jsonArray->access_token)?$jsonArray->access_token:'';
                        $user_info = $this->User_model->get_info_by_username($jsonArray->username);

                        if(!empty($user_info))
                        {
                            $jsonArray->user_id = $user_info->id;
                            $jsonArray->email = $user_info->email;
                        }
                        $ret = json_encode($jsonArray);
                        if($role=='ROLE_NGO_ADMIN' || $role=='ROLE_NGO_MEMBER')
                            $ngo_id = login_ngo_details($auth_token);
                        else
                            $ngo_id = 9;
                        if(empty($ngo_id))
                        {
                            $data['error'] = true;
                            $data['status'] = 401;
                            $data['message'] = "Unauthorized User....";
                            $data['name'] = "login";
                            header('HTTP/1.1 401 Unauthorized User');
                            echo json_encode($data,JSON_NUMERIC_CHECK);
                            return;
                        }
                        else
                        {
                            //audit login
                            $audit_id = $this->Audit_model->login($response);
                            //audit login
                            echo $ret;
                        }
                    }
                    else
                    {
                        $data['error'] = true;
                        $data['status'] = 401;
                        $data['message'] = "Unauthorized User.";
                        $data['name'] = "login";
                        header('HTTP/1.1 401 Unauthorized User');
                        echo json_encode($data,JSON_NUMERIC_CHECK);
                        return;
                    }
                }
                else    
                {
                    echo $ret;
                    return;
                }                   
                    
                return;
            }
        }//if not blank     
    }//check_credential

    public function get_csrf_token()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($auth_token))
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        
        $token = md5(uniqid() . microtime() . rand());

        $data = array(
            'token' => $token,
            'date_created' => date('Y-m-d H:i:s'),
        );

        $this->db->insert('csrf_token', $data); 

        $newD = [ 'token_name' => $token ];

        echo json_encode($newD,JSON_NUMERIC_CHECK);
    }

}//Login
/* End of file login.php */
/* Location: ./application/controllers/login.php */ 