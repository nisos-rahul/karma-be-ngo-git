<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';
// require 'vendor/autoload.php';

class FirstGiving extends Rest
{
    public function __construct()
    {
        parent::__construct();
        $this->config->load('firstGiving', true);
        $this->config->load('sendgrid', true);
        $this->load->model('Firstgiving_model');
        $this->load->model('Audit_model');
    }

    public function refresh_transaction_list()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id!=1) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        
        $array = json_decode(file_get_contents('php://input'),true); 

        $use_first_giving_production_api = $this->config->item('use_first_giving_production_api', 'firstGiving');
        if($use_first_giving_production_api==true)
        {
            $first_giving_url = $this->config->item('first_giving_api_url_production', 'firstGiving');
            $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_production', 'firstGiving');
            $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_production', 'firstGiving');
        }
        else
        {
            $first_giving_url = $this->config->item('first_giving_api_url_staging', 'firstGiving');
            $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_staging', 'firstGiving');
            $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_staging', 'firstGiving');
        }

        foreach ($array as $key => $value) 
        { 
            $transactionId = $value['val'];
            try {
                $url = $first_giving_url."transaction/detail?transactionId=$transactionId"; 
                $ch = curl_init();
                $ret = curl_setopt($ch, CURLOPT_URL, $url);
                $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'JG_APPLICATIONKEY: ' . $JG_APPLICATIONKEY,
                    'JG_SECURITYTOKEN: ' . $JG_SECURITYTOKEN
                ));
                $ret = curl_exec($ch);
                $info = curl_getinfo($ch);
            }
            catch(Exception $e)
            {
                header('HTTP/1.1 500 Internal Server Error');
                return;
            }
            if($info['http_code']==200)
            {
                $ret = json_decode(json_encode(simplexml_load_string($ret)), true);
                $transaction_live_data = $ret['firstGivingResponse']['transaction'];

                $update['status'] = $transaction_live_data['status'];

                $this->Firstgiving_model->update_donation_transaction_by_transaction_id($update, array('transaction_id' => $transactionId));
            }  
        }

        $data['error'] = false;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function refresh_transaction()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id!=1) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        // $array = json_decode(file_get_contents('php://input'),true); 
        $transactionId = $this->input->get('transactionId');

        $use_first_giving_production_api = $this->config->item('use_first_giving_production_api', 'firstGiving');
        if($use_first_giving_production_api==true)
        {
            $first_giving_url = $this->config->item('first_giving_api_url_production', 'firstGiving');
            $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_production', 'firstGiving');
            $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_production', 'firstGiving');
        }
        else
        {
            $first_giving_url = $this->config->item('first_giving_api_url_staging', 'firstGiving');
            $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_staging', 'firstGiving');
            $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_staging', 'firstGiving');
        }

        try {
            $url = $first_giving_url."transaction/detail?transactionId=$transactionId"; 
            $ch = curl_init();
            $ret = curl_setopt($ch, CURLOPT_URL, $url);
            $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'JG_APPLICATIONKEY: ' . $JG_APPLICATIONKEY,
                'JG_SECURITYTOKEN: ' . $JG_SECURITYTOKEN
            ));
            $ret = curl_exec($ch);
            $info = curl_getinfo($ch);
        }
        catch(Exception $e)
        {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        $transaction_live_data = null;
        if($info['http_code']==200)
        {
            $ret = json_decode(json_encode(simplexml_load_string($ret)), true);
            $transaction_live_data = $ret['firstGivingResponse']['transaction'];

            $update['status'] = $transaction_live_data['status'];

            $this->Firstgiving_model->update_donation_transaction_by_transaction_id($update, array('transaction_id' => $transactionId));
        }  
        
        $data['error'] = false;
        $data['resp'] = $transaction_live_data;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function list_transactions()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id!=1) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        // $limit = 10;
        $offset=($page-1)*$limit;
        $refund_status = ($this->input->get('refundStatus'))?$this->input->get('refundStatus'):'';
        $ngo_id = ($this->input->get('ngoId'))?$this->input->get('ngoId'):'';
        $amount = ($this->input->get('amount'))?$this->input->get('amount'):'';
        $date = ($this->input->get('date'))?$this->input->get('date'):'';
        $search = ($this->input->get('search'))?$this->input->get('search'):'';

        $list = $this->Firstgiving_model->get_donation_transactions_list($offset, $limit, $refund_status, $ngo_id, $amount, $date, $search);
        $count = $this->Firstgiving_model->get_donation_transactions_count($refund_status, $ngo_id, $amount, $date, $search);
        $i=0;
        $transaction_data = array();
        if(!empty($list))
        {
            foreach($list as $transaction)
            {
                $transaction_data[$i] = $this->transaction_details($transaction->id);
                $i++;
            }
        }

        $data['error'] = false;
        $data['resp']['count'] = $count->num;
        $data['resp']['transactions'] = $transaction_data;
        echo json_encode($data, JSON_NUMERIC_CHECK);
        return;
    }

    public function transaction_details($id='')
    {
        if($id=='')
            return;

        $where = array('donor_transactions.id'=>$id);
        $transaction_data = $this->Firstgiving_model->get_donation_transaction($where);
        // var_dump($transaction_data);
        // die;
        $transaction_details['id'] = $transaction_data->id;
        $transaction_details['ngoName'] = $transaction_data->ngo_name;
        $transaction_details['paymentGateway'] = $transaction_data->payment_gateway;
        $transaction_details['transactionId'] = $transactionId = $transaction_data->transaction_id;
        $transaction_details['transactionDatetime'] = $transaction_data->transaction_datetime;
        $transaction_details['paymentGatewayOrganizationName'] = $transaction_data->payment_gateway_organization_name;
        $transaction_details['paymentGatewayOrganizationId'] = $transaction_data->payment_gateway_organization_id;
        $transaction_details['paymentGatewayAttribution'] = $transaction_data->payment_gateway_attribution;
        $transaction_details['amount'] = $transaction_data->amount;
        $transaction_details['donorName'] = $transaction_data->donor_name;
        $transaction_details['donorEmail'] = $transaction_data->donor_email;
        $transaction_details['donorAddress'] = $transaction_data->donor_address;
        $transaction_details['donorCity'] = $transaction_data->donor_city;
        $transaction_details['donorState'] = $transaction_data->donor_state;
        $transaction_details['donorZip'] = $transaction_data->donor_zip;
        $transaction_details['donorCountry'] = $transaction_data->donor_country;
        $transaction_details['paymentGatewayDonationId'] = $transaction_data->payment_gateway_donation_id;
        $transaction_details['paymentGatewayCampaignName'] = $transaction_data->payment_gateway_campaign_name;
        $transaction_details['paymentGatewayPledgeId'] = $transaction_data->payment_gateway_pledge_id;
        $transaction_details['creditCardType'] = $transaction_data->credit_card_type;
        $transaction_details['currencyCode'] = $transaction_data->currency_code;
        $transaction_details['refundStaus'] = (bool)$transaction_data->refund_status;
        $transaction_details['status'] = $transaction_data->status;
        $transaction_details['isRecurring'] = (bool)$transaction_data->is_recurring;
        $transaction_details['recurringBillingFrequency'] = $transaction_data->recurring_billing_frequency;
        $transaction_details['recurringBillingTerm'] = $transaction_data->recurring_billing_term;
        
        return $transaction_details;
    }

    public function get_ngo_donation_status()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        $ngo_id = login_ngo_details($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $ngo_data = $this->Ngo_model->organization_details($ngo_id, 'any');
        if(empty($ngo_data))
        {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        if($ngo_data->use_karma_donation!=null)
            $ngo_details['useKarmaDonation'] = (bool)$ngo_data->use_karma_donation;
        else
            $ngo_details['useKarmaDonation'] = $ngo_data->use_karma_donation;

        $ngo_details['donationStatus'] = (bool)$ngo_data->donation_status;
        $ngo_details['donationUrl'] = $ngo_data->donation_url;
        $ngo_details['firstGivingUuidNo'] = $ngo_data->first_giving_uuid_no;

        $ngo_details['einNo'] = $ngo_data->ein_no;
        $ngo_details['firstGivingRegisteredName'] = $ngo_data->first_giving_registered_name;

        $application_data = $this->Firstgiving_model->get_application_by_ngo($ngo_id);
        if(!empty($application_data))
        {
            $application_details = $this->get_application_details($application_data->id);
            $ngo_details['application'] = $application_details['resp'];
        }
        else
            $ngo_details['application'] = [];

        if($ngo_data->use_karma_donation==null)
        {
            $donationConditionId = 1;
            $donationCondition = 'Setup not done';
        }
        elseif($ngo_data->use_karma_donation==0)
        {
            $donationConditionId = 3;
            $donationCondition = 'Not uses karma donation';
        }
        elseif($ngo_data->use_karma_donation==1)
        {
            if($ngo_data->first_giving_uuid_no!=null)
            {
                $donationConditionId = 2;
                $donationCondition = 'Karma donation setup done';
            }
            elseif(!empty($application_data))
            {
                if($application_data->status==3)
                {
                    $donationConditionId = 4;
                    $donationCondition = 'Karma donation application submit and approved';
                }
                else
                {
                    $donationConditionId = 5;
                    $donationCondition = 'Karma donation application submit but not approved';
                }
            }
        }

        $ngo_details['donationConditionId'] = $donationConditionId;
        $ngo_details['donationCondition'] = $donationCondition;

        $data['error'] = false;
        $data['resp'] = $ngo_details;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function delete_selected_firstgiving_entity()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        $ngo_id = login_ngo_details($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $role_id = $valid_auth_token->role_id;
        if($valid_auth_token->role_id==5) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;  
        }
        $ngo_data = $this->Ngo_model->organization_details($ngo_id, 'any');
        if(empty($ngo_data))
        {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $update['use_karma_donation'] = NULL;
        if((bool)$ngo_data->donation_status==true)
            $update['use_karma_donation'] = 0;
        $update['first_giving_uuid_no'] = NULL;
        $update['ein_no'] = NULL;
        $update['first_giving_registered_name'] = NULL;

        //audit donation setup
        $old_data['useKarmaDonation'] = $ngo_data->use_karma_donation;
        if($old_data['useKarmaDonation']!=NULL)
            $old_data['useKarmaDonation'] = (bool)$ngo_data->use_karma_donation;
        $old_data['firstGivingRegisteredName'] = $ngo_data->first_giving_registered_name;

        $new_data['useKarmaDonation'] = NULL;
        $new_data['firstGivingRegisteredName'] = NULL;

        $audit_info['user_id'] = $valid_auth_token->user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'donation setup';
        $audit_info['entity_id'] = $ngo_id;
        $audit_info['action'] = 'updated';
        $audit_id = $this->Audit_model->update_audit($old_data, $new_data, $audit_info);

        $this->Ngo_model->update_ngo($update, $ngo_id);

        if($audit_id!='false')
            $this->Audit_model->activate_audit($audit_id);
        //audit donation setup

        $application_details = $this->Firstgiving_model->get_application_by_ngo($ngo_id);
        if(!empty($application_details))
        {
            if($application_details->doc_url=='' || $application_details->doc_url==NULL)
            {
                $application_update['is_active'] = 0;
                $this->Firstgiving_model->update_application($application_update, $application_details->id);
            }
        }

        $data['error'] = false;
        $data['resp'] = 'Successfully deleted.';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;

    }

    public function update_ngo_donation_status()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        $ngo_id = login_ngo_details($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $role_id = $valid_auth_token->role_id;
        if($valid_auth_token->role_id==5) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;  
        }

        $ngo_data = $this->Ngo_model->organization_details($ngo_id, 'any');
        if(empty($ngo_data))
        {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $jsonArray = json_decode(file_get_contents('php://input'),true);   
        $update['use_karma_donation'] = isset($jsonArray['useKarmaDonation'])?$jsonArray['useKarmaDonation']:'';  
        $update['last_updated'] = date('Y-m-d H:i:s');
        $update['donation_status'] = isset($jsonArray['donationStatus'])?$jsonArray['donationStatus']:'';
        $update['donation_url'] = isset($jsonArray['donationUrl'])?$jsonArray['donationUrl']:'';
        $update['first_giving_uuid_no'] = isset($jsonArray['firstGivingUuidNo'])?$jsonArray['firstGivingUuidNo']:'';
        $update['ein_no'] = isset($jsonArray['einNo'])?$jsonArray['einNo']:'';
        $update['first_giving_registered_name'] = isset($jsonArray['firstGivingRegisteredName'])?$jsonArray['firstGivingRegisteredName']:'';
        
        //audit donation setup
        $old_data['useKarmaDonation'] = (bool)$ngo_data->use_karma_donation;
        $old_data['donationStatus'] = (bool)$ngo_data->donation_status;
        $old_data['donationUrl'] = $ngo_data->donation_url;
        $old_data['firstGivingRegisteredName'] = $ngo_data->first_giving_registered_name;

        $audit_info['user_id'] = $valid_auth_token->user_id;
        $audit_info['role_id'] = $role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'donation setup';
        $audit_info['entity_id'] = $ngo_id;
        $audit_info['action'] = 'updated';
        $audit_id = $this->Audit_model->update_audit($old_data, $jsonArray, $audit_info);

        $this->Ngo_model->update_ngo($update, $ngo_id);

        if($audit_id!='false')
            $this->Audit_model->activate_audit($audit_id);
        //audit donation setup

        if($update['first_giving_uuid_no']!='')
        {
            $application_details = $this->Firstgiving_model->get_application_by_ngo($ngo_id);
            if(empty($application_details))
            {
                $insert_application['name'] = $update['first_giving_registered_name'];
                $insert_application['ein_no'] = $update['ein_no'];
                $insert_application['doc_url'] = '';
                $insert_application['doc_name'] = '';
                $insert_application['user_id'] = $valid_auth_token->user_id;
                $insert_application['ngo_id'] = $ngo_id;
                $insert_application['status'] = 3;
                $insert_application['created_at'] = date('Y-m-d H:i:s');
                $insert_application['updated_at'] = date('Y-m-d H:i:s');

                $this->Firstgiving_model->store_application($insert_application);
            }
            else
            {
                $update_application['name'] = $update['first_giving_registered_name'];
                $update_application['ein_no'] = $update['ein_no'];
                $insert_application['user_id'] = $valid_auth_token->user_id;
                $insert_application['status'] = 3;
                $update_application['updated_at'] = date('Y-m-d H:i:s');

                $this->Firstgiving_model->update_application($update_application, $application_details->id);
            }

            //send mail to superadmin
            if($update['first_giving_uuid_no']!=$ngo_data->first_giving_uuid_no)
            {
                $user_info = $this->User_model->user_info_admin($ngo_data->user_id);
                $from_address = $this->config->item('sendgrid_superadmin_from_email_address', 'firstGiving');
                $to_address = $this->config->item('sendgrid_donation_notification_email', 'firstGiving');

                $email_settings = $this->Firstgiving_model->get_superadmin_email_settings();
                if(ord($email_settings->donations_activation_success)==1)
                {
                    $sendgrid_api_key = $this->config->item('sendgrid_api_key', 'sendgrid'); 
                    $template_data = $this->Firstgiving_model->get_sendgrid_template_id(array('to'=>'Superadmin', 'event'=>'donations activation success'));
                    if(!empty($template_data))
                    {
                        $sg = new \SendGrid($sendgrid_api_key);
                        $sendgrid_template_id = $template_data->template_id;
                        $from = new SendGrid\Email(null, $from_address);
                        $subject = "subject";
                        $to = new SendGrid\Email(null, $to_address);
                        $content = new SendGrid\Content("text/html", "content");
                        $mail = new SendGrid\Mail($from, $subject, $to, $content);
                        $mail->personalization[0]->addSubstitution("%NPO_name%", $ngo_data->name);
                        $mail->personalization[0]->addSubstitution("%NPO name%", $ngo_data->name);
                        $mail->personalization[0]->addSubstitution("%NPO_admin_first_name%", $user_info->first_name);
                        $mail->personalization[0]->addSubstitution("%NPO_admin_last_name%", $user_info->last_name);
                        $mail->setTemplateId($sendgrid_template_id);
                        $response = $sg->client->mail()->send()->post($mail);

                        if($response->statusCode()==202) 
                        {
                            $insert_mail_log['event'] = 'donations activation success';
                            $insert_mail_log['to_address'] = $to_address;
                            $insert_mail_log['from_address'] = $from_address;
                            $insert_mail_log['template_id'] = $sendgrid_template_id;
                            $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                            $insert_mail_log['is_email_successfully_sent'] = 'Yes';

                            $response = $sg->client->templates()->_($sendgrid_template_id)->get();
                            $body = json_decode($response->body(),true);
                            if($response->statusCode()==200)
                            {
                                $versions = $body['versions'];
                                if(!empty($versions))
                                {
                                    $key = array_search(1, array_column($versions, 'active'));
                                    $active_version = $versions[$key];
                                    $trans = array(
                                        "%NPO_name%" => $ngo_data->name, 
                                        "%NPO name%" => $ngo_data->name, 
                                        "%NPO_admin_first_name%" => $user_info->first_name,
                                        "%NPO_admin_last_name%" => $user_info->last_name
                                        );
                                    $insert_mail_log['subject'] = strtr($active_version['subject'], $trans);
                                    $insert_mail_log['html_content'] = strtr($active_version['html_content'], $trans);
                                    $insert_mail_log['plain_content'] = strtr($active_version['plain_content'], $trans);
                                }
                            }
                        }
                        else
                        {
                            $insert_mail_log['event'] = 'donations activation success';
                            $insert_mail_log['to_address'] = $to_address;
                            $insert_mail_log['from_address'] = $from_address;
                            $insert_mail_log['template_id'] = $sendgrid_template_id;
                            $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                            $insert_mail_log['is_email_successfully_sent'] = 'No';

                            $body = json_decode($response->body(),true);
                            $error = array_column($body['errors'], 'message');
                            $insert_mail_log['errors'] = json_encode($error);
                        } 
                    }
                    else
                    {
                        $insert_mail_log['event'] = 'donations activation success';
                        $insert_mail_log['to_address'] = $to_address;
                        $insert_mail_log['from_address'] = $from_address;
                        $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                        $insert_mail_log['is_email_successfully_sent'] = 'No';
                        $insert_mail_log['errors'] = 'Template data not found in database.';
                    }
                }      
                else
                {
                    $insert_mail_log['event'] = 'donations activation success';
                    $insert_mail_log['to_address'] = $to_address;
                    $insert_mail_log['from_address'] = $from_address;
                    $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                    $insert_mail_log['is_email_successfully_sent'] = 'No';
                    $insert_mail_log['errors'] = 'Sending email notification for this event is disable from email settings.';
                }
                $this->Firstgiving_model->store_email_notification_log($insert_mail_log);
            }
        }

        $data['error'] = false;
        $data['resp'] = 'Successfully Updated.';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function add_list()
    {
        if($this->input->server('REQUEST_METHOD')=="GET")
        {
            $data = $this->list_applications();
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        else
        {
            $data = $this->store_application();
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
    }

    public function application_details($id)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id!=1) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        if($this->input->server('REQUEST_METHOD')=="GET")
        {
            $data = $this->get_application_details($id);
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
    }

    public function store_application()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id==5) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        $ngo_id = login_ngo_details($auth_token);
        $application_details = $this->Firstgiving_model->get_application_by_ngo($ngo_id);
        if(!empty($application_details)) {

            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Application already submited.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $jsonArray = json_decode(file_get_contents('php://input'),true);
        $insert['name'] = isset($jsonArray['name'])?$jsonArray['name']:'';
        $insert['ein_no'] = isset($jsonArray['einNo'])?$jsonArray['einNo']:'';
        $insert['doc_url'] = isset($jsonArray['docUrl'])?$jsonArray['docUrl']:'';
        $insert['doc_name'] = isset($jsonArray['docName'])?$jsonArray['docName']:'';
        $insert['user_id'] = $valid_auth_token->user_id;
        $insert['ngo_id'] = $ngo_id;
        $insert['status'] = 1;
        $insert['created_at'] = date('Y-m-d H:i:s');
        $insert['updated_at'] = date('Y-m-d H:i:s');

        //audit add_donation_application
        $audit_info['user_id'] = $valid_auth_token->user_id;
        $audit_info['role_id'] = $valid_auth_token->role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'donation application';
        $audit_info['entity_id'] = $ngo_id;
        $audit_info['action'] = 'added';
        $audit_id = $this->Audit_model->create_audit($jsonArray, $audit_info);

        $id = $this->Firstgiving_model->store_application($insert);

        if($audit_id!='false')
            $this->Audit_model->activate_audit($audit_id);
        // audit add_donation_application

        //send mail to ngo admin
        $ngo_data = $this->Ngo_model->organization_details($ngo_id);
        $user_info = $this->User_model->user_info_admin($ngo_data->user_id);
        $sendgrid_api_key = $this->config->item('sendgrid_api_key', 'sendgrid'); 

        $from_address = $this->config->item('sendgrid_superadmin_from_email_address', 'firstGiving');
        $to_address = $user_info->email;
       
        $email_settings = $this->Firstgiving_model->get_superadmin_email_settings();
        if(ord($email_settings->donations_app_submitted_for_npo_admin)==1)
        {
            $template_data = $this->Firstgiving_model->get_sendgrid_template_id(array('to'=>'Npo admin', 'event'=>'donations app submitted'));
            if(!empty($template_data))
            {
                $sg = new \SendGrid($sendgrid_api_key);
                $sendgrid_template_id = $template_data->template_id;
                $from = new SendGrid\Email(null, $from_address);
                $subject = "subject";
                $to = new SendGrid\Email(null, $to_address);
                $content = new SendGrid\Content("text/html", "content");
                $mail = new SendGrid\Mail($from, $subject, $to, $content);
                $mail->personalization[0]->addSubstitution("%NPO_name%", $ngo_data->name);
                $mail->personalization[0]->addSubstitution("%NPO name%", $ngo_data->name);
                $mail->personalization[0]->addSubstitution("%NPO_admin_first_name%", $user_info->first_name);
                $mail->personalization[0]->addSubstitution("%NPO_admin_last_name%", $user_info->last_name);
                $mail->setTemplateId($sendgrid_template_id);

                $response = $sg->client->mail()->send()->post($mail);
                if($response->statusCode()==202) 
                {
                    $insert_mail_log['event'] = 'donations app submitted';
                    $insert_mail_log['to_address'] = $to_address;
                    $insert_mail_log['from_address'] = $from_address;
                    $insert_mail_log['template_id'] = $sendgrid_template_id;
                    $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                    $insert_mail_log['is_email_successfully_sent'] = 'Yes';

                    $response = $sg->client->templates()->_($sendgrid_template_id)->get();
                    $body = json_decode($response->body(),true);
                    if($response->statusCode()==200)
                    {
                        $versions = $body['versions'];
                        if(!empty($versions))
                        {
                            $key = array_search(1, array_column($versions, 'active'));
                            $active_version = $versions[$key];
                            $trans = array(
                                "%NPO_name%" => $ngo_data->name, 
                                "%NPO name%" => $ngo_data->name, 
                                "%NPO_admin_first_name%" => $user_info->first_name,
                                "%NPO_admin_last_name%" => $user_info->last_name
                                );
                            $insert_mail_log['subject'] = strtr($active_version['subject'], $trans);
                            $insert_mail_log['html_content'] = strtr($active_version['html_content'], $trans);
                            $insert_mail_log['plain_content'] = strtr($active_version['plain_content'], $trans);
                        }
                    }
                }
                else
                {
                    $insert_mail_log['event'] = 'donations app submitted';
                    $insert_mail_log['to_address'] = $to_address;
                    $insert_mail_log['from_address'] = $from_address;
                    $insert_mail_log['template_id'] = $sendgrid_template_id;
                    $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                    $insert_mail_log['is_email_successfully_sent'] = 'No';

                    $body = json_decode($response->body(),true);
                    $error = array_column($body['errors'], 'message');
                    $insert_mail_log['errors'] = json_encode($error);
                } 
            }
            else
            {
                $insert_mail_log['event'] = 'donations app submitted';
                $insert_mail_log['to_address'] = $to_address;
                $insert_mail_log['from_address'] = $from_address;
                $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                $insert_mail_log['is_email_successfully_sent'] = 'No';
                $insert_mail_log['errors'] = 'Template data not found in database.';
            }
            
        }
        else
        {
            $insert_mail_log['event'] = 'donations app submitted';
            $insert_mail_log['to_address'] = $to_address;
            $insert_mail_log['from_address'] = $from_address;
            $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
            $insert_mail_log['is_email_successfully_sent'] = 'No';
            $insert_mail_log['errors'] = 'Sending email notification for this event is disable from email settings.';
        }
        $this->Firstgiving_model->store_email_notification_log($insert_mail_log);

        //send mail to superadmin
        $insert_mail_log=array();
        $to_address = $this->config->item('sendgrid_donation_notification_email', 'firstGiving');

        $email_settings = $this->Firstgiving_model->get_superadmin_email_settings();
        if(ord($email_settings->donations_app_submitted_for_superadmin)==1)
        {
            $template_data = $this->Firstgiving_model->get_sendgrid_template_id(array('to'=>'Superadmin', 'event'=>'donations app submitted'));
            if(!empty($template_data))
            {
                $sg = new \SendGrid($sendgrid_api_key);
                $sendgrid_template_id = $template_data->template_id;
                $from = new SendGrid\Email(null, $from_address);
                $subject = "subject";
                $to = new SendGrid\Email(null, $to_address);
                $content = new SendGrid\Content("text/html", "content");
                $mail = new SendGrid\Mail($from, $subject, $to, $content);
                $mail->personalization[0]->addSubstitution("%NPO_name%", $ngo_data->name);
                $mail->personalization[0]->addSubstitution("%NPO name%", $ngo_data->name);
                $mail->personalization[0]->addSubstitution("%NPO_admin_first_name%", $user_info->first_name);
                $mail->personalization[0]->addSubstitution("%NPO_admin_last_name%", $user_info->last_name);
                $mail->setTemplateId($sendgrid_template_id);

                $response = $sg->client->mail()->send()->post($mail);
                if($response->statusCode()==202) 
                {
                    $insert_mail_log['event'] = 'donations app submitted';
                    $insert_mail_log['to_address'] = $to_address;
                    $insert_mail_log['from_address'] = $from_address;
                    $insert_mail_log['template_id'] = $sendgrid_template_id;
                    $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                    $insert_mail_log['is_email_successfully_sent'] = 'Yes';

                    $response = $sg->client->templates()->_($sendgrid_template_id)->get();
                    $body = json_decode($response->body(),true);
                    if($response->statusCode()==200)
                    {
                        $versions = $body['versions'];
                        if(!empty($versions))
                        {
                            $key = array_search(1, array_column($versions, 'active'));
                            $active_version = $versions[$key];
                            $trans = array(
                                "%NPO_name%" => $ngo_data->name, 
                                "%NPO name%" => $ngo_data->name, 
                                "%NPO_admin_first_name%" => $user_info->first_name,
                                "%NPO_admin_last_name%" => $user_info->last_name
                                );
                            $insert_mail_log['subject'] = strtr($active_version['subject'], $trans);
                            $insert_mail_log['html_content'] = strtr($active_version['html_content'], $trans);
                            $insert_mail_log['plain_content'] = strtr($active_version['plain_content'], $trans);
                        }
                    }
                }
                else
                {
                    $insert_mail_log['event'] = 'donations app submitted';
                    $insert_mail_log['to_address'] = $to_address;
                    $insert_mail_log['from_address'] = $from_address;
                    $insert_mail_log['template_id'] = $sendgrid_template_id;
                    $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                    $insert_mail_log['is_email_successfully_sent'] = 'No';

                    $body = json_decode($response->body(),true);
                    $error = array_column($body['errors'], 'message');
                    $insert_mail_log['errors'] = json_encode($error);
                } 
            }
            else
            {
                $insert_mail_log['event'] = 'donations app submitted';
                $insert_mail_log['to_address'] = $to_address;
                $insert_mail_log['from_address'] = $from_address;
                $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                $insert_mail_log['is_email_successfully_sent'] = 'No';
                $insert_mail_log['errors'] = 'Template data not found in database.';
            }
        }
        else
        {
            $insert_mail_log['event'] = 'donations app submitted';
            $insert_mail_log['to_address'] = $to_address;
            $insert_mail_log['from_address'] = $from_address;
            $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
            $insert_mail_log['is_email_successfully_sent'] = 'No';
            $insert_mail_log['errors'] = 'Sending email notification for this event is disable from email settings.';
        }
        $this->Firstgiving_model->store_email_notification_log($insert_mail_log);

        return $this->get_application_details($id);
    }

    public function update_application($id)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        $ngo_id = login_ngo_details($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id==5) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $application_details = $this->Firstgiving_model->get_application_by_ngo($ngo_id);
        if(empty($application_details)) {

            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Application not found.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($application_details->ngo_id!=$ngo_id) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($application_details->status!=1) {

            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Unable to update.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $jsonArray = json_decode(file_get_contents('php://input'),true);
        $update['name'] = isset($jsonArray['name'])?$jsonArray['name']:'';
        $update['ein_no'] = isset($jsonArray['einNo'])?$jsonArray['einNo']:'';
        $update['doc_url'] = isset($jsonArray['docUrl'])?$jsonArray['docUrl']:'';
        $update['doc_name'] = isset($jsonArray['docName'])?$jsonArray['docName']:'';
        $update['updated_at'] = date('Y-m-d H:i:s');

        //audit donation setup
        $old_data['name'] = $application_details->name;
        $old_data['einNo'] = $application_details->ein_no;
        $old_data['docUrl'] = $application_details->doc_url;

        $audit_info['user_id'] = $valid_auth_token->user_id;
        $audit_info['role_id'] = $valid_auth_token->role_id;
        $audit_info['org_id'] = $ngo_id;
        $audit_info['entity'] = 'donation application';
        $audit_info['entity_id'] = $ngo_id;
        $audit_info['action'] = 'updated';
        $audit_id = $this->Audit_model->update_audit($old_data, $jsonArray, $audit_info);

        $this->Firstgiving_model->update_application($update, $id);

        if($audit_id!='false')
            $this->Audit_model->activate_audit($audit_id);
        //audit donation setup

        $application_data = $this->get_application_details($id);
        echo json_encode($application_data,JSON_NUMERIC_CHECK);
        exit;
    }

    // public function get_ngo_application_details()
    // {
    //     $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
    //     $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
    //     if(empty($valid_auth_token)) {

    //         $data['error'] = true;
    //         $data['status'] = 401;
    //         $data['message'] = "Unauthorized User.";
    //         header('HTTP/1.1 401 Unauthorized User');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if($valid_auth_token->role_id==5) {

    //         $data['error'] = true;
    //         $data['status'] = 401;
    //         $data['message'] = "Unauthorized User.";
    //         header('HTTP/1.1 401 Unauthorized User');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }

    //     $ngo_id = login_ngo_details($auth_token);

    //     $application_data = $this->Firstgiving_model->get_application_by_ngo($ngo_id);
    //     if(empty($application_data))
    //     {
    //         header('HTTP/1.1 404 Not Found');
    //         return;
    //     }

    //     $data = $this->get_application_details($application_data->id);
    //     echo json_encode($data,JSON_NUMERIC_CHECK);
    //     exit;
    // }

    public function get_application_details($id)
    {
        $application_details = $this->Firstgiving_model->get_application_details($id);
        if(empty($application_details)) {

            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Application not found.";
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $application_data['id'] = $application_details->id;
        $application_data['name'] = $application_details->name;
        $application_data['einNo'] = $application_details->ein_no;
        $application_data['docUrl'] = $application_details->doc_url;
        $application_data['docName'] = $application_details->doc_name;
        $application_data['userId'] = $application_details->user_id;
        $application_data['ngoId'] = $application_details->ngo_id;
        $application_data['status'] = $application_details->status;
        $application_data['createdAt'] = $application_details->created_at;
        $application_data['updatedAt'] = $application_details->updated_at;
        $application_data['status_name'] = $application_details->status_name;
        $application_data['npoName'] = $application_details->npo_name;

        $data['error'] = false;
        $data['resp'] = $application_data;
        return $data;
    }

    public function list_applications()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id!=1) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;
        $query = ($this->input->get('query'))?$this->input->get('query'):'';
        $status = ($this->input->get('status'))?$this->input->get('status'):'';

        $application_list = $this->Firstgiving_model->get_application_list($offset, $limit, $query, $status);
        $application_data = array();
        $i=0;
        if(!empty($application_list))
        {
            foreach ($application_list as $application_details) {

                $application_data[$i]['id'] = $application_details->id;
                $application_data[$i]['name'] = $application_details->name;
                $application_data[$i]['einNo'] = $application_details->ein_no;
                $application_data[$i]['docUrl'] = $application_details->doc_url;
                $application_data[$i]['docName'] = $application_details->doc_name;
                $application_data[$i]['userId'] = $application_details->user_id;
                $application_data[$i]['ngoId'] = $application_details->ngo_id;
                $application_data[$i]['status'] = $application_details->status;
                $application_data[$i]['createdAt'] = $application_details->created_at;
                $application_data[$i]['updatedAt'] = $application_details->updated_at;
                $application_data[$i]['status_name'] = $application_details->status_name;
                $application_data[$i]['npoName'] = $application_details->npo_name;
                $i++;
            }
        }

        $data['error'] = false;
        $count = $this->Firstgiving_model->get_application_list_count($query, $status);
        $data['resp']['count'] = $count->num;
        $data['resp']['application'] = $application_data;
        return $data;
    }

    public function change_application_status($id)
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id!=1) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $application_details = $this->Firstgiving_model->get_application_details($id);
        if(empty($application_details)) {

            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = "Application not found.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($application_details->status==3) {

            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "You cannot change application status once it is approved.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $jsonArray = json_decode(file_get_contents('php://input'),true);
        $update['status'] = isset($jsonArray['status'])?$jsonArray['status']:'';
        if($update['status']!=1 && $update['status']!=2 && $update['status']!=3) {

            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Invalid status code.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $this->Firstgiving_model->update_application($update, $id);

        if($update['status']==3)
        {
            $ngo_data = $this->Ngo_model->organization_details($application_details->ngo_id);
            $user_info = $this->User_model->user_info_admin($ngo_data->user_id);

            $from_address = $this->config->item('sendgrid_superadmin_from_email_address', 'firstGiving');
            $to_address = $user_info->email;

            $email_settings = $this->Firstgiving_model->get_superadmin_email_settings();
            if(ord($email_settings->donations_app_approved)==1)
            {
                $sendgrid_api_key = $this->config->item('sendgrid_api_key', 'sendgrid'); 
                $template_data = $this->Firstgiving_model->get_sendgrid_template_id(array('to'=>'Npo admin', 'event'=>'donations app approved'));
                if(!empty($template_data))
                {
                    $sg = new \SendGrid($sendgrid_api_key);
                    $sendgrid_template_id = $template_data->template_id;
                    $from = new SendGrid\Email(null, $from_address);
                    $subject = "subject";
                    $to = new SendGrid\Email(null, $to_address);
                    $content = new SendGrid\Content("text/html", "content");
                    $mail = new SendGrid\Mail($from, $subject, $to, $content);
                    $mail->personalization[0]->addSubstitution("%NPO_name%", $ngo_data->name);
                    $mail->personalization[0]->addSubstitution("%NPO name%", $ngo_data->name);
                    $mail->personalization[0]->addSubstitution("%NPO_admin_first_name%", $user_info->first_name);
                    $mail->personalization[0]->addSubstitution("%NPO_admin_last_name%", $user_info->last_name);
                    $mail->setTemplateId($sendgrid_template_id);

                    $response = $sg->client->mail()->send()->post($mail);
                    if($response->statusCode()==202) 
                    {
                        $insert_mail_log['event'] = 'donations app approved';
                        $insert_mail_log['to_address'] = $to_address;
                        $insert_mail_log['from_address'] = $from_address;
                        $insert_mail_log['template_id'] = $sendgrid_template_id;
                        $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                        $insert_mail_log['is_email_successfully_sent'] = 'Yes';

                        $response = $sg->client->templates()->_($sendgrid_template_id)->get();
                        $body = json_decode($response->body(),true);
                        if($response->statusCode()==200)
                        {
                            $versions = $body['versions'];
                            if(!empty($versions))
                            {
                                $key = array_search(1, array_column($versions, 'active'));
                                $active_version = $versions[$key];
                                $trans = array(
                                    "%NPO_name%" => $ngo_data->name, 
                                    "%NPO name%" => $ngo_data->name, 
                                    "%NPO_admin_first_name%" => $user_info->first_name,
                                    "%NPO_admin_last_name%" => $user_info->last_name
                                    );
                                $insert_mail_log['subject'] = strtr($active_version['subject'], $trans);
                                $insert_mail_log['html_content'] = strtr($active_version['html_content'], $trans);
                                $insert_mail_log['plain_content'] = strtr($active_version['plain_content'], $trans);
                            }
                        }
                    }
                    else
                    {
                        $insert_mail_log['event'] = 'donations app approved';
                        $insert_mail_log['to_address'] = $to_address;
                        $insert_mail_log['from_address'] = $from_address;
                        $insert_mail_log['template_id'] = $sendgrid_template_id;
                        $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                        $insert_mail_log['is_email_successfully_sent'] = 'No';

                        $body = json_decode($response->body(),true);
                        $error = array_column($body['errors'], 'message');
                        $insert_mail_log['errors'] = json_encode($error);
                    } 
                }
                else
                {
                    $insert_mail_log['event'] = 'donations app approved';
                    $insert_mail_log['to_address'] = $to_address;
                    $insert_mail_log['from_address'] = $from_address;
                    $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                    $insert_mail_log['is_email_successfully_sent'] = 'No';
                    $insert_mail_log['errors'] = 'Template data not found in database.';
                }
            }
            else
            {
                $insert_mail_log['event'] = 'donations app approved';
                $insert_mail_log['to_address'] = $to_address;
                $insert_mail_log['from_address'] = $from_address;
                $insert_mail_log['datetime'] = date('Y-m-d H:i:s');
                $insert_mail_log['is_email_successfully_sent'] = 'No';
                $insert_mail_log['errors'] = 'Sending email notification for this event is disable from email settings.';
            }
            $this->Firstgiving_model->store_email_notification_log($insert_mail_log);
        }

        $application_data = $this->get_application_details($id);
        echo json_encode($application_data,JSON_NUMERIC_CHECK);
        exit;
    }

    public function store_transaction_log()
    {
        $ngo_id = $this->input->get('ngoId');
        if($ngo_id===false)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Ngo id not given.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $insert['ngo_id'] = $ngo_id;

        $ngo_data = $this->Ngo_model->organization_details($ngo_id, 'any');
        if(empty($ngo_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Invalid ngoId.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $insert['payment_gateway'] = 'firstgiving';

        $jsonArray = json_decode(file_get_contents('php://input'),true);
        $insert['transaction_id'] = $transactionId = isset($jsonArray['_fg_popup_transaction_id'])?$jsonArray['_fg_popup_transaction_id']:'';
        $insert['transaction_datetime'] = isset($jsonArray['_fg_popup_date'])?$jsonArray['_fg_popup_date']:'';
        $insert['payment_gateway_organization_name'] = isset($jsonArray['_fg_popup_organization_name'])?$jsonArray['_fg_popup_organization_name']:'';
        $insert['payment_gateway_organization_id'] = isset($jsonArray['_fg_popup_organization_id'])?$jsonArray['_fg_popup_organization_id']:'';
        $insert['payment_gateway_attribution'] = isset($jsonArray['_fg_popup_attribution'])?$jsonArray['_fg_popup_attribution']:'';
        $insert['amount'] = isset($jsonArray['_fg_popup_amount'])?$jsonArray['_fg_popup_amount']:'';
        $insert['donor_name'] = isset($jsonArray['_fg_popup_donor_name'])?$jsonArray['_fg_popup_donor_name']:'';
        $insert['donor_email'] = isset($jsonArray['_fgp_email'])?$jsonArray['_fgp_email']:'';
        $insert['donor_address'] = isset($jsonArray['_fgp_address'])?$jsonArray['_fgp_address']:'';
        $insert['donor_city'] = isset($jsonArray['_fgp_city'])?$jsonArray['_fgp_city']:'';
        $insert['donor_state'] = isset($jsonArray['_fgp_state'])?$jsonArray['_fgp_state']:'';
        $insert['donor_zip'] = isset($jsonArray['_fgp_zip'])?$jsonArray['_fgp_zip']:'';
        $insert['donor_country'] = isset($jsonArray['_fgp_country'])?$jsonArray['_fgp_country']:'';
        $insert['payment_gateway_donation_id'] = isset($jsonArray['_fg_popup_donationId'])?$jsonArray['_fg_popup_donationId']:'';
        $insert['payment_gateway_campaign_name'] = isset($jsonArray['_fg_popup_campaignName'])?$jsonArray['_fg_popup_campaignName']:'';
        $insert['payment_gateway_pledge_id'] = isset($jsonArray['_fg_popup_pledgeId'])?$jsonArray['_fg_popup_pledgeId']:'';
        $cc_type = isset($jsonArray['_fg_popup_ccType'])?$jsonArray['_fg_popup_ccType']:'';
        if($cc_type=='VI')
            $cc_type = 'Visa';
        elseif($cc_type=='MC')
            $cc_type = 'MasterCard';
        elseif($cc_type=='DI')
            $cc_type = 'Discover Card';
        elseif($cc_type=='AX')
            $cc_type = 'American Express';
        $insert['credit_card_type'] = $cc_type;
        
        // $use_first_giving_production_api = $this->config->item('use_first_giving_production_api', 'firstGiving');
        // if($use_first_giving_production_api==true)
        // {
        //     $first_giving_url = $this->config->item('first_giving_api_url_production', 'firstGiving');
        //     $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_production', 'firstGiving');
        //     $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_production', 'firstGiving');
        // }
        // else
        // {
        //     $first_giving_url = $this->config->item('first_giving_api_url_staging', 'firstGiving');
        //     $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_staging', 'firstGiving');
        //     $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_staging', 'firstGiving');
        // }

        // $url = $first_giving_url."transaction/detail?transactionId=$transactionId"; 
        // $ch = curl_init();
        // $ret = curl_setopt($ch, CURLOPT_URL, $url);
        // $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        // $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        //     'JG_APPLICATIONKEY: ' . $JG_APPLICATIONKEY,
        //     'JG_SECURITYTOKEN: ' . $JG_SECURITYTOKEN
        // ));
        // $ret = curl_exec($ch);

        // $ret = json_decode(json_encode(simplexml_load_string($ret)), true);
        // if(isset($ret['firstGivingResponse']['transaction']['currencyCode']))
        //     $insert['currency_code'] = $ret['firstGivingResponse']['transaction']['currencyCode'];
        $insert['currency_code'] = 'USD';

        $id = $this->Firstgiving_model->store_transaction_log($insert);

        $data['error'] = false;
        $data['resp'] = 'Successful';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function refund_transaction()
    {   
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id!=1) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $jsonArray = json_decode(file_get_contents('php://input'),true);
        $transaction_id = isset($jsonArray['transactionId'])?$jsonArray['transactionId']:'';
        $where = array('donor_transactions.transaction_id'=>$transaction_id);
        $transaction_data = $this->Firstgiving_model->get_donation_transaction($where);
        if(empty($transaction_data))
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Transaction data not found.";
            header('HTTP/1.1 400 Validation Error.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $use_first_giving_production_api = $this->config->item('use_first_giving_production_api', 'firstGiving');
        if($use_first_giving_production_api==true)
        {
            $first_giving_url = $this->config->item('first_giving_api_url_production', 'firstGiving');
            $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_production', 'firstGiving');
            $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_production', 'firstGiving');
        }
        else
        {
            $first_giving_url = $this->config->item('first_giving_api_url_staging', 'firstGiving');
            $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_staging', 'firstGiving');
            $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_staging', 'firstGiving');
        }

        try {
            $url = $first_giving_url."transaction/refundrequest?transactionId=$transaction_id&tranType=REFUNDREQUEST";
            $ch = curl_init();
            $ret = curl_setopt($ch, CURLOPT_URL, $url);
            $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'JG_APPLICATIONKEY: ' . $JG_APPLICATIONKEY,
                'JG_SECURITYTOKEN: ' . $JG_SECURITYTOKEN
            ));
            $ret = curl_exec($ch);
            $info = curl_getinfo($ch);
        }
        catch(Exception $e)
        {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }
        if($info['http_code']==403)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Forbidden.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        elseif($info['http_code']==404)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Unknown API Call.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        elseif($info['http_code']==405)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Method not allowed.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        // elseif($info['http_code']==500)
        // {
        //     $data['error'] = true;
        //     $data['status'] = 400;
        //     $data['message'] = "Internal Error - contact FirstGiving.com.";
        //     header('HTTP/1.1 400 Validation Error');
        //     echo json_encode($data,JSON_NUMERIC_CHECK);
        //     exit;
        // }
        
        if($info['http_code']==400 || $info['http_code']==500)
        {
            $ret = json_decode(json_encode(simplexml_load_string($ret)), true);
            $error = '';
            if($ret['firstGivingResponse']['@attributes']['verboseErrorMessage'])
                $error = $ret['firstGivingResponse']['@attributes']['verboseErrorMessage'];

            $update['last_refund_error'] = json_encode($ret);
            $this->Firstgiving_model->update_donation_transaction($update, $transaction_data->id);

            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = 'Firstgiving Error: '.$error;
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }

        if($info['http_code']!=200)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = 'validation error.';
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }
        
        $update['refund_status'] = 1;
        $update['status'] = 'Awaiting Refund';
        $this->Firstgiving_model->update_donation_transaction($update, $transaction_data->id);

        $data['error'] = false;
        $data['resp'] = 'Successful.';
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function get_ngo_list_for_refund()
    {
        $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
        $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
        if(empty($valid_auth_token)) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        if($valid_auth_token->role_id!=1) {

            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $page = ($this->input->get('page'))?$this->input->get('page'):1;
        $limit = ($this->input->get('limit'))?$this->input->get('limit'):10;
        $offset=($page-1)*$limit;
        $search = ($this->input->get('search'))?$this->input->get('search'):'';

        $list = $this->Firstgiving_model->get_ngo_list_for_refund($offset, $limit, $search);
        $count = $this->Firstgiving_model->get_ngo_list_for_refund_count($search);
        $ngo_data = array(); 
        $i=0;
        if(!empty($list))
        {
            foreach($list as $ngo)
            {
                $ngo_data[$i]['id'] = $ngo->id;
                $ngo_data[$i]['name'] = $ngo->name;
                $i++;
            }
        }
        
        $data['error'] = false;
        $data['resp']['count'] = $count;
        $data['resp']['ngo'] = $ngo_data;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }

    // public function make_transaction()
    // {
    //     $jsonArray = json_decode(file_get_contents('php://input'),true);
    //     $ccNumber = isset($jsonArray['ccNumber'])?$jsonArray['ccNumber']:'';
    //     $ccType = isset($jsonArray['ccType'])?$jsonArray['ccType']:'';
    //     $ccExpDateMonth = isset($jsonArray['ccExpDateMonth'])?$jsonArray['ccExpDateMonth']:'';
    //     $ccExpDateYear = isset($jsonArray['ccExpDateYear'])?$jsonArray['ccExpDateYear']:'';
    //     $billToAddressLine1 = isset($jsonArray['billToAddressLine1'])?$jsonArray['billToAddressLine1']:'';
    //     $billToCity = isset($jsonArray['billToCity'])?$jsonArray['billToCity']:'';
    //     $billToState = isset($jsonArray['billToState'])?$jsonArray['billToState']:'';
    //     $billToZip = isset($jsonArray['billToZip'])?$jsonArray['billToZip']:'';
    //     $ccCardValidationNum = isset($jsonArray['ccCardValidationNum'])?$jsonArray['ccCardValidationNum']:'';
    //     $billToTitle = isset($jsonArray['billToTitle'])?$jsonArray['billToTitle']:'';
    //     $billToFirstName = isset($jsonArray['billToFirstName'])?$jsonArray['billToFirstName']:'';
    //     $billToMiddleName = isset($jsonArray['billToMiddleName'])?$jsonArray['billToMiddleName']:'';
    //     $billToLastName = isset($jsonArray['billToLastName'])?$jsonArray['billToLastName']:'';
    //     $billToAddressLine2 = isset($jsonArray['billToAddressLine2'])?$jsonArray['billToAddressLine2']:'';
    //     $billToAddressLine3 = isset($jsonArray['billToAddressLine3'])?$jsonArray['billToAddressLine3']:'';
    //     $billToCountry = isset($jsonArray['billToCountry'])?$jsonArray['billToCountry']:'';
    //     $billToEmail = isset($jsonArray['billToEmail'])?$jsonArray['billToEmail']:'';
    //     $billToPhone = isset($jsonArray['billToPhone'])?$jsonArray['billToPhone']:'';
    //     $remoteAddr = isset($jsonArray['remoteAddr'])?$jsonArray['remoteAddr']:'';
    //     $amount = isset($jsonArray['amount'])?$jsonArray['amount']:'';
    //     $currencyCode = isset($jsonArray['currencyCode'])?$jsonArray['currencyCode']:'';
    //     $charityId = isset($jsonArray['charityId'])?$jsonArray['charityId']:'';
    //     $eventId = isset($jsonArray['eventId'])?$jsonArray['eventId']:'';
    //     $fundraiserId = isset($jsonArray['fundraiserId'])?$jsonArray['fundraiserId']:'';
    //     $orderId = isset($jsonArray['orderId'])?$jsonArray['orderId']:'';
    //     $description = isset($jsonArray['description'])?$jsonArray['description']:'';
    //     $reportDonationToTaxAuthority = isset($jsonArray['reportDonationToTaxAuthority'])?$jsonArray['reportDonationToTaxAuthority']:'';
    //     $personalIdentificationNumber = isset($jsonArray['personalIdentificationNumber'])?$jsonArray['personalIdentificationNumber']:'';
    //     $donationMessage = isset($jsonArray['donationMessage'])?$jsonArray['donationMessage']:'';
    //     $honorMemoryName = isset($jsonArray['honorMemoryName'])?$jsonArray['honorMemoryName']:'';
    //     $pledgeId = isset($jsonArray['pledgeId'])?$jsonArray['pledgeId']:'';
    //     $campaignName = isset($jsonArray['campaignName'])?$jsonArray['campaignName']:'';
    //     $commissionRate = isset($jsonArray['commissionRate'])?$jsonArray['commissionRate']:'';

    //     if(empty($ccNumber))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter credit card number.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($ccExpDateYear))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter credit card expiration year.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($ccExpDateMonth))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter credit card expiration month.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($ccCardValidationNum))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please credit card security number.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($billToFirstName))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter first name.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($billToLastName))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter last name..";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($billToAddressLine1))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter address line 1.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($billToCity))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter city.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     // if(empty($billToState))
    //     // {
    //     //     $data['error'] = true;
    //     //     $data['status'] = 400;
    //     //     $data['message'] = "Please enter state.";
    //     //     header('HTTP/1.1 400 Validation Error.');
    //     //     echo json_encode($data,JSON_NUMERIC_CHECK);
    //     //     exit;
    //     // }
    //     if(empty($billToZip))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter zip code.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($billToCountry))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter country.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($billToEmail))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter email address.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($remoteAddr))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Remote address not given.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($amount))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter amount.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($charityId))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "UUID not given.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     if(empty($description))
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Please enter description.";
    //         header('HTTP/1.1 400 Validation Error.');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }

    //     $postdata = array(
    //         'ccNumber' => $ccNumber,
    //         'ccType' => $ccType,
    //         'ccExpDateMonth' => (int)$ccExpDateMonth,
    //         'ccExpDateYear' => $ccExpDateYear,
    //         'billToAddressLine1' => $billToAddressLine1,
    //         'billToCity' => $billToCity,
    //         'billToState' => $billToState,
    //         'billToZip' => $billToZip,
    //         'ccCardValidationNum' => $ccCardValidationNum,
    //         'billToTitle' => $billToTitle,
    //         'billToFirstName' => $billToFirstName,
    //         'billToMiddleName' => $billToMiddleName,
    //         'billToLastName' => $billToLastName,
    //         'billToAddressLine2' => $billToAddressLine2,
    //         'billToAddressLine3' => $billToAddressLine3,
    //         'billToCountry' => $billToCountry,
    //         'billToEmail' => $billToEmail,
    //         'billToPhone' => $billToPhone,
    //         'remoteAddr' => $remoteAddr,
    //         'amount' => $amount,
    //         'currencyCode' => $currencyCode,
    //         'charityId' => $charityId,
    //         'eventId' => $eventId,
    //         // 'fundraiserId' => $fundraiserId,
    //         'orderId' => $orderId,
    //         'description' => $description,
    //         'reportDonationToTaxAuthority' => $reportDonationToTaxAuthority,
    //         'personalIdentificationNumber' => $personalIdentificationNumber,
    //         'donationMessage' => $donationMessage,
    //         'honorMemoryName' => $honorMemoryName,
    //         'pledgeId' => $pledgeId,
    //         'campaignName' => $campaignName,
    //         'commissionRate' => $commissionRate,
    //     );

    //     try{
    //         $use_first_giving_production_api = $this->config->item('use_first_giving_production_api', 'firstGiving');
            // if($use_first_giving_production_api==true)
            // {
            //     $first_giving_url = $this->config->item('first_giving_api_url_production', 'firstGiving');
            //     $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_production', 'firstGiving');
            //     $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_production', 'firstGiving');
            // }
            // else
            // {
            //     $first_giving_url = $this->config->item('first_giving_api_url_staging', 'firstGiving');
            //     $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_staging', 'firstGiving');
            //     $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_staging', 'firstGiving');
            // }
             

    //         $url = $first_giving_url."donation/creditcard";

    //         $data_string = http_build_query($postdata);
    //         $ch = curl_init();
    //         $ret = curl_setopt($ch, CURLOPT_URL, $url);
    //         $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    //         $ret = curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    //         $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    //             'JG_APPLICATIONKEY: ' . $JG_APPLICATIONKEY,
    //             'JG_SECURITYTOKEN: ' . $JG_SECURITYTOKEN
    //         ));
    //         $ret = curl_exec($ch);
    //         $info = curl_getinfo($ch);
    //     }
    //     catch(Exception $e)
    //     {
    //         header('HTTP/1.1 500 Internal Server Error');
    //         return;
    //     }
    //     if($info['http_code']==400)
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 400;
    //         $data['message'] = "Parameter missing.";
    //         header('HTTP/1.1 400 Validation Error');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     elseif($info['http_code']==403)
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 403;
    //         $data['message'] = "Forbidden.";
    //         header('HTTP/1.1 403 Forbidden');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     elseif($info['http_code']==404)
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 401;
    //         $data['message'] = "Unknown API Call.";
    //         header('HTTP/1.1 404 Not Found');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     elseif($info['http_code']==405)
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 405;
    //         $data['message'] = "Method not allowed.";
    //         header('HTTP/1.1 405 Method not allowed');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }
    //     elseif($info['http_code']==500)
    //     {
    //         $data['error'] = true;
    //         $data['status'] = 401;
    //         $data['message'] = "Internal Error - contact FirstGiving.com.";
    //         header('HTTP/1.1 401 Unauthorized User');
    //         echo json_encode($data,JSON_NUMERIC_CHECK);
    //         exit;
    //     }

    //     $ret = json_decode(json_encode(simplexml_load_string($ret), true));
    //     $transaction_id = $ret->firstGivingResponse->transactionId;

    //     $data['error'] = false;
    //     $data['resp']['transactionId'] = $transaction_id;
    //     echo json_encode($data,JSON_NUMERIC_CHECK);
    //     exit;
    // }

    public function get_transaction($transactionId)
    {
        if($this->input->server('HTTP_X_AUTH_TOKEN'))
        {
            $auth_token = $this->input->server('HTTP_X_AUTH_TOKEN');
            $valid_auth_token = $this->Verification_model->valid_auth_token($auth_token);
            if(empty($valid_auth_token)) {

                $data['error'] = true;
                $data['status'] = 401;
                $data['message'] = "Unauthorized User.";
                header('HTTP/1.1 401 Unauthorized User');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
            if($valid_auth_token->role_id!=1) {

                $data['error'] = true;
                $data['status'] = 401;
                $data['message'] = "Unauthorized User.";
                header('HTTP/1.1 401 Unauthorized User');
                echo json_encode($data,JSON_NUMERIC_CHECK);
                exit;
            }
        }

        $use_first_giving_production_api = $this->config->item('use_first_giving_production_api', 'firstGiving');
        if($use_first_giving_production_api==true)
        {
            $first_giving_url = $this->config->item('first_giving_api_url_production', 'firstGiving');
            $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_production', 'firstGiving');
            $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_production', 'firstGiving');
        }
        else
        {
            $first_giving_url = $this->config->item('first_giving_api_url_staging', 'firstGiving');
            $JG_APPLICATIONKEY = $this->config->item('first_giving_application_key_staging', 'firstGiving');
            $JG_SECURITYTOKEN = $this->config->item('first_giving_security_token_staging', 'firstGiving');
        }
        try{
            $url = $first_giving_url."transaction/detail?transactionId=$transactionId"; 
            $ch = curl_init();
            $ret = curl_setopt($ch, CURLOPT_URL, $url);
            $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'JG_APPLICATIONKEY: ' . $JG_APPLICATIONKEY,
                'JG_SECURITYTOKEN: ' . $JG_SECURITYTOKEN
            ));
            $ret = curl_exec($ch);
            $info = curl_getinfo($ch);
        }
        catch(Exception $e)
        {
            header('HTTP/1.1 500 Internal Server Error');
            return;
        }

        if($info['http_code']==400)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = "Parameter missing.";
            header('HTTP/1.1 400 Validation Error');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        elseif($info['http_code']==403)
        {
            $data['error'] = true;
            $data['status'] = 403;
            $data['message'] = "Forbidden.";
            header('HTTP/1.1 403 Forbidden');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        elseif($info['http_code']==404)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unknown API Call.";
            header('HTTP/1.1 404 Not Found');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        elseif($info['http_code']==405)
        {
            $data['error'] = true;
            $data['status'] = 405;
            $data['message'] = "Method not allowed.";
            header('HTTP/1.1 405 Method not allowed');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }
        // elseif($info['http_code']==500)
        // {
        //     $data['error'] = true;
        //     $data['status'] = 401;
        //     $data['message'] = "Internal Error - contact FirstGiving.com.";
        //     header('HTTP/1.1 401 Unauthorized User');
        //     echo json_encode($data,JSON_NUMERIC_CHECK);
        //     exit;
        // }
        // var_dump($ret);
        // die();
        $ret = json_decode(json_encode(simplexml_load_string($ret)), true);
        $transaction_data = $ret['firstGivingResponse']['transaction'];

        $data['error'] = false;
        $data['resp'] = $transaction_data;
        echo json_encode($data,JSON_NUMERIC_CHECK);
        exit;
    }
}