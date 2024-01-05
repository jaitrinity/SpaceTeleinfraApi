<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");

include("dbConfiguration.php");

$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$mapId = $jsonData->mappingId;
$empId = $jsonData->empId;
$meId = $jsonData->menuId;
$lId = $jsonData->locationId;
$event = $jsonData->event;
$gps = $jsonData->gps;
$net = $jsonData->net;
$google = $jsonData->google;

$distance = $jsonData->distance;
$dId = $jsonData->did;
$mobiledatetime = $jsonData->mobiledatetime;
If ($meId = "" )
{
$meId = 0;
}
else
{
$meId = 0;
}

if ((strpos($mobiledatetime, 'AM') !== false) || (strpos($mobiledatetime, 'PM')) || (strpos($mobiledatetime, 'am') !== false) || (strpos($mobiledatetime, 'pm')))   {
	$date = date_create_from_format("Y-m-d h:i:s A","$mobiledatetime");
	$date1 = date_format($date,"Y-m-d H:i:s");
}
else{
	$date1 = $mobiledatetime;
}


$insertActivity = "INSERT INTO `Activity1`(`DId`, `MappingId`, `EmpId`, `MenuId`, `LocationId`, `Event`, `Gps`,`Network`,`Google`, `Distance`, `MobileDateTime`, `ServerDateTime`)
		 VALUES ('$dId','$mapId','$empId',$meId,'$lId','$event','$gps','$net','$google','$distance','$date1',current_timestamp)";

//echo $insertActivity;
$output = "";
if(mysqli_query($conn,$insertActivity)){
	$output -> status = "success";
	$last_id = $conn->insert_id;
	$output -> Activity_Id = $last_id;
	//echo "New record created successfully. Last inserted ID is: " . $last_id;
}
else{
	$output -> status = "something went wrong";
}
echo json_encode($output);
?>