<?php
    header("Access-Control-Allow-Origin: *");
    
	require_once "functions.loader.php";
	date_default_timezone_set("Asia/Manila");
	
	$var = $_GET["var"];
	$param = explode(".", $var);
	$uid = $param[0];
	$token = $param[1];

    $objPHPExcel = new PHPExcel();

    $objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
    $objPHPExcel->getActiveSheet()->getProtection()->setPassword("686");
    $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
    $objPHPExcel->getActiveSheet()->getProtection()->setSort(true);
    $objPHPExcel->getActiveSheet()->getProtection()->setInsertRows(true);
    $objPHPExcel->getActiveSheet()->getProtection()->setFormatCells(true);

    $headerStyleArray = array(
        'borders' => array(
            'outline' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('argb' => 'FF000000'),
            ),
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        )
    );	
	
	/* Menu List */
	$empNo = "Employee No.";
	$empName = "Employee Name";
	$payPeriod = "Payroll Period";
	
	$basicSal = "Basic Salary";			
	$deminimis = "Deminimis";
	$eCola = "ECOLA";
	$daysPresent = "Days Present";	
	$adjAdd = "Adjustment (+)";
	$OT = "Overtime";
	$basicPay = "Basic Pay";
	
	$daysAbsent = "Days Absent";
	$tardy = "Tardiness";
	$adjLess = "Adjustment (-)";
	
	$sss = "SSS";
	$philhealth = "PhilHealth";
	$pagibig = "PAG-IBIG";
	$tax = "Withholding TAX";	

	#Loans
	$sssLoan = "SSS Loan";
	$hdmfLoan = "HDMF Loan";
	$companyLoan = "Company Loan";
	$cashAdvance = "Cash Advance"; // Added July 18, 2023 15:15

	$grossPay = "Gross Pay";
	$totalDeductions = "Total Deductions";
	
	$netIncome = "Net Income";
	/* Menu List */
	
	$period = read_timekeeping_log_file_by_uid($uid);	
	$title = "Payroll Period as of " . str_replace("From", "", $period->period);
	
    $objPHPExcel->getActiveSheet()->mergeCells("A1:V1");
    $objPHPExcel->getActiveSheet()->SetCellValue("A1", $title);
    $objPHPExcel->getActiveSheet()->getStyle("A1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle("A1")->applyFromArray(array("font" => array( "bold" => true)));
    $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setName("Verdana");
    $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(8);

    $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A2', $empNo)
                ->setCellValue('B2', $empName)
                ->setCellValue('C2', $basicSal)				
				->setCellValue('D2', $daysPresent)
				->setCellValue('E2', $daysAbsent)
				->setCellValue('F2', $basicPay)				
                ->setCellValue('G2', $deminimis)
                ->setCellValue('H2', $eCola)
                ->setCellValue('I2', $OT)
				->setCellValue('J2', $adjAdd)
				->setCellValue('K2', $grossPay)
                ->setCellValue('L2', $tardy)
                ->setCellValue('M2', $sss)
                ->setCellValue('N2', $philhealth)				
				->setCellValue('O2', $pagibig)
				->setCellValue('P2', $sssLoan)
				->setCellValue('Q2', $hdmfLoan)
				->setCellValue('R2', $cashAdvance)
				->setCellValue('S2', $companyLoan)
				->setCellValue('T2', $tax)
				->setCellValue('U2', $adjLess)
				->setCellValue('V2', $totalDeductions)
				->setCellValue('W2', $netIncome);


    $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(25);
    $objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("I")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("K")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("L")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("M")->setWidth(12);
    $objPHPExcel->getActiveSheet()->getColumnDimension("N")->setWidth(12);	
	$objPHPExcel->getActiveSheet()->getColumnDimension("O")->setWidth(12);
	$objPHPExcel->getActiveSheet()->getColumnDimension("P")->setWidth(12);
	$objPHPExcel->getActiveSheet()->getColumnDimension("Q")->setWidth(12);
	$objPHPExcel->getActiveSheet()->getColumnDimension("R")->setWidth(12);
	$objPHPExcel->getActiveSheet()->getColumnDimension("S")->setWidth(12);
	$objPHPExcel->getActiveSheet()->getColumnDimension("T")->setWidth(12);
	$objPHPExcel->getActiveSheet()->getColumnDimension("U")->setWidth(12);
	$objPHPExcel->getActiveSheet()->getColumnDimension("V")->setWidth(12);
	$objPHPExcel->getActiveSheet()->getColumnDimension("W")->setWidth(12);

    $sheet = $objPHPExcel->getActiveSheet(); 

    $header = "A2:W2";
    $sheet->getStyle($header)->getFont()->setName("Verdana");
    $sheet->getStyle($header)->getFont()->setSize(8);
    $sheet->getStyle($header)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
    $sheet->getStyle($header)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    $sheet->getStyle($header)->getFill()->getStartColor()->setRGB("2DA8EA");
    $sheet->getStyle($header)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
    $sheet->getStyle($header)->getBorders()->getAllBorders()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_BLACK));
    $sheet->getStyle($header)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);    

    $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('A1')->applyFromArray(array("font" => array( "bold" => true)));
    $sheet->getStyle('A1:W1')->getFont()->setSize(9);
    $sheet->getStyle('A1:W1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_WHITE);
    $sheet->getStyle('A1:W1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    $sheet->getStyle('A1:W1')->getFill()->getStartColor()->setRGB('2E45EA');
    
    foreach($objPHPExcel->getActiveSheet()->getRowDimensions() as $rd) { 
        $rd->setRowHeight(5); 
    }   
	
	$row = 3;
	
	$zero = "0.00";
	
	//$results = read_timekeeping_summary_by_uid($uid);	
	$results = read_payroll_summary_by_uid($uid);
	foreach($results as $result) {
		$emp_uid = $result->emp_uid;
		
		$name = utf8_decode(read_employee_lastname_by_uid($emp_uid));
		$emp_num = getEmployeeDetailsByUid($emp_uid);
		
		// 	id uid emp_uid period basic deminimis ecola ot tardiness sss philhealth pagibig tax adj_add adj_less net_amount date_created date_modified status
		
		$rowBasic = $result->basic;		
		$rowPresent = $result->days_present;
		$rowAbsent = $result->days_absent;
		$rowGross = $result->gross_regular_pay;		
		$rowDeminimis = $result->deminimis;
		$rowEcola = $result->ecola;
		$rowOT = $result->ot;
		$rowTardy = $result->tardiness;
		$rowSSS = $result->sss;
		$rowPhilHealth = $result->philhealth;
		$rowPagibig = $result->pagibig;

		#Loans
		$rowSSSLoans = $result->sss_loan;
		$rowPagibigLoans = $result->pagibig_loan;
		$rowCompanyLoans = $result->company_loan;
		$rowCashLoans = $result->cash_advanced;

		$rowTax = $result->tax;
		$rowAdjAdd = $result->adj_add;
		$rowAdjLess = $result->adj_less;
		$rowNetAmount = $result->net_amount;
		
		$get_salary = getSalaryByUid($emp_uid);
		$base_salary = $get_salary->base_salary;
		$pay_period = $get_salary->pay_period_name;
		
		# work_days_per_year work_hours_per_day work_hours_start work_hours_end break_hours 
		$work_policy = read_work_policy();
		$work_days_per_year = $work_policy->work_days_per_year;
		$work_hours_per_day = $work_policy->work_hours_per_day;
		
		# Daily Weekly Bi-Weekly Semi-Monthly Monthly Quarterly Semi-Annual Annual Fixed
		switch($pay_period) {
			case "Monthly":
				$basic = $base_salary;
				$daily = ($base_salary * 12) / $work_days_per_year;
				break;
			case "Daily":
				$basic = ($base_salary * $work_days_per_year) / 12;
				$daily = $base_salary;
				break;
			default:
				$basic = $base_salary;
				$daily = ($base_salary * 12) / $work_days_per_year;
				break;
		}
		
		$hourly = $daily / $work_hours_per_day;
		
		$objPHPExcel->getActiveSheet()->setCellValue("A" . $row, $emp_num->username);
		$objPHPExcel->getActiveSheet()->getStyle("A" . $row)->getNumberFormat()->setFormatCode("0000");
		$objPHPExcel->getActiveSheet()->setCellValue("B" . $row, $name);
		$objPHPExcel->getActiveSheet()->setCellValue("C" . $row, $rowBasic);		
		$objPHPExcel->getActiveSheet()->setCellValue("D" . $row, $rowPresent);
		$objPHPExcel->getActiveSheet()->setCellValue("E" . $row, $rowAbsent);
		$objPHPExcel->getActiveSheet()->setCellValue("F" . $row, $rowGross);		
		$objPHPExcel->getActiveSheet()->setCellValue("G" . $row, $rowDeminimis);
		$objPHPExcel->getActiveSheet()->setCellValue("H" . $row, $rowEcola);		
		$objPHPExcel->getActiveSheet()->setCellValue("I" . $row, $rowOT);
		$objPHPExcel->getActiveSheet()->setCellValue("J" . $row, $rowAdjAdd);
		//$objPHPExcel->getActiveSheet()->setCellValue("K" . $row, $rowGrossPay);
		$objPHPExcel->getActiveSheet()->setCellValue("K" . $row, "=SUM(F" . $row . ":J" . $row . ")");
		$objPHPExcel->getActiveSheet()->setCellValue("L" . $row, $rowTardy);		
		$objPHPExcel->getActiveSheet()->setCellValue("M" . $row, $rowSSS);
		$objPHPExcel->getActiveSheet()->setCellValue("N" . $row, $rowPhilHealth);
		$objPHPExcel->getActiveSheet()->setCellValue("O" . $row, $rowPagibig);
		$objPHPExcel->getActiveSheet()->setCellValue("P" . $row, $rowSSSLoans);
		$objPHPExcel->getActiveSheet()->setCellValue("Q" . $row, $rowPagibigLoans);
		$objPHPExcel->getActiveSheet()->setCellValue("R" . $row, $rowCashLoans);
		$objPHPExcel->getActiveSheet()->setCellValue("S" . $row, $rowCompanyLoans);		
		$objPHPExcel->getActiveSheet()->setCellValue("T" . $row, $rowTax);
		$objPHPExcel->getActiveSheet()->setCellValue("U" . $row, $rowAdjLess);
		$objPHPExcel->getActiveSheet()->setCellValue("V" . $row, "=SUM(L" . $row . ":U" . $row . ")");
		//$objPHPExcel->getActiveSheet()->setCellValue("V" . $row, $rowNetAmount);
		$objPHPExcel->getActiveSheet()->setCellValue("W" . $row, "=K".$row."-V".$row);
		
		$rows = "C".$row.":W".$row;
		$objPHPExcel->getActiveSheet()->getStyle($rows)->getNumberFormat()->setFormatCode('###,###,##0.00');
		
		$rowAll = "A".$row.":W".$row;
		$objPHPExcel->getActiveSheet()->getStyle($rowAll)->getFont()->setName("Verdana");
		$objPHPExcel->getActiveSheet()->getStyle($rowAll)->getFont()->setSize(8);
		
		$row++;
	}
	
	$total_row = $row;
	$last_row = $row - 1;
	
	$objPHPExcel->getActiveSheet()->setCellValue("V" . $total_row, "TOTAL");
	$sheet->getStyle("V" . $total_row)->applyFromArray(array("font" => array( "bold" => true)));
	$sheet->getStyle("V" . $total_row)->getFont()->setName("Verdana");
	$sheet->getStyle("V" . $total_row)->getFont()->setSize(8);

	$objPHPExcel->getActiveSheet()->setCellValue("W" . $total_row, "=SUM(W3:W" . $last_row . ")");
	$sheet->getStyle("W" . $total_row)->applyFromArray(array("font" => array( "bold" => true)));
	$sheet->getStyle("W" . $total_row)->getNumberFormat()->setFormatCode('###,###,##0.00');
	$sheet->getStyle("W" . $total_row)->getFont()->setName("Verdana");
	$sheet->getStyle("W" . $total_row)->getFont()->setSize(8);
	
    // Rename worksheet
    $objPHPExcel->getActiveSheet()->setTitle('PayrollSummary');
    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);
    // Save Excel 2007 file    
	$callStartTime = microtime(true);
    $fileName = "payroll_summary_" . date('YmdHis') . ".xlsx";
    $writer = new PHPExcel_Writer_Excel5($objPHPExcel);
    $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="'.$fileName.'"');
    header('Cache-Control: max-age=0');
    $writer->save("php://output");	
?>