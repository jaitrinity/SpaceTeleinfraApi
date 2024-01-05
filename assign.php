<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];

$sql = "SELECT *  FROM `Assign` WHERE `EmpId` = '$empId' AND date(`StartDate`) <= date(now()) AND date(`EndDate`) >= date(now()) AND `ActivityId` is  null AND `Active` = 1 ";
$query=mysqli_query($conn,$sql);
$responseArr = array();
while($row = mysqli_fetch_assoc($query)){
	$meId = $row["MenuId"];
	$lId = $row["LocationId"];
	$startDate = $row["StartDate"];
	$endDate = $row["EndDate"];
	$assignId = $row["AssignId"];

	$menuSql = "Select * from Menu where MenuId = $meId";
	$menuQuery  = mysqli_query($conn,$menuSql);
	$menuRow = mysqli_fetch_assoc($menuQuery);	

	if($lId == null || $lId == ''){
		$menu = "";
		$menu -> menuId = $meId;
		$iconArr = explode(",",$menuRow['Icons']);
		$geofence = "";
		if($menuRow['GeoFence'] != null && $menuRow['GeoFence'] != ''){
			$geofenceArr= explode(":",$menuRow['GeoFence']);
			if(count($geofenceArr) > 1){
				$geofence = $geofenceArr[1];
			}
		}
		if($menuRow['Caption'] != ''){
			$menu -> Caption = $menuRow['Caption'];
			$menu -> Icon = $iconArr[2];
		}
		else if($menuRow['Sub'] != ''){
			$menu -> Caption = $menuRow['Sub'];
			$menu -> Icon = $iconArr[1];
		}
		else{
			$menu -> Caption = $menuRow['Cat'];
			$menu -> Icon = $iconArr[0];
		}
		$menu -> checkpointId = $menuRow['CheckpointId'];;
		$menu -> active = $menuRow['Active'];
		$menu -> Editable = '';
		$menu -> GeoFence = $geofence;
		$menu -> verifier = $menuRow['Verifier'];
		$menu -> approver = $menuRow['Approver'];
		$menu -> subCategoryList = array();

		
		$menu -> GeoCoordinate = '';
		
		$json = "";
		//$json -> menuId = $meId;
		$json -> locationId = '';
		$json -> startDate = $startDate;
		$json -> endDate = $endDate;
		$json -> name = '';
		$json -> assignId = $assignId;
		$json -> latlong = '';
		$json -> menuData = $menu;
		array_push($responseArr,$json);
	}
	else{
		$lidArr = explode(",",$lId);
		
		// $menuSql = "Select * from Menu where MenuId = $meId";
		// $menuQuery  = mysqli_query($conn,$menuSql);
		// $menuRow = mysqli_fetch_assoc($menuQuery);	
		
		for ($x = 0; $x < count($lidArr); $x++) {
			$locationSql = "SELECT * FROM `Location` where `LocationId` = $lidArr[$x] ";
			$locationQuery=mysqli_query($conn,$locationSql);
			
			while($locationRow = mysqli_fetch_assoc($locationQuery)){
				$menu = "";
				$menu -> menuId = $meId;
				$iconArr = explode(",",$menuRow['Icons']);
				$geofence = "";
				if($menuRow['GeoFence'] != null && $menuRow['GeoFence'] != ''){
					$geofenceArr= explode(":",$menuRow['GeoFence']);
					if(count($geofenceArr) > 1){
						$geofence = $geofenceArr[1];
					}
				}
				if($menuRow['Caption'] != ''){
					$menu -> Caption = $menuRow['Caption'];
					$menu -> Icon = $iconArr[2];
				}
				else if($menuRow['Sub'] != ''){
					$menu -> Caption = $menuRow['Sub'];
					$menu -> Icon = $iconArr[1];
				}
				else{
					$menu -> Caption = $menuRow['Cat'];
					$menu -> Icon = $iconArr[0];
				}
				$menu -> checkpointId = $menuRow['CheckpointId'];;
				$menu -> active = $menuRow['Active'];
				$menu -> Editable = '';
				$menu -> GeoFence = $geofence;
				$menu -> verifier = $menuRow['Verifier'];
				$menu -> approver = $menuRow['Approver'];
				$menu -> subCategoryList = array();

				$name = $locationRow["Name"];
				$geoCoo = $locationRow["GeoCoordinates"];
				
				$menu -> GeoCoordinate = $geoCoo;
				
				$json = "";
				//$json -> menuId = $meId;
				$json -> locationId = $lidArr[$x];
				$json -> startDate = $startDate;
				$json -> endDate = $endDate;
				$json -> name = $name;
				$json -> assignId = $assignId;
				$json -> latlong = $geoCoo;
				$json -> menuData = $menu;
				array_push($responseArr,$json);
			}	
		}
	}
}
echo json_encode($responseArr);
?>