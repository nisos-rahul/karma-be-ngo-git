<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 if(!function_exists('ngo_profile_details')){
    function ngo_profile_details($organisation_id,$status='')
    {
        
        $CI = get_instance();
        $CI->load->model('Hashtag_model');
        $CI->load->model('Social_media_sharing_model');
        $organisation = $CI->Ngo_model->organization_details($organisation_id,$status);
        $data['error'] = false;
        $organisation_id = $data['resp']['id'] = $organisation->id;
        $data['resp']['description'] = $organisation->description;
        $data['resp']['imageUrl'] = $organisation->image_url;
        //List ngo favicon neha
        $data['resp']['faviconUrl'] = $organisation->favicon_url;
        //video data
        $videos = $CI->Ngo_model->organization_videos($organisation_id);
        $video_data = array();
        if(!empty($videos))
        {
            $j = 0;
            foreach($videos as $video)
            {
                $video_data[$j]['id'] = $video->id;
                $video_data[$j]['url'] = $video->video_url;
                $video_data[$j]['caption'] = $video->caption;
                $video_data[$j]['thumbUrl'] = $video->thumb_url;
                $j++;
            }
            
        }
        $data['resp']['videoUrls'] = $video_data;
        $data['resp']['name'] = $organisation->name;
        $data['resp']['ngoType'] = $organisation->ngo_type;
        $ngo_types_array = ngo_types();
        $i=0;
        for($i=0;$i<count($ngo_types_array);$i++)
        {
            if($ngo_types_array[$i]['key']==$data['resp']['ngoType'])
            {
                $data['resp']['ngoTypeDisplay']=$ngo_types_array[$i]['name'];
            }
        }       
        
        $data['resp']['annualRevenue'] = $organisation->annual_revenue;
        if (ord($organisation->is_active)==1 || $organisation->is_active==1)
            $data['resp']['status'] = true;
        else
            $data['resp']['status'] = false;
        
        $data['resp']['tickerSymbol'] = $organisation->ticker_symbol;
        $data['resp']['totalNoOfBenefeciaries'] = $organisation->total_no_of_benefeciaries;
        $data['resp']['lastUpdated'] = $organisation->last_updated;
        $data['resp']['dateCreated'] = $organisation->date_created;
        $data['resp']['deletedAt'] = $organisation->deleted_at;
        $data['resp']['contactUs'] = $organisation->contact_us;
        $data['resp']['copyright'] = $organisation->copyright;
        $data['resp']['thumbUrl'] = $organisation->thumb_url;
        if (ord($organisation->is_deleted)==1 || $organisation->is_deleted==1)
            $data['resp']['isDeleted'] = true;
        else
            $data['resp']['isDeleted'] = false;
        if (ord($organisation->is_verified)==1 || $organisation->is_verified==1)
            $data['resp']['isVerified'] = true;
        else
            $data['resp']['isVerified'] = false;
        $data['resp']['address1'] = $organisation->addres_line1;
        $data['resp']['address2'] = $organisation->addres_line2;            
        $data['resp']['programsNetSpend'] = $organisation->programs_net_spend;          
        
        $city_id = $organisation->city_id;
        if(!empty($city_id))    
            $city_details = $CI->Country_model->city_info($city_id);
        else
            $data['resp']['city'] = ""; 
        if(!empty($city_details))
        {
            $data['resp']['city'] = $city_details->name;                
        }
        else
        {
            $data['resp']['city'] = "";             
        }
        
        $country_id = $organisation->country_id;
        if(!empty($country_id)) 
            $country_details = $CI->Country_model->country_info($country_id);
        else
        {
            $data['resp']['country'] = "";
            $data['resp']['countryCode'] = "";
        }   
        
        if(!empty($country_details))
        {
            $data['resp']['country'] = $country_details->name;
            $data['resp']['countryCode'] = $country_details->code;
        }
        else
        {
            $data['resp']['country'] = "";
            $data['resp']['countryCode'] = "";
        }
        
        $state_id = $organisation->state_id;
        if(!empty($state_id))           
            $state_details = $CI->Country_model->state_info($state_id);
        else
        {
            $data['resp']['state'] = "";                
        }
        if(!empty($state_details))
        {
            $data['resp']['state'] = $state_details->name;              
        }
        else
        {
            $data['resp']['state'] = "";                
        }

        $data['resp']['websiteUrl'] = $organisation->website_url;
        $data['resp']['brandingUrl'] = $organisation->branding_url;
        $donation_status = $organisation->donation_status;
        if($donation_status==1)
            $data['resp']['donationStatus'] = true;
        else
            $data['resp']['donationStatus'] = false;
        $data['resp']['donationUrl'] = $organisation->donation_url;
        $data['resp']['zip'] = $organisation->zip_code; 
        // GA CODE.
        $data['resp']['registrationNo'] = $organisation->registration_no;           
        //category list
        $category_list = $CI->Ngo_model->category_ngo($organisation_id);
        $category_data = array();
        if(!empty($category_list))
        {
            $k = 0;
            foreach($category_list as $category)
            {
                
                $cat_id = $category->categories_id;                 
                $category_info = $CI->Category_model->category_info($cat_id);
                if(!empty($category_info))
                {
                    $category_data[$k]['id'] = $category_info->id;
                    $category_data[$k]['category'] = $category_info->category;
                    $category_data[$k]['description'] = $category_info->description;
                    $category_data[$k]['subcategory'] = $category_info->subcategory;
                    $category_data[$k]['imageUrl'] = $category_info->image_url;
                    $k++;
                }
            }
        }
        $data['resp']['category'] = $category_data;
        
        //handle list -  twitter_handles
        $handle_list = $CI->Hashtag_model->get_organization_handles($organisation_id);
        $data['resp']['handles'] = $handle_list;
        //handle list -  facebook_handles
        $facebook_handles = $CI->Hashtag_model->get_organization_fb_handles($organisation_id);
        $data['resp']['facebookHandles'] = $facebook_handles;
        //document list
        $documents = $CI->Ngo_model->organization_docs($organisation_id);
        $data['resp']['documentUrls'] = $documents;


        $social_media_info = $CI->Social_media_sharing_model->get_ngo_status($organisation_id);
        if(!empty($social_media_info))
        {
            if($social_media_info->facebook==null || $social_media_info->facebook=='')
                $data['resp']['facebook_connect_status'] = false;   
            else
                $data['resp']['facebook_connect_status'] = true;

            if($social_media_info->twitter==null || $social_media_info->twitter=='')
                $data['resp']['twitter_connect_status'] = false;    
            else
                $data['resp']['twitter_connect_status'] = true;

            $ngo_social_data = $CI->Social_media_sharing_model->get_user_data(array("ngo_id"=>$organisation_id));
            $data['resp']['manualUnlink'] = (bool)$ngo_social_data->manual_unlink;
        }
        else
        {
            $data['resp']['facebook_connect_status'] = false;
            $data['resp']['twitter_connect_status'] = false;    
            $data['resp']['manualUnlink'] = false;
        }

        $CI->config->load('firstGiving', true);

        $use_first_giving_production_donation_url = $CI->config->item('use_first_giving_production_donation_url', 'firstGiving');
        if($use_first_giving_production_donation_url==true)
        {
            $first_giving_url = $CI->config->item('first_giving_donation_url_production', 'firstGiving');
            $first_giving_affiliate_id = $CI->config->item('first_giving_affiliate_id_production', 'firstGiving'); 
        }
        else
        {
            $first_giving_url = $CI->config->item('first_giving_donation_url_staging', 'firstGiving'); 
            $first_giving_affiliate_id = $CI->config->item('first_giving_affiliate_id_staging', 'firstGiving'); 
        }
        
        $first_giving_style_sheet_url = $CI->config->item('first_giving_style_sheet_url', 'firstGiving'); 
        $first_giving_uuid_no = $organisation->first_giving_uuid_no;
        $branding_url = $organisation->branding_url;
        $branding_url = $branding_url.'/confirmationpage.html';
        $pb_success = base64_encode($branding_url);
        $first_giving_style_sheet_url = base64_encode($first_giving_style_sheet_url);
        $url = null;

        if($first_giving_uuid_no!=null && $branding_url!=null)
        {
            $url = $first_giving_url.'/secure/payment/'.$first_giving_uuid_no.'?amount=amount_value&affiliate_id='.$first_giving_affiliate_id.'&styleSheetURL='.$first_giving_style_sheet_url.'&_pb_success='.$pb_success.'&buttonText=DONATE%20NOW';
        }
        $data['resp']['firstGivingUrl'] = $url;
        $data['resp']['useKarmaDonation'] = $organisation->use_karma_donation;
        if($data['resp']['useKarmaDonation']!=null)
            $data['resp']['useKarmaDonation'] = (bool)$organisation->use_karma_donation;

        return $data;
    }//ngo_profile_details
 }
if(!function_exists('ngo_types')){
    function ngo_types()
    {
        $ngo_type_array[0]['name'] = "International NPO";
        $ngo_type_array[0]['key'] = "InternationalNgo";
        $ngo_type_array[1]['name'] = "Community Based NPO";
        $ngo_type_array[1]['key'] = "CommunityNgo";
        $ngo_type_array[2]['name'] = "National NPO";
        $ngo_type_array[2]['key'] = "CountryNgo";
        $ngo_type_array[3]['name'] = "StateWide NPO";
        $ngo_type_array[3]['key'] = "StateNgo";
        $ngo_type_array[4]['name'] = "Local NPO";
        $ngo_type_array[4]['key'] = "CityNgo";
        $ngo_type_array[5]['name'] = "Regional NPO";
        $ngo_type_array[5]['key'] = "RegionNgo";
        return $ngo_type_array;
    }   
}
if(!function_exists('ngo_types_arr'))
{
    function ngo_types_arr()
    {
        $types = array('CommunityNgo','CityNgo','StateNgo','RegionNgo','CountryNgo','InternationalNgo');
        return $types;
    }
}
/* End of file ngo_helper.php */
/* Location: ./application/helpers/ngo_helper.php */