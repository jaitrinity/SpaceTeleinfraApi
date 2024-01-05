<?php


require_once 'dbConfiguration.php';
if (mysqli_connect_errno())
{
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}


$empId=$_REQUEST['empId'];
$empId= str_replace("?","",$empId);
$submitList = array();
//$res = "";


$historySql = "Select"
			." m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,m.Verifier,m.Approver,"
			." l.Name as locationName,a.MobileDateTime as SubmitDateTime,"
			." a.ActivityId,th.Status,th.VerifierActivityId,th.ApproverActivityId"
			." from Activity a"
			." join Menu m on (a.MenuId  = m.MenuId)"
			." join TransactionHDR th on (a.ActivityId  = th.ActivityId)"
			." join Location l on (a.LocationId = l.LocationId )"
			." where a.Event = 'Submit' and DATE_SUB(CURDATE(), INTERVAL  30 DAY)" 
			." and a.EmpId = '$empId' order by a.MobileDateTime desc ";
			
$historyQuery = mysqli_query($conn,$historySql);
$historySize = mysqli_num_rows($historyQuery);

if($historySize > 0){
	while($hRow = mysqli_fetch_array($historyQuery)){

		$statusObj  = "";
		$statusObj->actId = $hRow['ActivityId'];
		$statusObj->status = $hRow['Status'];
		$statusObj->verifierActId = $hRow['VerifierActivityId'];
		$statusObj->approverActId = $hRow['ApproverActivityId'];
		$statusObj->fillerCpId = $hRow['CheckpointId'];
		$statusObj->verifierCpId = $hRow['Verifier'];
		$statusObj->approverCpId = $hRow['Approver'];
		
		$hObj = "";
		$iconArr = explode(",",$hRow['Icons']);

		$cat = $hRow["Cat"];
		$sub = $hRow["Sub"];
		$caption = $hRow["Caption"];


		if($sub == '' && $caption == ''){

			$hObj->name = $hRow['locationName'];
			$hObj->endDate= $hRow['SubmitDateTime'];
			$hObj->uniqueId = $hRow['ActivityId'];
			$hObj->Caption = $cat;
			$hObj->Icon = $iconArr[0];
			$hObj->subCategoryList = array();
			// echo '1-----';
			getFilledCheckpoints($hObj,$statusObj);
		}
		else if($sub != '' && $caption == ''){
			
			$hObj->Caption = $cat;
			$hObj->Icon = $iconArr[0];
			
			$hObjArray1 = array();
			$hObj1 = "";
			$hObj1->name = $hRow['locationName'];
			$hObj1->endDate= $hRow['SubmitDateTime'];
			$hObj1->checkpointId = $hRow['CheckpointId'];
			$hObj1->uniqueId = $hRow['ActivityId'];
			$hObj1->Caption = $sub;
			$hObj1->Icon = $iconArr[1];
			$hObj1->subCategoryList = array();
			// echo '2-----';
			getFilledCheckpoints($hObj1,$statusObj);

			array_push($hObjArray1,$hObj1);
			$hObj->subCategoryList = $hObjArray1;

		}
		else if($sub != '' && $caption != ''){

			
			$hObj->Caption = $cat;
			$hObj->Icon = $iconArr[0];
			
			$hObj1Array = array();
			$hObj1 = "";

			$hObj1->Caption = $sub;
			$hObj1->Icon = $iconArr[1];


			$hObj2Array = array();
			$hObj2 = "";

			$hObj2->name = $hRow['locationName'];
			$hObj2->endDate= $hRow['SubmitDateTime'];
			$hObj2->checkpointId = $hRow['CheckpointId'];
			$hObj2->uniqueId = $hRow['ActivityId'];
			$hObj2->Caption = $caption;
			$hObj2->Icon = $iconArr[2];
			$hObj2->subCategoryList = array();
			// echo '3-----';
			getFilledCheckpoints($hObj2,$statusObj);

			array_push($hObj2Array,$hObj2);
			$hObj1->subCategoryList = $hObj2Array;

			array_push($hObj1Array,$hObj1);			
			$hObj->subCategoryList = $hObj1Array;

		}


		array_push($submitList,$hObj);
	}
		
	//$res->status = "success";
}

/*else{
	$res->status = "No record found";
}
$res->submitList = $submitList;
*/

$response = "";
$response -> menu = $submitList;

header('Content-type:application/json');
echo json_encode($response);


function getFilledCheckpoints($obj,$statObj){
	
	global $conn;

	$cpIdString = "";
	if($statObj->fillerCpId != null && $statObj->fillerCpId != ''){
		if($cpIdString == ''){
			$cpIdString .= $statObj->fillerCpId;
		}
		else{
			$cpIdString .= ":".$statObj->fillerCpId;
		}
		
	}
	if($statObj->verifierCpId != null && $statObj->verifierCpId != ''){
		if($cpIdString == ''){
			$cpIdString .= $statObj->verifierCpId ;
		}
		else{
			$cpIdString .= ":".$statObj->verifierCpId ;
		}
			
	}
	if($statObj->approverCpId != null && $statObj->approverCpId != ''){
		if($cpIdString == ''){
			$cpIdString .= $statObj->approverCpId ;
		}
		else{
			$cpIdString .= ":".$statObj->approverCpId ;
		}
				
	}
	$hObj->checkpointId = $cpIdString;

	$isDataSend = "";
	$cpIdArray = explode(":",$Obj->checkpointId);
	for($cpId = 0; $cpId < count($cpIdArray); $cpId++){
		$isDataSend .= "0";
		
	}
	$Obj->isDataSend = $isDataSend;
	
	$cpArray = array();
	
	$actId = $statObj->actId;
	$verifierActId = $statObj>verifierActId ;
	$approverActId = $statObj->approverActId ;
	
	$filledCpString = str_replace(":",",",$statObj->fillerCpId);
	$verifierCpString = str_replace(":",",",$statObj->verifierCpId);
	$approvreCpString = str_replace(":",",",$statObj->approverCpId);

	if($actId  != null && $actId  != ''){
		$filledcpSql = "Select r2.*,r1.* 
			 from
			 (Select d.ChkId,d.Value as answer from TransactionDTL d
			 where d.ActivityId = '$actId' and d.DependChkId = 0
			 )r1
			 right join 
			 (Select c.* from Checkpoints c
			 where c.CheckpointId in ($filledCpString)
			 ) r2 on (r1.ChkId = r2.CheckpointId)";

		$filledcpQuery=mysqli_query($conn,$filledcpSql);
		while($fcp = mysqli_fetch_assoc($filledcpQuery)){
			$fcpObj = "";
			$fcpObj->Chkp_Id = $fcp['CheckpointId'];
			$fcpObj->editable = '0';
			if($fcp['answer'] != null){
				$fcpObj->value = $fcp['answer'];
			}
			else{
				$fcpObj->value = "";
			}
		
			$fdpArray = array();
			if($fcp['Dependent'] == "1"){
				$fdpSql = " Select r1.*,c.* from
					(Select d.ChkId,d.Value as answer from TransactionDTL d
					where d.ActivityId = '$actId' and d.DependChkId = (".$fcp['CheckpointId'].")
					) r1
					join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
				$fdpQuery = mysqli_query($conn,$fdpSql);
				while($fdp = mysqli_fetch_assoc($fdpQuery)){
					$fdpObj = "";
					$fdpObj->Chkp_Id = $fcp['CheckpointId']."_".$fdp['CheckpointId'];
					$fdpObj->editable = '0';
					$fdpObj->value = $fdp['answer'];
					array_push($fdpArray,$fdpObj);
				}
			}
			$fcpObj->Dependents = $fdpArray;
			array_push($cpArray,$fcpObj);
		} 

	}

	if($verifierActId != null && $verifierActId != ''){
		$apverifiedcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '$verifierActId' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($verifierCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
		//echo $apverifiedcpSql;
		$apverifiedcpQuery=mysqli_query($conn,$apverifiedcpSql);
	 
		 while($apvcp = mysqli_fetch_assoc($apverifiedcpQuery)){
			$apvcpObj = "";
			$apvcpObj->Chkp_Id = $apvcp['CheckpointId'];
			$apvcpObj->editable = '0';
			if($apvcp['answer'] != null){
				$apvcpObj->value = $apvcp['answer'];
			}
			else{
				$apvcpObj->value = "";
			}
		
			$apvdpArray = array();
			if($apvcp['Dependent'] == "1"){
				$apvdpSql = " Select r1.*,c.* from
						(Select d.ChkId,d.Value as answer from TransactionDTL d
						where d.ActivityId = '$verifierActId' and d.DependChkId = (".$apvcp['CheckpointId'].")
						) r1
						join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
				$apvdpQuery = mysqli_query($conn,$apvdpSql);
				while($apvdp = mysqli_fetch_assoc($apvdpQuery)){
					$apvdpObj = "";
					$apvdpObj->Chkp_Id = $apvcp['CheckpointId']."_".$apvdp['CheckpointId'];
					$apvdpObj->editable = '0';
					$apvdpObj->value = $apvdp['answer'];
					array_push($apvdpArray,$apvdpObj);
				}
			}
			$apvcpObj->Dependents = $apvdpArray;
			array_push($cpArray,$apvcpObj);
		} 

	}
	if($approverActId != null && $approverActId != ''){
		$approvedCpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '$approverActId' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($approverCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
		//echo $apverifiedcpSql;
		$approvedcpQuery=mysqli_query($conn,$approvedCpSql);
	 
		 while($ap = mysqli_fetch_assoc($approvedcpQuery)){
			$apcpObj = "";
			$apcpObj->Chkp_Id = $ap['CheckpointId'];
			$apcpObj->editable = '0';
			if($ap['answer'] != null){
				$apcpObj->value = $ap['answer'];
			}
			else{
				$apcpObj->value = "";
			}
		
			$apdpArray = array();
			if($ap['Dependent'] == "1"){
				$apdpSql = " Select r1.*,c.* from
						(Select d.ChkId,d.Value as answer from TransactionDTL d
						where d.ActivityId = 'approverActId' and d.DependChkId = (".$ap['CheckpointId'].")
						) r1
						join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
				$apdpQuery = mysqli_query($conn,$apdpSql);
				while($apdp = mysqli_fetch_assoc($apdpQuery)){
					$apdpObj = "";
					$apdpObj->Chkp_Id = $ap['CheckpointId']."_".$apdp['CheckpointId'];
					$apdpObj->editable = '0';
					$apdpObj->value = $apdp['answer'];
					array_push($apdpArray,$apdpObj);
				}
			}
			$apcpObj->Dependents = $apdpArray;
			array_push($cpArray,$apcpObj);
		} 

	}

	
	$obj->value = $cpArray;


}
 

?>