<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if(!function_exists('support_ngos')){
	function support_ngos($support_id)
	{
		$CI = get_instance();
		$CI->load->model('Support_model');

		$ngos_data = $CI->Support_model->get_ngos_by_support($support_id);
		if(empty($ngos_data))
		{
			$data['error'] = true;
			$data['status'] = 404;
			$data['message'] = "Operation failed to find user using id mentioned.";
			header('HTTP/1.1 404 Not Found');
			echo json_encode($data,JSON_NUMERIC_CHECK);
			exit;
		}
		else
		{
			foreach($ngos_data as $ngo_data)
			{
				$a = array(
					'id' => $ngo_data->ngo_id,
					'name' => $ngo_data->name,
				);
				$data[] = $a; 
			}
		}
		return $data;
	}
}
/* End of file login_helper.php */
/* Location: ./application/helpers/login_helper.php */