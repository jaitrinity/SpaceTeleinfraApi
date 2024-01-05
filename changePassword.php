<?php
include("dbConfiguration.php");
$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$loginEmpId = $jsonData->loginEmpId;
$mobileNumber = $jsonData->mobileNumber;
$newPassword = $jsonData->newPassword;

// $sql = "update `Employees` set `Password` = '$newPassword', `Update` = current_timestamp WHERE "
// 					. " `EmpId` = $loginEmpId "
// 					. " and `Mobile` = '$mobileNumber' ";

$sql = "update `Employees` set `Password` = '$newPassword', `Update` = current_timestamp WHERE "
					. " `Mobile` = '$mobileNumber' ";
// echo $sql;
$query=mysqli_query($conn,$sql);
if(mysqli_affected_rows($conn) == 0){
	$output = array('responseDesc' => "No Record Found", 'wrappedList' => [], 'responseCode' => "102001");
}
else{
	$output = array('responseDesc' => "SUCCESSFUL", 'wrappedList' => [], 'responseCode' => "100000");
}


echo json_encode($output);
?>