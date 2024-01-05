<?php

$json_str = file_get_contents('php://input');
$jsonw = json_decode($json_str,true);


require_once 'dbConfiguration.php';
if (mysqli_connect_errno())
{
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}

$empId = $jsonw['empId'];

$json = "";
$json->returnCode = "0";
$json->returnMsg = "Failure";
$json->errorMsg = "";
$json->wrappedList = array();

$wrappedListArray = array();

$assignedSql = "Select a.* from Assign a left join Employees e on (a.Eid = e.EId) where e.EmpId = '$empId' and curdate() >= a.StartDate and curdate() <= a.EndDate and a.Active = 1";
$assignedQuery = mysqli_query($conn,$assignedSql);
$assignedResult = mysqli_num_rows($assignedQuery);

if($assignedResult > 0){
	while($a = mysqli_fetch_array($assignedQuery)){
		$aObj = "";
		$aObj->assignId = $a['AssignId'];
		$aObj->checklistId = $a['Cid'];
		$aObj->locationId = $a['Lid'];
		$aObj->startDate = $a['StartDate'];
		$aObj->endDate = $a['EndDate'];
		array_push($wrappedListArray,$aObj);
	}
	$json->returnCode = "200";
	$json->returnMsg = "Success";
	$json->wrappedList = $wrappedListArray;
}
else{
	$json->errorMsg = "No task assigned";
}
	

header('Content-type:application/json');
echo json_encode($json);
 

?>