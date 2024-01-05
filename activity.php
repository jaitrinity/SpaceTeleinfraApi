<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
require 'EmployeeTenentId.php';

$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$mapId = $jsonData->mappingId;
$empId = $jsonData->empId;
$meId = $jsonData->menuId;
$lId = $jsonData->locationId;
$event = $jsonData->event;
$geolocation = $jsonData->geolocation;
$geolocation = str_replace(",", "/", $geolocation);
$distance = $jsonData->distance;
$dId = $jsonData->did;
$mobiledatetime = $jsonData->mobiledatetime;

$output = new StdClass;
// -- Start - For not save `periodicdata` data in `Activity` table.
// If want to save, then comment below if block.
if($event == "periodicdata"){
	$output -> status = "success";
	$output -> Activity_Id = -1;
	echo json_encode($output);
	return;
}
// -- End - For not save `periodicdata` data in `Activity` table.

if($meId == null || $meId == "")
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
$classObj = new EmployeeTenentId();
$tenentId = $classObj->getTenentIdByEmpId($conn,$empId);

$insertActivity = "INSERT INTO `Activity`(`DId`, `MappingId`, `EmpId`, `MenuId`, `LocationId`, `Event`, `GeoLocation`, `Distance`, `MobileDateTime`, `ServerDateTime`, 
`Tenent_Id`) VALUES ('$dId','$mapId','$empId',$meId,'$lId','$event','$geolocation','$distance','$date1',current_timestamp, $tenentId)";
// echo $insertActivity;

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