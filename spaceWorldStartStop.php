<?php 
include("dbConfiguration.php");
// $todayDate = '2021-11-26';
// $startSql = "SELECT * FROM `Activity` where `EmpId` = '9716744965' and date(`MobileDateTime`) = '$todayDate' and `Event` = 'start' ";

$todayDate = date('Y-m-d');
$startSql = "SELECT * FROM `Activity` where date(`MobileDateTime`) = '$todayDate' and `Event` = 'start' ";

$startQuery=mysqli_query($conn,$startSql);
while($row = mysqli_fetch_assoc($startQuery)){
	$dId = $row["DId"];
	$mappingId = $row["MappingId"];
	$empId = $row["EmpId"];
	$menuId = $row["MenuId"];
	$locationId = $row["LocationId"];
	$geolocation = $row["GeoLocation"];
	$distance = $row["Distance"];
	$tenentId = $row["Tenent_Id"];

	$stopSql = "SELECT * FROM `Activity` where `EmpId` = '$empId' and date(`MobileDateTime`) = '$todayDate' and `Event` = 'stop'";
	
	$stopQuery = mysqli_query($conn,$stopSql);
	$stopRowCount = mysqli_num_rows($stopQuery);
	if($stopRowCount == 0){
		$updateStop = "INSERT into `Activity` (`DId`, `MappingId`, `EmpId`, `MenuId`, `LocationId`, `Event`, `GeoLocation`, `Distance`, `Tenent_Id`, `MobileDateTime`) Values 
		($dId, '$mappingId', '$empId', $menuId, '$locationId', 'stop', '$geolocation', '$distance', $tenentId, current_timestamp) ";
		
		mysqli_query($conn,$updateStop);
	}
}

?>