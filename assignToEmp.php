<?php 
include("dbConfiguration.php");
$searchType = $_REQUEST["searchType"];
$tenentId = $_REQUEST["tenentId"];

$sql = "SELECT `EmpId`,`Name` FROM `Employees` WHERE `Active`= '1' and `Tenent_Id` = $tenentId ";
$query=mysqli_query($conn,$sql);
$empArr = array();
while($row = mysqli_fetch_assoc($query)){
	$empId = $row["EmpId"];
	$empName = $row["Name"];
	$json = array(
		'paramCode' => $empId,
		'paramDesc' => $empName,
	);
	array_push($empArr,$json);
}

$menuSql = "SELECT `MenuId`,`Cat`,`Sub`,`Caption` FROM `Menu` where `Tenent_Id` = $tenentId ";
$menuQuery=mysqli_query($conn,$menuSql);
$menuArr = array();
while($menuRow = mysqli_fetch_assoc($menuQuery)){
	$menuId = $menuRow["MenuId"];
	$cat = $menuRow["Cat"];
	$sub = $menuRow["Sub"];
	$caption = $menuRow["Caption"];

	$json1 = array(
		'paramCode' => $menuId,
		'paramDesc' => "(".$menuId.")".$cat." ~ ".$sub." ~ ".$caption
	);
	array_push($menuArr,$json1);

	// if($caption != null && $caption != ""){
	// 	$json1 = array(
	// 		'paramCode' => $menuId,
	// 		'paramDesc' => $caption,
	// 	);
	// 	array_push($menuArr,$json1);
	// }
	// else if($sub != null && $sub != ""){
	// 	$json1 = array(
	// 		'paramCode' => $menuId,
	// 		'paramDesc' => $sub,
	// 	);
	// 	array_push($menuArr,$json1);

	// }
	// else if($cat != null && $cat != ""){
	// 	$json1 = array(
	// 		'paramCode' => $menuId,
	// 		'paramDesc' => $cat,
	// 	);
	// 	array_push($menuArr,$json1);

	// }

	
}

$locationSql = "SELECT `LocationId`,`Name` FROM `Location` where `Is_Active` = 1 and `Tenent_Id` = $tenentId ";
$locationQuery=mysqli_query($conn,$locationSql);
$locationArr = array();
// if($searchType == 'checklist'){
// 	$forAll = array(
// 		'paramCode' => "0",
// 		'paramDesc' => "No Geofence",
// 	);
// 	array_push($locationArr,$forAll);
// }
while($locationRow = mysqli_fetch_assoc($locationQuery)){
	$locationId = $locationRow["LocationId"];
	$locationName = $locationRow["Name"];
	$json2 = array(
		'paramCode' => $locationId,
		'paramDesc' => $locationName,
	);
	array_push($locationArr,$json2);
}
$inputTypeSql = "SELECT `TypeId`,`Type` FROM `Type` order by `Type` ";
$inputTypeQuery=mysqli_query($conn,$inputTypeSql);
$inputTypeArr = array();
while($inputTypeRow = mysqli_fetch_assoc($inputTypeQuery)){
	$typeId = $inputTypeRow["TypeId"];
	$typeName = $inputTypeRow["Type"];
	if($typeId == 3 || $typeId == 4){
		$explodeTypeName = explode(",", $typeName);
		for($i=0;$i<count($explodeTypeName);$i++){
			$json3 = array(
				'paramCode' => $typeId.".".$i,
				'paramDesc' => $explodeTypeName[$i],
			);
			array_push($inputTypeArr,$json3);
		}
	}
	else{
		$json3 = array(
			'paramCode' => $typeId,
			'paramDesc' => $typeName,
		);
		array_push($inputTypeArr,$json3);
	}
	// $json3 = array(
	// 	'paramCode' => $typeId,
	// 	'paramDesc' => $typeName,
	// );
	// array_push($inputTypeArr,$json3);
}
$langSql = "SELECT `LanguageId`,`Name` FROM `Language` ";
$langQuery=mysqli_query($conn,$langSql);
$langArr = array();
while($inputTypeRow = mysqli_fetch_assoc($langQuery)){
	$langId = $inputTypeRow["LanguageId"];
	$langName = $inputTypeRow["Name"];
	$json4 = array(
		'paramCode' => $langId,
		'paramDesc' => $langName,
	);
	array_push($langArr,$json4);
}

// 'Admin','SpaceWorld','Vendor','Supervisor','PTW Raiser', 'PTW Admin'
$roleSql = "SELECT `RoleId`,`Role` FROM `Role` where `RoleId` not in (10,50,53,58,61,62) and `Tenent_Id` = $tenentId order by `Role` ";
$roleQuery=mysqli_query($conn,$roleSql);
$roleArr = array();
while($roleRow = mysqli_fetch_assoc($roleQuery)){
	$roleId = $roleRow["RoleId"];
	$roleName = $roleRow["Role"];
	$json5 = array(
		'paramCode' => $roleId,
		'paramDesc' => $roleName,
	);
	array_push($roleArr,$json5);
}

$rmSql = "SELECT `EmpId`,`Name` FROM `Employees` where `Active` = 1 and `Tenent_Id` = $tenentId ";
$rmQuery=mysqli_query($conn,$rmSql);
$rmArr = array();
while($rmRow = mysqli_fetch_assoc($rmQuery)){
	$rmId = $rmRow["EmpId"];
	$rmName = $rmRow["Name"];
	$json6 = array(
		'paramCode' => $rmId,
		'paramDesc' => $rmName,
	);
	array_push($rmArr,$json6);
}

$checkpointSql = "SELECT `CheckpointId`,`Description`,`TypeId` FROM `Checkpoints` where `Tenent_Id` = $tenentId order by `CheckpointId` desc ";
$checkpointQuery=mysqli_query($conn,$checkpointSql);
$checkpointArr = array();
while($checkpointRow = mysqli_fetch_assoc($checkpointQuery)){
	$checkpointId = $checkpointRow["CheckpointId"];
	$checkpointName = $checkpointRow["Description"];
	$typeId = $checkpointRow["TypeId"];
	$json7 = array(
		'paramCode' => $checkpointId,
		'paramDesc' => "(".$checkpointId.") ".$checkpointName,
		'typeId' => $typeId

	);
	array_push($checkpointArr,$json7);
}

$checklistSql = "SELECT * FROM `Menu` where `Tenent_Id` = $tenentId ";
$checklistQuery=mysqli_query($conn,$checklistSql);
$checklistArr = array();
while($checklistRow = mysqli_fetch_assoc($checklistQuery)){
	$menuId = $checklistRow["MenuId"];
	$category = $checklistRow["Cat"];
	$subcategory = $checklistRow["Sub"];
	$caption = $checklistRow["Caption"];


	$json8 = array(
		'menuId' => $menuId,
		'category' => $category,
		'subcategory' => $subcategory,
		'caption' => $caption
	);
	array_push($checklistArr,$json8);
}

$siteIdArr = array();
if($searchType == "location"){
	$siteSql = "SELECT `Site_Id`, `Name` FROM `Location` where `LocationId` !=1 and `Is_NBS_Site` = 0 and `Tenent_Id` = $tenentId and `Is_Active` = 1 ";
	$siteQuery=mysqli_query($conn,$siteSql);
	while($siteRow = mysqli_fetch_assoc($siteQuery)){
		$siteId = $siteRow["Site_Id"];
		$siteName = $siteRow["Name"];
		$json9 = array(
			'paramCode' => $siteId,
			'paramDesc' => $siteName.' ('.$siteId.')'
		);
		array_push($siteIdArr,$json9);
	}	
}
else if($searchType == "locationNBS"){
	$siteSql = "SELECT `Site_Id`, `Name` FROM `Location` where `LocationId` !=1 and `Is_NBS_Site` = 1 and `Tenent_Id` = $tenentId and `Is_Active` = 1 ";
	$siteQuery=mysqli_query($conn,$siteSql);
	while($siteRow = mysqli_fetch_assoc($siteQuery)){
		$siteId = $siteRow["Site_Id"];
		$siteName = $siteRow["Name"];
		$json9 = array(
			'paramCode' => $siteId,
			'paramDesc' => $siteName.' ('.$siteId.')'
		);
		array_push($siteIdArr,$json9);
	}	
}

$output = array();
$output = array('empList' => $empArr,'menuList' => $menuArr,'locationList' => $locationArr, 'inputTypeList' => $inputTypeArr, 'languageList' => $langArr, 
'roleList' => $roleArr, 'rmIdList' => $rmArr, 'checkpointList' => $checkpointArr,'categorySubcategoryCaptionList' => $checklistArr,'siteIdList' =>$siteIdArr);
echo json_encode($output);

?>