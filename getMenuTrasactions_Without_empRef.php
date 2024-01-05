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

// Site PM Checklist
if($menuId == 274){
	$pmResult = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/pmGetMenuTrasactions.php",$json);
 	echo $pmResult;
 	return;
}
// Incident Management
else if($menuId == 276){
	$inciResult = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/inciGetMenuTrasactions.php",$json);
 	echo $inciResult;
 	return;
}
// Meter Reading
else if($menuId == 282 || $menuId == 293){
	$meterReadingResult = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/getMeterReadingTrasactions.php",$json);
 	echo $meterReadingResult;
 	return;
}
// Training
else if($menuId == 283 || $menuId == 285 || $menuId == 287 || $menuId == 313){
	$trainingResult = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/getTrainingTrasactions.php",$json);
 	echo $trainingResult;
 	return;
}
// PTW
else if($menuId == 303 || $menuId == 304 || $menuId == 305 || $menuId == 306 || $menuId == 307 || $menuId == 308 || $menuId == 309 || $menuId == 310){
	$ptwResult = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/ptwGetMenuTrasactions.php",$json);
 	echo $ptwResult;
 	return;
}
// Management Visit
else if($menuId == 311 || $menuId == 312){
	$mgtVisitResult = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/getMgtVisitTrasactions.php",$json);
 	echo $mgtVisitResult;
 	return;
}

$empList = [];
// for $loginEmpRole == 'Admin(10)', 'SpaceWorld(50)', 'Management(51)', 'CB(52)', 'Quality Admin(56)', 'Corporate OnM lead(57)'
if($loginEmpRole == '10' || $loginEmpRole == '50' || $loginEmpRole == '51' || $loginEmpRole == '52' || $loginEmpRole == '56' || $loginEmpRole == '57'){
	// $empSql = "SELECT * FROM `Employees` WHERE `Tenent_Id` = $tenentId and `Active` = 1";
	$empSql = "SELECT * FROM `Employees` WHERE `Tenent_Id` = $tenentId";
	$empQuery=mysqli_query($conn,$empSql);
	if(mysqli_num_rows($empQuery) !=0){
		while($row11 = mysqli_fetch_assoc($empQuery)){
			array_push($empList,$row11["EmpId"]);
		}
	}

}
// for $loginEmpRole == CBH(46)
else if($loginEmpRole == '46'){
	// $empSql = "SELECT * FROM `Employees` WHERE `RoleId` in (46,48) and `Tenent_Id` = $tenentId and `Active` = 1";
	$empSql = "SELECT * FROM `Employees` WHERE `RoleId` in (46,48) and `Tenent_Id` = $tenentId";
	$empQuery=mysqli_query($conn,$empSql);
	if(mysqli_num_rows($empQuery) !=0){
		while($row11 = mysqli_fetch_assoc($empQuery)){
			array_push($empList,$row11["EmpId"]);
		}
	}
}
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
if($menuId == 284 || $menuId == 288){
	$menuId = '284,288';
}

$output = array();
$wrappedList = [];

$unionSql = "select DISTINCT t.`ActivityId` from (
SELECT `ActivityId` FROM `Mapping` where (`EmpId` in ('$loginEmpId') OR `Verifier` in ('$loginEmpId') OR `Approver` in ('$loginEmpId')) and `MenuId` in ($menuId) and `ActivityId` != 0
UNION
select `ActivityId` from `Activity` where `EmpId` in ('$loginEmpId') and `MenuId` in ($menuId) and `Event` = 'Submit'
UNION 
SELECT h.ActivityId FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId join TransactionHDR h on l.Site_Id = h.Site_Id join Activity a on h.ActivityId = a.ActivityId and a.MenuId in ($menuId) and a.Event = 'Submit' WHERE el.`Emp_Id` in ('$loginEmpId') ) t";

if($loginEmpRole == '10' || $loginEmpRole == '50' || $loginEmpRole == '51' || $loginEmpRole == '52' || $loginEmpRole == '56' || $loginEmpRole == '57'){
	$unionSql = "select `ActivityId` from `Activity` where `EmpId` in ('$loginEmpId') and `MenuId` in ($menuId) and `Event` = 'Submit'";
}

$sql = "SELECT distinct `h`.`ActivityId`, `a`.`MobileDateTime` as ServerDateTime, `h`.`Status`, `h`.`VerifierActivityId`, 
`h`.`ApproverActivityId`, `h`.`ThirdActivityId`, `h`.`FourthActivityId`, `h`.`FifthActivityId`, `h`.`Site_Name`, `a`.`EmpId` as fillingByEmpId, `e`.`Name` as fillerByEmpName, a.MenuId as loopMenuId,
`e`.`State` as fillingByState, 
`e`.`Area` as fillingByArea, `a1`.`MenuId`, `a1`.`EmpId` as verifyByEmpId, 
`e1`.`Name` as verifiedByEmpName, `a1`.`ServerDateTime` as verifiedDate, `a2`.`EmpId` as approveByEmpId,
`e2`.`Name` as approvedByEmpName, `a2`.`ServerDateTime` as approvedDate, l.`Name` as locationName, 
`a3`.`EmpId` as thirdByEmpId, `e3`.`Name` as thirdByEmpName, `a3`.`ServerDateTime` as thirdByDate,
`a4`.`EmpId` as fourthByEmpId, `e4`.`Name` as fourthByEmpName, `a4`.`ServerDateTime` as fourthByDate,
`a5`.`EmpId` as fifthByEmpId, `e5`.`Name` as fifthByEmpName, `a5`.`ServerDateTime` as fifthByDate, 
(case when a.`MappingId` = 0 then 'Incident' else 'Planned' end) as ticketType, 
(case 
	when `h`.`Verify_Final_Submit` is null and `h`.`TransactionStatus` = 1 then 'Pending'
	when `h`.`Verify_Final_Submit` = 'No' and `h`.`TransactionStatus` = 1 then 'In Progress' 
	when `h`.`Verify_Final_Submit` = 'Yes' and `h`.`TransactionStatus` = 1 then 'Completed' 
	else 'Closed' end) as site_survey_status, `h`.`Remark`,  
`h`.`Customer_Site_Id`, 
`h`.`Nominal_Latlong`, `h`.`Assign_To`, 
(case when a.`MenuId` = 283 then concat(f.`Percentage`,' %') when a.`MenuId` = 285 then concat(c.`Percentage`,' %') when a.`MenuId` = 287 then concat(fb.`Percentage`,' %') when a.`MenuId` = 313 then concat(vnt.`Percentage`,' %') else 'NA' end) as `Percentage`,    
(case when a.`MenuId` = 283 then f.`Result` when a.`MenuId` = 285 then c.`Result` when a.`MenuId` = 287 then fb.`Result` when a.`MenuId` = 313 then vnt.`Result` else 'NA' end) as `Result`,
'' as `ok_count`, '' as `not_ok_count`, m.`Verifier` as verifierEmpId, m.`Approver` as approverEmpId   
FROM `TransactionHDR` h
join `Activity` a on `h`.`ActivityId` = `a`.`ActivityId`
left join `Mapping` m on a.`ActivityId` = m.`ActivityId`
left join `Location` l on a.`LocationId` = l.`LocationId`
left join `Activity` a1 on `h`.`VerifierActivityId` = `a1`.`ActivityId` 
left join `Activity` a2 on `h`.`ApproverActivityId` = `a2`.`ActivityId`
left join `Activity` a3 on `h`.`ThirdActivityId` = `a3`.`ActivityId`
left join `Activity` a4 on `h`.`FourthActivityId` = `a4`.`ActivityId`
left join `Activity` a5 on `h`.`FifthActivityId` = `a5`.`ActivityId`
left join `Employees` e on `a`.`EmpId` = `e`.`EmpId` 
left join `Employees` e1 on `a1`.`EmpId` = `e1`.`EmpId` 
left join `Employees` e2 on `a2`.`EmpId` = `e2`.`EmpId` 
left join `Employees` e3 on `a3`.`EmpId` = `e3`.`EmpId` 
left join `Employees` e4 on `a4`.`EmpId` = `e4`.`EmpId` 
left join `Employees` e5 on `a5`.`EmpId` = `e5`.`EmpId` 
left join `Fire_Training_Result` f on `a`.`ActivityId` = `f`.`ActivityId` 
left join `Coslight_Training_Result` c on `a`.`ActivityId` = `c`.`ActivityId` 
left join `Fiber_Training_Result` fb on `a`.`ActivityId` = `fb`.`ActivityId` 
left join `VNT_Training_Result` vnt on `a`.`ActivityId` = `vnt`.`ActivityId`
where `h`.`ActivityId` in ($unionSql) ";

if($menuId == 300 || $menuId == 301){
	$sql = "SELECT distinct `h`.`ActivityId`, `a`.`MobileDateTime` as ServerDateTime, `h`.`Status`, `h`.`VerifierActivityId`, 
	`h`.`ApproverActivityId`, `h`.`ThirdActivityId`, `h`.`FourthActivityId`, `h`.`FifthActivityId`, `h`.`Site_Name`, `a`.`EmpId` as fillingByEmpId, `e`.`Name` as fillerByEmpName, a.MenuId as loopMenuId,
	`e`.`State` as fillingByState, 
	`e`.`Area` as fillingByArea, `a1`.`MenuId`, `a1`.`EmpId` as verifyByEmpId, 
	`e1`.`Name` as verifiedByEmpName, `a1`.`ServerDateTime` as verifiedDate, `a2`.`EmpId` as approveByEmpId,
	`e2`.`Name` as approvedByEmpName, `a2`.`ServerDateTime` as approvedDate, l.`Name` as locationName, 
	`a3`.`EmpId` as thirdByEmpId, `e3`.`Name` as thirdByEmpName, `a3`.`ServerDateTime` as thirdByDate,
	`a4`.`EmpId` as fourthByEmpId, `e4`.`Name` as fourthByEmpName, `a4`.`ServerDateTime` as fourthByDate,
	`a5`.`EmpId` as fifthByEmpId, `e5`.`Name` as fifthByEmpName, `a5`.`ServerDateTime` as fifthByDate, 
	'' as ticketType, qc.TktStatus as site_survey_status, '' as `Remark`, '' as `Customer_Site_Id`, 
	'' as `Nominal_Latlong`, '' as `Assign_To`, '' as `Percentage`, '' as `Result`, '' as `ok_count`, '' as `not_ok_count`, 
	m.`Verifier` as verifierEmpId, m.`Approver` as approverEmpId   
	FROM `TransactionHDR` h
	join `Activity` a on `h`.`ActivityId` = `a`.`ActivityId`
	left join `Mapping` m on a.`ActivityId` = m.`ActivityId`
	left join `Location` l on a.`LocationId` = l.`LocationId`
	left join `Activity` a1 on `h`.`VerifierActivityId` = `a1`.`ActivityId` 
	left join `Activity` a2 on `h`.`ApproverActivityId` = `a2`.`ActivityId`
	left join `Activity` a3 on `h`.`ThirdActivityId` = `a3`.`ActivityId`
	left join `Activity` a4 on `h`.`FourthActivityId` = `a4`.`ActivityId`
	left join `Activity` a5 on `h`.`FifthActivityId` = `a5`.`ActivityId`
	left join `Employees` e on `a`.`EmpId` = `e`.`EmpId` 
	left join `Employees` e1 on `a1`.`EmpId` = `e1`.`EmpId` 
	left join `Employees` e2 on `a2`.`EmpId` = `e2`.`EmpId` 
	left join `Employees` e3 on `a3`.`EmpId` = `e3`.`EmpId` 
	left join `Employees` e4 on `a4`.`EmpId` = `e4`.`EmpId` 
	left join `Employees` e5 on `a5`.`EmpId` = `e5`.`EmpId` 
	left join `Quality_Checklist` qc on h.`ActivityId` = qc.`ActivityId`
	where `h`.`ActivityId` in ($unionSql) ";
}

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
	$locationName = $row["locationName"];
	$ticketType = $row["ticketType"];
	$siteName = $row["Site_Name"];
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

	$okCount = $row["ok_count"];
	$notOkCount = $row["not_ok_count"];

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
	// $json -> transactionDetList = $transactionDetList;
	// $json -> topFirstCheckpointDesc = $topFirstCheckpointDesc;
	// $json -> topThirdCheckpointDesc = $topThirdCheckpointDesc;
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
	// $json -> topSecondCheckpointValue = $topSecondCheckpointValue;
	$json -> actionCheckpointList = [];
	$json -> verifyDetList = [];
	// $json -> topSecondKey = "topSecondCheckpointValue";
	$json -> topFirstCheckpointValue = $siteName;
	$json -> pendingForVerify = $pendingForVerify;
	// $json -> topSecondCheckpointDesc = $topSecondCheckpointDesc;
	// $json -> topThirdCheckpointValue = $topThirdCheckpointValue;
	// $json -> topThirdKey = "topThirdCheckpointValue";
	
	$json -> locationName = $locationName;
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


<?php
function CallAPI($method, $url, $data)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
	//echo $result."\n";
    curl_close($curl);

    return $result;
}
?>