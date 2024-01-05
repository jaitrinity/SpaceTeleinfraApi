<?php
include("dbConfiguration.php");
require 'EmployeeTenentId.php';
$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$myEmpId = $jsonData->loginEmpId;
$loginEmpId = $jsonData->loginEmpId;
$loginEmpRole = $jsonData->loginEmpRole;
$menuId = $jsonData->menuId;
$subCatMenuId = $jsonData->subCatMenuId;
$captionMenuId = $jsonData->captionMenuId;
$filterEmployeeId = $jsonData->filterEmployeeId;
$filterTransactionId = $jsonData->filterTransactionId;
$filterStartDate = $jsonData->filterStartDate;
$filterEndDate = $jsonData->filterEndDate;
$level = $jsonData->level;
$empTenObj = new EmployeeTenentId();
$tenentId = $empTenObj->getTenentIdByEmpId($conn,$loginEmpId);

$empList = [];

// 'Vendor(53)','PTW Raiser(61)' 
if($loginEmpRole == '53' || $loginEmpRole == '61'){
	$empSql = "SELECT * FROM `Employees` WHERE (`EmpId` = '$loginEmpId' or `RMId` = '$loginEmpId') and `Tenent_Id` = $tenentId";
	$empQuery=mysqli_query($conn,$empSql);
	if(mysqli_num_rows($empQuery) !=0){
		while($row11 = mysqli_fetch_assoc($empQuery)){
			array_push($empList,$row11["EmpId"]);
		}
	}
}
else{
	$empSql = "SELECT * FROM `Employees` WHERE `Tenent_Id` = $tenentId";
	$empQuery=mysqli_query($conn,$empSql);
	if(mysqli_num_rows($empQuery) !=0){
		while($row11 = mysqli_fetch_assoc($empQuery)){
			array_push($empList,$row11["EmpId"]);
		}
	}
}

	

$loginEmpId = implode("','", $empList);

if($level == 2){
	$menuId = $subCatMenuId;
}
else if($level == 3){
	$menuId = $captionMenuId;
}

if($menuId == ""){
	$menuId = "303,304,305,306,307,308,309,310";
}


$output = array();
$wrappedList = [];

$unionSql = "SELECT `ActivityId` from `Activity` where `EmpId` in ('$loginEmpId') and `MenuId` in ($menuId) and `Event` = 'Submit'";


$sql = "SELECT distinct `h`.`ActivityId`, `a`.`MobileDateTime` as ServerDateTime, `h`.`Status`, `h`.`VerifierActivityId`, `h`.`ApproverActivityId`, `h`.`ThirdActivityId`, `h`.`FourthActivityId`, `h`.`FifthActivityId`, `h`.`SixthActivityId`, h.`WorkStartDatetime`, h.`WorkEndDatetime`, l.`State` as `Site_Circle`, h.`Site_Id`, `h`.`Site_Name`, l.`Site_CAT` as Site_Category, `a`.`EmpId` as fillingByEmpId, `e`.`Name` as fillerByEmpName, a.`MenuId` as loopMenuId, m.`Cat`, m.`Sub`,
`e`.`State` as fillingByState, 
`e`.`Area` as fillingByArea, `a1`.`MenuId`, `a1`.`EmpId` as verifyByEmpId, 
`e1`.`Name` as verifiedByEmpName, `a1`.`ServerDateTime` as verifiedDate, `a2`.`EmpId` as approveByEmpId,
`e2`.`Name` as approvedByEmpName, `a2`.`ServerDateTime` as approvedDate, l.`Name` as locationName,
`a3`.`EmpId` as thirdByEmpId, `e3`.`Name` as thirdByEmpName, `a3`.`ServerDateTime` as thirdByDate,
`a4`.`EmpId` as fourthByEmpId, `e4`.`Name` as fourthByEmpName, `a4`.`ServerDateTime` as fourthByDate, 
`a5`.`EmpId` as fifthByEmpId, `e5`.`Name` as fifthByEmpName, `a5`.`ServerDateTime` as fifthByDate, 
`a6`.`EmpId` as sixthByEmpId, `e6`.`Name` as sixthByEmpName, `a6`.`ServerDateTime` as sixthByDate, 
'' as ticketType, '' as site_survey_status, `h`.`Remark`,  
`h`.`Customer_Site_Id`, 
`h`.`Nominal_Latlong`, `h`.`Assign_To`, 
'' as `Percentage`,    
'' as `Result`,
'' as `ok_count`, '' as `not_ok_count`, l.`State` as `siteCircle`, p.`Description` as `PtwStatus`, mp.`Verifier` as verifierEmpId, mp.`Approver` as approverEmpId, mp.`Active` as MappingActive
FROM `TransactionHDR` h
join `Activity` a on `h`.`ActivityId` = `a`.`ActivityId`
join `Menu` m on a.`MenuId` = m.`MenuId`
join `PTW_Status` p on  h.`Status` = p.`Status`
join `Mapping` mp on a.`ActivityId` = mp.`ActivityId`
left join `Location` l on a.`LocationId` = l.`LocationId`
left join `Activity` a1 on `h`.`VerifierActivityId` = `a1`.`ActivityId` 
left join `Activity` a2 on `h`.`ApproverActivityId` = `a2`.`ActivityId`
left join `Activity` a3 on `h`.`ThirdActivityId` = `a3`.`ActivityId`
left join `Activity` a4 on `h`.`FourthActivityId` = `a4`.`ActivityId`
left join `Activity` a5 on `h`.`FifthActivityId` = `a5`.`ActivityId`
left join `Activity` a6 on `h`.`SixthActivityId` = `a6`.`ActivityId`
left join `Employees_Reference` e on `a`.`EmpId` = `e`.`EmpId` and a.`RefId` = e.`RefId` 
left join `Employees_Reference` e1 on `a1`.`EmpId` = `e1`.`EmpId` and a1.`RefId` = e1.`RefId` 
left join `Employees_Reference` e2 on `a2`.`EmpId` = `e2`.`EmpId` and a2.`RefId` = e2.`RefId` 
left join `Employees_Reference` e3 on `a3`.`EmpId` = `e3`.`EmpId` and a3.`RefId` = e3.`RefId` 
left join `Employees_Reference` e4 on `a4`.`EmpId` = `e4`.`EmpId` and a4.`RefId` = e4.`RefId` 
left join `Employees_Reference` e5 on `a5`.`EmpId` = `e5`.`EmpId` and a5.`RefId` = e5.`RefId` 
left join `Employees_Reference` e6 on `a6`.`EmpId` = `e6`.`EmpId` and a6.`RefId` = e6.`RefId` 
where `h`.`ActivityId` in ($unionSql) ";

if($filterStartDate != ''){
	$sql .= " and DATE_FORMAT(`h`.`ServerDateTime`,'%Y-%m-%d') >= '$filterStartDate' "; 
}
if($filterEndDate != ''){
	$sql .= " and DATE_FORMAT(`h`.`ServerDateTime`,'%Y-%m-%d') <= '$filterEndDate' "; 
}

if($filterStartDate == "" && $filterEndDate == ""){
	$sql .= " and h.`ServerDateTime` >= now()-interval 3 month ";
}

$sql .= " order by `h`.`ActivityId` desc";

// echo $sql;

$query=mysqli_query($conn,$sql);
while($row = mysqli_fetch_assoc($query)){
	$activityId = $row["ActivityId"];
	$serverDateTime = $row["ServerDateTime"];
	$verifierActivityId = $row["VerifierActivityId"];
	$approverActivityId = $row["ApproverActivityId"];
	$thirdActivityId = $row["ThirdActivityId"];
	$fourthActivityId = $row["FourthActivityId"];
	$fifthActivityId = $row["FifthActivityId"];
	$sixthActivityId = $row["SixthActivityId"];

	$verifyByEmpId = $row["verifyByEmpId"];
	$verifiedByEmpName = $row["verifiedByEmpName"];
	$verifiedDate = $row["verifiedDate"];
	
	$approveByEmpId = $row["approveByEmpId"];
	$approvedByEmpName = $row["approvedByEmpName"];
	$approvedDate = $row["approvedDate"];
	
	$thirdByEmpId = $row["thirdByEmpId"];
	$thirdByEmpName = $row["thirdByEmpName"];
	$thirdByDate = $row["thirdByDate"];

	$fourthByEmpId = $row["fourthByEmpId"];
	$fourthByEmpName = $row["fourthByEmpName"];
	$fourthByDate = $row["fourthByDate"];

	$fifthByEmpId = $row["fifthByEmpId"];
	$fifthByEmpName = $row["fifthByEmpName"];
	$fifthByDate = $row["fifthByDate"];

	$sixthByEmpId = $row["sixthByEmpId"];
	$sixthByEmpName = $row["sixthByEmpName"];
	$sixthByDate = $row["sixthByDate"];

	$workStartDatetime = $row["WorkStartDatetime"];
	$workEndDatetime = $row["WorkEndDatetime"];


	$status = $row["Status"];
	$ptwStatus = $row["PtwStatus"];
	if($status == "PTW_02"){
		$byRole = "SELECT r.Role FROM Employees e join Role r on e.RoleId = r.RoleId WHERE e.EmpId = '$verifyByEmpId' ";
		$byRoleQuery = mysqli_query($conn,$byRole);
		$rowCount = mysqli_num_rows($byRoleQuery);
		if($rowCount !=0){
			$byRoleRow = mysqli_fetch_assoc($byRoleQuery);
			$ptwStatus .= " ".$byRoleRow["Role"];
		}
		
	}
	// else if($status == "PTW_04"){
	// 	$byRole = "SELECT r.Role FROM Employees e join Role r on e.RoleId = r.RoleId WHERE e.EmpId = '$sixthByEmpId' ";
	// 	$byRoleQuery = mysqli_query($conn,$byRole);
	// 	$rowCount = mysqli_num_rows($byRoleQuery);
	// 	if($rowCount !=0){
	// 		$byRoleRow = mysqli_fetch_assoc($byRoleQuery);
	// 		$ptwStatus .= " ".$byRoleRow["Role"];
	// 	}
		
	// }
	// else if($status == "PTW_103"){
	// 	$byRole = "SELECT r.Role FROM Employees e join Role r on e.RoleId = r.RoleId WHERE e.EmpId = '$fourthByEmpId' ";
	// 	$byRoleQuery = mysqli_query($conn,$byRole);
	// 	$rowCount = mysqli_num_rows($byRoleQuery);
	// 	if($rowCount !=0){
	// 		$byRoleRow = mysqli_fetch_assoc($byRoleQuery);
	// 		$ptwStatus .= " ".$byRoleRow["Role"];
	// 	}
	// }
	else if($status == "PTW_99"){
		$tempEmpId = $verifierActivityId == null ? $sixthByEmpId : $verifyByEmpId;
		$byRole = "SELECT r.Role FROM Employees e join Role r on e.RoleId = r.RoleId WHERE e.EmpId = '$tempEmpId' ";
		$byRoleQuery = mysqli_query($conn,$byRole);
		$rowCount = mysqli_num_rows($byRoleQuery);
		if($rowCount !=0){
			$byRoleRow = mysqli_fetch_assoc($byRoleQuery);
			$ptwStatus .= " ".$byRoleRow["Role"];
		}
	}
	$locationName = $row["locationName"];
	$ticketType = $row["ticketType"];
	$siteCircle = $row["Site_Circle"];
	$siteId = $row["Site_Id"];
	$siteName = $row["Site_Name"];
	$siteCategory = $row["Site_Category"];
	
	$postPercentage = $row["Percentage"];
	$postResult = $row["Result"];
	$siteSurveyStatus = $row["site_survey_status"];
	$nominalLatlong = $row["Nominal_Latlong"];
	$customerSiteId = $row["Customer_Site_Id"];
	$assignTo = $row["Assign_To"];
	$remark = $row["Remark"];
	$loopMenuId = $row["loopMenuId"];
	$catName = $row["Cat"];
	$subName = $row["Sub"];

	$fillingByEmpId = $row["fillingByEmpId"];
	$fillerByEmpName = $row["fillerByEmpName"];
	if($loopMenuId == 303 || $loopMenuId == 304 || $loopMenuId == 305 || $loopMenuId == 306 || $loopMenuId == 307 || $loopMenuId == 308 || $loopMenuId == 309 || $loopMenuId == 310){
		$fillingByState = $row["siteCircle"];
	}
	else{
		$fillingByState = $row["fillingByState"];
	}
	$fillingByArea = $row["fillingByArea"];

	$okCount = $row["ok_count"];
	$notOkCount = $row["not_ok_count"];

	//echo $loginEmpId.'-'.$fillingByEmpId."-".$verifyByEmpId."-".$approveByEmpId.'-------------------';

	$verifierEmpId = $row["verifierEmpId"];
	$approverEmpId = $row["approverEmpId"];
	$mappingActive = $row["MappingActive"];

	$isVerifierExist = false;
	$isApproverExist = false;
	if($mappingActive == 1 && $verifierEmpId != null && $verifierEmpId != ""){
		$isVerifierExist = true;
	}
	
	if($mappingActive == 1 && $approverEmpId != null && $approverEmpId != ""){
		$isApproverExist = true;
	}

	

	$isVerifier = false;
	if($mappingActive == 1 && $verifierActivityId == null){
		$expVeri = explode(",", $verifierEmpId);
		if(count($expVeri) == 1){
			if($verifierEmpId == $myEmpId){
				$isVerifier = true;
			}
		}
		else{
			for($vi = 0; $vi < count($expVeri); $vi++){
				$loopVeriEmpId = $expVeri[$vi];
				if($loopVeriEmpId == $myEmpId){
					$isVerifier = true;
				}
			}
		}
			
	}
	
	$isApprover = false;
	if($mappingActive == 1 && $approverActivityId == null){
		$expApp = explode(",", $approverEmpId);
		if(count($expApp) == 1){
			if($approverEmpId == $myEmpId){
				$isApprover = true;
			}
		}
		else{
			for($ai = 0; $ai < count($expApp); $ai++){
				$loopAppEmpId = $expApp[$ai];
				if($loopAppEmpId == $myEmpId){
					$isApprover = true;
				}
			}
		}
			
	}

	$pendingForApprove = "Yes";
	$pendingForVerify = "Yes";

	$myRoleForTask = "";
	if($isVerifier){
		$myRoleForTask = "Verifier";
	}
	else if($isApprover){
		$myRoleForTask = "Approver";
	}

	if(($isVerifierExist || $isVerifier) && ($status == "Created" || $status == "PTW_01")){
		$pendingForVerify = "No";
	}
	if(($isApproverExist || $isApprover) && ($status == "Created" || $status == "PTW_01" || $status == "Verified" || $status == "PTW_02")){
		$pendingForApprove = "No";
	}

	if(!$isVerifierExist)
		$pendingForVerify = "NA";

	if(!$isApproverExist)
		$pendingForApprove = "NA";

	
	$json = new StdClass;
	
	$json -> pendingForApprove = $pendingForApprove;
	$json -> menuId = $loopMenuId;
	$json -> catName = $catName;
	$json -> subName = $subName;
	$json -> transactionId = $activityId;
	$json -> verifierTId = $verifierActivityId;
	$json -> approvedTId = $approverActivityId;
	$json -> thirdActivityId = $thirdActivityId;
	$json -> fourthActivityId = $fourthActivityId;
	$json -> fifthActivityId = $fifthActivityId;
	$json -> sixthActivityId = $sixthActivityId;
	$json -> dateTime = $serverDateTime;
	$json -> approveDetList = [];
	$json -> myRoleForTask = $myRoleForTask;
	$json -> transactionDetList = $transactionDetList;
	$json -> topFirstCheckpointDesc = $topFirstCheckpointDesc;
	$json -> topThirdCheckpointDesc = $topThirdCheckpointDesc;
	$json -> fillingByEmpId = $fillingByEmpId;
	$json -> fillingBy = $fillerByEmpName;
	$json -> fillingByState = $fillingByState;
	$json -> fillingByArea = $fillingByArea;
	if($loopMenuId == 279){
		$json -> verifiedBy = $assignTo;
	}
	else{
		$json -> verifiedBy = $verifiedByEmpName;
	}
	$json -> approvedBy = $approvedByEmpName;
	$json -> verifiedDate = $verifiedDate;
	$json -> approvedDate = $approvedDate;
	$json -> thirdByEmpName = $thirdByEmpName;
	$json -> thirdByDate = $thirdByDate;

	$json -> fourthByEmpName = $fourthByEmpName;
	$json -> fourthByDate = $fourthByDate;

	$json -> fifthByEmpName = $fifthByEmpName;
	$json -> fifthByDate = $fifthByDate;

	$json -> sixthByEmpName = $sixthByEmpName;
	$json -> sixthByDate = $sixthByDate;

	$json -> topFirstKey = "topFirstCheckpointValue";
	$json -> topSecondCheckpointValue = $topSecondCheckpointValue;
	$json -> actionCheckpointList = [];
	$json -> verifyDetList = [];
	$json -> topSecondKey = "topSecondCheckpointValue";
	// $json -> topFirstCheckpointValue = $topFirstCheckpointValue;
	$json -> siteId = $siteId;
	$json -> siteCircle = $siteCircle;
	$json -> topFirstCheckpointValue = $siteName;
	$json -> siteCategory = $siteCategory;
	$json -> pendingForVerify = $pendingForVerify;
	// $json -> verifierTId = "NA";
	// $json -> approvedTId = "NA";
	$json -> topSecondCheckpointDesc = $topSecondCheckpointDesc;
	$json -> topThirdCheckpointValue = $topThirdCheckpointValue;
	$json -> topThirdKey = "topThirdCheckpointValue";
	
	$json -> locationName = $locationName;
	$json -> ticketType = $ticketType;
	$json -> siteSurveyStatus = $siteSurveyStatus;
	$json -> nominalLatlong = $nominalLatlong;
	$json -> customerSiteId = $customerSiteId;
	if($loopMenuId == 283 || $loopMenuId == 285){
		$json -> notOkCount = $postPercentage;
		$json -> status = $postResult;
	}
	else{
		$json -> okCount = $okCount;
		$json -> notOkCount = $notOkCount;
		$json -> status = $status;
	}
	$json -> siteSurveyRemark = $remark;
	$json -> ptwStatus = $ptwStatus;
	$json -> workStartDatetime = $workStartDatetime;
	$json -> workEndDatetime = $workEndDatetime;
	
	array_push($wrappedList,$json);

}

$output = array('wrappedList' => $wrappedList, 'responseDesc' => 'SUCCESSFUL', 'responseCode' => '100000', 'count' => $level);
echo json_encode($output);
?>
