<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';
require APPPATH.'libraries/aws-autoloader.php';
require APPPATH.'libraries/kraken-php-share/lib/Kraken.php';

use Aws\S3\S3Client;

class Media extends Rest 
{
    public function __construct() {

        parent::__construct();
        $this->load->model('Media_model');
    }

    public function upload_optimized_media() 
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

        if(empty($_FILES['media']["tmp_name"]))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Unable to upload media.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $ext = pathinfo($_FILES['media']["name"], PATHINFO_EXTENSION);
        $image_data = file_get_contents($_FILES['media']["tmp_name"]);
        $image_tmp_name = $_FILES['media']["tmp_name"];
        $image_name = $_FILES['media']["name"];

        $this->load->library('s3');
        $this->config->load('s3', true);
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $this->config->item('aws_s3_region', 's3'),
            'credentials' => [
                'key'    => $this->config->item('access_key', 's3'),
                'secret' => $this->config->item('secret_key', 's3')
            ]
        ]);
        $bucket = $this->config->item('bucket_name', 's3');
        $region = $this->config->item('aws_s3_region', 's3');
        $data['error'] = false;

        $cloud_front_url = $this->config->item('cloudfront_domain_url');

        $mime = mime_content_type($_FILES['media']["tmp_name"]);
        if(strstr($mime, "image/"))
        {
            $kraken_api_status = $this->config->item('kraken_api_status');
            if($kraken_api_status===true)
            {
                $image_name = rand().$image_name;
                $saveTo = 'temp_media/'.$image_name;
                file_put_contents($saveTo, $image_data);
                $kraken_api_key = $this->config->item('kraken_api_key');
                $kraken_api_secret = $this->config->item('kraken_api_secret');

                $kraken = new Kraken($kraken_api_key, $kraken_api_secret);

                $params = array(
                    "file" => $saveTo,
                    "wait" => true,
                    "lossy" => true,
                    "s3_store" => array(
                        "key" => $this->config->item('access_key', 's3'),
                        "secret" => $this->config->item('secret_key', 's3'),
                        "bucket" => $this->config->item('bucket_name', 's3'),
                        "path" => $image_name,
                        "region" => $this->config->item('aws_s3_region', 's3'),
                        "headers" => array(
                            "Cache-Control" => "max-age=31536000"
                        )
                    )
                );

                $upload_data = $kraken->upload($params);
                unlink($saveTo);
                if (!empty($upload_data["success"])) {

                    $link = $s3_url = $upload_data["kraked_url"];
                    if (strpos($link, $region) !== false) 
                    {
                        if($cloud_front_url!='cloudfrontDomainUrl')
                            $link = str_replace('https://'.$bucket.'.s3-'.$region.'.amazonaws.com/',$cloud_front_url,$link);
                    }
                    else
                    {
                        if($cloud_front_url!='cloudfrontDomainUrl')
                            $link = str_replace('https://'.$bucket.'.s3.amazonaws.com/',$cloud_front_url,$link);
                    }
                    $data['mediapath'] = $link;
                } elseif (isset($upload_data["message"])) {

                    $data['error'] = true;
                    $data['message'] = "Optimization failed. Error message from Kraken.io: " . $upload_data["message"];
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    exit;
                } else {

                    $data['error'] = true;
                    $data['message'] = "cURL request failed. Error message: " . $upload_data["error"];
                    echo json_encode($data,JSON_NUMERIC_CHECK);
                    exit;
                }
            }
            else
            {
                $key = rand().$image_name;
                $res = $s3->putObject([
                    'Bucket' => $bucket,
                    'Key'    => $key,
                    'Body'   => $image_data,
                    'CacheControl' => 'max-age=31536000'
                ]);
                $link = $s3_url = 'https://'.$bucket.'.s3.amazonaws.com/'.$key;

                $cloud_front_url = $this->config->item('cloudfront_domain_url');
                if($cloud_front_url!='cloudfrontDomainUrl')
                    $link = str_replace('https://'.$bucket.'.s3.amazonaws.com/',$cloud_front_url,$link);

                $data['error'] = false;
                $data['mediapath'] = $link;
            }
        }
        elseif(strstr($mime, "video/"))
        {
            $key = rand().$image_name;
            $res = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'Body'   => $image_data,
                'CacheControl' => 'max-age=31536000'
            ]);
            $link = $s3_url = 'https://'.$bucket.'.s3.amazonaws.com/'.$key;

            if($cloud_front_url!='cloudfrontDomainUrl')
                $link = str_replace('https://'.$bucket.'.s3.amazonaws.com/',$cloud_front_url,$link);

            $data['mediapath'] = $link;

            $output = rand().'.png';
            echo exec("ffmpeg -i $image_tmp_name -ss 00:00:02 -f image2 temp_media/$output");

            $myfile = fopen('temp_media/'.$output, "r") or die("Unable to open file!");
            $filedata = fread($myfile,filesize('temp_media/'.$output));
            fclose($myfile);
            unlink('temp_media/'.$output);
            $res = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $output,
                'Body'   => $filedata,
                'CacheControl' => 'max-age=31536000'
            ]);
            $link2 = 'https://'.$bucket.'.s3.amazonaws.com/'.$output;

            if($cloud_front_url!='cloudfrontDomainUrl')
                $link2 = str_replace('https://'.$bucket.'.s3.amazonaws.com/',$cloud_front_url,$link2);

            $data['thumbnailpath'] = $link2;
        }
        else
        {
            $key = rand().$image_name;
            $res = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'Body'   => $image_data,
                'CacheControl' => 'max-age=31536000'
            ]);
            $link = $s3_url = 'https://'.$bucket.'.s3.amazonaws.com/'.$key;

            $cloud_front_url = $this->config->item('cloudfront_domain_url');
            if($cloud_front_url!='cloudfrontDomainUrl')
                $link = str_replace('https://'.$bucket.'.s3.amazonaws.com/',$cloud_front_url,$link);

            $data['error'] = false;
            $data['mediapath'] = $link;
            $data['medianame'] = $image_name;
        }

        $insert_log['user_id'] = $valid_auth_token->user_id;
        $insert_log['url'] = $s3_url;
        $insert_log['datetime'] = date('Y-m-d H:i:s');
        $this->Media_model->insert_upload_log($insert_log);

        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function upload_media() 
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

        if(empty($_FILES['media']["tmp_name"]))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Unable to upload media.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $ext = pathinfo($_FILES['media']["name"], PATHINFO_EXTENSION);
        $image_data = file_get_contents($_FILES['media']["tmp_name"]);
        $image_tmp_name = $_FILES['media']["tmp_name"];

        $this->load->library('s3');
        $this->config->load('s3', true);
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => $this->config->item('aws_s3_region', 's3'),
            'credentials' => [
                'key'    => $this->config->item('access_key', 's3'),
                'secret' => $this->config->item('secret_key', 's3')
            ]
        ]);
        $bucket = $this->config->item('bucket_name', 's3');
        $key = "media".rand().".".$ext;
        $res = $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'Body'   => $image_data,
            'CacheControl' => 'max-age=31536000'
        ]);
        $link = $s3_url = 'https://'.$bucket.'.s3.amazonaws.com/'.$key;

        $cloud_front_url = $this->config->item('cloudfront_domain_url');
        if($cloud_front_url!='cloudfrontDomainUrl')
            $link = str_replace('https://'.$bucket.'.s3.amazonaws.com/',$cloud_front_url,$link);

        $data['error'] = false;
        $data['mediapath'] = $link;

        $mime = mime_content_type($_FILES['media']["tmp_name"]);
        if(strstr($mime, "video/"))
        {
            $output = rand().'.png';
            echo exec("ffmpeg -i $image_tmp_name -ss 00:00:02 -f image2 temp_media/$output");

            $myfile = fopen('temp_media/'.$output, "r") or die("Unable to open file!");
            $filedata = fread($myfile,filesize('temp_media/'.$output));
            fclose($myfile);
            unlink('temp_media/'.$output);
            $res = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $output,
                'Body'   => $filedata,
                'CacheControl' => 'max-age=31536000'
            ]);
            $link2 = 'https://'.$bucket.'.s3.amazonaws.com/'.$output;

            if($cloud_front_url!='cloudfrontDomainUrl')
                $link2 = str_replace('https://'.$bucket.'.s3.amazonaws.com/',$cloud_front_url,$link2);

            $data['thumbnailpath'] = $link2;
        }

        $insert_log['user_id'] = $valid_auth_token->user_id;
        $insert_log['url'] = $s3_url;
        $insert_log['datetime'] = date('Y-m-d H:i:s');
        $this->Media_model->insert_upload_log($insert_log);

        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }
}