<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class FirstGiving_model extends CI_Model 
{
    public function store_application($insert)
    {
        $this->db->insert('donation_applications', $insert); 
        return $this->db->insert_id();
    }

    public function get_application_details($id)
    {
        $this->db->select('donation_applications.*, first_giving_application_status.status_name, organisation.name as npo_name');
        $this->db->from('donation_applications'); 
        $this->db->join('first_giving_application_status', 'first_giving_application_status.id = donation_applications.status'); 
        $this->db->join('organisation', 'organisation.id = donation_applications.ngo_id'); 
        $this->db->where('donation_applications.id', $id);
        $result = $this->db->get();
        return $result->row();
    }

    public function get_application_list($offset, $limit, $query, $status='')
    {
        $this->db->select('donation_applications.*, first_giving_application_status.status_name, organisation.name as npo_name'); 
        $this->db->where('donation_applications.is_active', 1); 
        $this->db->from('donation_applications'); 
        $this->db->join('first_giving_application_status', 'first_giving_application_status.id = donation_applications.status'); 
        $this->db->join('organisation', 'organisation.id = donation_applications.ngo_id'); 
        if($query!='')
            $this->db->like('donation_applications.name', $query);
        $this->db->order_by("donation_applications.created_at", "desc");
        $this->db->limit($limit,$offset);
        $result = $this->db->get();
        return $result->result();
    }
    public function get_application_list_count($query, $status='')
    {
        $this->db->select('count(*) as num'); 
        $this->db->where('donation_applications.is_active', 1); 
        $this->db->from('donation_applications'); 
        if($query!='')
            $this->db->like('donation_applications.name', $query);
        $result = $this->db->get();
        return $result->row();
    }

    public function update_application($update, $application_id)
    {
        $this->db->update('donation_applications', $update, array('id' => $application_id));
        return;
    }

    public function update_ngo($update, $id)
    {
        $this->db->where('id', $id);
        $this->db->update('organisation', $update);
        return;
    }

    public function get_application_by_ngo($ngo_id)
    {
        $this->db->select('*');
        $this->db->from('donation_applications');
        $this->db->where('ngo_id', $ngo_id);
        $this->db->where('is_active', 1);
        $result = $this->db->get();
        return $result->row();
    }

    public function get_sendgrid_template_id($where)
    {
        $this->db->select('*');
        $this->db->from('sendgrid_template_id');
        $this->db->where($where);
        $result = $this->db->get();
        return $result->row();
    }

    public function store_email_notification_log($insert)
    {
        $this->db->insert('mail_logs', $insert); 
        return $this->db->insert_id();
    }

    public function get_superadmin_email_settings()
    {
        $this->db->select('*');
        $this->db->from('email_settings');
        $this->db->where('user_id', 1);
        $result = $this->db->get();
        return $result->row();
    }

    public function store_transaction_log($insert)
    {
        $this->db->insert('donor_transactions', $insert); 
        return $this->db->insert_id();
    }

    public function get_donation_transaction($where)
    {
        $this->db->select('donor_transactions.*, organisation.name as ngo_name');
        $this->db->from('donor_transactions');
        $this->db->join('organisation', 'organisation.id = donor_transactions.ngo_id');
        $this->db->where($where);
        $result = $this->db->get();
        return $result->row();
    }

    public function get_donation_transactions_list($offset, $limit, $refund_status, $ngo_id, $amount, $date, $search)
    {
        $this->db->select('donor_transactions.*'); 
        $this->db->from('donor_transactions'); 
        if($refund_status!='')
        {
            $refund_status = ($refund_status=='true')?1:0;
            $this->db->where('donor_transactions.refund_status', $refund_status);
        }
        if($ngo_id!='')
            $this->db->where('donor_transactions.ngo_id', $ngo_id);
        if($search!='')
            $this->db->like('donor_transactions.donor_name', $search);
        if($amount!='')
        {
            $amount = number_format($amount, 2);
            $this->db->where('donor_transactions.amount', $amount);
        }
        if($date!='')
            $this->db->where("donor_transactions.transaction_datetime LIKE '%$date%'");

        $this->db->order_by("donor_transactions.transaction_datetime", "desc");
        $this->db->limit($limit,$offset);

        $result = $this->db->get();
        return $result->result();
    }

    public function get_donation_transactions_count($refund_status, $ngo_id, $amount, $date, $search)
    {
        $this->db->select('count(*) as num'); 
        $this->db->from('donor_transactions'); 
        if($refund_status!='')
        {
            $refund_status = ($refund_status=='true')?1:0;
            $this->db->where('donor_transactions.refund_status', $refund_status);
        }
        if($ngo_id!='')
            $this->db->where('donor_transactions.ngo_id', $ngo_id);
        if($search!='')
            $this->db->like('donor_transactions.donor_name', $search);
        if($amount!='')
        {
            $amount = number_format($amount, 2);
            $this->db->where('donor_transactions.amount', $amount);
        }
        if($date!='')
            $this->db->where("donor_transactions.transaction_datetime LIKE '%$date%'");
        
        $result = $this->db->get();
        return $result->row();
    }

    public function update_donation_transaction($update, $id)
    {
        $this->db->update('donor_transactions', $update, array('id' => $id));
        return;
    }

    public function update_donation_transaction_by_transaction_id($update, $where)
    {
        $this->db->update('donor_transactions', $update, $where);
        return;
    }

    public function get_ngo_list_for_refund($offset, $limit, $search)
    {
        $this->db->distinct();
        $this->db->select('organisation.id, organisation.name');
        $this->db->join('organisation', 'organisation.id = donor_transactions.ngo_id'); 
        $this->db->from('donor_transactions');
        if($search!='')
            $this->db->like('organisation.name', $search);  

        $this->db->order_by("organisation.id", "asc");
        $this->db->limit($limit,$offset);
        $result = $this->db->get();
        return $result->result();
    }

    public function get_ngo_list_for_refund_count($search)
    {
        $this->db->distinct();
        $this->db->select('organisation.id');
        $this->db->join('organisation', 'organisation.id = donor_transactions.ngo_id'); 
        $this->db->from('donor_transactions');
        if($search!='')
            $this->db->like('organisation.name', $search);  
        
        $result = $this->db->get();
        return $result->num_rows();
    }

    public function get_transaction_between_dates($first_date, $second_date)
    {
        $this->db->select('donor_transactions.*, organisation.name as ngo_name');
        $this->db->from('donor_transactions');
        $this->db->join('organisation', 'organisation.id = donor_transactions.ngo_id'); 
        $this->db->where('donor_transactions.transaction_datetime >=', date('m/d/Y', strtotime($first_date)));
        $this->db->where('donor_transactions.transaction_datetime <', date('m/d/Y', strtotime($second_date)));
        
        $result = $this->db->get(); 
        return $result->result();
    }
}