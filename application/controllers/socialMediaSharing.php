<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';
include APPPATH.'libraries/facebook-php-sdk-v4-5.0.0/src/Facebook/autoload.php';
include APPPATH.'libraries/oauth-subscriber-master/src/Oauth1.php';
require './vendor/autoload.php';

use GuzzleHttp\Subscriber\Oauth\Oauth1;

class SocialMediaSharing extends Rest 
{
    public function __construct() {

        parent::__construct();
        // $this->load->library('codeigniter-guzzle-master/guzzle');
        $this->load->library('twitteroauth');
        $this->load->model('Social_media_sharing_model');
        $this->load->library('google_url_api');
    }

    function get_screen_name($twitter_id)
    {
        $ngo_data = $this->Social_media_sharing_model->get_user_data(array('twitter'=>$twitter_id));
        if(empty($ngo_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Twitter error: Required data not found for this Npo.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($ngo_data->twitter_access_token=='' || $ngo_data->twitter_access_token==null
            ||$ngo_data->twitter_access_token_secret=='' || $ngo_data->twitter_access_token_secret==null)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Twitter error: Required data not found for this Npo.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $this->load->library('twitteroauth');
        $consumerKey = $this->config->item('twitter_consumer_token');
        $consumerSecret = $this->config->item('twitter_consumer_secret');
        $accessToken = $ngo_data->twitter_access_token;
        $accessTokenSecret = $ngo_data->twitter_access_token_secret;

        $connection = $this->twitteroauth->create($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
        $result1 = $connection->get('account/settings');
        $data1['error'] = false;
        $data1['screen_name'] = $result1->screen_name;
        $data1['message'] = 'Successful.';
        echo json_encode($data1,JSON_NUMERIC_CHECK);
        exit;
    }

    public function facebook()
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
        if($role_id!=1 && $role_id!=4)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $user_id = $valid_auth_token->user_id;
        $organisation = $this->Ngo_model->organization_exist($user_id);

        $client = new \GuzzleHttp\Client();
        $jsonArray = json_decode(file_get_contents('php://input'), true);
        $params = [
            'code' => $jsonArray['code'],
            'client_id' => $jsonArray['clientId'],
            'redirect_uri' => $jsonArray['redirectUri'],
            'client_secret' => $this->config->item('facebook_app_secret'),
        ];

        $accessTokenResponse = $client->request('GET', 'https://graph.facebook.com/v2.5/oauth/access_token', [
            'query' => $params
        ]);
        $accessToken = json_decode($accessTokenResponse->getBody(), true);

        // $accessToken['access_token'] = 'EAADspJzki4gBADPxe8c224eTrk4ubWg0EqyW52ZC02hcm2x20nASIk1Sqd4od1Rknssxjdk2GG2MIzPetLHvIh1MBhmKjJQEirI2Nfk5eF1FK1jBC5TEKlZCtjRn0DrS8T5zNWsoylqTlnCAR39VbxMsjU84Yem4BfHKXbZCQZDZD';

        $fb = new \Facebook\Facebook([
            'app_id' => $this->config->item('facebook_app_id'),
            'app_secret' => $this->config->item('facebook_app_secret'),
            'default_graph_version' => 'v2.5',
        ]);
        $client2 = $fb->getOAuth2Client();
        $tokenInfo = $client2->debugToken($accessToken['access_token']);
        $expiresAt = json_decode(json_encode($tokenInfo->getExpiresAt()))->date;
        // $env = $_SERVER['ENV'];
        // if($env!='production')
        // {
        //     $date = date('Y-m-d H:i:s');
        //     $expiresAt = date('Y-m-d H:i:s', strtotime($date . ' +1 day'));
        // }

        $fields = 'id,email,first_name,last_name,link,name';
        $profileResponse = $client->request('GET', 'https://graph.facebook.com/v2.5/me', [
            'query' => [
                'access_token' => $accessToken['access_token'],
                'fields' => $fields
            ]
        ]);
        $profile = json_decode($profileResponse->getBody(), true);

        $where1 = array('facebook'=>$profile['id']);
        $same_facebook_id_check = $this->Social_media_sharing_model->get_user_data($where1);
        if(!empty($same_facebook_id_check))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "This Facebook account has already connected to another NPO.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $where2 = array('user_id'=>$user_id);
        $user = $this->Social_media_sharing_model->get_user_data($where2);
        if(empty($user))
        {
            $insert['facebook'] = $profile['id'];
            $insert['user_id'] = $user_id;
            if($role_id==4)
                $insert['ngo_id'] = $organisation->id;
            $insert['fb_extended_access_token'] = $accessToken['access_token'];
            $insert['fb_extended_access_token_expires'] = $expiresAt;
            $insert['created_at'] = date('Y-m-d H:i:s');
            $insert['updated_at'] = date('Y-m-d H:i:s');
            $insert['facebook_is_post_on_pages'] = 0;
            $insert['manual_unlink'] = 0;
            $user_social_id = $this->Social_media_sharing_model->store_user_data($insert);
        }
        else
        {
            if($user->facebook!='')
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "You are already logged in with another Facebook account.";
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
            $update['facebook'] = $profile['id'];
            $update['fb_extended_access_token'] = $accessToken['access_token'];
            $update['fb_extended_access_token_expires'] = $expiresAt;
            $update['updated_at'] = date('Y-m-d H:i:s');
            $update['facebook_is_post_on_pages'] = 0;
            $update['manual_unlink'] = 0;
            $user_social_id = $user->id;
            $this->Social_media_sharing_model->update_user_data($update, array('id'=>$user_social_id));
        }
        $data['token'] = 'abc';
        $data['id'] = $profile['id'];
        $data['firstName'] = $profile['first_name'];
        $data['lastName'] = $profile['last_name'];
        $data['name'] = $profile['name'];
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function get_user_pages()
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
        if($role_id!=4)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $ngo_id = login_ngo_details($auth_token);

        $ngo_data = $this->Social_media_sharing_model->get_user_data(array('ngo_id'=>$ngo_id));
        if(empty($ngo_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Facebook error: Required data not found for this Npo.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($ngo_data->facebook=='' || $ngo_data->facebook==null)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Facebook error: Required data not found for this Npo.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $access_token = $ngo_data->fb_extended_access_token;
        if($access_token=='' || $access_token==null)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Facebook error: Access token not found.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $fb = new \Facebook\Facebook([
            'app_id' => $this->config->item('facebook_app_id'),
            'app_secret' => $this->config->item('facebook_app_secret'),
            'default_graph_version' => 'v2.5',
        ]);

        $response = $fb->get("/me/accounts", $access_token);
        $json = $response->getDecodedBody();
        $pages = $json['data'];
        $array = array();
        $i=0;
        foreach ($pages as $key => $value) {
            $array[$i]['id'] = $value['id'];
            $array[$i]['name'] = $value['name'];
            $i++;
        }
        
        $data['error'] = false; 
        $data['resp'] = $array;
        echo json_encode($data, JSON_NUMERIC_CHECK);
        return;
    } 

    public function set_post_on_page() 
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
        if($role_id!=4)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $ngo_id = login_ngo_details($auth_token);
        $user_data = $this->Social_media_sharing_model->get_user_data(array('ngo_id'=>$ngo_id));
        if(empty($user_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Facebook error: Required data not found for this Npo.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $access_token = $user_data->fb_extended_access_token;

        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $page_id = isset($jsonArray['pageId'])?$jsonArray['pageId']:'';

        $fb = new \Facebook\Facebook([
            'app_id' => $this->config->item('facebook_app_id'),
            'app_secret' => $this->config->item('facebook_app_secret'),
            'default_graph_version' => 'v2.5',
        ]);

        $response = $fb->get("/$page_id?fields=access_token", $access_token);
        $json = $response->getBody();
        $page_data = json_decode($json, true);
        $page_access_token = $page_data['access_token'];
        
        $update['updated_at'] = date('Y-m-d H:i:s');
        $update['facebook_is_post_on_pages'] = 1;
        $update['facebook_page_id'] = $page_id;
        $update['facebook_page_access_token'] = $page_access_token;
        $this->Social_media_sharing_model->update_user_data($update, array('ngo_id'=>$ngo_id));

        $data['error'] = false;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function post_activity_on_facebook() {

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
        if($role_id!=4 && $role_id!=5)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $ngo_id = login_ngo_details($auth_token);

        $user_data = $this->Social_media_sharing_model->get_user_data(array('ngo_id'=>$ngo_id));
        if(empty($user_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Facebook error: Required data not found for this Npo.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($user_data->facebook=='' || $user_data->facebook==null)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Facebook error: Required data not found for this Npo.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $fb = new \Facebook\Facebook([
            'app_id' => $this->config->item('facebook_app_id'),
            'app_secret' => $this->config->item('facebook_app_secret'),
            'default_graph_version' => 'v2.5',
        ]);

        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $facebook_link = isset($jsonArray['facebookLink'])?$jsonArray['facebookLink']:'';
        $facebook_message = isset($jsonArray['facebookMessage'])?$jsonArray['facebookMessage']:'';
        $social_data = [
            'link' => $facebook_link,
            'message' => $facebook_message,
        ];

        if($user_data->facebook_is_post_on_pages==0)
        {
            $access_token = $user_data->fb_extended_access_token;

            try {
                $response = $fb->post('/me/feed', $social_data, $access_token);
            } catch(Facebook\Exceptions\FacebookResponseException $e) {
                $error = $e->getMessage();
                $error = "Facebook error: ".$error;
                if($e->getCode()==190)
                {
                    $error = 'Oops.. We need you to authenticate with Facebook again (they make us do this every once in a while). Could you please use the refresh button below to continue posting on Facebook? Thanks!';
                    if($role_id!=4)
                    {
                        $error = 'Please ask NPO admin to reconnect to facebook from NPO info page.';
                    }
                    $data['flag'] = 190;
                }
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = $error;
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            } catch(Facebook\Exceptions\FacebookSDKException $e) {
                $error = $e->getMessage();
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Facebook error: ".$error;
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
            $data['id'] = $user_data->facebook;

        }
        else
        {
            $access_token = $user_data->facebook_page_access_token;
            $page_id = $user_data->facebook_page_id;
            try {
                $response = $fb->post("/$page_id/feed", $social_data, $access_token );
            } catch(Exception $e) {
                $error = $e->getMessage();
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Facebook error: ".$error;
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
            $data['id'] = $page_id;
        }

        $data['error'] = false;
        $data['message'] = 'Successfully Posted on Facebook.';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function twitter()
    {
        if(!$this->input->get('oauth_token') || !$this->input->get('oauth_verifier'))
        {
            $stack = GuzzleHttp\HandlerStack::create();
            $requestTokenOauth = new Oauth1([
                'consumer_key' => $this->config->item('twitter_consumer_token'),
                'consumer_secret' => $this->config->item('twitter_consumer_secret'),
                'token' => '',
                'token_secret' => ''
            ]);
            $stack->push($requestTokenOauth);

            $client = new GuzzleHttp\Client([
                'handler' => $stack
            ]);

            $requestTokenResponse = $client->request('POST', 'https://api.twitter.com/oauth/request_token', [
                'auth' => 'oauth'
            ]);

            $oauthToken = array();
            parse_str($requestTokenResponse->getBody(), $oauthToken);

            $c = $oauthToken['oauth_token'];
            redirect("https://api.twitter.com/oauth/authenticate?oauth_token=$c");
            exit;
        }
        else
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
            if($role_id!=1 && $role_id!=4)
            {
                $data['error'] = true;
                $data['status'] = 401;
                $data['message'] = "Unauthorized User.";
                header('HTTP/1.1 401 Unauthorized User');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
            $user_id = $valid_auth_token->user_id;
            $organisation = $this->Ngo_model->organization_exist($user_id);

            $stack = GuzzleHttp\HandlerStack::create();
            $c = $this->input->get('oauth_token');
            $e = $this->input->get('oauth_verifier');
            $accessTokenOauth = new Oauth1([
                'consumer_key' => $this->config->item('twitter_consumer_token'),
                'consumer_secret' => $this->config->item('twitter_consumer_secret'),
                'token' => $c,
                'verifier' =>$e,
                'token_secret' => ''
            ]);
            $stack->push($accessTokenOauth);
            $client = new GuzzleHttp\Client([
                'handler' => $stack
            ]);

            $accessTokenResponse = $client->request('POST', 'https://api.twitter.com/oauth/access_token', [
            'auth' => 'oauth'
            ]);

            $accessToken = array();
            parse_str($accessTokenResponse->getBody(), $accessToken);
            $profileOauth = new Oauth1([
                'consumer_key' => $this->config->item('twitter_consumer_token'),
                'consumer_secret' => $this->config->item('twitter_consumer_secret'),
                'oauth_token' => $accessToken['oauth_token'],
                'token_secret' => ''
            ]);
            $stack->push($profileOauth);
            $client = new GuzzleHttp\Client([
                'handler' => $stack
            ]);

            $profileResponse = $client->request('GET', 'https://api.twitter.com/1.1/users/show.json?screen_name=' . $accessToken['screen_name'], [
                'auth' => 'oauth'
            ]);
            $profile = json_decode($profileResponse->getBody(), true);

            $where1 = array('twitter'=>$profile['id']);
            $same_twitter_id_check = $this->Social_media_sharing_model->get_user_data($where1);
            if(!empty($same_twitter_id_check))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "This Twitter account has already connected to another NPO.";
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }

            $where2 = array('user_id'=>$user_id);
            $user = $this->Social_media_sharing_model->get_user_data($where2);
            if(empty($user))
            {
                $insert['twitter'] = $profile['id'];
                $insert['user_id'] = $user_id;
                if($role_id==4)
                    $insert['ngo_id'] = $organisation->id;
                $insert['twitter_access_token'] = $accessToken['oauth_token'];
                $insert['twitter_access_token_secret'] = $accessToken['oauth_token_secret'];
                $insert['twitter_screen_name'] = $accessToken['screen_name'];
                $insert['created_at'] = date('Y-m-d H:i:s');
                $insert['updated_at'] = date('Y-m-d H:i:s');
                $user_social_id = $this->Social_media_sharing_model->store_user_data($insert);
            }
            else
            {
                if($user->twitter!='')
                {
                    $data['error'] = true;
                    $data['status'] = 400;
                    $data['message'] = "You are already logged in with another Twitter account.";
                    header('HTTP/1.1 400 Validation Error.');
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    exit;
                }
                $update['twitter'] = $profile['id'];
                $update['twitter_access_token'] = $accessToken['oauth_token'];
                $update['twitter_access_token_secret'] = $accessToken['oauth_token_secret'];
                $update['twitter_screen_name'] = $accessToken['screen_name'];
                $update['updated_at'] = date('Y-m-d H:i:s');
                $user_social_id = $user->id;
                $this->Social_media_sharing_model->update_user_data($update, array('id'=>$user_social_id));
            }

            $data['token'] = 'abc';
            $data['screen_name'] = $accessToken['screen_name'];

            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
    }

    public function post_activity_on_twitter() {

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
        if($role_id!=4 && $role_id!=5)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $ngo_id = login_ngo_details($auth_token);

        $jsonArray = json_decode(file_get_contents('php://input'),true); 
        $activity_type = isset($jsonArray['activityType'])?$jsonArray['activityType']:'';
        $title = isset($jsonArray['update'])?$jsonArray['update']:'';
        $project_id = isset($jsonArray['projectId'])?$jsonArray['projectId']:'';

        $org_data = $this->Ngo_model->organization_details($ngo_id, 'any');
        if(empty($org_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Twitter error: Please set branding url to post.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if(empty($org_data->branding_url))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Twitter error: Please set branding url to post.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $url = $org_data->branding_url."/project-detail.html?projectId=".$project_id;
        // $url = 'http://nisostech.dev.karmaworld.co/project-detail.html?projectId=19';
        // $short_url = $this->google_url_api->shorten($url);
        // $url = $short_url->id;
        $short_url = $this->google_url_api->shorten($url);
        $short_url_array = json_decode(json_encode($short_url),true);
        if(!array_key_exists("id",$short_url_array))
        {
            if(array_key_exists("error",$short_url_array))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Twitter error: ".$short_url_array['error']['message'];
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
            else
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Twitter error: url shortner not working, contact karma admin.";
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
        }
        $url = $short_url->id;

        if(strlen($title)>50)
        {
            $title = substr($title, 0, 49);
            $title = rtrim($title)."...";
        }
        $message = "$activity_type: $title
            via $url";

        $ngo_data = $this->Social_media_sharing_model->get_user_data(array('ngo_id'=>$ngo_id));
        if(empty($ngo_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Twitter error: Required data not found for this Npo.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($ngo_data->twitter_access_token=='' || $ngo_data->twitter_access_token==null
            ||$ngo_data->twitter_access_token_secret=='' || $ngo_data->twitter_access_token_secret==null)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Twitter error: Required data not found for this Npo.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $this->load->library('twitteroauth');
        $consumerKey = $this->config->item('twitter_consumer_token');
        $consumerSecret = $this->config->item('twitter_consumer_secret');
        $accessToken = $ngo_data->twitter_access_token;
        $accessTokenSecret = $ngo_data->twitter_access_token_secret;

        $connection = $this->twitteroauth->create($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
        $data = array(
            'status' => $message
        );
        if(strlen($message)<=140)
        {
            $result = $connection->post('statuses/update', $data);
            $result = json_decode(json_encode($result),true);
            if(array_key_exists("errors",$result))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Twitter error: ".$result['errors'][0]['message'];
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
            if(array_key_exists("error",$result))
            {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Twitter error: ".$result['error'];
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
        }
        else
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Twitter error: Message should not be more 140 charachers.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $data1['screen_name'] = $ngo_data->twitter_screen_name;
        $data1['error'] = false;
        $data1['message'] = 'Successfully Posted on Twitter.';
        echo json_encode($data1,JSON_NUMERIC_CHECK);
        exit;
    }

    public function unlink($provider)
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
        if($role_id!=1 && $role_id!=4)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $user_id = $valid_auth_token->user_id;

        $user = $this->Social_media_sharing_model->get_user_data(array('user_id'=>$user_id));
        if(empty($user))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "User Not Found.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        if($provider!='facebook' && $provider!='twitter')
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Invalid service name.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        
        if ($provider=='facebook')
        {
            $data['id'] = $user->facebook;
            $update['facebook'] = '';
            $update['fb_extended_access_token'] = '';
            $update['fb_extended_access_token_expires'] = '';
            $update['facebook_is_post_on_pages'] = 0;
            $update['facebook_page_id'] = '';
            $update['facebook_page_access_token'] = '';
            $update['manual_unlink'] = 0;
        }
        elseif ($provider=='twitter')
        {
            $data['screen_name'] = $user->twitter_screen_name;
            $update['twitter'] = '';
            $update['twitter_access_token'] = '';
            $update['twitter_access_token_secret'] = '';
            $update['twitter_screen_name'] = '';
        }
        $update['updated_at'] = date('Y-m-d H:i:s');

        $this->Social_media_sharing_model->update_user_data($update, array('user_id'=>$user_id));

        $data['resp'] = false;
        $data['message'] = 'successful.';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function check_superadmin_status()
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
        if($role_id!=1)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $data['error'] = false;
        $user = $this->Social_media_sharing_model->get_user_data(array('user_id'=>1));

        if(empty($user))
        {
            $data['resp']['facebook'] = false;
            $data['resp']['twitter'] = false;   
        }
        else
        {
            if($user->facebook==null || $user->facebook=='')
                $data['resp']['facebook'] = false;  
            else
                $data['resp']['facebook'] = true;

            if($user->twitter==null || $user->twitter=='')
                $data['resp']['twitter'] = false;   
            else
                $data['resp']['twitter'] = true;
        }
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function check_facebook_expiration()
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
        if($role_id==1)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($role_id==7)
        {
            $data['error'] = false;
            $data['resp'] = false;
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        
        $ngo_id = login_ngo_details($auth_token);

        $where1 = array('ngo_id'=>$ngo_id);
        $ngo_social_data = $this->Social_media_sharing_model->get_user_data($where1);
        if(!empty($ngo_social_data))
        {
            if($ngo_social_data->facebook_is_post_on_pages==0)
            {
                if($ngo_social_data->manual_unlink==1)
                {
                    $status = false;
                }
                else
                {
                    if($ngo_social_data->fb_extended_access_token_expires!=null || $ngo_social_data->fb_extended_access_token_expires!='')
                    {
                        $expiresAt = $ngo_social_data->fb_extended_access_token_expires;
                        $current_date = date('Y-m-d H:i:s');

                        if($expiresAt<$current_date)
                        {
                            $status = true;
                        
                            $update['manual_unlink'] = 1;
                            $update['facebook'] = null;
                            $update['fb_extended_access_token'] = null;
                            $update['fb_extended_access_token_expires'] = null;
                            $update['facebook_is_post_on_pages'] = 0;
                            $update['facebook_page_id'] = '';
                            $update['facebook_page_access_token'] = '';
                            $social_id = $ngo_social_data->id;
                            $this->Social_media_sharing_model->update_user_data($update, array('id'=>$social_id));
                        }
                        else
                        {
                            $status = false;
                        }
                    }
                    else
                    {
                        $status = false;
                    }
                }
            }
            else
            {
                $status = false;
            }
        }
        else
            $status = false;


        $data['error'] = false;
        $data['resp'] = $status;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }
}