<?php
include("dbConfiguration.php");
$todayDate = date("Y-m-d");
$sql = "SELECT * FROM `TransactionHDR` where `Status` in ('PTW_01','PTW_02') and `WorkEndDatetime` < '$todayDate'";
$query = mysqli_query($conn,$sql);
$output = "";
$successList = array();
$errorList = array();
$rowCount = mysqli_num_rows($query);
if($rowCount == 0){
	$res = "No record found data of ".$todayDate." date";
	array_push($successList,$res);
}
else{
	while($row = mysqli_fetch_assoc($query)){
		$activityId = $row["ActivityId"];
		$trStatus = "UPDATE `TransactionHDR` set `Status` = 'PTW_101' where `ActivityId` = $activityId ";
		if(mysqli_query($conn,$trStatus)){
			$mpStatus = "UPDATE `Mapping` set `Active` = 0 where `ActivityId` = $activityId ";
			if(mysqli_query($conn,$mpStatus)){
				$res = "Successfully auto cancel of ".$activityId;
				array_push($successList,$res);
			}
			else{
				$res = "Something went wrong while update in `Mapping` table of ".$activityId;
				array_push($errorList,$res);
			}
			
		}
		else{
			$res = "Something went wrong while update in `TransactionHDR` table of ".$activityId;
			array_push($errorList,$res);
		}
	}
}

$output -> successList = $successList;
$output -> errorList = $errorList;

$result = array('hitDate' => $todayDate, 'output' => $output);
echo json_encode($result);

file_put_contents('/var/www/trinityapplab.in/html/SpaceTeleinfra/log/PTW_log.log', json_encode($result)."\n", FILE_APPEND);
?>