<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
include APPPATH.'controllers/rest.php';
require APPPATH.'libraries/PHPExcel_1.8.0_doc/Classes/PHPExcel.php';
require APPPATH.'libraries/PHPExcel_1.8.0_doc/Classes/PHPExcel/Writer/Excel2007.php';
require APPPATH.'libraries/aws-autoloader.php';

use Aws\S3\S3Client;

class CorporateDonate extends Rest 
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Corporate_donate_model');
    }

    public function add_corporate_donate_details()
    {

        $jsonArray = json_decode(file_get_contents('php://input'),true);
        $insert['email'] = isset($jsonArray['email'])?$jsonArray['email']:'';
        $insert['first_name'] = isset($jsonArray['firstName'])?$jsonArray['firstName']:'';
        $insert['last_name'] = isset($jsonArray['lastName'])?$jsonArray['lastName']:'';
        $insert['corporate_name'] = isset($jsonArray['corporateName'])?$jsonArray['corporateName']:'';
        $insert['amount'] = isset($jsonArray['amount'])?$jsonArray['amount']:'';
        $insert['ngo_id'] = isset($jsonArray['ngoId'])?$jsonArray['ngoId']:'';
        $insert['willing_to_partner'] = isset($jsonArray['willingToPartner'])?$jsonArray['willingToPartner']:'';
        $insert['date'] = date('d M Y');
        $insert['date_created'] = date('Y-m-d H:i:s');
        $insert['last_updated'] = date('Y-m-d H:i:s');
        $insert['is_submitted'] = false;
        $projects = isset($jsonArray['projects'])?$jsonArray['projects']:'';

        $insert = $this->security->xss_clean($insert);
        $projects = $this->security->xss_clean($projects);

        $corporate_donate_id = $this->Corporate_donate_model->add_corporate_donate_details($insert);

        if(!empty($projects))
        {
            foreach($projects as $project)
            {
                $insert_project['corporate_donor_id'] = $corporate_donate_id;
                $insert_project['project_id'] = $project['id'];
                $this->Corporate_donate_model->insert_projects($insert_project);
            }
        }

        $data = $this->corporate_donate_deatails($corporate_donate_id);
        echo json_encode($data, JSON_NUMERIC_CHECK);
        return;
    }

    public function corporate_deatails($id)
    {
        $data = $this->corporate_donate_deatails($id);
        echo json_encode($data,JSON_NUMERIC_CHECK);
        return;
    }

    public function corporate_donate_deatails($id)
    {
        $corporate_donate_data = $this->Corporate_donate_model->corporate_donate_details($id);

        if(empty($corporate_donate_data))
        {
            $data['error'] = true;
            $data['status'] = 404;
            $data['message'] = 'Corporate donor not found.';
            header('HTTP/1.1 404 Not Found');
            return $data;
        }

        $data['error'] = false;
        $data['resp']['email'] = $corporate_donate_data->email;
        $data['resp']['firstName'] = $corporate_donate_data->first_name;
        $data['resp']['lastName'] = $corporate_donate_data->last_name;
        $data['resp']['corporateName'] = $corporate_donate_data->corporate_name;
        $data['resp']['amount'] = $corporate_donate_data->amount;
        $data['resp']['ngoId'] = $corporate_donate_data->ngo_id;
        $data['resp']['willingToPartner'] = $corporate_donate_data->willing_to_partner;

        $projects = $this->Corporate_donate_model->get_corporate_donate_projects($id);
        $i = 0;
        $project_data = array();
        if(!empty($projects))
        {
            foreach($projects as $project)
            {
                $project_data[$i]['id'] = $project->project_id;
                $i++;
            }
        }
        $data['resp']['projects'] = $project_data;

        return $data;
    }

    public function export_excel()
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

        $objPHPExcel = new PHPExcel();

        $count = $this->Corporate_donate_model->get_all_donate_contact_count();
        if($count==0)
        {
            $data['error'] = true;
            $data['status'] = 400;
            $data['message'] = 'No records were found. Thus, no report will be generated.';
            header('HTTP/1.1 400 No records were found.');
            echo json_encode($data,JSON_NUMERIC_CHECK);
            return;
        }

        $organisations = $this->Corporate_donate_model->get_active_organisations();

        $rowCount = 1;
        if(!empty($organisations))
        {
            foreach ($organisations as $organisation_data)
            {
                $ngo_id = $organisation_data->id;
                $table_data = $this->Corporate_donate_model->get_corporate_donate_details_by_organisation($ngo_id);

                if(!empty($table_data))
                {
                    $objPHPExcel->getActiveSheet()->mergeCells("A".($rowCount).":I".($rowCount));
                    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $organisation_data->name);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$rowCount)->applyFromArray(
                        array(
                            'fill' => array(
                                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                                'color' => array('rgb' => 'ff9900')
                            )
                        )
                    );

                    $objPHPExcel->getActiveSheet()->getRowDimension($rowCount)->setRowHeight(20);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$rowCount)
                    ->getAlignment()
                    ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $rowCount = $rowCount+2;
                    $start = $rowCount;
                    $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, 'Email');
                    $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, 'First Name');
                    $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, 'Last Name');
                    $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, 'Corporate Name');
                    $objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, 'Projects');
                    $objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, 'Amount');
                    $objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, 'Willing to partner with another entiity');
                    $objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, 'Date');
                    $objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, 'Submitted');

                    $rowCount = $rowCount+1;
                    foreach ($table_data as $key => $value)
                    {
                        $projects_data = $this->Corporate_donate_model->get_corporate_donate_project_names($value->id);
                        $i=0;
                        $projects = array();
                        if(!empty($projects_data))
                        {
                            foreach ($projects_data as $project) {
                                $projects[$i] = $project->title;
                                $i++;
                            }
                        }
                        $projects = implode (", ", $projects);

                        $objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $value->email);
                        $objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $value->first_name);
                        $objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $value->last_name);
                        $objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $value->corporate_name);
                        $objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, $projects);
                        $objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $value->amount);
                        $willing_to_partner_status = ($value->willing_to_partner==1)?'YES':'NO';
                        $objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, $willing_to_partner_status);
                        $objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, $value->date);
                        $is_submitted = ($value->is_submitted==1)?'YES':'NO';
                        $objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, $is_submitted);
                        $end = $rowCount;
                        $rowCount++;
                    }

                    $rowCount = $rowCount+2;

                    $styleArray = array(
                      'borders' => array(
                        'allborders' => array(
                          'style' => PHPExcel_Style_Border::BORDER_THIN
                        )
                      )
                    );

                    $objPHPExcel->getActiveSheet()->getStyle('A'.$start.':I'.$end)->applyFromArray($styleArray);
                }
            }

            $objPHPExcel->getActiveSheet()->getStyle('E1:E'.$rowCount)
                ->getAlignment()->setWrapText(true);

            foreach(range('A','D') as $columnID) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
            }

            foreach(range('F','I') as $columnID) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
            }

            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(50);
            $objPHPExcel->getActiveSheet()->getStyle('E1:E'.$rowCount)
                ->getAlignment()->setWrapText(true);

            $objPHPExcel->getActiveSheet()
            ->getStyle( $objPHPExcel->getActiveSheet()->calculateWorksheetDimension() )
            ->getAlignment()
            ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $this->Corporate_donate_model->set_is_submitted_to_true();


            $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
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

            ob_start();
            $objWriter->save('php://output');
            $excelOutput = ob_get_clean();

            $key = 'Corporate_donate_interest/Corporate_donate_interest_'.date("Y-m-d_H:i:s").'.xlsx';
            $res = $s3->putObject([
                'Bucket' => $bucket,
                'Key'    => $key,
                'Body'   => $excelOutput
            ]);
            $link = 'https://'.$bucket.'.s3.amazonaws.com/'.$key;

            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => $key
            ]);

            $request = $s3->createPresignedRequest($cmd, '+10 minutes');

            $presignedUrl = (string) $request->getUri();

            $data['error'] = false;
            $data['url'] = $presignedUrl;
            echo json_encode($data);
            return;
        }
    }
}
