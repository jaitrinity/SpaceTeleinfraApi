<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];

$wrappedList = "";
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
		$json -> menuId = $meId;
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
				//$menu -> verifier = $menuRow['Verifier'];
				//$menu -> approver = $menuRow['Approver'];
				$menu -> subCategoryList = array();

				$name = $locationRow["Name"];
				$geoCoo = $locationRow["GeoCoordinates"];
				
				$menu -> GeoCoordinate = $geoCoo;
				
				$json = "";
				$json -> menuId = $meId;
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
$wrappedList->todoList = $responseArr;

$viewerList = array();

$viewerSql = "Select r.*,h.TransactionId,
			(case when m.Caption != '' then m.Caption when m.Sub !='' then m.Sub ELSE m.Cat end) as menuCaption,
			(case when m.Caption != '' then SUBSTRING_INDEX(m.Icons, ',', -1)
			 when m.Sub != '' then SUBSTRING_INDEX(m.Icons, ',', -1)
			 ELSE SUBSTRING_INDEX(m.Icons, ',', 1) end) as menuIcon,
			m.Icons,m.CheckpointId,m.Verifier from
			(Select a.ActivityId,a.MenuId,a.LocationId
			from Activity a where a.EmpId in (Select distinct e.EmpId from Employees e where e.RMId = '$empId' and e.Active = 1) and a.Event= 'Submit'
			)r
			join TransactionHDR h on (r.ActivityId = h.ActivityId)
			join Menu m on (r.MenuId = m.MenuId)";
//echo $viewerSql;
$viewerQuery = mysqli_query($conn,$viewerSql);
//$responseArr = array();

while($v = mysqli_fetch_assoc($viewerQuery)){
	//echo "inside while loop";
	$vObj = "";
	$vObj->transactionId = $v['TransactionId'];
	$vObj->activityId = $v['ActivityId'];
	$vObj->menuId = $v['MenuId'];
	$vObj->locationId = $v['LocationId'];
	$vObj->menuCaption = $v['menuCaption'];
	$vObj->menuIcon = $v['menuIcon'];
	$vObj->checkpointId = $v['CheckpointId'];
	$vObj->verifier = $v['Verifier'];

	$filledDataArray = array();
	$filledDataSql = "Select * from TransactionDTL where TransactionId = '".$v['TransactionId']."'";
	$filledDataQuery = mysqli_query($conn,$filledDataSql);
	while($f = mysqli_fetch_assoc($filledDataQuery)){
		$fObj = "";
		$fObj->cpId = $f['ChkId'];
		$fObj->dependId = $f['DependChkId'];
		$fObj->answer = $f['Value'];
		array_push($filledDataArray,$fObj);
	}
	$vObj->dataList = $filledDataArray;
	array_push($viewerList,$vObj);
}
$wrappedList->viewerList = $viewerList;
echo json_encode($wrappedList);
?>