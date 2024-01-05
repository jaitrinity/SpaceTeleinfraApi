<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
require 'base64ToAny.php';
$insertType = $_REQUEST["insertType"];
$methodType = $_SERVER['REQUEST_METHOD'];
//echo $insertType;
$json = file_get_contents('php://input');
$jsonData=json_decode($json);
if($insertType == "employeeLocationMapping" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$tenentId = $jsonData->tenentId;
	$state = $jsonData->state;
	$siteId = $jsonData->siteId;
	$role = $jsonData->role;
	$employee = $jsonData->employee;
	$isOR = $jsonData->isOR;
	$actionType = $jsonData->actionType;
	$locType = $jsonData->locType;
	
	if($actionType == 'insert'){
		
		$filterSql = "";
		if($locType == "NBS"){
			$filterSql .= " and l.Is_NBS_Site = 1 ";
		}
		else{
			$filterSql .= " and l.Is_NBS_Site = 0 ";
		}

		$insertEmpLocMapping = "INSERT INTO `EmployeeLocationMapping`(`State`, `Name`, `LocationId`, `Role`, `Emp_Id`, `Tenent_Id`, `CreatedBy`) ";
		$tableData = "";
		if($isOR == false){
			$explodeState = explode(",", $state);
			$implodeState = implode("','", $explodeState);

			$stateLocSql = "SELECT l.LocationId FROM Location l where l.State in ('".$implodeState."') ".$filterSql." and l.Tenent_Id = $tenentId and l.Is_Active = 1 ";
			$stateLocQuery = mysqli_query($conn,$stateLocSql);
			while($stateLocRow = mysqli_fetch_assoc($stateLocQuery)){
				$locId = $stateLocRow["LocationId"];
				$locMapSql = "SELECT Id FROM EmployeeLocationMapping where LocationId = $locId and Emp_Id = '$employee' and Role = '$role' 
				and Tenent_Id = $tenentId and Is_Active = 1 ";
				$locMapQuery = mysqli_query($conn,$locMapSql);
				$locMapRowcount = mysqli_num_rows($locMapQuery);
				if($locMapRowcount == 0){
					$tableData .= "('', '', $locId, '$role', '$employee', $tenentId, '$loginEmpId'),";
				}
			}

			$tableData = rtrim($tableData,',');

			$insertEmpLocMapping .= 'VALUES '.$tableData;
		}
		else{
			$explodeSiteId = explode(",", $siteId);
			$implodeSiteId = implode("','", $explodeSiteId);

			$siteLocSql = "SELECT l.LocationId FROM Location l where l.Site_Id in ('".$implodeSiteId."') ".$filterSql." and l.Tenent_Id = $tenentId and l.Is_Active = 1 ";

			// echo $siteLocSql;
			$siteLocQuery = mysqli_query($conn,$siteLocSql);
			while($siteLocRow = mysqli_fetch_assoc($siteLocQuery)){
				$locId = $siteLocRow["LocationId"];
				$locMapSql = "SELECT Id FROM EmployeeLocationMapping where LocationId = $locId and Emp_Id = '$employee' and Role = '$role' 
				and Tenent_Id = $tenentId and Is_Active = 1 ";
				$locMapQuery = mysqli_query($conn,$locMapSql);
				$locMapRowcount = mysqli_num_rows($locMapQuery);
				if($locMapRowcount == 0){
					$tableData .= "('', '', $locId, '$role', '$employee', $tenentId, '$loginEmpId'),";
				}
			}

			$tableData = rtrim($tableData,',');

			$insertEmpLocMapping .= 'VALUES '.$tableData;
		}

		// echo $insertEmpLocMapping;

		$output = "";
		if($tableData == ""){
			$output -> responseCode = "403";
			$output -> responseDesc = "Employee location mapping already exist as per select criteria";
		}
		else{
			if(mysqli_query($conn,$insertEmpLocMapping)){
				$output -> responseCode = "100000";
				$output -> responseDesc = "Successfully inserted";
			}
			else{
				$output -> responseCode = "-102003";
				$output -> responseDesc = "Something went wrong";
			}
		}			
		echo json_encode($output);
	}	
	else{
		$updateEmpLocMapping = "";
		if($isOR == false){
			$locIdSql = "SELECT `LocationId` FROM `Location` where `State` = '$state' and `Tenent_Id` = $tenentId and Is_Active = 1 ";
			$locIdQuery = mysqli_query($conn,$locIdSql);
			$locIdList = [];
			while($locIdRow = mysqli_fetch_assoc($locIdQuery)){
				array_push($locIdList, $locIdRow["LocationId"]);
			}
			$updateEmpLocMapping = "UPDATE `EmployeeLocationMapping` SET `Emp_Id`='$employee', `Update_Date` = current_timestamp, `UpdatedBy` = '$loginEmpId'  
			WHERE `LocationId` in (".implode(",", $locIdList).") and `Role` = '$role' and `Tenent_Id`= $tenentId";
		}
		else{
			$locIdSql = "SELECT `LocationId` FROM `Location` where Site_Id in ('".implode("','", explode(",", $siteId))."') and `Tenent_Id` = $tenentId and Is_Active = 1 ";
			// echo $locIdSql;
			$locIdQuery = mysqli_query($conn,$locIdSql);
			$locIdList = [];
			while($locIdRow = mysqli_fetch_assoc($locIdQuery)){
				array_push($locIdList, $locIdRow["LocationId"]);
			}
			$updateEmpLocMapping = "UPDATE `EmployeeLocationMapping` SET `Emp_Id`='$employee', `Update_Date` = current_timestamp, `UpdatedBy` = '$loginEmpId'   
			WHERE `LocationId` in (".implode(",", $locIdList).") and `Role` = '$role' and `Tenent_Id`= $tenentId";
		}
			

		// echo $updateEmpLocMapping;
		$output = "";
		if(mysqli_query($conn,$updateEmpLocMapping)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully update";
		}
		else{
			$output -> responseCode = "-102003";
			$output -> responseDesc = "Something went wrong";
		}
		echo json_encode($output);
	}
}
else if($insertType == "importLocation" && $methodType === 'POST'){
	// echo $jsonData;
	$failExcelArr = [];
	foreach($jsonData as $importData) { 
		$loginEmpId = $importData->loginEmpId;
		$srNo = $importData->srNo;
		$state = $importData->state;
		$locationName = $importData->locationName;
		$siteId = $importData->siteId;
		$siteType = $importData->siteType;
		$siteCategory = $importData->siteCategory;
		$airportMetro = $importData->airportMetro;
		$rfiDate = $importData->rfiDate;
		$isHighRevenue = $importData->isHighRevenue;
		$isISQ = $importData->isISQ;
		$isRetailsIBS = $importData->isRetailsIBS;
		$geoCoordinate = $importData->geoCoordinate;
		$geoCoordinate = str_replace(",", "/", $geoCoordinate);
		$tenentId = $importData->tenentId;
		$locType = $importData->locType;

		$insertLocation = "INSERT INTO `Location`(`Name`, `State`, `Site_Id`, `Site_Type`, `Site_CAT`, `Airport_Metro`, `RFI_date`, `High_Revenue_Site`, 
		`ISQ`, `Retail_IBS`, `GeoCoordinates`, `Tenent_Id`, `Is_Active`, `CreatedBy`) 
		VALUES ('$locationName', '$state', '$siteId', '$siteType', '$siteCategory', '$airportMetro', '$rfiDate', $isHighRevenue, $isISQ, $isRetailsIBS, 
		'$geoCoordinate', $tenentId, 1, '$loginEmpId')";

		if($locType == "NBS"){
			$insertLocation = "INSERT INTO `Location`(`Name`, `State`, `Site_Id`, `Site_Type`, `Site_CAT`, `Airport_Metro`, `High_Revenue_Site`, `ISQ`, 
			`Retail_IBS`, `GeoCoordinates`, `Tenent_Id`, `Is_NBS_Site`, `Is_Active`, `CreatedBy`) 
				VALUES ('$locationName', '$state', '$siteId', '$siteType', '$siteCategory', '$airportMetro', $isHighRevenue, $isISQ, $isRetailsIBS, 
				'$geoCoordinate', $tenentId, 1, 1, '$loginEmpId')";
		}

		// echo $insertLocation;

		if(mysqli_query($conn,$insertLocation)){
			// Succfully insert
		}
		else{
			array_push($failExcelArr, $srNo);
		}
		
	}

	$output = "";
	if(count($failExcelArr) == 0){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully inserted";
	}
	else{
		$output -> responseCode = "-102003";
		$output -> responseDesc = "Except ".implode(',',$failExcelArr)." SrNo of excel, Data Successfully inserted";
	}
	echo json_encode($output);

}
else if($insertType == "location" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$loginEmpRoleId = $jsonData->loginEmpRoleId;
	$state = $jsonData->state;
	$locationName = $jsonData->locationName;
	$siteId = $jsonData->siteId;
	$siteType = $jsonData->siteType;
	$siteCategory = $jsonData->siteCategory;
	$airportMetro = $jsonData->airportMetro;
	$isHighRevenue = $jsonData->isHighRevenue;
	$isISQ = $jsonData->isISQ;
	$isRetailsIBS = $jsonData->isRetailsIBS;
	$rfiDate = $jsonData->rfiDate;
	$geoCoordinate = $jsonData->geoCoordinate;
	$geoCoordinate = str_replace(",", "/", $geoCoordinate);
	$address = $jsonData->address;
	$tenentId = $jsonData->tenentId;
	$locType = $jsonData->locType;

	$insertLocation = "INSERT INTO `Location`(`Name`, `State`, `Site_Id`, `Site_Type`, `Site_CAT`, `Airport_Metro`, `RFI_date`, `High_Revenue_Site`, `ISQ`, 
	`Retail_IBS`, `GeoCoordinates`, `Address`, `Tenent_Id`, `Is_Active`, `CreatedBy`) 
	VALUES ('$locationName', '$state', '$siteId', '$siteType', '$siteCategory', '$airportMetro', '$rfiDate', $isHighRevenue, $isISQ, $isRetailsIBS, 
	'$geoCoordinate', '$address', $tenentId, 1, '$loginEmpId')";

	if($locType == "NBS"){
		$insertLocation = "INSERT INTO `Location`(`Name`, `State`, `Site_Id`, `Site_Type`, `Site_CAT`, `Airport_Metro`, `High_Revenue_Site`, `ISQ`, 
	`Retail_IBS`, `GeoCoordinates`, `Address`, `Tenent_Id`, `Is_NBS_Site`, `Is_Active`, `CreatedBy`) 
		VALUES ('$locationName', '$state', '$siteId', '$siteType', '$siteCategory', '$airportMetro', $isHighRevenue, $isISQ, $isRetailsIBS, 
		'$geoCoordinate', '$address', $tenentId, 1, 1, '$loginEmpId')";
	}

	$output = "";
	if(mysqli_query($conn,$insertLocation)){
		//$last_id = $conn->insert_id;
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully inserted";
		//echo "New record created successfully. Last inserted ID is: " . $last_id;
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
		//echo "New record created successfully. Last inserted ID is: " . $last_id;
	}
	echo json_encode($output);
}
else if($insertType == "employee" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$employeeName = $jsonData->employeeName;
	$roleId = $jsonData->roleId;
	$mobile = $jsonData->mobile;
	$state = $jsonData->state;
	$tenentId = $jsonData->tenentId;
	$fieldUser = 0;

	$sql1 = "select * from `Employees` where `Mobile` = '$mobile' and `Tenent_Id` = $tenentId and `Active` = 1";
	$query1 = mysqli_query($conn,$sql1);

	$isExist1 = false;
	if(mysqli_num_rows($query1) != 0){
		$isExist1 = true;
	}

	$output = "";
	if($isExist1){
		$output -> responseCode = "422";
		$output -> responseDesc = "already exist employee on ".$mobile." mobile number";
	}
	
	else{
		// $employeeId = rand(10001,99999);
		// $employeeId = $mobile;
		$currentTime = time();
		$employeeId = $currentTime;
		$password  = $mobile;

		$insertEmployee = "INSERT INTO `Employees`(`EmpId`, `Name`, `Password`, `Mobile`, `RoleId`, `State`, `FieldUser`, 
		`Tenent_Id`, `Registered`, `Update`, `Active`, `CreatedBy`) VALUES ('$employeeId', '$employeeName', '$password', '$mobile', $roleId, '$state', $fieldUser, 
		$tenentId, current_timestamp, current_timestamp, 1, '$loginEmpId')";

		// echo $insertEmployee;

		if(mysqli_query($conn,$insertEmployee)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully inserted";	
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
	}
	echo json_encode($output);

}
else if($insertType == "assign" && $methodType === 'POST'){
	$empId = $jsonData->empId;
	$menuId = $jsonData->menuId;
	$locationId = $jsonData->locationId;
	$startDate = $jsonData->startDate;
	$endDate = $jsonData->endDate;
	
	$insertAssign = "INSERT INTO `Assign`(`EmpId`, `MenuId`,`LocationId`,`StartDate`,`EndDate`,`Active`) VALUES ('$empId',$menuId,'$locationId','$startDate','$endDate',1)";


	$output = "";
	if(mysqli_query($conn,$insertAssign)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully inserted";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);

}
else if($insertType == "mapping" && $methodType === 'POST'){
	$empRole = $jsonData->empRole;
	$menuId = $jsonData->menuId;
	$state = $jsonData->state;
	$city = $jsonData->city;
	$area = $jsonData->area;
	$verifierRole = $jsonData->verifierRole;
	$approverRole = $jsonData->approverRole;
	$startDate = $jsonData->startDate;
	$endDate = $jsonData->endDate;
	$tenentId = $jsonData->tenentId;

	$explodeState = explode(",", $state);
	$implodeState = implode("','", $explodeState);

	$explodeEmpRole = explode(",", $empRole);
	$implodeEmpRole = implode("','", $explodeEmpRole);

	$sql = "SELECT `Emp_Id`,`LocationId` FROM `EmployeeLocationMapping` WHERE `State` in ('".$implodeState."') and `Role` in ('".$implodeEmpRole."') 
	and `Tenent_Id` = $tenentId and Is_Active = 1 ";
	if($city != ''){
	 	$sql .= "and `City` = '$city' ";
	}
	
	if($area != ''){
		$sql .= "and `Area` = '$area' ";
	}
	
	// echo $sql;
	$query=mysqli_query($conn,$sql);
	$rowcount=mysqli_num_rows($query);

	$sql1 = "SELECT `Emp_Id` FROM `EmployeeLocationMapping` WHERE `State` in ('".$implodeState."') and `Role` = '$verifierRole' and `Tenent_Id` = $tenentId and Is_Active = 1 ";
	if($city != ''){
	 	$sql1 .= "and `City` = '$city' ";
	}
	
	if($area != ''){
		$sql1 .= "and `Area` = '$area' ";
	}
	
	$query1=mysqli_query($conn,$sql1);
	$row1 = mysqli_fetch_assoc($query1);
	$verifierId = $row1["Emp_Id"];

	$sql2 = "SELECT `Emp_Id` FROM `EmployeeLocationMapping` WHERE `State` in ('".$implodeState."') and `Role` = '$approverRole' and `Tenent_Id` = $tenentId and Is_Active = 1 ";
	if($city != ''){
	 	$sql2 .= "and `City` = '$city' ";
	}
	
	if($area != ''){
		$sql2 .= "and `Area` = '$area' ";
	}
	
	$query2=mysqli_query($conn,$sql2);
	$row2 = mysqli_fetch_assoc($query2);
	$approverId = $row2["Emp_Id"];

	$insertTable = "INSERT INTO `Mapping`(`EmpId`, `MenuId`, `LocationId`, `Verifier`,`Approver`,`Start`,`End`,`Tenent_Id`) ";
	$insertValue = "";
	$ii = 0;
	while($row = mysqli_fetch_assoc($query)){
		$fillerId = $row["Emp_Id"];
		$locationId = $row["LocationId"];

		$insertValue .= "('$fillerId',$menuId,'$locationId', '$verifierId','$approverId','$startDate','$endDate',$tenentId)";
		if($ii<($rowcount-1)){
			$insertValue .= ",";
		}

		$ii++;
	}
	
	$output = "";

	if($insertValue != ""){
		
		$insertMapping = $insertTable.' VALUES '.$insertValue;
		// echo $insertMapping;
		
		if(mysqli_query($conn,$insertMapping)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully inserted";
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
	}
	else{
		$output -> responseCode = "404";
		$output -> responseDesc = "No record found as per select data.";
	}
	echo json_encode($output);

}
else if($insertType == "mapping_old" && $methodType === 'POST'){
	$empId = $jsonData->empId;
	$menuId = $jsonData->menuId;
	$locationId = $jsonData->locationId;
	$verifier = $jsonData->verifier;
	$approver = $jsonData->approver;
	$startDate = $jsonData->startDate;
	$endDate = $jsonData->endDate;
	$tenentId = $jsonData->tenentId;

	$explodeLocationId = explode(",", $locationId);
	$insertMapping = "INSERT INTO `Mapping`(`EmpId`, `MenuId`,`LocationId`,`Verifier`,`Approver`,`Start`,`End`,`Tenent_Id`) VALUES ";
	$insertValue = "";
	for($i=0;$i<count($explodeLocationId);$i++){
		$insertValue .= "('$empId',$menuId,'".$explodeLocationId[$i]."','$verifier','$approver','$startDate','$endDate',$tenentId)";
		if($i != count($explodeLocationId)-1){
			$insertValue .= ",";
		}
	}
	
	$output = "";
	if(mysqli_query($conn,$insertMapping.$insertValue)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully inserted";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);

}
else if($insertType == "checkpoint" && $methodType === 'POST'){
	$tenentId = $jsonData->tenentId;
	$description = $jsonData->description;
	$optionValue = $jsonData->optionValue;
	$isMandatory = $jsonData->isMandatory;
	$isEditable = $jsonData->isEditable;
	$inputTypeId = $jsonData->inputTypeId;
	$languageId = $jsonData->languageId;
	$correct = $jsonData->correct;
	$size = $jsonData->size;
	$score = $jsonData->score;
	$dependent = $jsonData->dependent;
	$isSql = $jsonData->isSql;
	$active = $jsonData->active;
	$logic = $jsonData->logic;
	$type = $jsonData->type;
	$videoBase64 = $jsonData->videoBase64;
	$imageBase64 = $jsonData->imageBase64;
	if($active == ""){
		$active = 1;
	}

	$errorInSql = "";
	$queryColumn = "";
	$columnValueArr = array();
 
	if($type == 0 && $inputTypeId == 21){
		$descSql = $optionValue;
		// echo $descSql;
		if(startsWith($descSql,"SELECT")){
			if(!$conn->query($descSql)){
				
				$errorInSql = ":".$conn->error;
			}
			else{
				
				$queryResult = mysqli_query($conn,$descSql);
				$fieldinfo = $queryResult -> fetch_fields();
				if(count($fieldinfo) == 1){
					foreach ($fieldinfo as $val) {
					    $queryColumn .= $val -> name;

					}

					array_push($columnValueArr,$queryColumn);
					while($row = mysqli_fetch_assoc($queryResult)){
						array_push($columnValueArr,$row[$queryColumn]);
					}					
				}
				else{
					$errorInSql = ": Only single column query valid.";
				}
			}
		}
		else{
			$errorInSql = ": only `select` query is valid. ";
		}
	}


	$t=time();
	$base64 = new Base64ToAny();

	if($inputTypeId == 18){
		$optionValue = $base64->base64_to_jpeg($videoBase64,$t.'_Video');
	}
	else if($inputTypeId == 19){
		$optionValue = $base64->base64_to_jpeg($imageBase64,$t.'_Image');
	}
	
	$insertCheckpoint = "INSERT INTO `Checkpoints`(`Description`, `Value`, `TypeId`, `Mandatory`, `Editable`, `Language`, `Correct`, `Size`, `Score`, 
	`Dependent`, `Logic`, `IsSql`, `Active`,`Tenent_Id`) 
	VALUES ('$description', '$optionValue', $inputTypeId, $isMandatory, $isEditable, $languageId, '$correct', '$size', '$score', '$dependent', '$logic', 
	$isSql, $active, $tenentId)";

	$output = "";
	if($errorInSql !=""){
		$output -> responseCode = "-102003";
		$output -> responseDesc = "Wrong Sql ".$errorInSql;
	}
	else if($queryColumn != ""){
		$output -> responseCode = "200";
		$output -> responseDesc = "Sql Column :".$queryColumn;
		$output -> columnValueArr = $columnValueArr;
	}
	else if($errorInSql == "" && mysqli_query($conn,$insertCheckpoint)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully inserted";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong ";
	}
	echo json_encode($output);

}
else if($insertType == "inputType" && $methodType === 'POST'){
	$typeName = $jsonData->typeName;
	$insertInputType = "INSERT INTO `Type`(`Type`) VALUES ('$typeName')";
	$output = "";
	if(mysqli_query($conn,$insertInputType)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully inserted";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($insertType == "checklist" && $methodType === 'POST'){
	$category = $jsonData->category;
	$subcategory = $jsonData->subcategory;
	$caption = $jsonData->caption;
	$checkpointId = $jsonData->checkpointId;
	$verifierId = $jsonData->verifierId;
	$approverId = $jsonData->approverId;
	$geoFence = $jsonData->geoFence;
	$icons = $jsonData->icons;
	$categoryIcon = $jsonData->categoryIcon;
	$subcategoryIcon = $jsonData->subcategoryIcon;
	$captionIcon = $jsonData->captionIcon;
	$active = $jsonData->active;
	$editMenuId = $jsonData->editMenuId;
	$tenentId = $jsonData->tenentId;
	$verifierRole = $jsonData->verifierRole;
	$approvalRole = $jsonData->approvalRole;
	$type = $jsonData->type;

	$t=time();
	$base64 = new Base64ToAny();

	if($type == "new"){

		if($categoryIcon != ""){
			$icons = $base64->base64_to_jpeg($categoryIcon,$t.'_CategoryIcon');
		}
		if($subcategory != "" && $subcategoryIcon != ""){
			$ic = $base64->base64_to_jpeg($subcategoryIcon,$t.'_SubcategoryIcon');
			$icons .= ','.$ic;
		}
		if($caption != "" && $captionIcon != ""){
			$ic = $base64->base64_to_jpeg($captionIcon,$t.'_CaptionIcon');
			$icons .= ','.$ic;
		}

		
		$insertChecklist = "INSERT INTO `Menu`(`Cat`,`Sub`,`Caption`,`CheckpointId`,`Verifier`,`Approver`,`GeoFence`,`Icons`,`Active`,`Verifier_Role`,
		`Approver_Role`,`Tenent_Id`) VALUES 
		('$category', '$subcategory','$caption','$checkpointId','$verifierId','$approverId','$geoFence','$icons',$active,'$verifierRole',
		'$approvalRole',$tenentId)";

		$output = "";
		$checklistResult = mysqli_query($conn,$insertChecklist);
		// echo $checklistResult;
		if($checklistResult != ""){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully inserted";
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
		echo json_encode($output);
	}
	else{
		

		if($categoryIcon != ""){
			$icons = $base64->base64_to_jpeg($categoryIcon,$t.'_CategoryIcon');
		}
		if($subcategory != "" && $subcategoryIcon != ""){
			$ic = $base64->base64_to_jpeg($subcategoryIcon,$t.'_SubcategoryIcon');
			$icons .= ','.$ic;
		}
		if($caption != "" && $captionIcon != ""){
			$ic = $base64->base64_to_jpeg($captionIcon,$t.'_CaptionIcon');
			$icons .= ','.$ic;
		}


		$updateChecklist = "UPDATE `Menu` set `Cat` = '$category', `Sub` = '$subcategory', `Caption` = '$caption', `CheckpointId` = '$checkpointId',
		`Verifier` = '$verifierId', `Approver` = '$approverId', `GeoFence` = '$geoFence', `Icons` = '$icons', `Active` = $active, 
		`Verifier_Role` = '$verifierRole', `Approver_Role` = '$approvalRole'
		where `MenuId` = '$editMenuId' ";

		$output = "";
		$checklistResult = mysqli_query($conn,$updateChecklist);
		// echo $checklistResult;
		if($checklistResult != ""){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully inserted";
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
		echo json_encode($output);
	}

		
}
else if($insertType == "role" && $methodType === 'POST'){
	$roleName = $jsonData->roleName;
	$menuId = $jsonData->menuId;
	$tenentId = $jsonData->tenentId;

	$sql = "SELECT * FROM `Role` WHERE `Role` = '$roleName' and Tenent_Id = $tenentId ";
	$query=mysqli_query($conn,$sql);
	$rowcount=mysqli_num_rows($query);

	$output = "";
	if($rowcount == 0){

		$insertRole = "INSERT INTO `Role`(`Role`,`MenuId`,`Tenent_Id`) VALUES ('$roleName', '$menuId', $tenentId)";
		if(mysqli_query($conn,$insertRole)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully inserted";
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
	}
	else{
		$output -> responseCode = "403";
		$output -> responseDesc = "$roleName role already exist.";
	}
	echo json_encode($output);
}
else if($insertType == "submitSiteSurvey" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$tenentId = $jsonData->tenentId;
	$saveData = $jsonData->saveData;
	$locId = 1;
	$menuId = 279;
	$status = false;
	$message = "";
	if(count($saveData) != 0){
		$insertLocId = 1;
		for($inc=0;$inc<4;$inc++){
			$activitySql = "Insert into Activity(EmpId, MenuId, LocationId, Event, MobileDateTime, Tenent_Id) values ('$loginEmpId', $menuId, $locId, 
			'Submit', current_timestamp, $tenentId)";
			if(mysqli_query($conn,$activitySql)){
				$activityId = mysqli_insert_id($conn);
				$status = true;

				$insertMapping = "INSERT INTO `Mapping`(`EmpId`, `MenuId`, `LocationId`, `Start`, `End`, `ActivityId`, `Tenent_Id`, `CreateDateTime`) 
				values ('$loginEmpId', $menuId, $locId, curdate(), curdate(), '$activityId', '$tenentId', current_timestamp) ";

				if(mysqli_query($conn,$insertMapping)){
					$mappingId = $conn->insert_id;
					$status = true;
					$insertInTransHdr="INSERT INTO `TransactionHDR` (`ActivityId`, `Status`) VALUES ('$activityId', 'Created')";
					if(mysqli_query($conn,$insertInTransHdr)){
						$lastTransHdrId = $conn->insert_id;
						$status = true;

						$customerId = "";
						$acEmpId = "";
						$lat = "";
						$long = "";
						for($ii=0;$ii<count($saveData);$ii++){
							$chkp_id = $saveData[$ii]->chpId;
							$answer = $saveData[$ii]->value;

								
							if($chkp_id == 4927){
								$customerId = $answer.'_'.$inc;
								$answer = $answer.'_'.$inc;
							}
							if($chkp_id == 5196){
								$acEmpId = $answer;
							}
							if($chkp_id == 4931){
								$lat = $answer;
							}
							if($chkp_id == 4932){
								$long = $answer;
							}

							$insertInTransDtl="INSERT INTO `TransactionDTL` (`ActivityId`, `ChkId`, `Value`, `DependChkId`) 
							VALUES ($activityId, '$chkp_id', '$answer', 0)";
							if(mysqli_query($conn,$insertInTransDtl)){
								$status = true;
							}
							else{
								$status = false;
								$message = "Something went wrong while save data in `TransactionDTL` table";
							}
						}
							

						if($acEmpId != ""){
							$explodeAcEmpId = explode(" --- ", $acEmpId);
							$acEmpId = $explodeAcEmpId[0];
							$acEmpName = $explodeAcEmpId[1];

							$updateMapping = "update Mapping set Verifier = '$acEmpId' where MappingId = $mappingId and Tenent_Id = $tenentId ";
							mysqli_query($conn,$updateMapping);

							$updateHdr = "update `TransactionHDR` set `Assign_To` = '$acEmpName' where `SRNo` = $lastTransHdrId ";
							mysqli_query($conn,$updateHdr);
						}

						if($customerId != ""){
							$updateHdr = "update `TransactionHDR` set `Customer_Site_Id` = '$customerId' where `SRNo` = $lastTransHdrId ";
							mysqli_query($conn,$updateHdr);

							$updateMapping = "update Mapping set Customer_Site_Id = '$customerId' where MappingId = $mappingId and 
							Tenent_Id = $tenentId ";
							mysqli_query($conn,$updateMapping);

							$updateActivity = "update `Activity` set `Customer_Site_Id` = '$customerId' where `ActivityId` = $activityId ";
							mysqli_query($conn,$updateActivity);
						}

						if($lat != '' && $long !=''){
							$updateHdr = "update `TransactionHDR` set `Nominal_Latlong` = '$lat/$long' where `SRNo` = $lastTransHdrId ";
							mysqli_query($conn,$updateHdr);

						}
					}
					else{
						$status = false;
						$message = "Something went wrong while save data in `TransactionHDR` table";
					}
				}
				else{
					$status = false;
					$message = "Something went wrong while save data in `Mapping` table";
				}
			}
			else{
				$status = false;
				$message = "Something went wrong while save data in `Activity` table";
			}
		}
	}
	else{
		$message = "No data for saving.";
	}
	
	$output = "";
	$output -> status = $status;
	$output -> message = $message;
	echo json_encode($output);
}
else if($insertType == "submitPtw" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$loginEmpName = $jsonData->loginEmpName;
	$browser = $jsonData->browser;
	$workStartDatetime = $jsonData->workStartDatetime;
	$workEndDatetime = $jsonData->workEndDatetime;
	$tenentId = $jsonData->tenentId;
	$circle = $jsonData->circle;
	$siteName = $jsonData->siteName;
	$supervisorMobile = $jsonData->supervisorMobile;
	$saveData = $jsonData->saveData;
	$height = $jsonData->height;
	$electrical = $jsonData->electrical;
	$matHandling = $jsonData->matHandling;
	$ofcRoute = $jsonData->ofcRoute;
	$confined = $jsonData->confined;
	$hotWork = $jsonData->hotWork;
	$siteAccess = $jsonData->siteAccess;
	$explodeSiteName = explode(" --- ", $siteName);
	$siteId = $explodeSiteName[1];
	$siteNamee = $explodeSiteName[0];

	$vendorSql = "SELECT e.VendorType FROM Employees e WHERE e.EmpId = '$loginEmpId' and e.Active = 1";
	$vendorQuery=mysqli_query($conn,$vendorSql);
	$vendorRow = mysqli_fetch_assoc($vendorQuery);
	$vendorType = $vendorRow["VendorType"];

	$svSql = "SELECT e.EmpId FROM Employees e WHERE e.Mobile = '$supervisorMobile' and e.Active = 1 ";
	$svQuery=mysqli_query($conn,$svSql);
	$svRow = mysqli_fetch_assoc($svQuery);
	$supervisorMobile = $svRow["EmpId"];
	
	$locId = 1;
	$menuId = 303;
	if($height) $menuId = 304;
	if($electrical) $menuId = 305;
	if($matHandling) $menuId = 306;
	if($ofcRoute) $menuId = 307;
	if($confined) $menuId = 308;
	if($hotWork) $menuId = 309;
	if($siteAccess) $menuId = 310;

	$ptwType = "";
	if($height) $ptwType = "Height";
	if($electrical) $ptwType = "Electrical";
	if($matHandling) $ptwType = "Material Handling";
	if($ofcRoute) $ptwType = "OFC-Route Work";
	if($confined) $ptwType = "Confined Space Work";
	if($hotWork) $ptwType = "Hot Work";
	if($siteAccess) $ptwType = "Site Access";

	$locSql = "SELECT `LocationId` FROM `Location` WHERE `Name` = '$siteNamee' and `Site_Id` = '$siteId' and `Is_Active` = 1 ";
	$locResult = mysqli_query($conn,$locSql);
	$locRow = mysqli_fetch_assoc($locResult);
	$locId = $locRow["LocationId"];

	$sql = "SELECT r1.Verifier_Role, r1.Approver_Role FROM `Menu` r1 where r1.MenuId = '$menuId' ";
	$result = mysqli_query($conn,$sql);
	$row = mysqli_fetch_assoc($result);
	$verifier_Role = $row["Verifier_Role"];
	$approver_Role = $row["Approver_Role"];

	$verifierMobile = "";
	if($verifier_Role != null && $verifier_Role !=''){
		$sql2 = "SELECT el.Emp_Id FROM EmployeeLocationMapping el where el.LocationId = $locId and el.Role = '$verifier_Role' and el.Tenent_Id = $tenentId and el.Is_Active = 1 ";
		$result2 = mysqli_query($conn,$sql2);
		while ($row2 = mysqli_fetch_assoc($result2)) {
			$verifierMobile .= $row2["Emp_Id"].',';
		}
		$otherSql = "SELECT * FROM `Employees` where RoleId in (52,59,60) and Active = 1";
		$otherResult = mysqli_query($conn,$otherSql);
		while ($otherRow = mysqli_fetch_assoc($otherResult)) {
			$verifierMobile .= $otherRow["EmpId"].',';
		}
		if($verifierMobile != ""){
			$verifierMobile = substr($verifierMobile, 0, strlen($verifierMobile)-1);
		}
	}

	$auditMobile = "";
	$auditSql = "SELECT * FROM `Employees` where `RoleId` in (43,44,45,51,52,57,59,60,54,63) and `Active` = 1";
	$auditResult = mysqli_query($conn,$auditSql);
	while ($auditRow = mysqli_fetch_assoc($auditResult)) {
		$auditMobile .= $auditRow["EmpId"].',';
	}
	if($auditMobile != ""){
		$auditMobile = substr($auditMobile, 0, strlen($auditMobile)-1);
	}

	$status = false;
	$message = "";
	$activitySql = "INSERT INTO Activity(EmpId, MenuId, LocationId, Event, Browser, MobileDateTime, Tenent_Id) VALUES ('$loginEmpId', $menuId, $locId, 'Submit', '$browser', current_timestamp, $tenentId)";
	if(mysqli_query($conn,$activitySql)){
		$activityId = mysqli_insert_id($conn);
		$status = true;
		$insertMapping = "INSERT INTO `Mapping`(`EmpId`, `Verifier`, `Approver`, `Third`, `Fourth`, `Fifth`, `MenuId`, `LocationId`, `Start`, `End`, `ActivityId`, `Tenent_Id`, `CreateDateTime`) 
				VALUES ('$loginEmpId', '$verifierMobile', '$supervisorMobile', '$supervisorMobile', '$auditMobile', '$supervisorMobile', $menuId, $locId, curdate(), curdate(), '$activityId', '$tenentId', current_timestamp) ";
		if(mysqli_query($conn,$insertMapping)){
			$mappingId = $conn->insert_id;
			$status = true;
			$insertInTransHdr="INSERT INTO `TransactionHDR` (`ActivityId`, `Site_Id`, `Site_Name`, `Status`, `WorkStartDatetime`, `WorkEndDatetime`, `StatusDatetime`) VALUES ('$activityId', '$siteId', '$siteNamee', 'PTW_01', '$workStartDatetime', '$workEndDatetime', current_timestamp)";
			if(mysqli_query($conn,$insertInTransHdr)){
				$lastTransHdrId = $conn->insert_id;
				$status = true;

				for($ii=0;$ii<count($saveData);$ii++){
					$chkp_id = $saveData[$ii]->chpId;
					$answer = $saveData[$ii]->value;
					$dependent = $saveData[$ii]->dependent;
					if($chkp_id == 5510) $answer = $vendorType;

					$insertInTransDtl="INSERT INTO `TransactionDTL` (`ActivityId`, `ChkId`, `Value`, `DependChkId`) 
					VALUES ($activityId, '$chkp_id', '$answer', $dependent)";
					if(mysqli_query($conn,$insertInTransDtl)){
						$status = true;
					}
					else{
						$status = false;
						$message = "Something went wrong while save data in `TransactionDTL` table";
					}
				}
			}
			else{
				$status = false;
				$message = "Something went wrong while save data in `TransactionHDR` table";
			}
		}
		else{
			$status = false;
			$message = "Something went wrong while save data in `Mapping` table";
		}
	}
	else{
		$status = false;
		$message = "Something went wrong while save data in `Activity` table";
	}

	if($status){
		$msg = "Dear Pushkar,<br><br>";
		$msg .= "PTW ($activityId) has been raised by $loginEmpName. <br>"; 
		$msg .= "<h3><u>PTW Details :</u></h3>";
		$msg .= "Circle : $circle <br>";
		$msg .= "Site : $siteNamee($siteId) <br>";
		$msg .= "Work Start Datetime : $workStartDatetime <br>";
		$msg .= "Work End Datetime : $workEndDatetime <br>";
		$msg .= "PTW Type : $ptwType <br><br>";
		$msg .= "Kindly do the needful. "; 
		// sendMail($msg);
	}

	$output = "";
	$output -> status = $status;
	$output -> message = $message;
	echo json_encode($output);
}
else if($insertType == "submitPtw_new" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$loginEmpName = $jsonData->loginEmpName;
	$browser = $jsonData->browser;
	$workStartDatetime = $jsonData->workStartDatetime;
	$workEndDatetime = $jsonData->workEndDatetime;
	$tenentId = $jsonData->tenentId;
	$circle = $jsonData->circle;
	$siteName = $jsonData->siteName;
	$supervisorMobile = $jsonData->supervisorMobile;
	$saveData = $jsonData->saveData;
	$height = $jsonData->height;
	$electrical = $jsonData->electrical;
	$matHandling = $jsonData->matHandling;
	$ofcRoute = $jsonData->ofcRoute;
	$confined = $jsonData->confined;
	$hotWork = $jsonData->hotWork;
	$siteAccess = $jsonData->siteAccess;
	$explodeSiteName = explode(" --- ", $siteName);
	$siteId = $explodeSiteName[1];
	$siteNamee = $explodeSiteName[0];

	$vendorSql = "SELECT e.VendorType FROM Employees e WHERE e.EmpId = '$loginEmpId' and e.Active = 1";
	$vendorQuery=mysqli_query($conn,$vendorSql);
	$vendorRow = mysqli_fetch_assoc($vendorQuery);
	$vendorType = $vendorRow["VendorType"];

	$svSql = "SELECT e.EmpId FROM Employees e WHERE e.Mobile = '$supervisorMobile' and e.Active = 1";
	$svQuery=mysqli_query($conn,$svSql);
	$svRow = mysqli_fetch_assoc($svQuery);
	$supervisorMobile = $svRow["EmpId"];
	
	$locId = 1;
	$menuId = 303;
	if($height) $menuId = 304;
	if($electrical) $menuId = 305;
	if($matHandling) $menuId = 306;
	if($ofcRoute) $menuId = 307;
	if($confined) $menuId = 308;
	if($hotWork) $menuId = 309;
	if($siteAccess) $menuId = 310;

	$ptwType = "";
	if($height) $ptwType = "Height";
	if($electrical) $ptwType = "Electrical";
	if($matHandling) $ptwType = "Material Handling";
	if($ofcRoute) $ptwType = "OFC-Route Work";
	if($confined) $ptwType = "Confined Space Work";
	if($hotWork) $ptwType = "Hot Work";
	if($siteAccess) $ptwType = "Site Access";

	$locSql = "SELECT `LocationId` FROM `Location` WHERE `Name` = '$siteNamee' and `Site_Id` = '$siteId' and `Is_Active` = 1 ";
	$locResult = mysqli_query($conn,$locSql);
	$locRow = mysqli_fetch_assoc($locResult);
	$locId = $locRow["LocationId"];

	$sql = "SELECT r1.Verifier_Role, r1.Approver_Role FROM `Menu` r1 where r1.MenuId = '$menuId' ";
	$result = mysqli_query($conn,$sql);
	$row = mysqli_fetch_assoc($result);
	$verifier_Role = $row["Verifier_Role"];
	$approver_Role = $row["Approver_Role"];

	$verifierMobile = "";
	if($verifier_Role != null && $verifier_Role !=''){
		$sql2 = "SELECT el.Emp_Id FROM EmployeeLocationMapping el where el.LocationId = $locId and el.Role = '$verifier_Role' and el.Tenent_Id = $tenentId and el.Is_Active = 1 ";
		$result2 = mysqli_query($conn,$sql2);
		while ($row2 = mysqli_fetch_assoc($result2)) {
			$verifierMobile .= $row2["Emp_Id"].',';
		}
		$otherSql = "SELECT * FROM `Employees` where RoleId in (52,59,60) and Active = 1";
		$otherResult = mysqli_query($conn,$otherSql);
		while ($otherRow = mysqli_fetch_assoc($otherResult)) {
			$verifierMobile .= $otherRow["EmpId"].',';
		}
		if($verifierMobile != ""){
			$verifierMobile = substr($verifierMobile, 0, strlen($verifierMobile)-1);
		}
	}

	$auditMobile = "";
	$auditSql = "SELECT * FROM `Employees` where `RoleId` in (43,44,45,51,52,57,59,60,54,63) and `Active` = 1";
	$auditResult = mysqli_query($conn,$auditSql);
	while ($auditRow = mysqli_fetch_assoc($auditResult)) {
		$auditMobile .= $auditRow["EmpId"].',';
	}
	if($auditMobile != ""){
		$auditMobile = substr($auditMobile, 0, strlen($auditMobile)-1);
	}

	$status = false;
	$message = "";
	$activitySql = "INSERT INTO Activity(EmpId, MenuId, LocationId, Event, Browser, MobileDateTime, Tenent_Id) VALUES ('$loginEmpId', $menuId, $locId, 'Submit', '$browser', current_timestamp, $tenentId)";
	if(mysqli_query($conn,$activitySql)){
		$activityId = mysqli_insert_id($conn);
		$status = true;
		$insertMapping = "INSERT INTO `Mapping`(`EmpId`, `Verifier`, `Approver`, `Third`, `Fourth`, `Fifth`, `MenuId`, `LocationId`, `Start`, `End`, `ActivityId`, `Tenent_Id`, `CreateDateTime`) 
				VALUES ('$loginEmpId', '$verifierMobile', '$supervisorMobile', '$supervisorMobile', '$auditMobile', '$supervisorMobile', $menuId, $locId, curdate(), curdate(), '$activityId', '$tenentId', current_timestamp) ";
		if(mysqli_query($conn,$insertMapping)){
			$mappingId = $conn->insert_id;
			$status = true;
			$insertInTransHdr="INSERT INTO `TransactionHDR` (`ActivityId`, `Site_Id`, `Site_Name`, `Status`, `WorkStartDatetime`, `WorkEndDatetime`, `StatusDatetime`) VALUES ('$activityId', '$siteId', '$siteNamee', 'PTW_01', '$workStartDatetime', '$workEndDatetime', current_timestamp)";
			if(mysqli_query($conn,$insertInTransHdr)){
				$lastTransHdrId = $conn->insert_id;
				$status = true;

				for($ii=0;$ii<count($saveData);$ii++){
					$chkp_id = $saveData[$ii]->chpId;
					$answer = $saveData[$ii]->value;
					$dependent = $saveData[$ii]->dependent;
					if($chkp_id == 5510) $answer = $vendorType;
					else if($chkp_id == 5826){
						$t=time();
						$base64 = new Base64ToAny();
						$sstReport = $base64->base64_to_jpeg($answer,$t.'_'.$chkp_id.'_'.$dependent);
						$answer = $sstReport;
					}
					else if($chkp_id == 5827){
						$t=time();
						$base64 = new Base64ToAny();
						$strDrawing = $base64->base64_to_jpeg($answer,$t.'_'.$chkp_id.'_'.$dependent);
						$answer = $strDrawing;
					}


					$insertInTransDtl="INSERT INTO `TransactionDTL` (`ActivityId`, `ChkId`, `Value`, `DependChkId`) 
					VALUES ($activityId, '$chkp_id', '$answer', $dependent)";
					if(mysqli_query($conn,$insertInTransDtl)){
						$status = true;
					}
					else{
						$status = false;
						$message = "Something went wrong while save data in `TransactionDTL` table";
					}
				}
			}
			else{
				$status = false;
				$message = "Something went wrong while save data in `TransactionHDR` table";
			}
		}
		else{
			$status = false;
			$message = "Something went wrong while save data in `Mapping` table";
		}
	}
	else{
		$status = false;
		$message = "Something went wrong while save data in `Activity` table";
	}

	if($status){
		$msg = "Dear Pushkar,<br><br>";
		$msg .= "PTW ($activityId) has been raised by $loginEmpName. <br>"; 
		$msg .= "<h3><u>PTW Details :</u></h3>";
		$msg .= "Circle : $circle <br>";
		$msg .= "Site : $siteNamee($siteId) <br>";
		$msg .= "Work Start Datetime : $workStartDatetime <br>";
		$msg .= "Work End Datetime : $workEndDatetime <br>";
		$msg .= "PTW Type : $ptwType <br><br>";
		$msg .= "Kindly do the needful. "; 
		// sendMail($msg);
	}

	$output = "";
	$output -> status = $status;
	$output -> message = $message;
	echo json_encode($output);

}
else if($insertType == "createRaiser" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$employeeName = $jsonData->employeeName;
	$roleId = 61;
	$rmId = $jsonData->loginEmpId;
	$mobile = $jsonData->mobile;
	$whatsappNumber = $jsonData->whatsappNumber;
	$aadharNumber = $jsonData->aadharNumber;
	$tenentId = $jsonData->tenentId;
	$isfieldUser = 0;

	// $sql1 = "select * from `Employees` where `Mobile` = '$mobile' and `RoleId` = $roleId and `Tenent_Id` = $tenentId and `Active` = 1";
	$sql1 = "select * from `Employees` where `Mobile` = '$mobile' and `Tenent_Id` = $tenentId and `Active` = 1";
	$query1 = mysqli_query($conn,$sql1);

	$isExist1 = false;
	if(mysqli_num_rows($query1) != 0){
		$isExist1 = true;
	}

	$output = "";
	if($isExist1){
		$output -> responseCode = "422";
		$output -> responseDesc = "already exist employee on ".$mobile." mobile number";
	}
	else{
		// $employeeId = $mobile;
		$currentTime = time();
		$employeeId = $currentTime;
		$password  = $mobile;

		$insertEmployee = "INSERT INTO `Employees`(`EmpId`, `Name`, `Password`, `Mobile`, `Whatsapp_Number`, `AadharCard_Number`, `RoleId`, `RMId`, `FieldUser`, `Tenent_Id`, `Registered`, `Update`, `Active`, `CreatedBy`) VALUES ('$employeeId', '$employeeName', '$password', '$mobile', '$whatsappNumber', '$aadharNumber', $roleId, '$rmId', $isfieldUser, $tenentId, current_timestamp, current_timestamp, 1, '$loginEmpId')";

		// echo $insertEmployee;

		if(mysqli_query($conn,$insertEmployee)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully inserted";	
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
	}
	echo json_encode($output);
}
else if($insertType == 'createVendor' && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$employeeName = $jsonData->vendorName;
	$vendorCode = $jsonData->vendorCode;
	$vendorType = $jsonData->vendorType;
	$state = $jsonData->state;
	$roleId = 53;
	$mobile = $jsonData->vendorMobile;
	$tenentId = $jsonData->tenentId;
	$isfieldUser = 0;

	// $sql1 = "select * from `Employees` where `Mobile` = '$mobile' and `RoleId` = $roleId and `Tenent_Id` = $tenentId and `Active` = 1";
	$sql1 = "select * from `Employees` where `Mobile` = '$mobile' and `Tenent_Id` = $tenentId and `Active` = 1";
	$query1 = mysqli_query($conn,$sql1);

	// $sql2 = "select * from `Employees` where `EmpId` = '$vendorCode' and `RoleId` = $roleId and `Tenent_Id` = $tenentId and `Active` = 1";
	$sql2 = "select * from `Employees` where `EmpId` = '$vendorCode' and `Tenent_Id` = $tenentId and `Active` = 1";
	$query2 = mysqli_query($conn,$sql2);

	$isExist1 = false;
	if(mysqli_num_rows($query1) != 0){
		$isExist1 = true;
	}

	$isExist2 = false;
	if(mysqli_num_rows($query2) != 0){
		$isExist2 = true;
	}

	$output = "";
	if($isExist1){
		$output -> responseCode = "422";
		$output -> responseDesc = "already exist employee on ".$mobile." mobile number";
	}
	else if($isExist2){
		$output -> responseCode = "422";
		$output -> responseDesc = "already exist employee on ".$vendorCode." vendor code";
	}
	else{
		$employeeId = $vendorCode;
		$password  = $mobile;

		$insertEmployee = "INSERT INTO `Employees`(`EmpId`, `Name`, `Password`, `Mobile`, `State`, `VendorType`, `RoleId`, `FieldUser`, `Tenent_Id`, `Registered`, `Update`, `Active`, `CreatedBy`) VALUES ('$employeeId', '$employeeName', '$password', '$mobile', '$state', '$vendorType', $roleId, $isfieldUser, $tenentId, current_timestamp, current_timestamp, 1, '$loginEmpId')";

		// echo $insertEmployee;

		if(mysqli_query($conn,$insertEmployee)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully inserted";	
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
	}
	echo json_encode($output);
}
else if($insertType == "supervisor" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$employeeName = $jsonData->employeeName;
	$roleId = 58;
	$rmId = $jsonData->loginEmpId;
	$mobile = $jsonData->mobile;
	$whatsappNumber = $jsonData->whatsappNumber;
	$aadharNumber = $jsonData->aadharNumber;
	$tenentId = $jsonData->tenentId;
	$isfieldUser = 0;

	// $sql1 = "select * from `Employees` where `Mobile` = '$mobile' and `RoleId` = $roleId and `Tenent_Id` = $tenentId and `Active` = 1";
	$sql1 = "select * from `Employees` where `Mobile` = '$mobile' and `Tenent_Id` = $tenentId and `Active` = 1";
	$query1 = mysqli_query($conn,$sql1);

	$isExist1 = false;
	if(mysqli_num_rows($query1) != 0){
		$isExist1 = true;
	}

	$output = "";
	if($isExist1){
		$output -> responseCode = "422";
		$output -> responseDesc = "already exist employee on ".$mobile." mobile number";
	}
	else{
		// $employeeId = $mobile;
		$currentTime = time();
		$employeeId = $currentTime;
		$password  = $mobile;

		$insertEmployee = "INSERT INTO `Employees`(`EmpId`, `Name`, `Password`, `Mobile`, `Whatsapp_Number`, `AadharCard_Number`, `RoleId`, `RMId`, `FieldUser`, `Tenent_Id`, `Registered`, `Update`, `Active`, `CreatedBy`) VALUES ('$employeeId', '$employeeName', '$password', '$mobile', '$whatsappNumber', '$aadharNumber', $roleId, '$rmId', $isfieldUser, $tenentId, current_timestamp, current_timestamp, 1, '$loginEmpId')";

		// echo $insertEmployee;

		if(mysqli_query($conn,$insertEmployee)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully inserted";	
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
	}
	echo json_encode($output);
}
else if($insertType == "supervisor_new" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$employeeName = $jsonData->employeeName;
	$roleId = 58;
	$rmId = $jsonData->loginEmpId;
	$mobile = $jsonData->mobile;
	$whatsappNumber = $jsonData->whatsappNumber;
	$aadharNumber = $jsonData->aadharNumber;
	$tenentId = $jsonData->tenentId;
	$trainingList = $jsonData->trainingList;
	$isfieldUser = 0;

	// $sql1 = "select * from `Employees` where `Mobile` = '$mobile' and `RoleId` = $roleId and `Tenent_Id` = $tenentId and `Active` = 1";
	$sql1 = "select * from `Employees` where `Mobile` = '$mobile' and `Tenent_Id` = $tenentId and `Active` = 1";
	$query1 = mysqli_query($conn,$sql1);

	$isExist1 = false;
	if(mysqli_num_rows($query1) != 0){
		$isExist1 = true;
	}

	$output = "";
	if($isExist1){
		$output -> responseCode = "422";
		$output -> responseDesc = "already exist employee on ".$mobile." mobile number";
	}
	else{
		// $employeeId = $mobile;
		$currentTime = time();
		$employeeId = $currentTime;
		$password  = $mobile;

		$insertEmployee = "INSERT INTO `Employees`(`EmpId`, `Name`, `Password`, `Mobile`, `Whatsapp_Number`, `AadharCard_Number`, `RoleId`, `RMId`, `FieldUser`, `Tenent_Id`, `Registered`, `Update`, `Active`, `CreatedBy`) VALUES ('$employeeId', '$employeeName', '$password', '$mobile', '$whatsappNumber', '$aadharNumber', $roleId, '$rmId', $isfieldUser, $tenentId, current_timestamp, current_timestamp, 1, '$loginEmpId')";

		if(mysqli_query($conn,$insertEmployee)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully inserted";	
			if(count($trainingList) > 0){
				for($i=0;$i<count($trainingList);$i++){
					$trType = $trainingList[$i]->trType;
					$trCompanyName = $trainingList[$i]->trCompanyName;
					$trIdNo = $trainingList[$i]->trIdNo;
					$trMode = $trainingList[$i]->trMode;
					$trGivenBy = $trainingList[$i]->trGivenBy;
					$trDate = $trainingList[$i]->trDate;
					$trExDate = $trainingList[$i]->trExDate;
					$trPic = $trainingList[$i]->trPic;
					$trTypeCombo = $trainingList[$i]->trTypeCombo;
					if($trPic != ''){
						$t = time();
						$base64 = new Base64ToAny();
						$trPic = $base64->base64_to_jpeg($trPic,$t."_Training".$i);
					}
					
					$trTable = "";
					$dataSql = "";
					if($trTypeCombo == "1"){
						$trTable = "INSERT INTO `SupervisorTraining`(`EmpId`, `CompanyName`, `TrainingType`, `TrainingIdNo`, `TrainingDate`, `TrainingExDate`, `TrainingPic`, `CreatedBy`) ";
						$dataSql = "('$employeeId', '$trCompanyName', '$trType', '$trIdNo', '$trDate', '$trExDate', '$trPic', '$loginEmpId')";
					}
					else if($trTypeCombo == "2"){
						$trTable = "INSERT INTO `SupervisorTraining`(`EmpId`, `CompanyName`, `TrainingType`, `TrainingDate`, `TrainingGivenBy`, `ModeOfTraining`, `TrainingPic`, `CreatedBy`) ";
						$dataSql = "('$employeeId', '$trCompanyName', '$trType', '$trDate', '$trGivenBy', '$trMode', '$trPic', '$loginEmpId')";
					}
					else if($trTypeCombo == "3"){
						$trTable = "INSERT INTO `SupervisorTraining`(`EmpId`, `CompanyName`, `TrainingType`, `TrainingDate`, `TrainingPic`, `CreatedBy`) ";
						$dataSql = "('$employeeId', '$trCompanyName', '$trType', '$trDate', '$trPic', '$loginEmpId')";
					}

					if($trTable != "" && $dataSql != ""){
						$insertTraining = $trTable." VALUES ".$dataSql;
						mysqli_query($conn,$insertTraining);
					}
				}
				
			}
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
	}
	echo json_encode($output);
}
else if($insertType == "addTraining" && $methodType === 'POST'){
	$loginEmpId = $jsonData->loginEmpId;
	$empId = $jsonData->empId;
	$trainingList = $jsonData->trainingList;

	for($i=0;$i<count($trainingList);$i++){
		$trType = $trainingList[$i]->trType;
		$trCompanyName = $trainingList[$i]->trCompanyName;
		$trIdNo = $trainingList[$i]->trIdNo;
		$trMode = $trainingList[$i]->trMode;
		$trGivenBy = $trainingList[$i]->trGivenBy;
		$trDate = $trainingList[$i]->trDate;
		$trExDate = $trainingList[$i]->trExDate;
		$trPic = $trainingList[$i]->trPic;
		$trTypeCombo = $trainingList[$i]->trTypeCombo;
		if($trPic != ''){
			$t = time();
			$base64 = new Base64ToAny();
			$trPic = $base64->base64_to_jpeg($trPic,$t."_Training".$i);
		}

		$trTable = "";
		$dataSql = "";
		if($trTypeCombo == "1"){
			$trTable = "INSERT INTO `SupervisorTraining`(`EmpId`, `CompanyName`, `TrainingType`, `TrainingIdNo`, `TrainingDate`, `TrainingExDate`, `TrainingPic`, `CreatedBy`) ";
			$dataSql = "('$empId', '$trCompanyName', '$trType', '$trIdNo', '$trDate', '$trExDate', '$trPic', '$loginEmpId')";
		}
		else if($trTypeCombo == "2"){
			$trTable = "INSERT INTO `SupervisorTraining`(`EmpId`, `CompanyName`, `TrainingType`, `TrainingDate`, `TrainingGivenBy`, `ModeOfTraining`, `TrainingPic`, `CreatedBy`) ";
			$dataSql = "('$empId', '$trCompanyName', '$trType', '$trDate', '$trGivenBy', '$trMode', '$trPic', '$loginEmpId')";
		}
		else if($trTypeCombo == "3"){
			$trTable = "INSERT INTO `SupervisorTraining`(`EmpId`, `CompanyName`, `TrainingType`, `TrainingDate`, `TrainingPic`, `CreatedBy`) ";
			$dataSql = "('$empId', '$trCompanyName', '$trType', '$trDate', '$trPic', '$loginEmpId')";
		}

		if($trTable != "" && $dataSql != ""){
			$insertTraining = $trTable." VALUES ".$dataSql;
			mysqli_query($conn,$insertTraining);
		}
	}

	$output = new StdClass;
	$output -> responseCode = "100000";
	$output -> responseDesc = "Successfully inserted";
	echo json_encode($output);
}

?>

<?php
function startsWith ($string, $startString) 
{
	$string = strtolower($string);
	$startString = strtolower($startString);

	$len = strlen($startString); 
	return (substr($string, 0, $len) === $startString); 
}
function sendMail($msg){
	$emailFrom = "Galaxy spin";
	// $empEmailId = "pushkar.tyagi@trinityapplab.co.in";
	$empEmailId = "jai.prakash@trinityapplab.co.in";
	$ccEmailId = "";
	$bccEmailId = "";
	// $bccEmailId = "jai.prakash@trinityapplab.co.in";
	$subject = "PTW Raise";
	$url = "http://www.in3.co.in:8080/Aviom/aviom/sendCompaintMailPhp";

	$dataArray = ['emailFrom' => $emailFrom, 'emailId' => $empEmailId, 'ccEmailId' => $ccEmailId, 'bccEmailId' => $bccEmailId, 'subject' => $subject, 
	'msg' => $msg];
	$data = http_build_query($dataArray);
	$getUrl = $url."?".$data;

	$ch = curl_init();   
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $getUrl);
    curl_setopt($ch, CURLOPT_TIMEOUT, 80);
       
    $response = curl_exec($ch);
        
    if(curl_error($ch)){
        // echo 'Request Error:' . curl_error($ch);
    }else{
        // echo $response;
    }
       
    curl_close($ch);
}
?>