<?php
include("dbConfiguration.php");
$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$loginEmpId = $jsonData->loginEmpId;
$loginEmpRole = $jsonData->loginEmpRole;
$transactionId = $jsonData->transactionId;
$menuId = $jsonData->menuId;
$locationId = $jsonData->locationId;
$status = $jsonData->status;
$validatedDataList = $jsonData->validatedDataList;


$activitySql = "INSERT INTO `Activity`(`EmpId`, `MenuId`, `LocationId`, `Event`,`MobileDateTime`, `ServerDateTime`) VALUES 
('$loginEmpId', $menuId, '$locationId', 'Submit', current_timestamp, current_timestamp ) ";
$activityQuery = mysqli_query($conn,$activitySql);
$lastActivityId = 0;
if($activityQuery){
	$lastActivityId = $conn->insert_id;

	$type = "";
	if($status == "Verified" || $status == "PTW_02"){
		$type = " `VerifierActivityId` = $lastActivityId ";
	}
	else{
		$type = " `ApproverActivityId` = $lastActivityId ";
	}

	$updateHDR_Sql = "UPDATE `TransactionHDR` set Status = '".$status."', $type WHERE `ActivityId` = ".$transactionId." ";
	$updateHDRQuery = mysqli_query($conn,$updateHDR_Sql);

	$ptwAction = "";
	$insertDET_Sql = "INSERT INTO `TransactionDTL`(`ActivityId`, `ChkId`, `Value`, `DependChkId`) VALUES ";
	for($i=0;$i<count($validatedDataList);$i++){
		$checkpointId = $validatedDataList[$i]->checkpointId;
		$checkpointValue = $validatedDataList[$i]->checkpointValue;
		if($checkpointId == 5536){
			$ptwAction = $checkpointValue;
		}
		$dependChpId = $validatedDataList[$i]->dependChpId;
		$typeId = $validatedDataList[$i]->typeId;
		$size = $validatedDataList[$i]->size;
		if($typeId == "7" && $size == "1"){
			// date
			$newDate = date("d/m/Y", strtotime($checkpointValue));
			$checkpointValue = $newDate;
		}
		else if($typeId == "7" && $size == "0"){
			// time
			$newTime = date("g:i A", strtotime($checkpointValue));
			$checkpointValue = $newTime;
		}

		$detSql = $insertDET_Sql."($lastActivityId, '$checkpointId', '$checkpointValue', $dependChpId)";
		// echo $detSql;
		$detQuery = mysqli_query($conn,$detSql);
	}

	if($ptwAction == "Reject"){
		$updateHDR_Sql = "UPDATE `TransactionHDR` set Status = 'PTW_99' WHERE `ActivityId` = ".$transactionId." ";
		$updateHDRQuery = mysqli_query($conn,$updateHDR_Sql);
	}

}

if($lastActivityId !=0 ){
	$output = array('wrappedList' => [], 'responseDesc' => 'SUCCESSFUL', 'responseCode' => '100000');
	echo json_encode($output);
}	
else{
	$output = array('wrappedList' => [], 'responseDesc' => 'Something wrong', 'responseCode' => '-102003');
	echo json_encode($output);
}

?>