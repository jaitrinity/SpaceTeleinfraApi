<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
require 'EmployeeTenentId.php';

$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$mapId = "";
$empId = $jsonData->empId;
$meId = "0";
$lId = "";
$event = $jsonData->event;
$geolocation = $jsonData->geolocation;
$geolocation = str_replace(",", "/", $geolocation);
$distance = "";
if($event != "periodicdata"){
	$mapId = $jsonData->mappingId;
	$meId = $jsonData->menuId;
	$lId = $jsonData->locationId;
	$distance = $jsonData->distance;
}
$dId = $jsonData->did;
$mobiledatetime = $jsonData->mobiledatetime;
if($meId == null || $meId == "")
{
$meId = 0;
}
// else
// {
// $meId = 0;
// }
// $mapId=$_REQUEST['mappingId'];
// $empId=$_REQUEST['empId'];
// $meId=$_REQUEST['menuId'];
// $lId=$_REQUEST['locationId'];
// $event=$_REQUEST['event'];
// $geolocation=$_REQUEST['geolocation'];
// $distance=$_REQUEST['distance'];
// $mobiledatetime=$_REQUEST['mobiledatetime'];

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
$output = new StdClass;
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