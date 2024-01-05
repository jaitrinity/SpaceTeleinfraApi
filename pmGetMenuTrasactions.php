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
// for $loginEmpRole == 'Admin(10)', 'SpaceWorld(50)', 'Management(51)', 'CB(52)', 'Quality Admin(56)', 'Corporate OnM lead(57)'
if($loginEmpRole == '10' || $loginEmpRole == '50' || $loginEmpRole == '51' || $loginEmpRole == '52' || $loginEmpRole == '56' || $loginEmpRole == '57'){
	$empSql = "SELECT * FROM `Employees` WHERE `Tenent_Id` = $tenentId";
	$empQuery=mysqli_query($conn,$empSql);
	if(mysqli_num_rows($empQuery) !=0){
		while($row11 = mysqli_fetch_assoc($empQuery)){
			array_push($empList,$row11["EmpId"]);
		}
	}

}
// for $loginEmpRole == CBH(46)
// else if($loginEmpRole == '46'){
// 	$empSql = "SELECT * FROM `Employees` WHERE `RoleId` in (46,48) and `Tenent_Id` = $tenentId";
// 	$empQuery=mysqli_query($conn,$empSql);
// 	if(mysqli_num_rows($empQuery) !=0){
// 		while($row11 = mysqli_fetch_assoc($empQuery)){
// 			array_push($empList,$row11["EmpId"]);
// 		}
// 	}
// }
else{
	array_push($empList,$loginEmpId);
}

$loginEmpId = implode("','", $empList);

if($level == 2){
	$menuId = $subCatMenuId;
}
else if($level == 3){
	$menuId = $captionMenuId;
}

$output = array();
$wrappedList = [];

$unionSql = "select DISTINCT t.`ActivityId` from (
SELECT `ActivityId` FROM `Mapping` where (`EmpId` in ('$loginEmpId') OR `Verifier` in ('$loginEmpId') OR `Approver` in ('$loginEmpId')) and `MenuId` in ($menuId) and `ActivityId` != 0
UNION
select `ActivityId` from `Activity` where `EmpId` in ('$loginEmpId') and `MenuId` in ($menuId) and `Event` = 'Submit'
UNION 
SELECT h.ActivityId FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId join TransactionHDR h on l.Site_Id = h.Site_Id join Activity a on h.ActivityId = a.ActivityId and a.MenuId in ($menuId) and a.Event = 'Submit' WHERE el.`Emp_Id` in ('$loginEmpId') and el.`Is_Active` = 1
) t";

if($loginEmpRole == '10' || $loginEmpRole == '50' || $loginEmpRole == '51' || $loginEmpRole == '52' || $loginEmpRole == '56' || $loginEmpRole == '57'){
	$unionSql = "select `ActivityId` from `Activity` where `EmpId` in ('$loginEmpId') and `MenuId` in ($menuId) and `Event` = 'Submit'";
}

$sql = "SELECT distinct `h`.`ActivityId`, `a`.`MobileDateTime` as ServerDateTime, `h`.`Status`, `h`.`VerifierActivityId`, 
`h`.`ApproverActivityId`, `h`.`ThirdActivityId`, `h`.`FourthActivityId`, `h`.`FifthActivityId`, h.`Site_Id`, `h`.`Site_Name`, `a`.`EmpId` as fillingByEmpId, `e`.`Name` as fillerByEmpName, a.MenuId as loopMenuId,
`e`.`State` as fillingByState, 
`e`.`Area` as fillingByArea, `a1`.`MenuId`, `a1`.`EmpId` as verifyByEmpId, 
`e1`.`Name` as verifiedByEmpName, `a1`.`ServerDateTime` as verifiedDate, `a2`.`EmpId` as approveByEmpId,
`e2`.`Name` as approvedByEmpName, `a2`.`ServerDateTime` as approvedDate, l.`Name` as locationName, l.State as siteCircle,
'' as thirdByEmpId, '' as thirdByEmpName, '' as thirdByDate,
'' as fourthByEmpId, '' as fourthByEmpName, '' as fourthByDate,
'' as fifthByEmpId, '' as fifthByEmpName, '' as fifthByDate, 
'' as ticketType, 
'' as site_survey_status, `h`.`Remark`,  
`h`.`Customer_Site_Id`, 
`h`.`Nominal_Latlong`, `h`.`Assign_To`, 
'' as `Percentage`,    
'' as `Result`,
m.`Verifier` as verifierEmpId, m.`Approver` as approverEmpId   
FROM `TransactionHDR` h
join `Activity` a on `h`.`ActivityId` = `a`.`ActivityId`
left join `Mapping` m on a.`ActivityId` = m.`ActivityId`
left join `Location` l on h.`Site_Id` = l.`Site_Id` and h.`Site_Name` = l.`Name`
left join `Activity` a1 on `h`.`VerifierActivityId` = `a1`.`ActivityId` 
left join `Activity` a2 on `h`.`ApproverActivityId` = `a2`.`ActivityId`
left join `Employees_Reference` e on `a`.`EmpId` = `e`.`EmpId` and a.`RefId` = e.`RefId` 
left join `Employees_Reference` e1 on `a1`.`EmpId` = `e1`.`EmpId` and a1.`RefId` = e1.`RefId` 
left join `Employees_Reference` e2 on `a2`.`EmpId` = `e2`.`EmpId` and a2.`RefId` = e2.`RefId` 
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

	$verifiedByEmpName = $row["verifiedByEmpName"];
	$verifiedDate = $row["verifiedDate"];

	$approvedByEmpName = $row["approvedByEmpName"];
	$approvedDate = $row["approvedDate"];

	$thirdByEmpName = $row["thirdByEmpName"];
	$thirdByDate = $row["thirdByDate"];

	$fourthByEmpName = $row["fourthByEmpName"];
	$fourthByDate = $row["fourthByDate"];

	$status = $row["Status"];
	$siteCircle = $row["siteCircle"];
	$locationName = $row["locationName"];
	$ticketType = $row["ticketType"];
	$siteName = $row["Site_Name"];
	$siteId = $row["Site_Id"];
	$postPercentage = $row["Percentage"];
	$postResult = $row["Result"];
	$siteSurveyStatus = $row["site_survey_status"];
	$nominalLatlong = $row["Nominal_Latlong"];
	$customerSiteId = $row["Customer_Site_Id"];
	$assignTo = $row["Assign_To"];
	$remark = $row["Remark"];
	$loopMenuId = $row["loopMenuId"];

	$fillingByEmpId = $row["fillingByEmpId"];
	$fillerByEmpName = $row["fillerByEmpName"];
	$fillingByState = $row["fillingByState"];
	$fillingByArea = $row["fillingByArea"];

	$verifyByEmpId = $row["verifyByEmpId"];
	$verifiedByEmpName = $row["verifiedByEmpName"];

	$approveByEmpId = $row["approveByEmpId"];
	$approvedByEmpName = $row["approvedByEmpName"];

	$thirdByEmpId = $row["thirdByEmpId"];
	$thirdByEmpName = $row["thirdByEmpName"];

	$fourthByEmpId = $row["fourthByEmpId"];
	$fourthByEmpName = $row["fourthByEmpName"];

	$okNotOkSql = "SELECT * FROM `Site_PM_OK_NOTOK` where `ActivityId` = $activityId";
	$okNotOkQuery = mysqli_query($conn,$okNotOkSql);
	$okNotOkRow = mysqli_fetch_assoc($okNotOkQuery);
	$okCount = $okNotOkRow["ok_count"];
	$notOkCount = $okNotOkRow["not_ok_count"];

	$verifierEmpId = $row["verifierEmpId"];
	$approverEmpId = $row["approverEmpId"];

	$isVerifierExist = false;
	$isApproverExist = false;
	
	if($verifierEmpId != null && $verifierEmpId != ""){
		$isVerifierExist = true;
	}
	
	if($approverEmpId != null && $approverEmpId != ""){
		$isApproverExist = true;
	}

	

	$isVerifier = false;
	if($verifierActivityId == null){
		// if(in_array($verifierEmpId,$empList)){
		// 	$isVerifier = true;
		// }
		$expVeri = explode(",", $verifierEmpId);
		if(count($expVeri) == 1){
			// if(in_array($verifierEmpId,$empList)){
			// 	$isVerifier = true;
			// }
			if($verifierEmpId == $myEmpId){
				$isVerifier = true;
			}
		}
		else{
			for($vi = 0; $vi < count($expVeri); $vi++){
				$loopVeriEmpId = $expVeri[$vi];
				// if(in_array($loopVeriEmpId,$empList)){
				// 	$isVerifier = true;
				// }
				if($loopVeriEmpId == $myEmpId){
					$isVerifier = true;
				}
			}
		}
			
	}
	
	$isApprover = false;
	if($approverActivityId == null){
		// if(in_array($approverEmpId,$empList)){
		// 	$isApprover = true;
		// }
		$expApp = explode(",", $approverEmpId);
		if(count($expApp) == 1){
			// if(in_array($approverEmpId,$empList)){
			// 	$isApprover = true;
			// }
			if($approverEmpId == $myEmpId){
				$isApprover = true;
			}
		}
		else{
			for($ai = 0; $ai < count($expApp); $ai++){
				$loopAppEmpId = $expApp[$ai];
				// if(in_array($loopAppEmpId,$empList)){
				// 	$isApprover = true;
				// }
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

	if(($isVerifierExist || $isVerifier) && $status == "Created"){
		$pendingForVerify = "No";
	}
	if(($isApproverExist || $isApprover) && ($status == "Created" || $status == "Verified")){
		$pendingForApprove = "No";
	}

	if(!$isVerifierExist)
		$pendingForVerify = "NA";

	if(!$isApproverExist)
		$pendingForApprove = "NA";

	
	$json = new StdClass;
	$json -> pendingForApprove = $pendingForApprove;
	$json -> menuId = $loopMenuId;
	$json -> transactionId = $activityId;
	$json -> verifierTId = $verifierActivityId;
	$json -> approvedTId = $approverActivityId;
	$json -> thirdActivityId = $thirdActivityId;
	$json -> fourthActivityId = $fourthActivityId;
	$json -> fourthActivityId = $fourthActivityId;
	$json -> fifthActivityId = $fifthActivityId;
	$json -> sixthActivityId = '';
	$json -> dateTime = $serverDateTime;
	$json -> approveDetList = [];
	$json -> myRoleForTask = $myRoleForTask;
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

	$json -> fifthByEmpName = '';
	$json -> fifthByDate = '';

	$json -> sixthByEmpName = '';
	$json -> sixthByDate = '';


	$json -> topFirstKey = "topFirstCheckpointValue";
	$json -> actionCheckpointList = [];
	$json -> verifyDetList = [];
	$json -> topFirstCheckpointValue = $siteName;
	$json -> siteId = $siteId;
	$json -> pendingForVerify = $pendingForVerify;
	
	$json -> locationName = $locationName;
	$json -> siteCircle = $siteCircle;
	$json -> ticketType = $ticketType;
	$json -> siteSurveyStatus = $siteSurveyStatus;
	$json -> nominalLatlong = $nominalLatlong;
	$json -> customerSiteId = $customerSiteId;
	if($loopMenuId == 283 || $loopMenuId == 285 || $loopMenuId == 287 || $loopMenuId == 313){
		$json -> notOkCount = $postPercentage;
		$json -> status = $postResult;
	}
	else{
		$json -> okCount = $okCount;
		$json -> notOkCount = $notOkCount;
		$json -> status = $status;
	}
	$json -> siteSurveyRemark = $remark;
	$json -> ptwStatus = '';
	
	array_push($wrappedList,$json);

}

$output = array('wrappedList' => $wrappedList, 'responseDesc' => 'SUCCESSFUL', 'responseCode' => '100000', 'count' => $level);
echo json_encode($output);
?>
