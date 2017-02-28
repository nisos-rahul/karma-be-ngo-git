<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';

class SendGrid extends Rest 
{
    public function __construct()
    {
        parent::__construct();
    }
    public function list_outcomes($project_id)
    {
        
    }
}