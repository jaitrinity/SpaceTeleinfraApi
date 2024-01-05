<?php 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
mysqli_set_charset($conn,'utf8');

include("dbConfiguration.php");
require 'EmployeeTenentId.php';

$json = file_get_contents('php://input');
$jsonData=json_decode($json,true);
$req = $jsonData[0];

$mapId=$req['mappingId'];
$empId=$req['Emp_id'];
$mId=$req['M_Id'];
$lId=$req['locationId'];
$event=$req['event'];
$geolocation=$req['geolocation'];
$distance=$req['distance'];
$mobiledatetime=$req['mobiledatetime'];
$fakeGpsMessage=$req['fakeGpsMessage'];
 
$caption = $req['caption'];
$transactionId = $req['timeStamp'];
$checklist = $req['checklist'];
$dId = $req['did'];
$assignId = $req['assignId'];
$actId = $req['activityId'];
$lastTransHdrId = "";
$activityId = 0;

$logEnableSql = "SELECT `Log_Status` FROM `configuration`";
$logEnableQuery=mysqli_query($conn,$logEnableSql);
$logEnableRow = mysqli_fetch_assoc($logEnableQuery);
$configLogStatus = $logEnableRow["Log_Status"];
if($configLogStatus == 1){
$logReqSql = "INSERT INTO `Save_Logs`(`Api_Name`, `Emp_Id`, `Data_Type`, `Data_Json`, `Mobile_Datetime`, `Server_Datetime`) VALUES ('saveCheckpoint.php', '$empId', 'Request', '".json_encode($req)."', '$mobiledatetime', current_timestamp)";
 mysqli_query($conn,$logReqSql);
}

if ((strpos($mobiledatetime, 'AM') !== false) || (strpos($mobiledatetime, 'PM')) || (strpos($mobiledatetime, 'am') !== false) || (strpos($mobiledatetime, 'pm')))   {
	$date = date_create_from_format("Y-m-d h:i:s A","$mobiledatetime");
	$date1 = date_format($date,"Y-m-d H:i:s");
}
else{
	$date1 = $mobiledatetime;
}

if($lId == ""){
 	$lId = '1';
}

if($mId == ''){
 	$mId = '0';
}

if($mapId == ''){
	$mapId = '0';
}
 
if($actId == ''){
	 $actId = null;
}

if($event == 'Submit'){
	$existSql = "SELECT `ActivityId` FROM `Activity` where `MobileTimestamp` = '$transactionId' and Event = 'Submit'";
	$existResult = mysqli_query($conn,$existSql);
	$existRowCount=mysqli_num_rows($existResult);
	if($existRowCount !=0){
		$existrow = mysqli_fetch_assoc($existResult);
		$existActId = $existrow["ActivityId"];
		$existOutput = "";
		$existOutput -> error = "200";
		$existOutput -> message = "success";
		$existOutput -> TransID = "$existActId";
		echo json_encode($existOutput);

		if($configLogStatus == 1){
			$logResSql = "INSERT INTO `Save_Logs`(`Api_Name`, `Emp_Id`, `Data_Type`, `Data_Json`, `Mobile_Datetime`, `Server_Datetime`) VALUES ('saveCheckpoint.php', '$empId', 'Response - duplicate', '".json_encode($existOutput)."', '$mobiledatetime', current_timestamp)";
		 	mysqli_query($conn,$logResSql);
		}
		return;
	}
	
	$classObj = new EmployeeTenentId();
	$tenentId = $classObj->getTenentIdByEmpId($conn,$empId);

	if($actId == null  && $actId == ''){
		$sql = "SELECT r1.Verifier_Role, r1.Approver_Role FROM `Menu` r1 where r1.MenuId = '$mId' ";
		$result = mysqli_query($conn,$sql);
		$row = mysqli_fetch_assoc($result);
		$verifier_Role = $row["Verifier_Role"];
		$approver_Role = $row["Approver_Role"];

		$activitySql = "Insert into Activity(DId,MappingId,EmpId,MenuId,LocationId,Event,GeoLocation,Distance,MobileDateTime,MobileTimestamp,Tenent_Id)"
							." values ('$dId','$mapId','$empId','$mId','$lId','$event','$geolocation','$distance','$date1','$transactionId',$tenentId)";

		if(mysqli_query($conn,$activitySql)){
			$activityId = mysqli_insert_id($conn);
		}

		if($checklist != null && count($checklist) != 0){
			$insertMapping = "INSERT INTO `Mapping`(`EmpId`, `MenuId`, `LocationId`, `Start`, `End`, `ActivityId`, `Tenent_Id`, `CreateDateTime`) 
			values ('$empId', '$mId', '$lId', curdate(), curdate(), '$activityId', '$tenentId', current_timestamp) ";
			mysqli_query($conn,$insertMapping);
			$mappingId = $conn->insert_id;

			$insertInTransHdr="INSERT INTO `TransactionHDR` (`ActivityId`,`Status`,`Lat_Long`, `FakeGPS_App`) VALUES 
					('$activityId','Created','$geolocation','$fakeGpsMessage')";

			if(mysqli_query($conn,$insertInTransHdr)){
				$lastTransHdrId = $conn->insert_id;
				$siteName = "";
				foreach($checklist as $k=>$v)
				{
					$answer=$v['value'];
					$chkp_idArray=explode("_",$v['Chkp_Id']);
					if(count($chkp_idArray) > 1){
						$chkp_id = $chkp_idArray[1];
					}
					else{
						$chkp_id = $chkp_idArray[0];
						if($chkp_id == 5295 || $chkp_id == 5317){
							$siteName = $answer;
						}
					}
					$dependent=$v['Dependent'];
					if($dependent == ""){
						$dependent = 0;
					}
					
					$insertInTransDtl="INSERT INTO `TransactionDTL` (`ActivityId`, `ChkId`, `Value`, `DependChkId`) VALUES ($activityId, '$chkp_id', '$answer','$dependent')";
					mysqli_query($conn,$insertInTransDtl);
				}
				if($siteName != ''){
					$explodeSiteName = explode(" --- ", $siteName);
					$siteId = $explodeSiteName[1];
					$siteNamee = $explodeSiteName[0];

					$updateHdr = "update TransactionHDR set Site_Id = '$siteId', Site_Name = '$siteNamee' where SRNo = $lastTransHdrId ";
					mysqli_query($conn,$updateHdr);

					$verifierMobile = "";
					if($verifier_Role != null && $verifier_Role !=''){
						// $sql2 = "SELECT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where l.Name = '$siteNamee' 
						// and l.Site_Id = '$siteId' and el.Role = '$verifier_Role' and l.Tenent_Id = $tenentId ";

						// $sql2 = "SELECT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where l.Name = '$siteNamee' 
						// and l.Site_Id = '$siteId' and el.Role = '$verifier_Role' ";
						// $result2 = mysqli_query($conn,$sql2);
						// while ($row2 = mysqli_fetch_assoc($result2)) {
						// 	$verifierMobile .= $row2["Emp_Id"].',';
						// }
						// $verifierMobile = substr($verifierMobile, 0, strlen($verifierMobile)-1);

						$verifierMobile = "9911005494";
					}

					$approverMobile = "";
					if($approver_Role != null && $approver_Role !=''){
						// $sql3 = "SELECT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where l.Name = '$siteNamee' 
						// and l.Site_Id = '$siteId' and el.Role = '$approver_Role' and l.Tenent_Id = $tenentId";
						// $result3 = mysqli_query($conn,$sql3);
						// while ($row3 = mysqli_fetch_assoc($result3)) {
						// 	$approverMobile .= $row3["Emp_Id"].',';
						// }
						// $approverMobile = substr($approverMobile, 0, strlen($approverMobile)-1);

						// $sql3 = "SELECT e.EmpId FROM Role r join Employees e on r.RoleId = e.RoleId where r.Role = '$approver_Role'";
						// $result3 = mysqli_query($conn,$sql3);
						// while ($row3 = mysqli_fetch_assoc($result3)) {
						// 	$approverMobile .= $row3["EmpId"].',';
						// }
						// $approverMobile = substr($approverMobile, 0, strlen($approverMobile)-1);


						$approverMobile = "9615001596";
					}

					$updateMapping = "update Mapping set Verifier = '$verifierMobile', Approver = '$approverMobile' where MappingId = $mappingId and 
					Tenent_Id = $tenentId ";
					mysqli_query($conn,$updateMapping);
				}

			}
		}
	}
	else{
		// For save Assign emp data
		$activitySql = "Insert into Activity(DId,MappingId,EmpId,MenuId,LocationId,Event,GeoLocation,Distance,MobileDateTime,MobileTimestamp,Tenent_Id)"
						." values ('$dId','$mapId','$empId','$mId','$lId','$event','$geolocation','$distance','$date1','$transactionId',$tenentId)";

		if(mysqli_query($conn,$activitySql)){
			$activityId = mysqli_insert_id($conn);
		}

		if($checklist != null && count($checklist) != 0){
			$isAllSave = false;

			$actionValueCDH = "";
			$actionValueCDH1 = "";
			$actionValueNQH = "";
			$actionValueNQH1 = "";
			foreach($checklist as $k=>$v)
			{
				$answer=$v['value'];
				$chkp_idArray=explode("_",$v['Chkp_Id']);

				if(count($chkp_idArray) > 1){
					$chkp_id = $chkp_idArray[1];
				}
				else{
					$chkp_id = $chkp_idArray[0];
					if($chkp_id == 5340){
						$actionValueCDH = $answer;
					}
					else if($chkp_id == 5342){
						$actionValueCDH1 = $answer;
					}
					else if($chkp_id == 5341){
						$actionValueNQH = $answer;
					}
					else if($chkp_id == 5343){
						$actionValueNQH1 = $answer;
					}
				}	
				
				$dependent=$v['Dependent'];
				if($dependent == ""){
					$dependent = 0;
				}
				
				$insertInTransDtl="INSERT INTO `TransactionDTL` (`ActivityId`, `ChkId`, `Value`, `DependChkId`) 
				VALUES ($activityId, '$chkp_id', '$answer','$dependent')";
				if(mysqli_query($conn,$insertInTransDtl)){
					$isAllSave = true;
				}
			}
			if($isAllSave){
				$lastTransHdrId = $activityId;
				if($assignId != ""){
					$updateAssignTaskSql = "Update Mapping set ActivityId = '$activityId' where MappingId = $assignId";
					mysqli_query($conn,$updateAssignTaskSql);
				}


				if($actionValueCDH == "Reject"){
					$updateAssignTaskSql = "Update Mapping set Approver = null where ActivityId = $actId";
					mysqli_query($conn,$updateAssignTaskSql);
				}
				if($actionValueCDH1 == "Approve"){
					$updateAssignTaskSql = "Update Mapping set Fourth = Approver where ActivityId = $actId";
					mysqli_query($conn,$updateAssignTaskSql);
				}
				if($actionValueNQH == "Approve"){

				}
				else if($actionValueNQH == "RFI"){
					$updateAssignTaskSql = "Update Mapping set Third = Verifier where ActivityId = $actId";
					mysqli_query($conn,$updateAssignTaskSql);
				}
				else if($actionValueNQH == "Reject"){

				}
					

				$selectTransHdrSql = "Select * from TransactionHDR  where ActivityId = $actId";
				$selectTransHdrResult = mysqli_query($conn,$selectTransHdrSql);
				$thRow=mysqli_fetch_array($selectTransHdrResult);
				if($thRow['Status'] == 'Created'){
					$updateTransHdrSql = "Update TransactionHDR set Status = 'Verified', VerifierActivityId = '$activityId' where ActivityId = $actId";
				}
				else if($thRow['Status'] == 'Verified'){
					$updateTransHdrSql = "Update TransactionHDR set Status = 'Approved', ApproverActivityId = '$activityId' where ActivityId = $actId";
				}
				else if($actionValueCDH1 == "Approve" && $thRow['Status'] == 'Approved'){
					$updateTransHdrSql = "Update TransactionHDR set Status = 'STATUS_03', ThirdActivityId = '$activityId' where ActivityId = $actId";
				}
				else if($actionValueNQH1 != "" && $thRow['Status'] == 'STATUS_03'){
					$updateTransHdrSql = "Update TransactionHDR set Status = 'STATUS_04', FourthActivityId = '$activityId' where ActivityId = $actId";
				}
				
				mysqli_query($conn,$updateTransHdrSql);
			}

		}
	}
	$output = "";
	if($lastTransHdrId != ""){
		$output -> error = "200";
		$output -> message = "success";
		$output -> TransID = "$activityId";
	}
	else{
		$output -> error = "0";
		$output -> message = "something wrong";
		$output -> TransID = "$activityId";
	}
	echo json_encode($output);

}
?>