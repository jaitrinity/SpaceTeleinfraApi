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
$clId = $jsonw['clId'];
$lId = $jsonw['lId'];
$event = $jsonw['event'];
$geoLocation = $jsonw['geoLocation'];
$distance = $jsonw['distance'];
$mobileDateTime = $jsonw['mobileDateTime'];

$json = "";
$json->returnCode = "0";
$json->returnMsg = "Failure";
$json->errorMsg = "";
$json->wrappedList = array();

$activitySql = "Insert into Activity(EId,CId,LId,Event,GeoLocation,Distance,MobileDateTime,ServerDateTime)"
				."vallues"
				."('$empId',$clId,'$lId','$event','$geoLocation','$distance','$mobileDateTime',curdate())";
$activityQuery = mysqli_query($conn,$activitySql);

if($activityQuery){
	$json->returnCode = "200";
	$json->returnMsg = "Success";
}
else{
	$json->errorMsg = "No site found in master";
}
	

header('Content-type:application/json');
echo json_encode($json);
 

?>