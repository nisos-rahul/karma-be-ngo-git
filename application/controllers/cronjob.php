<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// include APPPATH.'controllers/rest.php';
require APPPATH.'libraries/PHPExcel_1.8.0_doc/Classes/PHPExcel.php';
require APPPATH.'libraries/PHPExcel_1.8.0_doc/Classes/PHPExcel/Writer/Excel2007.php';
require APPPATH.'libraries/aws-autoloader.php';

class Cronjob extends CI_Controller 
{
    function __construct()
    {
        parent::__construct();     
        $this->load->model('Ngo_model');
        $this->load->model('Project_model');
        $this->load->model('Activity_model');
        $this->load->model('Firstgiving_model');
    }

    function nightlyreport()
    {
        if (!$this->input->is_cli_request()) show_error('Direct access is not allowed');
        
        $ngo_list = $this->Ngo_model->get_organisation_list(array('is_active'=>1,'is_archive'=>0,'is_deleted'=>0));
        if(empty($ngo_list))
        {
            $objPHPExcel = new PHPExcel();
            $objWorkSheet = $objPHPExcel->createSheet(0);
            $objWorkSheet->setTitle('Nightly Report');

            $rowCount = 2;

            $objWorkSheet->mergeCells("B".($rowCount).":F".($rowCount));
            $objWorkSheet->SetCellValue('B'.$rowCount, 'Npo not found.');

            $objWorkSheet->getRowDimension($rowCount)->setRowHeight(20);
            $objWorkSheet->getStyle('B'.$rowCount)
            ->getAlignment()
            ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        else
        {
            $data1 = array();
            $i = 0;
            foreach($ngo_list as $ngo)
            {
                $total = $this->Project_model->get_total_active_beneficiaries($ngo->id);

                $data1[$i]['id'] = $ngo->id;
                $data1[$i]['name'] = $ngo->name;
                $data1[$i]['target_beneficiaries'] = (int)$total->no_of_benefeciaries;
                $i++;
            }
            $final_data['target_beneficiaries_by_ngo'] = $data1;
            
            // -----------------------------------------------------------
            $data2 = array();
            $j = 0;
            foreach($ngo_list as $ngo)
            {
                $project_data = array();
                $project_list = $this->Project_model->get_project_list(array('ngo_id'=>$ngo->id, 'is_active'=>1));

                if(!empty($project_list))
                {
                    $k=0;
                    foreach($project_list as $project)
                    {
                        $outcome_list = $this->Project_model->get_project_outcomes($project->id);
                        if(!empty($outcome_list))
                        {
                            $div = array();
                            foreach($outcome_list as $key1 => $outcome)
                            {
                                $outcome_goal = (int)$outcome->goal_target;
                                $outcome_current = (int)$outcome->goal_achieved;
                                if($outcome_goal==0)
                                    $div[$key1] = 0;
                                else
                                    $div[$key1] = $outcome_current / $outcome_goal;
                            }
                            $sum = array_sum($div)/count($outcome_list)*100;
                            $sum = round($sum);

                            $project_data[$k]['id'] = $project->id;
                            $project_data[$k]['title'] = $project->title;
                            $project_data[$k]['percentage_complete'] = $sum;
                            $k++;
                        }
                    }
                }

                $ngo_data = array();
                $ngo_data['id'] = $ngo->id;
                $ngo_data['name'] = $ngo->name;
                $ngo_data['project'] = $project_data;

                $data2['ngo'][$j] = $ngo_data;
                $j++;
            }
            $final_data['ngo_projects_with_percentage_complete'] = $data2;

            // -----------------------------------------------------------
            $data3 = array();
            $j = 0;
            foreach($ngo_list as $ngo)
            {
                $project_list = $this->Project_model->get_project_list(array('ngo_id'=>$ngo->id, 'is_active'=>1));
                $total_update_count = 0;
                if(!empty($project_list))
                {
                    $k=0;
                    foreach($project_list as $project)
                    {
                        $outcome_count = $this->Activity_model->project_activity_count($project->id);
                        $total_update_count = $total_update_count + $outcome_count;
                    }
                } 
                $data3['ngo'][$j]['id'] = $ngo->id;
                $data3['ngo'][$j]['name'] = $ngo->name;
                $data3['ngo'][$j]['update_count'] = $total_update_count;
                $j++;
            }
            $final_data['ngo_list_with_update_count'] = $data3;

            // -----------------------------------------------------------
            $data4 = array();
            $current_date = date('Y-m-d');
            $day = date("d", strtotime($current_date));

            if($day>=1 && $day<15)
            {
                $first_date = $first_week_start = date("Y-n-15", strtotime("last day of previous month"));
                $first_week_end = date("Y-n-21", strtotime("last day of previous month"));
                $second_date = $second_week_start = date('Y-n-22', strtotime("last day of previous month"));
                $second_week_end = date('Y-n-j', strtotime("last day of previous month"));
                $third_date = date('Y-m-01');
            }
            else
            {
                $first_date  = $first_week_start = date('Y-m-01');
                $first_week_end = date('Y-m-07');
                $second_date = $second_week_start = date('Y-m-08');
                $second_week_end = date('Y-m-14');
                $third_date = date('Y-m-15');
            }

            // $first_date  = $first_week_start = '2016-11-01';
            // $first_week_end = '2016-11-07';
            // $second_date = $second_week_start = '2016-11-08';
            // $second_week_end = '2016-11-14';
            // $third_date = '2016-11-15';

            // $first_week_duration_statement = 'Update Count for Duration '.$first_week_start.' to '.$first_week_end;
            // $second_week_duration_statement = 'Update Count for Duration '.$second_week_start.' to '.$second_week_end;

            $first_week_duration_statement = 'Update Count for Duration '.date('m/d/Y', strtotime($first_week_start)).' to '.date('m/d/Y', strtotime($first_week_end));
            $second_week_duration_statement = 'Update Count for Duration '.date('m/d/Y', strtotime($second_week_start)).' to '.date('m/d/Y', strtotime($second_week_end));

            $j = 0;
            $total_weekly_count = 0; 
            $ngo_data = array();
            foreach($ngo_list as $ngo)
            {
                $project_list = $this->Project_model->get_project_list(array('ngo_id'=>$ngo->id, 'is_active'=>1));
                $total_update_count = 0;
                if(!empty($project_list))
                {
                    $k=0;
                    foreach($project_list as $project)
                    {
                        $outcome_count = $this->Activity_model->project_activity_count_between_dates($project->id, $first_date, $second_date);
                        $total_update_count = $total_update_count + $outcome_count;
                        $total_weekly_count = $total_weekly_count + $outcome_count;
                    }
                } 
                $ngo_data[$j]['id'] = $ngo->id;
                $ngo_data[$j]['name'] = $ngo->name;
                $ngo_data[$j]['update_count'] = $total_update_count;
                $j++;
            }
            $data4[$first_week_duration_statement]['total_count'] = $total_weekly_count;
            $data4[$first_week_duration_statement]['ngo'] = $ngo_data;


            $j = 0;
            $total_weekly_count = 0; 
            $ngo_data = array();
            foreach($ngo_list as $ngo)
            {
                $project_list = $this->Project_model->get_project_list(array('ngo_id'=>$ngo->id, 'is_active'=>1));
                
                $total_update_count = 0;
                if(!empty($project_list))
                {
                    $k=0;
                    foreach($project_list as $project)
                    {
                        $outcome_count = $this->Activity_model->project_activity_count_between_dates($project->id, $second_date, $third_date);
                        $total_update_count = $total_update_count + $outcome_count;
                        $total_weekly_count = $total_weekly_count + $outcome_count;
                    }
                } 
                $ngo_data[$j]['id'] = $ngo->id;
                $ngo_data[$j]['name'] = $ngo->name;
                $ngo_data[$j]['update_count'] = $total_update_count;
                $j++;
            }
            $data4[$second_week_duration_statement]['total_count'] = $total_weekly_count;
            $data4[$second_week_duration_statement]['ngo'] = $ngo_data;
            
            $final_data['weekly_update_count'] = $data4;

            //-------------------------------------------------

            $data5 = array();
            $donation_transaction_list = $this->Firstgiving_model->get_transaction_between_dates($first_week_start, $third_date);
            if(!empty($donation_transaction_list))
            {
                foreach ($donation_transaction_list as $key =>$value) {

                    $data5[$key]['ngo_id'] = $value->ngo_id;
                    $data5[$key]['ngo_name'] = $value->ngo_name;
                    $data5[$key]['donor_name'] = $value->donor_name;
                    $data5[$key]['donor_email'] = $value->donor_email;
                    $data5[$key]['donation_amount'] = $value->amount;
                    $data5[$key]['donation_date'] = $value->transaction_datetime;
                }
            }
            $final_data['donation_transaction_list'] = $data5;

            //-------------------------------------------------

            $objPHPExcel = new PHPExcel();
            
            if(!empty($final_data['target_beneficiaries_by_ngo']))
            {
                $objWorkSheet = $objPHPExcel->createSheet(0);
                $objWorkSheet->setTitle('Target Beneficiaries');

                $rowCount = $start = 2;
                $objWorkSheet->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet->SetCellValue('D'.$rowCount, 'Target Beneficiaries');

                $objWorkSheet->getStyle('B'.$rowCount.':D'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet->getStyle('B'.$rowCount.':D'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $target_beneficiaries_array = $final_data['target_beneficiaries_by_ngo'];
                foreach($target_beneficiaries_array as $value1)
                {
                    $objWorkSheet->SetCellValue('B'.$rowCount, $value1['id']);
                    $objWorkSheet->SetCellValue('C'.$rowCount, $value1['name']);
                    $objWorkSheet->SetCellValue('D'.$rowCount, $value1['target_beneficiaries']);
                    $rowCount++;
                }
                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-1;
                $objWorkSheet->getStyle('B'.$start.':D'.$end)->applyFromArray($styleArray);

                foreach(range('B','D') as $columnID) {
                    $objWorkSheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                $objWorkSheet->getStyle( $objPHPExcel->getActiveSheet()->calculateWorksheetDimension() )
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }

            //--------------------------------------

            if(!empty($final_data['ngo_projects_with_percentage_complete']))
            {
                $objWorkSheet2 = $objPHPExcel->createSheet(1);
                $objWorkSheet2->setTitle('Projects Percentage');

                $rowCount = $start = 2;
                $objWorkSheet2->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet2->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet2->SetCellValue('D'.$rowCount, 'Project Id');
                $objWorkSheet2->SetCellValue('E'.$rowCount, 'Project Title');
                $objWorkSheet2->SetCellValue('F'.$rowCount, 'Percentage Complete');

                $objWorkSheet2->getStyle('B'.$rowCount.':F'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet2->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet2->getStyle('B'.$rowCount.':F'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $percentage_complete_array = $final_data['ngo_projects_with_percentage_complete']['ngo'];
                foreach($percentage_complete_array as $value2)
                {
                    $projects_array = $value2['project'];
                    if(!empty($projects_array))
                    {
                        foreach ($projects_array as$value3) 
                        {
                            $objWorkSheet2->SetCellValue('B'.$rowCount, $value2['id']);
                            $objWorkSheet2->SetCellValue('C'.$rowCount, $value2['name']);
                            $objWorkSheet2->SetCellValue('D'.$rowCount, $value3['id']);
                            $objWorkSheet2->SetCellValue('E'.$rowCount, $value3['title']);
                            $objWorkSheet2->SetCellValue('F'.$rowCount, $value3['percentage_complete']);
                            $rowCount++;
                        }
                    }
                }
                $rowCount = $rowCount + 1; 
                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-2;
                $objWorkSheet2->getStyle('B'.$start.':F'.$end)->applyFromArray($styleArray);

                foreach(range('B','F') as $columnID) {
                    $objWorkSheet2->getColumnDimension($columnID)->setAutoSize(true);
                }

                $objWorkSheet2->getStyle( $objPHPExcel->getActiveSheet()->calculateWorksheetDimension() )
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }

            //--------------------------------------

            if(!empty($final_data['ngo_list_with_update_count']))
            {
                $objWorkSheet3 = $objPHPExcel->createSheet(2);
                $objWorkSheet3->setTitle('Ngo List with Update Count');

                $rowCount = $start = 2;
                $objWorkSheet3->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet3->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet3->SetCellValue('D'.$rowCount, 'Update Count');

                $objWorkSheet3->getStyle('B'.$rowCount.':D'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet3->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet3->getStyle('B'.$rowCount.':D'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $update_count_array = $final_data['ngo_list_with_update_count']['ngo'];
                foreach($update_count_array as $value4)
                {
                    $objWorkSheet3->SetCellValue('B'.$rowCount, $value4['id']);
                    $objWorkSheet3->SetCellValue('C'.$rowCount, $value4['name']);
                    $objWorkSheet3->SetCellValue('D'.$rowCount, $value4['update_count']);
                    $rowCount++;
                }

                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-1;
                $objWorkSheet3->getStyle('B'.$start.':D'.$end)->applyFromArray($styleArray);

                foreach(range('B','D') as $columnID) {
                    $objWorkSheet3->getColumnDimension($columnID)->setAutoSize(true);
                }

                $objWorkSheet3->getStyle( $objWorkSheet3->calculateWorksheetDimension() )
                    ->getAlignment()
                    ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }

            //--------------------------------------

            if(!empty($final_data['weekly_update_count']))
            {
                $objWorkSheet4 = $objPHPExcel->createSheet(3);
                $objWorkSheet4->setTitle('Weekly Update Count');

                $rowCount = $start = 2;
                $objWorkSheet4->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet4->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet4->SetCellValue('D'.$rowCount, $first_week_duration_statement);
                $objWorkSheet4->SetCellValue('E'.$rowCount, $second_week_duration_statement);

                $objWorkSheet4->getStyle('B'.$rowCount.':E'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet4->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet4->getStyle('B'.$rowCount.':E'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $update_count_array1 = $final_data['weekly_update_count'][$first_week_duration_statement]['ngo'];
                $update_count_array2 = $final_data['weekly_update_count'][$second_week_duration_statement]['ngo'];
                foreach($update_count_array1 as $key => $value5)
                {
                    $objWorkSheet4->SetCellValue('B'.$rowCount, $value5['id']);
                    $objWorkSheet4->SetCellValue('C'.$rowCount, $value5['name']);
                    $objWorkSheet4->SetCellValue('D'.$rowCount, $value5['update_count']);
                    $objWorkSheet4->SetCellValue('E'.$rowCount, $update_count_array2[$key]['update_count']);
                    $rowCount++;
                }

                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-1;
                $objWorkSheet4->getStyle('B'.$start.':E'.$end)->applyFromArray($styleArray);

                foreach(range('B','E') as $columnID) {
                    $objWorkSheet4->getColumnDimension($columnID)->setAutoSize(true);
                }

                $objWorkSheet4->getStyle( $objWorkSheet4->calculateWorksheetDimension() )
                    ->getAlignment()
                    ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }

            //--------------------------------------------

            $objWorkSheet5 = $objPHPExcel->createSheet(4);
            $objWorkSheet5->setTitle('Donation Transaction List');

            $rowCount = 2;

            $objWorkSheet5->mergeCells("B".($rowCount).":G".($rowCount));
            $objWorkSheet5->SetCellValue('B'.$rowCount, 'Donations for Duration '.date('m/d/Y', strtotime($first_week_start)).' to '.date('m/d/Y', strtotime($second_week_end)));

            $objWorkSheet5->getRowDimension($rowCount)->setRowHeight(20);
            $objWorkSheet5->getStyle('B'.$rowCount)
            ->getAlignment()
            ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            if(!empty($final_data['donation_transaction_list']))
            {
                $rowCount = $start = $rowCount + 2;
                $objWorkSheet5->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet5->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet5->SetCellValue('D'.$rowCount, 'Donor Name');
                $objWorkSheet5->SetCellValue('E'.$rowCount, 'Donor Email');
                $objWorkSheet5->SetCellValue('F'.$rowCount, 'Donor Amount');
                $objWorkSheet5->SetCellValue('G'.$rowCount, 'Donation Date');

                $objWorkSheet5->getStyle('B'.$rowCount.':G'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet5->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet5->getStyle('B'.$rowCount.':G'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $donation_array = $final_data['donation_transaction_list'];
                foreach($donation_array as $value5)
                {
                    $donation_date = $value5['donation_date'];
                    $objWorkSheet5->SetCellValue('B'.$rowCount, $value5['ngo_id']);
                    $objWorkSheet5->SetCellValue('C'.$rowCount, $value5['ngo_name']);
                    $objWorkSheet5->SetCellValue('D'.$rowCount, $value5['donor_name']);
                    $objWorkSheet5->SetCellValue('E'.$rowCount, $value5['donor_email']);
                    $objWorkSheet5->SetCellValue('F'.$rowCount, $value5['donation_amount']);
                    $objWorkSheet5->SetCellValue('G'.$rowCount, date('m/d/Y', strtotime($donation_date)));
                    $rowCount++;
                }

                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-1;
                $objWorkSheet5->getStyle('B'.$start.':G'.$end)->applyFromArray($styleArray);

                foreach(range('B','G') as $columnID) {
                    $objWorkSheet5->getColumnDimension($columnID)->setAutoSize(true);
                }
            }
            else
            {
                $rowCount = $rowCount + 2;
                $objWorkSheet5->mergeCells("B".($rowCount).":G".($rowCount));
                $objWorkSheet5->SetCellValue('B'.$rowCount, 'Records not found.');
            }
            $objWorkSheet5->getStyle( $objWorkSheet5->calculateWorksheetDimension() )
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        //--------------------------------------

        // header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment;filename="01simple.xlsx"');
        // header('Cache-Control: max-age=0');
        // header('Cache-Control: max-age=1');

        // header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        // header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        // header ('Cache-Control: cache, must-revalidate');
        // header ('Pragma: public');

        $server_box = $this->config->item('server_box');

        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

        ob_start();
        $objWriter->save('php://output');
        $excelOutput = ob_get_clean();

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

        $s3->registerStreamWrapper();
        $bucket = $this->config->item('bucket_name', 's3');

        $key = 'Nightly_email_reports/report_for_'.$server_box.'_server_on_'.date("Y-m-d").'.xlsx';
        $res = $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'Body'   => $excelOutput
        ]);
        $link = 'https://'.$bucket.'.s3.amazonaws.com/'.$key;

        //-------------------------------------------------------------

        $from_address = $this->config->item('from_email');
        $nightly_to_emails = $this->config->item('nightly_to_emails');
        $to_email_array = explode(',', $nightly_to_emails);

        $this->config->load('sendgrid', true);
        $sendgrid_api_key = $this->config->item('sendgrid_api_key', 'sendgrid');
        $content_msg = "Hi,<br>Please download fortnightly report from following link,<br><a href='$link'>Download Report</a>";

        $url = "https://api.sendgrid.com/v3/mail/send";

        foreach ($to_email_array as $value) {
            $data_string = '{"personalizations":[{"to":[{"email": "'.$value.'"}]}],"from":{"email": "'.$from_address.'"},"subject": "Nightly report for '.$server_box.' server","content": [{"type": "text/html", "value": "'.$content_msg.'"}]}';

            $ch = curl_init();
            $ret = curl_setopt($ch, CURLOPT_URL, $url);
            $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            $ret = curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $sendgrid_api_key,
                'Content-Type: application/json'
            ));
            $ret = curl_exec($ch);
            $info = curl_getinfo($ch);
        }
    }

    function custom_nightlyreport()
    {
        // if (!$this->input->is_cli_request()) show_error('Direct access is not allowed');

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
        if($role_id!=1)
        {
            $data['error'] = true;
            $data['status'] = 401;
            $data['message'] = "Unauthorized User.";
            header('HTTP/1.1 401 Unauthorized User');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            exit;
        }

        $ngo_list = $this->Ngo_model->get_organisation_list(array('is_active'=>1,'is_archive'=>0,'is_deleted'=>0));
        if(empty($ngo_list))
        {
            $objPHPExcel = new PHPExcel();
            $objWorkSheet = $objPHPExcel->createSheet(0);
            $objWorkSheet->setTitle('Nightly Report');

            $rowCount = 2;

            $objWorkSheet->mergeCells("B".($rowCount).":F".($rowCount));
            $objWorkSheet->SetCellValue('B'.$rowCount, 'Npo not found.');

            $objWorkSheet->getRowDimension($rowCount)->setRowHeight(20);
            $objWorkSheet->getStyle('B'.$rowCount)
            ->getAlignment()
            ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        }
        else
        {
            $data1 = array();
            $i = 0;
            foreach($ngo_list as $ngo)
            {
                $total = $this->Project_model->get_total_active_beneficiaries($ngo->id);

                $data1[$i]['id'] = $ngo->id;
                $data1[$i]['name'] = $ngo->name;
                $data1[$i]['target_beneficiaries'] = (int)$total->no_of_benefeciaries;
                $i++;
            }
            $final_data['target_beneficiaries_by_ngo'] = $data1;
            
            // -----------------------------------------------------------
            $data2 = array();
            $j = 0;
            foreach($ngo_list as $ngo)
            {
                $project_data = array();
                $project_list = $this->Project_model->get_project_list(array('ngo_id'=>$ngo->id, 'is_active'=>1));

                if(!empty($project_list))
                {
                    $k=0;
                    foreach($project_list as $project)
                    {
                        $outcome_list = $this->Project_model->get_project_outcomes($project->id);
                        if(!empty($outcome_list))
                        {
                            $div = array();
                            foreach($outcome_list as $key1 => $outcome)
                            {
                                $outcome_goal = (int)$outcome->goal_target;
                                $outcome_current = (int)$outcome->goal_achieved;
                                if($outcome_goal==0)
                                    $div[$key1] = 0;
                                else
                                    $div[$key1] = $outcome_current / $outcome_goal;
                            }
                            $sum = array_sum($div)/count($outcome_list)*100;
                            $sum = round($sum);

                            $project_data[$k]['id'] = $project->id;
                            $project_data[$k]['title'] = $project->title;
                            $project_data[$k]['percentage_complete'] = $sum;
                            $k++;
                        }
                    }
                }

                $ngo_data = array();
                $ngo_data['id'] = $ngo->id;
                $ngo_data['name'] = $ngo->name;
                $ngo_data['project'] = $project_data;

                $data2['ngo'][$j] = $ngo_data;
                $j++;
            }
            $final_data['ngo_projects_with_percentage_complete'] = $data2;

            // -----------------------------------------------------------
            $data3 = array();
            $j = 0;
            foreach($ngo_list as $ngo)
            {
                $project_list = $this->Project_model->get_project_list(array('ngo_id'=>$ngo->id, 'is_active'=>1));
                $total_update_count = 0;
                if(!empty($project_list))
                {
                    $k=0;
                    foreach($project_list as $project)
                    {
                        $outcome_count = $this->Activity_model->project_activity_count($project->id);
                        $total_update_count = $total_update_count + $outcome_count;
                    }
                } 
                $data3['ngo'][$j]['id'] = $ngo->id;
                $data3['ngo'][$j]['name'] = $ngo->name;
                $data3['ngo'][$j]['update_count'] = $total_update_count;
                $j++;
            }
            $final_data['ngo_list_with_update_count'] = $data3;

            // -----------------------------------------------------------
            $data4 = array();
            // $current_date = date('Y-m-d');
            // $day = date("d", strtotime($current_date));

            // if($day>=1 && $day<15)
            // {
            //     $first_date = $first_week_start = date("Y-n-15", strtotime("last day of previous month"));
            //     $first_week_end = date("Y-n-21", strtotime("last day of previous month"));
            //     $second_date = $second_week_start = date('Y-n-22', strtotime("last day of previous month"));
            //     $second_week_end = date('Y-n-j', strtotime("last day of previous month"));
            //     $third_date = date('Y-m-01');
            // }
            // else
            // {
            //     $first_date  = $first_week_start = date('Y-m-01');
            //     $first_week_end = date('Y-m-07');
            //     $second_date = $second_week_start = date('Y-m-08');
            //     $second_week_end = date('Y-m-14');
            //     $third_date = date('Y-m-15');
            // }

            $first_date = '2016-09-01';
            $second_date = '2016-12-21';
            $third_date = '2016-12-22';

            $first_week_duration_statement = 'Update Count for Duration '.date('m/d/Y', strtotime($first_date)).' to '.date('m/d/Y', strtotime($second_date));

            $j = 0;
            $total_weekly_count = 0; 
            $ngo_data = array();
            foreach($ngo_list as $ngo)
            {
                $project_list = $this->Project_model->get_project_list(array('ngo_id'=>$ngo->id, 'is_active'=>1));
                $total_update_count = 0;
                if(!empty($project_list))
                {
                    $k=0;
                    foreach($project_list as $project)
                    {
                        $outcome_count = $this->Activity_model->project_activity_count_between_dates($project->id, $first_date, $third_date);
                        $total_update_count = $total_update_count + $outcome_count;
                        $total_weekly_count = $total_weekly_count + $outcome_count;
                    }
                } 
                $ngo_data[$j]['id'] = $ngo->id;
                $ngo_data[$j]['name'] = $ngo->name;
                $ngo_data[$j]['update_count'] = $total_update_count;
                $j++;
            }
            $data4[$first_week_duration_statement]['total_count'] = $total_weekly_count;
            $data4[$first_week_duration_statement]['ngo'] = $ngo_data;
            
            $final_data['weekly_update_count'] = $data4;

            //-------------------------------------------------

            $data5 = array();
            $donation_transaction_list = $this->Firstgiving_model->get_transaction_between_dates($first_date, $third_date);
            if(!empty($donation_transaction_list))
            {
                foreach ($donation_transaction_list as $key =>$value) {

                    $data5[$key]['ngo_id'] = $value->ngo_id;
                    $data5[$key]['ngo_name'] = $value->ngo_name;
                    $data5[$key]['donor_name'] = $value->donor_name;
                    $data5[$key]['donor_email'] = $value->donor_email;
                    $data5[$key]['donation_amount'] = $value->amount;
                    $data5[$key]['donation_date'] = $value->transaction_datetime;
                }
            }
            $final_data['donation_transaction_list'] = $data5;

            // var_dump($final_data['weekly_update_count']);
            // var_dump($final_data['donation_transaction_list']);
            // die();

            //-------------------------------------------------

            $objPHPExcel = new PHPExcel();
            
            if(!empty($final_data['target_beneficiaries_by_ngo']))
            {
                $objWorkSheet = $objPHPExcel->createSheet(0);
                $objWorkSheet->setTitle('Target Beneficiaries');

                $rowCount = $start = 2;
                $objWorkSheet->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet->SetCellValue('D'.$rowCount, 'Target Beneficiaries');

                $objWorkSheet->getStyle('B'.$rowCount.':D'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet->getStyle('B'.$rowCount.':D'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $target_beneficiaries_array = $final_data['target_beneficiaries_by_ngo'];
                foreach($target_beneficiaries_array as $value1)
                {
                    $objWorkSheet->SetCellValue('B'.$rowCount, $value1['id']);
                    $objWorkSheet->SetCellValue('C'.$rowCount, $value1['name']);
                    $objWorkSheet->SetCellValue('D'.$rowCount, $value1['target_beneficiaries']);
                    $rowCount++;
                }
                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-1;
                $objWorkSheet->getStyle('B'.$start.':D'.$end)->applyFromArray($styleArray);

                foreach(range('B','D') as $columnID) {
                    $objWorkSheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                $objWorkSheet->getStyle( $objPHPExcel->getActiveSheet()->calculateWorksheetDimension() )
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }

            //--------------------------------------

            if(!empty($final_data['ngo_projects_with_percentage_complete']))
            {
                $objWorkSheet2 = $objPHPExcel->createSheet(1);
                $objWorkSheet2->setTitle('Projects Percentage');

                $rowCount = $start = 2;
                $objWorkSheet2->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet2->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet2->SetCellValue('D'.$rowCount, 'Project Id');
                $objWorkSheet2->SetCellValue('E'.$rowCount, 'Project Title');
                $objWorkSheet2->SetCellValue('F'.$rowCount, 'Percentage Complete');

                $objWorkSheet2->getStyle('B'.$rowCount.':F'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet2->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet2->getStyle('B'.$rowCount.':F'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $percentage_complete_array = $final_data['ngo_projects_with_percentage_complete']['ngo'];
                foreach($percentage_complete_array as $value2)
                {
                    $projects_array = $value2['project'];
                    if(!empty($projects_array))
                    {
                        foreach ($projects_array as$value3) 
                        {
                            $objWorkSheet2->SetCellValue('B'.$rowCount, $value2['id']);
                            $objWorkSheet2->SetCellValue('C'.$rowCount, $value2['name']);
                            $objWorkSheet2->SetCellValue('D'.$rowCount, $value3['id']);
                            $objWorkSheet2->SetCellValue('E'.$rowCount, $value3['title']);
                            $objWorkSheet2->SetCellValue('F'.$rowCount, $value3['percentage_complete']);
                            $rowCount++;
                        }
                    }
                }
                $rowCount = $rowCount + 1; 
                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-2;
                $objWorkSheet2->getStyle('B'.$start.':F'.$end)->applyFromArray($styleArray);

                foreach(range('B','F') as $columnID) {
                    $objWorkSheet2->getColumnDimension($columnID)->setAutoSize(true);
                }

                $objWorkSheet2->getStyle( $objPHPExcel->getActiveSheet()->calculateWorksheetDimension() )
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }

            //--------------------------------------

            if(!empty($final_data['ngo_list_with_update_count']))
            {
                $objWorkSheet3 = $objPHPExcel->createSheet(2);
                $objWorkSheet3->setTitle('Ngo List with Update Count');

                $rowCount = $start = 2;
                $objWorkSheet3->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet3->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet3->SetCellValue('D'.$rowCount, 'Update Count');

                $objWorkSheet3->getStyle('B'.$rowCount.':D'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet3->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet3->getStyle('B'.$rowCount.':D'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $update_count_array = $final_data['ngo_list_with_update_count']['ngo'];
                foreach($update_count_array as $value4)
                {
                    $objWorkSheet3->SetCellValue('B'.$rowCount, $value4['id']);
                    $objWorkSheet3->SetCellValue('C'.$rowCount, $value4['name']);
                    $objWorkSheet3->SetCellValue('D'.$rowCount, $value4['update_count']);
                    $rowCount++;
                }

                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-1;
                $objWorkSheet3->getStyle('B'.$start.':D'.$end)->applyFromArray($styleArray);

                foreach(range('B','D') as $columnID) {
                    $objWorkSheet3->getColumnDimension($columnID)->setAutoSize(true);
                }

                $objWorkSheet3->getStyle( $objWorkSheet3->calculateWorksheetDimension() )
                    ->getAlignment()
                    ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }

            //--------------------------------------

            if(!empty($final_data['weekly_update_count']))
            {
                $objWorkSheet4 = $objPHPExcel->createSheet(3);
                $objWorkSheet4->setTitle('Weekly Update Count');

                $rowCount = $start = 2;
                $objWorkSheet4->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet4->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet4->SetCellValue('D'.$rowCount, $first_week_duration_statement);
                // $objWorkSheet4->SetCellValue('E'.$rowCount, $second_week_duration_statement);

                $objWorkSheet4->getStyle('B'.$rowCount.':D'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet4->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet4->getStyle('B'.$rowCount.':D'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $update_count_array1 = $final_data['weekly_update_count'][$first_week_duration_statement]['ngo'];
                // $update_count_array2 = $final_data['weekly_update_count'][$second_week_duration_statement]['ngo'];
                foreach($update_count_array1 as $key => $value5)
                {
                    $objWorkSheet4->SetCellValue('B'.$rowCount, $value5['id']);
                    $objWorkSheet4->SetCellValue('C'.$rowCount, $value5['name']);
                    $objWorkSheet4->SetCellValue('D'.$rowCount, $value5['update_count']);
                    // $objWorkSheet4->SetCellValue('E'.$rowCount, $update_count_array2[$key]['update_count']);
                    $rowCount++;
                }

                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-1;
                $objWorkSheet4->getStyle('B'.$start.':D'.$end)->applyFromArray($styleArray);

                foreach(range('B','D') as $columnID) {
                    $objWorkSheet4->getColumnDimension($columnID)->setAutoSize(true);
                }

                $objWorkSheet4->getStyle( $objWorkSheet4->calculateWorksheetDimension() )
                    ->getAlignment()
                    ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }

            //--------------------------------------------

            $objWorkSheet5 = $objPHPExcel->createSheet(4);
            $objWorkSheet5->setTitle('Donation Transaction List');

            $rowCount = 2;

            $objWorkSheet5->mergeCells("B".($rowCount).":G".($rowCount));
            $objWorkSheet5->SetCellValue('B'.$rowCount, 'Donations for Duration '.date('m/d/Y', strtotime($first_date)).' to '.date('m/d/Y', strtotime($second_date)));

            $objWorkSheet5->getRowDimension($rowCount)->setRowHeight(20);
            $objWorkSheet5->getStyle('B'.$rowCount)
            ->getAlignment()
            ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            if(!empty($final_data['donation_transaction_list']))
            {
                $rowCount = $start = $rowCount + 2;
                $objWorkSheet5->SetCellValue('B'.$rowCount, 'NPO Id');
                $objWorkSheet5->SetCellValue('C'.$rowCount, 'NPO Name');
                $objWorkSheet5->SetCellValue('D'.$rowCount, 'Donor Name');
                $objWorkSheet5->SetCellValue('E'.$rowCount, 'Donor Email');
                $objWorkSheet5->SetCellValue('F'.$rowCount, 'Donor Amount');
                $objWorkSheet5->SetCellValue('G'.$rowCount, 'Donation Date');

                $objWorkSheet5->getStyle('B'.$rowCount.':G'.$rowCount)->getFont()->setBold(true);
                $objWorkSheet5->getRowDimension($rowCount)->setRowHeight(20);
                $objWorkSheet5->getStyle('B'.$rowCount.':G'.$rowCount)
                        ->getAlignment()
                        ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                $rowCount = $rowCount + 2;
                $donation_array = $final_data['donation_transaction_list'];
                foreach($donation_array as $value5)
                {
                    $donation_date = $value5['donation_date'];
                    $objWorkSheet5->SetCellValue('B'.$rowCount, $value5['ngo_id']);
                    $objWorkSheet5->SetCellValue('C'.$rowCount, $value5['ngo_name']);
                    $objWorkSheet5->SetCellValue('D'.$rowCount, $value5['donor_name']);
                    $objWorkSheet5->SetCellValue('E'.$rowCount, $value5['donor_email']);
                    $objWorkSheet5->SetCellValue('F'.$rowCount, $value5['donation_amount']);
                    $objWorkSheet5->SetCellValue('G'.$rowCount, date('m/d/Y', strtotime($donation_date)));
                    $rowCount++;
                }

                $styleArray = array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                    )
                );
                $end = $rowCount-1;
                $objWorkSheet5->getStyle('B'.$start.':G'.$end)->applyFromArray($styleArray);

                foreach(range('B','G') as $columnID) {
                    $objWorkSheet5->getColumnDimension($columnID)->setAutoSize(true);
                }
            }
            else
            {
                $rowCount = $rowCount + 2;
                $objWorkSheet5->mergeCells("B".($rowCount).":G".($rowCount));
                $objWorkSheet5->SetCellValue('B'.$rowCount, 'Records not found.');
            }
            $objWorkSheet5->getStyle( $objWorkSheet5->calculateWorksheetDimension() )
                ->getAlignment()
                ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        //--------------------------------------

        // header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        // header('Content-Disposition: attachment;filename="01simple.xlsx"');
        // header('Cache-Control: max-age=0');
        // header('Cache-Control: max-age=1');

        // header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        // header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        // header ('Cache-Control: cache, must-revalidate');
        // header ('Pragma: public');

        $server_box = $this->config->item('server_box');

        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

        ob_start();
        $objWriter->save('php://output');
        $excelOutput = ob_get_clean();

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

        $s3->registerStreamWrapper();
        $bucket = $this->config->item('bucket_name', 's3');

        $key = 'Nightly_email_reports/report_for_'.$server_box.'_server_on_'.date("Y-m-d").'.xlsx';
        $res = $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $key,
            'Body'   => $excelOutput
        ]);
        $link = 'https://'.$bucket.'.s3.amazonaws.com/'.$key;

        //-------------------------------------------------------------

        $from_address = $this->config->item('from_email');
        $nightly_to_emails = $this->config->item('nightly_to_emails');
        $to_email_array = explode(',', $nightly_to_emails);

        $this->config->load('sendgrid', true);
        $sendgrid_api_key = $this->config->item('sendgrid_api_key', 'sendgrid');
        $content_msg = "Hi,<br>Please download fortnightly report from following link,<br><a href='$link'>Download Report</a>";

        $url = "https://api.sendgrid.com/v3/mail/send";

        foreach ($to_email_array as $value) {
            $data_string = '{"personalizations":[{"to":[{"email": "'.$value.'"}]}],"from":{"email": "'.$from_address.'"},"subject": "Nightly report for '.$server_box.' server","content": [{"type": "text/html", "value": "'.$content_msg.'"}]}';

            $ch = curl_init();
            $ret = curl_setopt($ch, CURLOPT_URL, $url);
            $ret = curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            $ret = curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            $ret = curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $sendgrid_api_key,
                'Content-Type: application/json'
            ));
            $ret = curl_exec($ch);
            $info = curl_getinfo($ch);
        }
    }
}