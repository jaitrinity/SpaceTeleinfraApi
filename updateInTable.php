<?php
include("dbConfiguration.php");
$updateType = $_REQUEST["updateType"];
$json = file_get_contents('php://input');
$jsonData=json_decode($json);
if($updateType == "device"){
	$deviceId = $jsonData->deviceId;
	$action = $jsonData->action;
	
	$updateDevice = "update `Devices` set `Active` = $action, `Update` = current_timestamp where `DeviceId` = $deviceId ";
	$output = "";
	if(mysqli_query($conn,$updateDevice)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "mapping"){
	$mappingId = $jsonData->mappingId;
	$action = $jsonData->action;
	
	$updateMapping = "update `Mapping` set `Active` = $action where `MappingId` = $mappingId ";
	$output = "";
	if(mysqli_query($conn,$updateMapping)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "assign"){
	$assignId = $jsonData->assignId;
	$action = $jsonData->action;
	
	$updateAssign = "update `Assign` set `Active` = $action where `AssignId` = $assignId ";
	$output = "";
	if(mysqli_query($conn,$updateAssign)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "employee"){
	$loginEmpId = $jsonData->loginEmpId;
	$id = $jsonData->id;
	$action = $jsonData->action;
	$moreSql = "";
	if($action == 1) 
		$moreSql = ", `Active_Date` = CURRENT_DATE";
	else if($action == 0) 
		$moreSql = ", `Deactive_Date` = CURRENT_DATE";
	
	//$updateEmployee = "UPDATE `Employees` set `Active` = $action, `Update` = current_timestamp, `UpdatedBy` = '$loginEmpId' where `Id` = $id ";

	$updateEmployee = "UPDATE `Employees` set `Active` = $action, `Update` = current_timestamp, `UpdatedBy` = '$loginEmpId' 
	$moreSql where `Id` = $id ";


	$output = "";
	if(mysqli_query($conn,$updateEmployee)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "SupervisorTraining"){
	$loginEmpId = $jsonData->loginEmpId;
	$id = $jsonData->id;
	$action = $jsonData->action;
	$rejectReason = $jsonData->rejectReason;
	$actionSql = "";
	if($action == 2){
		$actionSql = ", `RejectReason` = '$rejectReason'";
	}
	
	$updateEmployee = "update `SupervisorTraining` set `Status` = $action ".$actionSql." where `Id` = $id ";
	$output = "";
	if(mysqli_query($conn,$updateEmployee)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "editEmployee"){
	$loginEmpId = $jsonData->loginEmpId;
	$employeeId = $jsonData->employeeId;
	$employeeName = $jsonData->employeeName;
	$roleId = $jsonData->roleId;
	$mobile = $jsonData->mobile;
	$state = $jsonData->state;

	$sql1 = "select * from `Employees` where `EmpId` = '$employeeId' and `Mobile` = '$mobile'  ";
	// echo $sql1;
	$query1 = mysqli_query($conn,$sql1);
	$isSame = false;
	if(mysqli_num_rows($query1) != 0){
		$isSame = true;
	}

	if(!$isSame){
		$sql2 = "select * from `Employees` where `Mobile` = '$mobile' ";
		$query2 = mysqli_query($conn,$sql2);
		$isExist2 = false;
		if(mysqli_num_rows($query2) != 0){
			$isExist2 = true;
		}
	}

	$output = "";
	if($isSame){
		
		$updateEditEmployee = "update `Employees` set `Name`='$employeeName', `Mobile`='$mobile', `RoleId`=$roleId, `State`='$state', `Update`=current_timestamp, `UpdatedBy` = '$loginEmpId' where `EmpId` = '$employeeId' ";
	
		if(mysqli_query($conn,$updateEditEmployee)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully update";
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
	}
	
	else if($isExist2){
		$output -> responseCode = "422";
		$output -> responseDesc = "already exist employee on ".$mobile." mobile number";
	}
	
	else{

		$updateEditEmployee = "update `Employees` set `Name`='$employeeName', `Mobile`='$mobile', `RoleId`=$roleId, `State`='$state', `Update`=current_timestamp, `UpdatedBy` = '$loginEmpId' where `EmpId` = '$employeeId' ";
	
		if(mysqli_query($conn,$updateEditEmployee)){
			$output -> responseCode = "100000";
			$output -> responseDesc = "Successfully update";
		}
		else{
			$output -> responseCode = "0";
			$output -> responseDesc = "Something wrong";
		}
	}
	echo json_encode($output);
}
else if($updateType == "roleDelete"){
	$roleId = $jsonData->roleId;
	
	$deleteRole = "delete from `Role` where `RoleId` = $roleId ";
	$output = "";
	if(mysqli_query($conn,$deleteRole)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully Deleted";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "roleUpdate"){
	$roleId = $jsonData->roleId;
	$menuId = $jsonData->menuId;
	// $verifierRole = $jsonData->verifierRole;
	// $approverRole = $jsonData->approverRole;
	
	// $updateRole = "update `Role` set `MenuId` = '$menuId',`Verifier_Role` = '$verifierRole', `Approver_Role` = '$approverRole' where `RoleId` = $roleId ";
	$updateRole = "update `Role` set `MenuId` = '$menuId' where `RoleId` = $roleId ";
	$output = "";
	if(mysqli_query($conn,$updateRole)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}

else if($updateType == "updateMapping"){
	$mappingId = $jsonData->mappingId;
	$locationId = $jsonData->locationId;
	$verifierId = $jsonData->verifierId;
	$approverId = $jsonData->approverId;
	
	$updateMapping = "update `Mapping` set `LocationId` = '$locationId',`Verifier` = '$verifierId', `Approver` = '$approverId' where `MappingId` = $mappingId ";
	$output = "";
	if(mysqli_query($conn,$updateMapping)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "updateLocation"){
	$loginEmpId = $jsonData->loginEmpId;
	$locationId = $jsonData->locationId;
	$locationName = $jsonData->locationName;
	$geoCoordinate = $jsonData->geoCoordinate;
	$siteId = $jsonData->siteId;
	$siteType = $jsonData->siteType;
	$siteCategory = $jsonData->siteCategory;
	$airportMetro = $jsonData->airportMetro;
	$isHighRevenue = $jsonData->isHighRevenue;
	$isISQ = $jsonData->isISQ;
	$isRetailsIBS = $jsonData->isRetailsIBS;
	$rfiDate = $jsonData->rfiDate;
	// $address = $jsonData->address;

	$updateLocation = "";
	if($rfiDate != ""){
		$updateLocation = "update `Location` set `Name` = '$locationName', `Site_Id` = '$siteId', `Site_Type` = '$siteType', `Site_CAT` = '$siteCategory', 
		`Airport_Metro` = '$airportMetro', `RFI_date` = '$rfiDate', `High_Revenue_Site` = $isHighRevenue, `ISQ` = $isISQ, `Retail_IBS` = $isRetailsIBS, 
		`GeoCoordinates` = '$geoCoordinate', `Is_NBS_Site` = 0, `Update_Date` = current_timestamp, `UpdatedBy` = '$loginEmpId' where `LocationId` = $locationId ";
	}
	else{
		$updateLocation = "update `Location` set `Name` = '$locationName', `Site_Id` = '$siteId', `Site_Type` = '$siteType', `Site_CAT` = '$siteCategory', 
		`Airport_Metro` = '$airportMetro', `High_Revenue_Site` = $isHighRevenue, `ISQ` = $isISQ, `Retail_IBS` = $isRetailsIBS, 
		`GeoCoordinates` = '$geoCoordinate', `Is_NBS_Site` = 1, `Update_Date` = current_timestamp, `UpdatedBy` = '$loginEmpId' where `LocationId` = $locationId ";
	}
	
	// $updateLocation = "update `Location` set `Name` = '$locationName', `GeoCoordinates` = '$geoCoordinate', `Address` = '$address' 
	// where `LocationId` = $locationId ";
	$output = "";
	if(mysqli_query($conn,$updateLocation)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "routerSequence"){
	$loginEmpId = $jsonData->loginEmpId;
	$currentRouter = $jsonData->currentRouter;
	$explodeRouter = explode("/", $currentRouter);

	$updateRouter = "update `Header_Menu` set `Display_Order` = `Display_Order` + 1 where `Router_Link` = '".$explodeRouter[2]."' ";
	$output = "";
	if(mysqli_query($conn,$updateRouter)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == 'changeChecklistChecpointSequence'){
	$menuId = $jsonData->menuId;
	$checkpointId = $jsonData->checkpointId;

	$updateChlChp = "update `Menu` set `CheckpointId` = '$checkpointId' where `MenuId` = ".$menuId." ";
	$output = "";
	if(mysqli_query($conn,$updateChlChp)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "actionOnTransaction"){
	$actionType = $jsonData->actionType;
	$transactionId = $jsonData->transactionId;
	$status = $jsonData->status;
	$remark = $jsonData->remark;
	$reasonOfCancel = $jsonData->reasonOfCancel;
	$otherReason = $jsonData->otherReason;
	$currentStatus = $jsonData->currentStatus;

	$trStatus = "UPDATE `TransactionHDR` set `TransactionStatus` = $status, `Remark` = '$remark' where `ActivityId` = $transactionId ";
	if($actionType == 'ptw')
		$ptwStatus = "PTW_100"; // Reject (Before Approved) 
		if($currentStatus == "PTW_02") $ptwStatus = "PTW_102"; // Reject (After Approved)
		$trStatus = "UPDATE `TransactionHDR` set `Status` = '$ptwStatus', `ReasonOfCancel` = '$reasonOfCancel', `OtherReason` = '$otherReason' where `ActivityId` = $transactionId ";
	$output = "";
	if(mysqli_query($conn,$trStatus)){
		$mpStatus = "UPDATE `Mapping` set `Active` = $status where `ActivityId` = $transactionId ";
		mysqli_query($conn,$mpStatus);
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "siteCategory"){
	$sql = "SELECT * FROM `Location`";
	$query=mysqli_query($conn,$sql);
	$i = 0;
	while($row = mysqli_fetch_assoc($query)){
		$siteName = $row["Name"];
		$siteId = $row["Site_Id"];
		$siteCat = $row["Site_CAT"];
		// $outage = "UPDATE `Outage_Uptime` set `Site Category` = '$siteCat' where `Site Id` = '$siteId' ";
		$outage = "UPDATE `Outage_Uptime` set `Site Category` = '$siteCat' where `Site Name` = '$siteName' and `Site Category` is null ";
		if(mysqli_query($conn,$outage)){
			$i++;
		}
	}
	$output = "";
	$output -> responseCode = "100000";
	$output -> responseDesc = "Successfully update ".$i." records.";
	echo json_encode($output);
}
else if($updateType == "changeSiteStatus"){
	$loginEmpId = $jsonData->loginEmpId;
	$locationId = $jsonData->locationId;
	$siteStatus = $jsonData->siteStatus;
	$siteStatusReason = $jsonData->siteStatusReason;

	$sql = "UPDATE `Location` set `Is_Active` = $siteStatus, `Site_Status_Reason` = '$siteStatusReason', `Deactive_Date` = CURDATE(), `UpdatedBy` = '$loginEmpId' where LocationId = $locationId";
	$output = "";
	if(mysqli_query($conn,$sql)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "updateSupervisor"){
	$loginEmpId = $jsonData->loginEmpId;
	$id = $jsonData->id;
	$employeeName = $jsonData->employeeName;
	$mobile = $jsonData->mobile;
	$whatsappNumber = $jsonData->whatsappNumber;
	$aadharNumber = $jsonData->aadharNumber;
	$tenentId = $jsonData->tenentId;
	
	$updateEmployee = "update `Employees` set `Name`='$employeeName', `Mobile`='$mobile', `Whatsapp_Number`='$whatsappNumber', `AadharCard_Number`='$aadharNumber', `Update`=current_timestamp, `UpdatedBy` = '$loginEmpId' where `Id` = $id ";
	$output = "";
	if(mysqli_query($conn,$updateEmployee)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}

else if($updateType == "updateRaiser"){
	$loginEmpId = $jsonData->loginEmpId;
	$id = $jsonData->id;
	$employeeName = $jsonData->employeeName;
	$mobile = $jsonData->mobile;
	$whatsappNumber = $jsonData->whatsappNumber;
	$aadharNumber = $jsonData->aadharNumber;
	$tenentId = $jsonData->tenentId;
	
	$updateEmployee = "update `Employees` set `Name`='$employeeName', `Mobile`='$mobile', `Whatsapp_Number`='$whatsappNumber', `AadharCard_Number`='$aadharNumber', `Update`=current_timestamp, `UpdatedBy` = '$loginEmpId' where `Id` = $id ";
	$output = "";
	if(mysqli_query($conn,$updateEmployee)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "updateVendor"){
	$loginEmpId = $jsonData->loginEmpId;
	$id = $jsonData->id;
	$vendorName = $jsonData->vendorName;
	$vendorCode = $jsonData->vendorCode;
	$vendorType = $jsonData->vendorType;
	$state = $jsonData->state;
	$vendorMobile = $jsonData->vendorMobile;
	$tenentId = $jsonData->tenentId;
	
	$updateEmployee = "update `Employees` set `EmpId` = '$vendorCode', `Name`='$vendorName', `Mobile`='$vendorMobile', `State`='$state', `VendorType`='$vendorType', `Update`=current_timestamp, `UpdatedBy` = '$loginEmpId' where `Id` = $id ";

	// echo $updateEmployee;
	$output = "";
	if(mysqli_query($conn,$updateEmployee)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully update";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}
else if($updateType == "deleteTraining"){
	$loginEmpId = $jsonData->loginEmpId;
	$id = $jsonData->id;
	$empId = $jsonData->empId;

	$delTraining = "UPDATE `SupervisorTraining` SET `DeletedBy` = '$loginEmpId', `DeleteDate` = current_timestamp, `IsDeleted`= 1 WHERE `Id` = $id and `EmpId` = '$empId' ";
	$output = "";
	if(mysqli_query($conn,$delTraining)){
		$output -> responseCode = "100000";
		$output -> responseDesc = "Successfully delete";
	}
	else{
		$output -> responseCode = "0";
		$output -> responseDesc = "Something wrong";
	}
	echo json_encode($output);
}

?>