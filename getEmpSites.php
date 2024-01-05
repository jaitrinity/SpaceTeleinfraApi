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

$json = "";
$json->returnCode = "0";
$json->returnMsg = "Failure";
$json->errorMsg = "";
$json->wrappedList = array();
$wrappedListArray = array();

$siteMapSql = "Select distinct Lid from Mapping where Eid = (Select EId from Employees where EmpId = '$empId') ";
$siteMapQuery = mysqli_query($conn,$siteMapSql);
$siteMapSize = mysqli_num_rows($siteMapQuery);

if($siteMapSize > 0 ){
	$siteIdString = "";
	while($sm = mysqli_fetch_array($siteMapQuery)){
		$siteIdString = $siteIdString.$sm['Lid'].",";
	}
	$siteIdString = substr($siteIdString,0,strlen($siteIdString)-1);
	$siteSql = "Select * from Location where Lid in ( $siteIdString )";
	$siteQuery = mysqli_query($conn,$siteSql);
	$siteSize = mysqli_num_rows($siteQuery);
	if($siteSize > 0 ){
		while($s = mysqli_fetch_array($siteQuery)){
			$sObj = "";
			$sObj->lId = $t['Lid'];
			$sObj->lName = $t['Name'];
			$sObj->geoCoordinates = $t['GeoCoordinates'];
			array_push($wrappedListArray,$sObj);
		}
		$json->returnCode = "200";
		$json->returnMsg = "Success";
		$json->wrappedList = $wrappedListArray;
	}
	else{
		$json->errorMsg = "No site found in master";
	}
	
}
else{
	$json->errorMsg = "No site mapping Found";
}

header('Content-type:application/json');
echo json_encode($json);
 

?>