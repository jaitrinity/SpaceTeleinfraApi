<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$tenentId = $jsonData->tenentId;
$loginEmpRole = $jsonData->loginEmpRole;
$categoryName = $jsonData->categoryName;

$distSubCat = [];
$sql = "SELECT `MenuId`,`Sub`,`Caption` FROM `Menu` WHERE `Cat` = '$categoryName' and `Tenent_Id` = $tenentId ORDER BY `MenuId` ASC ";
$query=mysqli_query($conn,$sql);

$output = array();
$level = 1;
$wrappedList = [];
while($row = mysqli_fetch_assoc($query)){
	$menuId = $row["MenuId"];
	$subName = $row["Sub"];
	$captionName = $row["Caption"];
	if($subName != ''){
		$level = 2;
	}
	if($captionName != ''){
		$level = 3;
	}
	if(!in_array($subName, $distSubCat)){
		$json = "";
		$json -> paramCode = $menuId;
		$json -> paramDesc = $subName;
		array_push($wrappedList,$json);
	
		array_push($distSubCat,$subName);
	}
}
$output = array('wrappedList' => $wrappedList, 'responseDesc' => 'SUCCESSFUL', 'responseCode' => '100000', 'count' => $level);
echo json_encode($output);
?>