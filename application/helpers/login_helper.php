<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 if(!function_exists('login_ngo_details')){
	function login_ngo_details($auth_token)
	{
		$ngo_id='';
		if(empty($auth_token))
		{
			return $ngo_id;	
		}
		$CI = get_instance();
		$valid_auth_token = $CI->Verification_model->valid_auth_token($auth_token);	
		if(empty($valid_auth_token))
		{
			return $ngo_id;	
		}
		$user_id = $valid_auth_token->user_id;			
		//list all ngo user
		$role_id = $valid_auth_token->role_id;
		//organisation details
		//if ngo admin then check directly organization
		if($role_id==4)
		{
			$organisation = $CI->Ngo_model->organization_exist($user_id);
		}
		elseif($role_id==5)
		{

			//get organisation_id from ngo_user
			$organisation = $CI->Ngo_model->organization_member_details($user_id);
		}
		else
		{
			$CI->load->model('Support_model');
			$organisation = $CI->Support_model->support_role_details($user_id); 
			// $organisation = $CI->Support_model->get_ngos_by_support($user_id); 
		}
		// var_dump($organisation);
		// 	die();
		if(!empty($organisation))
		{
			if($role_id==4 || $role_id==5)
			{
				$ngo_id = $organisation->id;
			}
			else
			{
				$ngo_id = $CI->input->get('ngoId');
				if(empty($ngo_id))
				{
					$data['error'] = true;
					$data['status'] = 400;
					$data['message'] = "ngo id not given.";
					header('HTTP/1.1 400 Validation Error.');
					echo json_encode($data,JSON_NUMERIC_CHECK);
					exit;
				}
				$user_id = $valid_auth_token->user_id;
				$supportNgos = support_ngos($user_id);
				$supportCount = 0;
				foreach($supportNgos as $supportNgo)
				{
					if($supportNgo['id']==$ngo_id) {
						$supportCount++;
					}
				}

				if($supportCount==0)
				{
					return null;
				}
			}
		}
		return $ngo_id;	
	}
	
  }
if(!function_exists('login_user_details')){
	function login_user_details($auth_token)
	{
		$user_id = '';
		if(empty($auth_token))
		{
			return $user_id;	
		}
		$CI = get_instance();
		$valid_auth_token = $CI->Verification_model->valid_auth_token($auth_token);	
		if(empty($valid_auth_token))
		{
			return $user_id;	
		}
		$user_id = $valid_auth_token->user_id;
		return $user_id;
	}
}	
/* End of file login_helper.php */
/* Location: ./application/helpers/login_helper.php */