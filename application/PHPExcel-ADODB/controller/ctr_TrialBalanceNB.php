<?php
class TrialBalanceNB{

	public function __construct($details , $parameters){
		
		$this->report =  new Report();
		$this->report->initialize($details);
		$this->param = $parameters;
		
	}

	public function create(){
	
		$objPHPExcel = $this->report->getPHPExcelObj();
		$params = $this->param;
	
		// Rename sheet
		$this->report->log("Created Worksheet");
		$objPHPExcel->getActiveSheet()->setTitle($this->report->fileName);
		
		// Add some data
		$this->report->log("Adding Report Content");
		
		$objPHPExcel->setActiveSheetIndex(0);
		
		$worksheet = $objPHPExcel->getActiveSheet();
		
		$worksheet->SetCellValue('B2', 'Penta Insurance Broker Services Inc.');
		$worksheet->SetCellValue('B3', 'Trial Balance');
		$monthName = date("F", mktime(0, 0, 0, $params['month'], 10)); 
		$worksheet->SetCellValue('B4', 'As of '.$monthName.' '.$params['day'].', '.$params['year']);
		
		$worksheet->SetCellValue('C6', 'Debit');
		$worksheet->SetCellValue('D6', 'Credit');
		
		$worksheet->getStyle('B6:D6')->getFont()->getColor()->setRGB('FFFFFF');
		$worksheet->getStyle('B6:D6')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
		$worksheet->getStyle('B6:D6')->getFill()->getStartColor()->setRGB('666666');
		
		$DB = new TrialBalanceNBModel();
		$result = $DB->show_balances($params['year'], $params['month']);
		
		$ctr = 7; // Starting row
		$balStart = $ctr; // Used for getting the totals cells
		$acct_type = ""; // Initial account type
		
		$totalsC = array();
		$totalsD = array();
		
		while($row = $result->FetchRow()){

				//if($row['Debit'] > 0 || $row['Credit'] > 0){//Show to display rows with values
					
					if($acct_type <> $row['Account Type']){
					
						if($acct_type <> "" /*Processing just started)*/){
						
							$totStart = $balStart + 1;
							$totEnd = $ctr - 1;
							
							$worksheet->getStyle('B'.$ctr)->getFont()->setBold(true);
							$worksheet->SetCellValue('B'.$ctr, "Total ".$acct_type);
							
							$getSumOfC = 'SUM(C'.$totStart.':C'.$totEnd.')';
							$getSumOfD = 'SUM(D'.$totStart.':D'.$totEnd.')';
							$worksheet->SetCellValue('C'.$ctr, '='.$getSumOfC);	
							$worksheet->SetCellValue('D'.$ctr, '='.$getSumOfD);
							
							$totalsC[] = 'C'.$ctr;
							$totalsD[] = 'D'.$ctr;
							$ctr++;
							
						}
					
						$acct_type = $row['Account Type'];
						$worksheet->SetCellValue('B'.$ctr, $acct_type);
						$worksheet->getStyle('B'.$ctr)->getFont()->setBold(true);
						$balStart = $ctr;
						$ctr++;
					}
		
					$balance = $row['Debit'] - $row['Credit'];
					$worksheet->SetCellValue('B'.$ctr, $row['Account']);
					$worksheet->SetCellValue('C'.$ctr, $row['Debit']);
					$worksheet->SetCellValue('D'.$ctr, $row['Credit']);
					//$col = $this->checkAccountType($row['Account Type']);
					//$col = $this->checkNegativeBalance($balance,$col,$row['Account Type']);
					//$worksheet->SetCellValue($col.$ctr, abs($balance));
					$ctr++;
					
				/*}*///Show to display rows with values
				if($ctr == 45){//Page Break every 50 rows
					$worksheet->setBreak( 'B' . $ctr, PHPExcel_Worksheet::BREAK_ROW );
				}
		
		}
		
		
		$totStart = $balStart + 1;
		$totEnd = $ctr - 1;
		
		$worksheet->getStyle('B'.$ctr)->getFont()->setBold(true);
		$worksheet->SetCellValue('B'.$ctr, "Total ".$acct_type);
		
		$getSumOfC = 'SUM(C'.$totStart.':C'.$totEnd.')';
		$getSumOfD = 'SUM(D'.$totStart.':D'.$totEnd.')';
		$worksheet->SetCellValue('C'.$ctr, '='.$getSumOfC);	
		$worksheet->SetCellValue('D'.$ctr, '='.$getSumOfD);
		
		$totalsC[] = 'C'.$ctr;
		$totalsD[] = 'D'.$ctr;		
		$ctr++;
		
		
		
		
		$worksheet->SetCellValue('B'.$ctr,'Total');
	
		$sumFormulaC = "=";
		foreach($totalsC as $index):
				$sumFormulaC .= $index;
				if(end($totalsC) <> $index){
					$sumFormulaC .= "+";
				}
		endforeach;
		
		$sumFormulaD = "=";
		foreach($totalsD as $index):
				$sumFormulaD .= $index;
				if(end($totalsD) <> $index){
					$sumFormulaD .= "+";
				}
		endforeach;
		
		
		$worksheet->SetCellValue('C'.$ctr,$sumFormulaC);
		$worksheet->SetCellValue('D'.$ctr,$sumFormulaD);
		
		
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
		$this->report->end($this->report->getFormat());

	}

	
	private function checkAccountType($account){
		if ($account == 'Assets' || $account == 'Income'){
			return 'C'; //column for Debit
		}else{
			return 'D';
		}
	}
	private function checkNegativeBalance($balance,$column,$account){
		if(($account == 'Assets' || $account == 'Income') && $balance < 0){
			return $this->reverseColumn($column);
		}else{
			return $column;
		}
	}
	
	private function reverseColumn($column){
		if($column == 'C'){ return 'D'; }
		else{ return 'C'; }
	}
	
	public function view(){
		$view['html'] = $this->report->view(); //load html components
		$view['logs'] = $this->report->getLogs(); //load logs
		$view['details'] = $this->report->getDetails(); //load logs
		return $view;
	}

}

?>