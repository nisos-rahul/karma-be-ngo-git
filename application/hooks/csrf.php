<?php

class CSRF_Protection
{

  private $CI;
 
  private static $token_name = 'token_name';
 
  private static $token;
 
  public function __construct()
  {
    $this->CI =& get_instance();
  }
  
  public function validate_tokens()
  {
    if(!$this->CI->input->is_cli_request())
    {
      if ($_SERVER['REQUEST_METHOD'] == 'POST')
      {
        $whitelist_url = array('ngo','ngo/(:num)','project','project/(:num)','activity','activity/(:num)','donor','donor/(:num)');
       
        $routes = array_reverse($this->CI->router->routes);
        foreach ($routes as $key => $val) {
          $route = $key; 
          $key = str_replace(array(':any', ':num'), array('[^/]+', '[0-9]+'), $key);
          if (preg_match('#^'.$key.'$#', $this->CI->uri->uri_string(), $matches)) break;
        }

        if(in_array($route, $whitelist_url))
        {
          $token = $this->CI->input->server('HTTP_X_XSRF_TOKEN');
          $this->CI->load->model('Csrf_model');
          $token_v = $this->CI->Csrf_model->token_check($token);
          
          if (empty($token_v))
          {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Request was invalid. Tokens did not match.";
            header('HTTP/1.1 400 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;          
          }
          else
          {
            $delete_token = $this->CI->Csrf_model->delete_token($token);
          }
        }
      }
    }
  }
}