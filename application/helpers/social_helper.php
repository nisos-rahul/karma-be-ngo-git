<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
include '/home/devuser/projects/karma-be-ngo/application/libraries/facebook-php-sdk-v4-5.0.0/src/Facebook/autoload.php';

if(!function_exists('post_on_facebook')) {
	function post_on_facebook($ngo_id, $linkData)
	{
		$CI = get_instance();
		$CI->load->model('Social_media_sharing_model');

		$ngo_data = $CI->Social_media_sharing_model->get_user_data(array('ngo_id'=>$ngo_id));
		if(empty($ngo_data))
		{
			$data['error'] = true;
			$data['status'] = 400;
			$data['message'] = "Required data not found for this Npo.";
			header('HTTP/1.1 400 Validation Error.');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		}
		if($ngo_data->facebook=='' || $ngo_data->facebook==null)
		{
			$data['error'] = true;
			$data['status'] = 400;
			$data['message'] = "Required data not found for this Npo.";
			header('HTTP/1.1 400 Validation Error.');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		}
		$access_token = $ngo_data->fb_extended_access_token;

		$fb = new \Facebook\Facebook([
			'app_id' => $CI->config->item('facebook_app_id'),
			'app_secret' => $CI->config->item('facebook_app_secret'),
			'default_graph_version' => 'v2.5',
		]);

		try{								
			$url = "https://graph.facebook.com/me?access_token=$access_token";
			$ch = curl_init(); 
			$ret = curl_setopt($ch, CURLOPT_URL, $url);                   
			$ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			$ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  							
			$ret = curl_exec($ch);					
			$info = curl_getinfo($ch);
			curl_close($ch);
		}
		catch(Exception $e)
		{
			$data['error'] = true;
			$data['status'] = 400;
			$data['message'] = $e;
			header('HTTP/1.1 400 Validation Error.');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		}
		$resp = json_decode($ret, true);
		if (array_key_exists("error",$resp))
		{
			$data['error'] = true;
			$data['status'] = 400;
			$data['message'] = $resp;
			header('HTTP/1.1 400 Validation Error.');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		}

		try {
			$response = $fb->post('/me/feed', $linkData, $access_token);
		} catch(Facebook\Exceptions\FacebookResponseException $e) {

			$error = $e->getMessage();
			if($error=="Duplicate status message")
			{
				$message = "You are sending a duplicate post to facebook. Please un-check facebook and click update or change the update text and try again.";
			}
			else
			{
				$message = "An authentication error occurred while posting to facebook, please connect to facebook again.";
			}
			$data['error'] = true;
			$data['status'] = 400;
			$data['message'] = $error;
			header('HTTP/1.1 400 Validation Error.');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			$data['error'] = true;
			$data['status'] = 400;
			$data['message'] = $error;
			header('HTTP/1.1 400 Validation Error.');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		}

		$graphNode = $response->getGraphNode();
	}
}

if(!function_exists('post_on_twitter')){
	function post_on_twitter($ngo_id, $linkData)
	{
		$CI = get_instance();
		$CI->load->model('Social_media_sharing_model');

		$ngo_data = $CI->Social_media_sharing_model->get_user_data(array('ngo_id'=>$ngo_id));
		if(empty($ngo_data))
		{
			$data['error'] = true;
			$data['status'] = 400;
			$data['message'] = "Required data not found for this Npo.";
			header('HTTP/1.1 400 Validation Error.');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		}
		if($ngo_data->twitter_access_token=='' || $ngo_data->twitter_access_token==null
			||$ngo_data->twitter_access_token_secret=='' || $ngo_data->twitter_access_token_secret==null)
		{
			$data['error'] = true;
			$data['status'] = 400;
			$data['message'] = "Required data not found for this Npo.";
			header('HTTP/1.1 400 Validation Error.');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		}
		// $access_token = $ngo_data->fb_extended_access_token;
		$CI->load->library('twitteroauth');
		$consumerKey = $CI->config->item('twitter_consumer_token');
		$consumerSecret = $CI->config->item('twitter_consumer_secret');
		$accessToken = $ngo_data->twitter_access_token;
		$accessTokenSecret = $ngo_data->twitter_access_token_secret;

		$connection = $CI->twitteroauth->create($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
		$data = array(
		    'status' => $linkData
		);
		if(strlen($linkData)<=140)
			$result = $connection->post('statuses/update', $data);
		else
		{
			$data['error'] = true;
			$data['status'] = 400;
			$data['message'] = "Message should not be more 140 charachers.";
			header('HTTP/1.1 400 Validation Error.');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		}
	}
}
