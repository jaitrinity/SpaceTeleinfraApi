<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];
$sql = "SELECT `MappingId`,`MenuId`,`LocationId` FROM `Mapping` WHERE `EmpId` = '$empId' and `Active` = 1 ";
$query=mysqli_query($conn,$sql);
$responseArr = array();
while($row = mysqli_fetch_assoc($query)){
	$mapId = $row["MappingId"];
	$meId = $row["MenuId"];
	$Lid = $row["LocationId"];
	$lidArr = explode(",",$Lid);
	
	for ($x = 0; $x < count($lidArr); $x++) {
		$locationSql = "SELECT * FROM `Location` where `LocationId` = $lidArr[$x] ";
		$locationQuery=mysqli_query($conn,$locationSql);
		
		while($locationRow = mysqli_fetch_assoc($locationQuery)){
			$name = $locationRow["Name"];
			$geoCoo = $locationRow["GeoCoordinates"];
			//$lat = explode(",",$geoCoo)[0];
			//$long = explode(",",$geoCoo)[1];
			
			$json = new StdClass;
			$json -> mappingId = $mapId;
			$json -> menuId = $meId;
			$json -> locationId = $lidArr[$x];
			$json -> name = $name;
			// $json -> lat = $lat;
			// $json -> long = $long;
			$json -> latlong = $geoCoo;
			array_push($responseArr,$json);
		}	
	}
}

$sql1 = "select r.MenuId from Employees emp join Role r on emp.RoleId = r.RoleId where emp.EmpId = '$empId' ";
$query1 = mysqli_query($conn,$sql1);
$distLocIdArr = array(); 
while($row1 = mysqli_fetch_assoc($query1)){
	$menuId = $row1["MenuId"];

	$menuIdArr = explode(",",$menuId);
	for ($i = 0; $i < count($menuIdArr); $i++) {
		$loopMenuId = $menuIdArr[$i];
		$sql2 = "SELECT GeoFence FROM Menu where MenuId = $loopMenuId and GeoFence is not null and GeoFence != '' ";
		$query2 = mysqli_query($conn,$sql2);
		while($row2 = mysqli_fetch_assoc($query2)){
			$geoFence = $row2["GeoFence"];

			$locationIdStr = explode(":",$geoFence)[0];

			$locationIdArr = explode(",", $locationIdStr);

			for ($y = 0; $y < count($locationIdArr); $y++) {
				$loopLocId = $locationIdArr[$y];
				if(!in_array($loopLocId, $distLocIdArr)){

					$sql3 = "SELECT * FROM `Location` where `LocationId` = $loopLocId ";
					$query3=mysqli_query($conn,$sql3);
					while($row3 = mysqli_fetch_assoc($query3)){
						$name = $row3["Name"];
						$geoCoo = $row3["GeoCoordinates"];
						//$lat = explode(",",$geoCoo)[0];
						//$long = explode(",",$geoCoo)[1];
						
						$json = new StdClass;
						$json -> mappingId = '0';
						$json -> menuId = $loopMenuId;
						$json -> locationId = $loopLocId;
						$json -> name = $name;
						// $json -> lat = $lat;
						// $json -> long = $long;
						$json -> latlong = $geoCoo;
						array_push($responseArr,$json);
					}

					array_push($distLocIdArr,$loopLocId);
				}
			}
		}
	}	
}


echo json_encode($responseArr);
?>