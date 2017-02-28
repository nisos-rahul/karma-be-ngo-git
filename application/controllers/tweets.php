<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class Tweets extends Rest 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Tweets_model');
    }
    public function ngo_tweets($id)
    {
        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset = ($page-1)*$limit; 
        $organisation = $this->Ngo_model->organization_details($id);
        if(empty($organisation))
        {
            $data['error'] = true;
            $data['status'] = 404;
            header('HTTP/1.1 404 Not Found');
            $data['message'] = "Organisation was not found.";
        }           
        $where = array('twitter_handles.organisation_id' => $id, 
            'twitter_handles.is_active' => true,
            'twitter_handles.is_deleted' => false,
            'tweets.is_deleted' => false,
            'tweets.tweet_status'=>'Approved'
            );
        $data['error'] = false;
        $data['resp']['count'] = $this->Tweets_model->ngo_tweets_count($where)->num;
        $data['resp']['tweets'] = $this->Tweets_model->ngo_tweets_with_highlights($offset, $limit, $id);
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }
    
}//end tweets

/* End of file tweets.php */
/* Location: ./application/controllers/tweets.php */