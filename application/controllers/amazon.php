<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';
// require APPPATH.'libraries/aws-autoloader.php';

class Amazon extends Rest 
{   
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Audit_model');
        $this->load->model('Amazon_model');

        // if($this->input->server('HTTP_X_AUTH_TOKEN'))
        // {
        //  $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');        
        //  $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);

        //  if(empty($valid_auth_token))
        //  {
        //      $data['error'] = true;
        //      $data['status'] = 401;
        //      $data['message'] = "Unauthorized User.";
        //      header('HTTP/1.1 401 Unauthorized User');
        //      echo json_encode($data,JSON_NUMERIC_CHECK);
        //      exit;
        //  }

        //  $role_id = $valid_auth_token->role_id;
        //  if($role_id!=1)
        //  {
        //      $data['error'] = true;
        //      $data['status'] = 401;
        //      $data['message'] = "Unauthorized User.";
        //      header('HTTP/1.1 401 Unauthorized User');
        //      echo json_encode($data,JSON_NUMERIC_CHECK);
        //      exit;
        //  }
        // }
    }

    public function move_archive_ngo_media($ngo_id) 
    {
        ignore_user_abort(true);
        set_time_limit(0);
        ob_start();  
        usleep(1500);       
        $data['error'] = false;
        $data['status'] = 200;
        $data['message'] = "Archive is under process.";
        echo json_encode($data,JSON_NUMERIC_CHECK);
        $size = ob_get_length(); 
        header("Content-Length: $size"); 
        header('Connection: close'); 
        ob_end_flush(); 
        ob_flush(); 
        flush();

        $ngo_profile_images = $this->Amazon_model->ngo_profile_image($ngo_id);
        $ngo_profile_icon = $this->Amazon_model->ngo_profile_icon($ngo_id);
        $ngo_project_images = $this->Amazon_model->project_profile_images($ngo_id);
        $admin_profile_image = $this->Amazon_model->get_admin_profile_image($ngo_id);
        $member_profile_images = $this->Amazon_model->get_member_profile_images($ngo_id);
        $activity_images = $this->Amazon_model->get_activity_images($ngo_id);
        $activity_videos = $this->Amazon_model->get_activity_videos($ngo_id);

        $all_urls = array();
        $i=0;
        if(!empty($ngo_profile_images))
        {
            $all_urls[$i] = $ngo_profile_images->ngo_logo;
            $i++;
        }

        if(!empty($ngo_profile_icon))
        {
            $all_urls[$i] = $ngo_profile_icon->ngo_icon;
            $i++;
        }

        if(!empty($ngo_project_images))
        {
            foreach ($ngo_project_images as $ngo_project_image) {
                $all_urls[$i] = $ngo_project_image->project_profile_images;
                $i++;
            }
        }

        if(!empty($admin_profile_image))
        {
            $all_urls[$i] = $admin_profile_image[0]->image_url;
            $i++;           
        }
        
        if(!empty($member_profile_images))
        {
            foreach ($member_profile_images as $member_profile_image) {
                $all_urls[$i] = $member_profile_image->image_url;
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

        if(!empty($activity_videos))
        {
            foreach ($activity_videos as $activity_video) {
                $all_urls[$i] = $activity_video->url;
                $i++;
            }
        }

        $this->load->library('s3');
        $this->config->load('s3', true);
        $bucket = $this->config->item('bucket_name', 's3');

        foreach ($all_urls as $key => $all_url) {
            
            $url = parse_url($all_url);
            $url = @substr_replace($url['path'],'',0,1);

            $target_uri = 'archived/'.$ngo_id.'/'.$url;
            if (@S3::copyObject($bucket, $url, $bucket, $target_uri, S3::ACL_PUBLIC_READ))
                echo "Successfully Copied.<br>";
            else 
                echo "Failed<br>";

            if (@S3::deleteObject($bucket, $url)) 
                echo "Successfully Deleted.<br>";
            else 
                echo "error<br>";
        }
    }

    public function unarchive_ngo_media($ngo_id) 
    {
        ignore_user_abort(true);    
        set_time_limit(0);      
        ob_start();  
        usleep(1500);
        $data['error'] = false;
        $data['status'] = 200;
        $data['message'] = "Unarchive is under process.";
        echo json_encode($data,JSON_NUMERIC_CHECK);
        $size = ob_get_length(); 
        header("Content-Length: $size"); 
        header('Connection: close'); 
        ob_end_flush(); 
        ob_flush(); 
        flush();

        $this->config->load('s3', true);
        $bucket = $this->config->item('bucket_name', 's3');

        $this->load->library('s3');
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $this->config->item('aws_s3_region', 's3'),
             'credentials' => [
                    'key'    => $this->config->item('access_key', 's3'),
                    'secret' => $this->config->item('secret_key', 's3')
            ]
        ]);

        $folder_link = 'archived/'.$ngo_id.'/';
        $objects = $s3->getIterator('ListObjects', array(
            "Bucket" => $bucket,
            "Prefix" => $folder_link
        ));

        foreach ($objects as $object) {
            $url = $object['Key'];
            $target_uri = @str_replace($folder_link, '', $url);

            if (@S3::copyObject($bucket, $url, $bucket, $target_uri, S3::ACL_PUBLIC_READ))
            {
                if (@S3::deleteObject($bucket, $url)) 
                    echo "Successfully Deleted.<br>";
                else 
                    echo "error<br>";
                echo "Successfully Copied.<br>";
            }
            else 
                echo "Failed<br>";

            
        }
    }

    public function delete_ngo_media($ngo_id) 
    {
        ignore_user_abort(true); 
        set_time_limit(0); 
        ob_start();  
        usleep(1500);
        $data['error'] = false;
        $data['status'] = 200;
        $data['message'] = "Delete is under process.";
        echo json_encode($data,JSON_NUMERIC_CHECK);
        $size = ob_get_length(); 
        header("Content-Length: $size"); 
        header('Connection: close'); 
        ob_end_flush(); 
        ob_flush(); 
        flush();

        $this->config->load('s3', true);
        $bucket = $this->config->item('bucket_name', 's3');

        $this->load->library('s3');     
        $s3 = new Aws\S3\S3Client([
            // 'version' => 'latest',
            // 'region'  => 'us-east-1',
            'region'  => $this->config->item('aws_s3_region', 's3'),
            'credentials' => [
                    'key'    => $this->config->item('access_key', 's3'),
                    'secret' => $this->config->item('secret_key', 's3')
            ]
        ]);

        $folder_link = 'archived/'.$ngo_id.'/';
        $objects = $s3->getIterator('ListObjects', array(
            "Bucket" => $bucket,
            "Prefix" => $folder_link
        ));

        foreach ($objects as $object) {
            $url = $object['Key'];
            $target_uri = @str_replace('archived', 'deleted', $url);

            if (@S3::copyObject($bucket, $url, $bucket, $target_uri, S3::ACL_PUBLIC_READ))
            {
                echo "Successfully Copied.<br>";
                if (@S3::deleteObject($bucket, $url)) 
                    echo "Successfully Deleted.<br>";
                else 
                    echo "error<br>";
            }
            else 
                echo "Failed<br>";
        }
    }

    function check_bucket() 
    {
        $this->load->library('s3');
        $this->config->load('s3', true);
        $bucket = $this->config->item('bucket_name', 's3');
        if (($contents = S3::getBucket($bucket)) !== false) {
            foreach ($contents as $object) {
                var_dump($object);
            }
        }
    }

    public function put_object() {
        
        $this->load->library('s3');

        $bucket = '';
        $file = "";
        $uri = $file;

        $input = S3::inputFile($file);

        if (S3::putObject($input, $bucket, $uri, S3::ACL_PUBLIC_READ)) {
            echo "File uploaded.";
        } else {
            echo "Failed to upload file.";
        }
    }
}