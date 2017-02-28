<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tweets_model extends CI_Model 
{
    public function ngo_tweets($where,$offset,$limit)
    {
        $this->db->select('tweets.*');
        $this->db->from('tweets');
        $this->db->join('twitter_handles','tweets.twitter_handles_id = twitter_handles.id');
        $this->db->where($where);
        $this->db->order_by('tweets.date_created','desc');
        $this->db->limit($limit,$offset);
        $result = $this->db->get();
        return $result->result();
    }
    public function ngo_tweets_count($where)
    {
        $this->db->select('count(tweets.id) as num');
        $this->db->from('tweets');
        $this->db->join('twitter_handles','tweets.twitter_handles_id = twitter_handles.id');
        $this->db->where($where);
        $result = $this->db->get();
        return $result->row();
    }

    public function ngo_tweets_with_highlights($offset,$limit,$ngo_id)
    {
        $query = "SELECT `tweets`.`id`, `tweets`.`tweet_created_at`, `tweets`.`last_updated`, 
            `tweets`.`name` as tweet_or_project_name, `tweets`.`tweet_text` as tweet_text_or_update_name, 
            `tweets`.`expanded_urls` as expanded_urls_or_image_url, 
            `tweets`.`user_screen_name` as user_sceen_name_or_project_id, 'tweet' as type
        FROM (`tweets`)
        JOIN `twitter_handles` ON `tweets`.`twitter_handles_id` = `twitter_handles`.`id`
        WHERE `twitter_handles`.`organisation_id` =  $ngo_id
        AND `twitter_handles`.`is_active` =  1
        AND `twitter_handles`.`is_deleted` =  0
        AND `tweets`.`is_deleted` =  0
        AND `tweets`.`tweet_status` =  'Approved' 
        UNION 
        SELECT `project_report`.`id`, `project_report`.`date_created`, `project_report`.`last_updated`, `project`.`title`, `project_report`.`report`, `project`.`image_url`, `project`.`id`, 'activity' as type
        FROM (`project_report`)
        JOIN `project` ON `project_report`.`project_id` = `project`.`id`
        WHERE `project_report`.`project_report_type` =  'Project Highlight'
        AND `project_report`.`is_deleted` =  0
        AND `project`.`ngo_id` =  $ngo_id 
        AND `project`.`is_active` = 1
        order by tweet_created_at desc limit $offset,$limit";

        $res = $this->db->query($query);
        return $res->result();
    }
}//end of class
/* End of file Tweets_model.php */
/* Location: ./application/models/Tweets_model.php */