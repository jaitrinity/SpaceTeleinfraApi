<?php

require_once 'dbConfiguration.php';
if (mysqli_connect_errno())
{
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}

$json = "";
$json->returnCode = "0";
$json->returnMsg = "Failure";
$json->errorMsg = "";
$json->wrappedList = array();
$wrappedListArray = array();

$typeSql = "Select * from Type";
$typeQuery = mysqli_query($conn,$typeSql);
$typeSize = mysqli_num_rows($typeQuery);

if($typeSize > 0 ){
	while($t = mysqli_fetch_array($typeQuery)){
		$tObj = "";
		$tObj->typeId = $t['TyId'];
		$tObj->type = $t['Type'];
		array_push($wrappedListArray,$tObj);
	}
	$json->returnCode = "200";
	$json->returnMsg = "Success";
	$json->wrappedList = $wrappedListArray;
}
else{
	$json->errorMsg = "No type Master Found";
}

header('Content-type:application/json');
echo json_encode($json);
 

?>