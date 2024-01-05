<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId = $_REQUEST["empId"];

// $sql = "SELECT e.`FieldUser`, e.`Active`, r.`AppMenuId`  FROM `Employees` e join `Role` r on e.RoleId = r.RoleId WHERE `EmpId` = '$empId'";
$sql = "SELECT e.`FieldUser`, e.`Active`, r.`AppMenuId`  FROM `Employees` e join `Role` r on e.RoleId = r.RoleId WHERE e.`EmpId` = '$empId' and e.`Active` = 1";
$query= mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($query);
$isFieldUser = $row["FieldUser"];
$isActive = $row["Active"];
$appMenuId = $row["AppMenuId"];

$versionSql = "Select * from Version";
$versionQuery= mysqli_query($conn, $versionSql);
$rowcount=mysqli_num_rows($versionQuery);

$ar = new StdClass;

if($rowcount > 0){
	$ver = mysqli_fetch_assoc($versionQuery);

	$android = explode(";",$ver['Android']);
	$ios = explode(";",$ver['Ios']);

	$ar->andVer=$android[0];
	$ar->andForce=$android[1];
	$ar->iosVer=$ios[0];
	$ar->iosForce=$ios[1];
	$ar->fakeGps = $ver["FakeGPS"];
	$ar->whitelistApp = explode(",", $ver["WA"]);
	$ar->blacklistApp = explode(",", $ver["BA"]);
	$ar->isFieldUser = $isFieldUser == null ? "0" : $isFieldUser;
	$ar->isActive = $isActive;
	$ar->appMenuId = $appMenuId;
		
}

echo json_encode($ar);

?>