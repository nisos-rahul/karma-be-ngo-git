<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class DesignPage extends Rest 
{
    public function __construct() {

        parent::__construct();
        $this->load->model('Design_page_model');
        $this->load->model('Audit_model');
    }

    // public function upload_media() 
    // {   
    //     if(empty($_FILES['media']["tmp_name"]))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Unable to upload media.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }

    //     $ext = pathinfo($_FILES['media']["name"], PATHINFO_EXTENSION);
    //     $image_data = file_get_contents($_FILES['media']["tmp_name"]);
    //     $image_tmp_name = $_FILES['media']["tmp_name"];

    //     $this->load->library('s3');
    //     $this->config->load('s3', true);
    //     $s3 = new Aws\S3\S3Client([
    //         'version' => 'latest',
    //         'region'  => $this->config->item('aws_s3_region', 's3'),
    //         'credentials' => [
    //             'key'    => $this->config->item('access_key', 's3'),
    //             'secret' => $this->config->item('secret_key', 's3')
    //         ]
    //     ]);

    //     $bucket = $this->config->item('bucket_name', 's3');

    //     $key = "media".rand().".".$ext;
    //     $res = $s3->putObject([
    //         'Bucket' => $bucket,
    //         'Key'    => $key,
    //         'Body'   => $image_data
    //     ]);
    //     $link = 'https://'.$bucket.'.s3.amazonaws.com/'.$key;

    //     $cloud_front_url = $this->config->item('cloudfront_domain_url');
    //     $cloud_front_final_url = str_replace('https://'.$bucket.'.s3.amazonaws.com/',$cloud_front_url,$link);


    //     $data['error'] = false;
    //     $data['mediapath'] = $cloud_front_final_url;

    //     $mime = mime_content_type($_FILES['media']["tmp_name"]);
    //     if(strstr($mime, "video/"))
    //     {
    //         $output = rand().'.png';
    //         echo exec("ffmpeg -i $image_tmp_name -ss 00:00:02 -f image2 temp_media/$output");

    //         $myfile = fopen('temp_media/'.$output, "r") or die("Unable to open file!");
    //         $filedata = fread($myfile,filesize('temp_media/'.$output));
    //         fclose($myfile);
    //         unlink('temp_media/'.$output);
    //         $res = $s3->putObject([
    //             'Bucket' => $bucket,
    //             'Key'    => $output,
    //             'Body'   => $filedata
    //         ]);
    //         $link2 = 'https://'.$bucket.'.s3.amazonaws.com/'.$output;

    //         $cloud_front_final_thumbnail_url = str_replace('https://'.$bucket.'.s3.amazonaws.com/',$cloud_front_url,$link2);

    //         $data['thumbnailpath'] = $cloud_front_final_thumbnail_url;
    //     }

    //     echo json_encode($data,JSON_NUMERIC_CHECK);
    //     return;
    // }

    public function save_template() {

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
        $user_id = $valid_auth_token->user_id;
        if($role_id==7)
            $ngo_id = $this->input->get('ngoId');
        else
            $ngo_id = login_ngo_details($auth_token);

        $jsonArray = json_decode(file_get_contents('php://input'), true);
        // $ngo_id = isset($jsonArray['ngoId'])?$jsonArray['ngoId']:'';
        // $user_id = isset($jsonArray['user_id'])?$jsonArray['user_id']:'';

        $ngo_data = $this->Audit_model->org_info($ngo_id);
        $branding_url = $ngo_data->branding_url;
        if($branding_url=='' ||$branding_url==null)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please set branding url.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }

        $version = isset($jsonArray['version'])?$jsonArray['version']:'';
        if(!is_int($version))
        {
            $insert['ngo_id'] = $ngo_id;
            $json = $insert['json'] = isset($jsonArray['json'])?$jsonArray['json']:'';
            $is_published = $insert['ispublished'] = isset($jsonArray['status'])?$jsonArray['status']:'';
            $insert['template_name'] = isset($jsonArray['template_name'])?$jsonArray['template_name']:'';
            $insert['created_at'] = date('Y-m-d H:i:s');
            $insert['updated_at'] = date('Y-m-d H:i:s');
            $last_version = $this->Design_page_model->get_last_version($ngo_id);
            if(!empty($last_version))
                $insert['version'] = $version = $last_version->version+1;
            else
                $insert['version'] = $version = 0;
            $id = $this->Design_page_model->add_template($insert);
            $audit_info['action'] = 'created';
        }
        else
        {
            $update['ngo_id'] = $ngo_id;
            $update['json'] = isset($jsonArray['json'])?$jsonArray['json']:'';
            $is_published = isset($jsonArray['status'])?$jsonArray['status']:'';
            $update['updated_at'] = date('Y-m-d H:i:s');

            $check = $this->Design_page_model->check_version($ngo_id, $version);
            if(empty($check)) {
                $data['error'] = true;
                $data['status'] = 400;
                $data['message'] = "Version Not Found.";
                header('HTTP/1.1 400 Validation Error.');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
            else 
            {
                $id = $check->id;
                $this->Design_page_model->update_template($update, $id);
            }
            $audit_info['action'] = 'updated';
        }

        if($is_published==false)
        {
            $user_data = $this->Audit_model->user_info((int)$user_id);
            $audit_info['user_id'] = $user_id;
            $audit_info['role_id'] = (int)$user_data->role_id;
            $audit_info['org_id'] = $ngo_id;
            $audit_info['entity'] = 'Design Page';
            $audit_info['entity_id'] = $version;
            $this->Audit_model->save_design_page($audit_info);
        }

        $data = $this->template_data($ngo_id, $version);
        exit;
    } 

    public function publish_template() {

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
        $user_id = $valid_auth_token->user_id;
        if($role_id==7)
            $ngo_id = $this->input->get('ngoId');
        else
            $ngo_id = login_ngo_details($auth_token);

        $jsonArray = json_decode(file_get_contents('php://input'), true);
        // $ngo_id = isset($jsonArray['ngoId'])?$jsonArray['ngoId']:'';
        // $user_id = isset($jsonArray['user_id'])?$jsonArray['user_id']:'';

        $ngo_data = $this->Audit_model->org_info($ngo_id);
        $branding_url = $ngo_data->branding_url;
        if($branding_url=='' ||$branding_url==null)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Please set branding url.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }

        $json = $update['json'] = isset($jsonArray['json'])?$jsonArray['json']:'';
        $is_published = $update['ispublished'] = isset($jsonArray['status'])?$jsonArray['status']:'';
        $version = isset($jsonArray['version'])?$jsonArray['version']:'';
        $update['updated_at'] = date('Y-m-d H:i:s');

        $check = $this->Design_page_model->check_version($ngo_id, $version);
        if(empty($check)) 
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Version Not Found.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        else 
        {       
            $id = $check->id;
            $this->Design_page_model->set_is_published_false($ngo_id);
            $this->Design_page_model->update_template($update, $id);
        }

        $data = $this->template_data($ngo_id, $version);
        return;
    }

    public function template_data($ngo_id='',$version='')
    {
        if($version!=null && $version!='null')
            $version=(int)$version;
        if(!is_int($version))
            $template_data = $this->Design_page_model->get_active_version($ngo_id);
        else
            $template_data = $this->Design_page_model->get_details($ngo_id, $version);
        if(empty($template_data))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Operation failed to find version using id mentioned.";  
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }

        $data['error'] = false;
        $data['resp']['id'] = $template_data->id;
        $data['resp']['ngo_id'] = $template_data->ngo_id;
        $data['resp']['json'] = $template_data->json;
        $data['resp']['template_name'] = $template_data->template_name;
        $data['resp']['version'] = $template_data->version;
        $data['resp']['status'] = (bool)$template_data->ispublished;
        $data['resp']['created_at'] = $template_data->created_at;
        $data['resp']['updated_at'] = $template_data->updated_at;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function template_list()
    {
        $ngo_id = ($this->input->get('ngoId'))?$this->input->get('ngoId'):'';
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;   

        $template_list = $this->Design_page_model->all_template_data($ngo_id,$offset,$limit);
        $template_data = array();
        if(!empty($template_list))
        {
            $i=0;
            foreach ($template_list as $template) {
                $template_data[$i]['id'] = $template->id;
                $template_data[$i]['ngo_id'] = $template->ngo_id;
                $template_data[$i]['json'] = $template->json;
                $template_data[$i]['template_name'] = $template->template_name;
                $template_data[$i]['version'] = $template->version;
                $template_data[$i]['status'] = (bool)$template->ispublished;
                $template_data[$i]['created_at'] = $template->created_at;
                $template_data[$i]['updated_at'] = $template->updated_at;
                $i++;
            }
        }

        $count = $this->Design_page_model->all_template_count($ngo_id);
        $data['error'] = false;
        $data['resp']['count'] = $count->num;
        $data['resp']['template'] = $template_data;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }
}
?>