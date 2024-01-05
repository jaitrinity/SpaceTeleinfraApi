<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
require 'EmployeeTenentId.php';
$empId=$_REQUEST['empId'];
$roleId=$_REQUEST['roleId'];

$empTenObj = new EmployeeTenentId();
$tenentId = $empTenObj->getTenentIdByEmpId($conn,$empId);

$locationSql = "SELECT * FROM `Location` where `Tenent_Id` = $tenentId ";
$locationQuery=mysqli_query($conn,$locationSql);

$responseArr = array();
$locationQuery=mysqli_query($conn,$locationSql);
while($locationRow = mysqli_fetch_assoc($locationQuery)){
	$locId = $locationRow["LocationId"];
	$name = $locationRow["Name"];
	$geoCoo = $locationRow["GeoCoordinates"];
	$json = "";
	$json -> locationId = $locId;
	$json -> name = $name;
	$json -> geoCoordinates = $geoCoo;
	array_push($responseArr,$json);
}	

if(count($responseArr) > 0){
	$outputJson = array('status' => true, 'message' => 'Success', 'response' => $responseArr);
	echo json_encode($outputJson);
}else{
	$outputJson = array('status' => false, 'message' => 'No data found', 'response' => $responseArr);
	echo json_encode($outputJson);
}

?>