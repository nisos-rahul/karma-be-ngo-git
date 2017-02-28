<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';
require APPPATH.'libraries/kraken-php-share/lib/Kraken.php';

class KrakenApi extends Rest 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Amazon_model');
        $this->load->model('Ngo_model');
        $this->load->model('Kraken_model');
    }

    public function upload_media()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();  
        usleep(1500);       
        $data['error'] = false;
        $data['status'] = 200;
        $data['message'] = "Processing.";
        echo json_encode($data,JSON_NUMERIC_CHECK);
        $size = ob_get_length(); 
        header("Content-Length: $size"); 
        header('Connection: close'); 
        ob_end_flush(); 
        ob_flush(); 
        flush();

        $kraken_api_key = $this->config->item('kraken_api_key');
        $kraken_api_secret = $this->config->item('kraken_api_secret');
        $cloudFrontUrl = $this->config->item('cloudfront_domain_url');

        $ngo_list = $this->Ngo_model->organisation_list();
        foreach ($ngo_list as $key => $value) {
            $insert_kraken_log['ngo_id'] = $value->id;
            $this->Kraken_model->insert_kraken_log($insert_kraken_log);
        }

        foreach ($ngo_list as $ngo) {

            $ngo_id = $ngo->id;
            echo "\nNgo Id:".$ngo_id."\n";
            $ngo_project_images = $this->Amazon_model->project_profile_images($ngo_id);
            $activity_images = $this->Amazon_model->get_activity_images($ngo_id);
            $donor_images = $this->Amazon_model->get_donor_images($ngo_id);
            $template_data = $this->Amazon_model->get_template_data($ngo_id);
            $member_profile_images = $this->Amazon_model->get_member_profile_images($ngo_id);
            $admin_profile_image = $this->Amazon_model->get_admin_profile_image($ngo_id);
            $k = 0;
            $bannerimagesarray = array();
            if(!empty($template_data))
            {   
                foreach ($template_data as $template) {
                    $array = json_decode($template->json, true);
                    $bannerimages = $array['bannerimage'];
                    if(!empty($bannerimages))
                    {
                        foreach ($bannerimages as $banner_image) {
                            $bannerimagesarray[$k] = $banner_image['src'];
                            $k++;
                        }
                    }
                }
            }
            $bannerimagesarray = array_unique($bannerimagesarray);

            $all_urls = array();
            $i=0;
            if(!empty($ngo_project_images))
            {
                foreach ($ngo_project_images as $ngo_project_image) {
                    $all_urls[$i] = $ngo_project_image->project_profile_images;
                    $i++;
                }
            }

            if(!empty($activity_images))
            {
                foreach ($activity_images as $activity_image) {
                    $all_urls[$i] = $activity_image->url;
                    $i++;
                }
            }

            if(!empty($donor_images))
            {
                foreach ($donor_images as $donor_image) {
                    $all_urls[$i] = $donor_image->image_url;
                    $i++;
                }
            }

            if(!empty($member_profile_images))
            {
                foreach ($member_profile_images as $member_profile_image) {
                    $all_urls[$i] = $member_profile_image->image_url;
                    $i++;
                }
            }

            if(!empty($admin_profile_image))
            {
                $all_urls[$i] = $admin_profile_image[0]->image_url;
                $i++;           
            }

            $all_url = array_merge($all_urls, $bannerimagesarray);
            $kraken = new Kraken($kraken_api_key, $kraken_api_secret);
            $this->config->load('s3', true);
            $logs = array();
            foreach ($all_url as $key => $url) {
                $path = "kraken-images/".str_replace($cloudFrontUrl, "", $url);
                $params = array(
                    "file" => $url,
                    "wait" => true,
                    "lossy" => true,
                    "s3_store" => array(
                        "key" => $this->config->item('access_key', 's3'),
                        "secret" => $this->config->item('secret_key', 's3'),
                        "bucket" => $this->config->item('bucket_name', 's3'),
                        "path" => $path
                    )
                );  

                $data = $kraken->upload($params);
                if (!empty($data["success"])) {

                    $logs[$url] = "Success. Optimized image URL: " . $data["kraked_url"];
                } elseif (isset($data["message"])) {

                    $logs[$url] = "Optimization failed. Error message from Kraken.io: " . $data["message"];
                } else {

                    $logs[$url] ="cURL request failed. Error message: " . $data["error"];
                }
            }
            $update_kraken_log['status'] = 'Done';
            $update_kraken_log['details'] = json_encode($logs);
            $this->Kraken_model->update_kraken_log($update_kraken_log, array('ngo_id'=>$ngo_id));
        } 
    }
}