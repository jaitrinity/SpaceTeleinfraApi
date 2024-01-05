<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
mysqli_set_charset($conn,'utf8');

include("dbConfiguration.php");
require 'EmployeeTenentId.php';

$json = file_get_contents('php://input');
file_put_contents('/var/www/trinityapplab.in/html/SpaceTeleinfra/log/Ptwlog_'.date("Y-m-d").'.log', date("Y-m-d H:i:s").' '.$json."\n", FILE_APPEND);
$jsonData=json_decode($json,true);
$req = $jsonData[0];
//echo json_encode($req);

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
			//echo $activitySql;
			if(mysqli_query($conn,$activitySql)){
				$activityId = mysqli_insert_id($conn);
			}
			
			if($checklist != null && count($checklist) != 0){
				$insertMapping = "INSERT INTO `Mapping`(`EmpId`, `MenuId`, `LocationId`, `Start`, `End`, `ActivityId`, `Tenent_Id`, `CreateDateTime`) 
				values ('$empId', '$mId', '$lId', curdate(), curdate(), '$activityId', '$tenentId', current_timestamp) ";
				mysqli_query($conn,$insertMapping);

				if($actId == null  && $actId == ''){
					$mappingId = $conn->insert_id;
					$insertInTransHdr="INSERT INTO `TransactionHDR` (`ActivityId`,`Status`,`Lat_Long`, `FakeGPS_App`) VALUES 
					('$activityId','PTW_01','$geolocation','$fakeGpsMessage')";
					
					if(mysqli_query($conn,$insertInTransHdr)){
						$lastTransHdrId = $conn->insert_id;
						$siteName = "";
						$siteType = "";
						$circle = "";
						$supervisorName = "";
						$supervisorMobile = "";
						foreach($checklist as $k=>$v)
						{
							$answer=$v['value'];
							$chkp_idArray=explode("_",$v['Chkp_Id']);

							if(count($chkp_idArray) > 1){
								$chkp_id = $chkp_idArray[1];
								if($chkp_id == 5248){
									$siteName = $answer;
								}
								if($chkp_id == 5247){
									$circle = $answer;
								}
							}
							else{
								$chkp_id = $chkp_idArray[0];
								if($chkp_id == 5308){
									$siteType = $answer;
								}
								if($chkp_id == 5320){
									$supervisorName = $answer;
								}
								if($chkp_id == 5322){
									$supervisorMobile = $answer;
								}
							}	
							
							$dependent=$v['Dependent'];
							if($dependent == ""){
								$dependent = 0;
							}
							
							$insertInTransDtl="INSERT INTO `TransactionDTL` (`ActivityId`, `ChkId`, `Value`, `DependChkId`) VALUES ($activityId, '$chkp_id', '$answer','$dependent')";
							mysqli_query($conn,$insertInTransDtl);
							
						}
						if($siteType != ''){
							if($siteType == "NBS" && $circle != ''){
								$verifierMobile = "";
								if($verifier_Role != null && $verifier_Role !=''){
									$sql2 = "SELECT DISTINCT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId 
									where l.State = '$circle' and el.Role = '$verifier_Role' and l.Tenent_Id = $tenentId ";
									$result2 = mysqli_query($conn,$sql2);
									while ($row2 = mysqli_fetch_assoc($result2)) {
										$verifierMobile .= $row2["Emp_Id"].',';
									}
									if($verifierMobile != ""){
										$verifierMobile = substr($verifierMobile, 0, strlen($verifierMobile)-1);

										$updateMapping = "update Mapping set Verifier = '$verifierMobile' where MappingId = $mappingId and Tenent_Id = $tenentId ";
										mysqli_query($conn,$updateMapping);
									}
										
								}
							}
							else if($siteType == "Existing Site" && $siteName != ''){
								$explodeSiteName = explode(" --- ", $siteName);
								$siteId = $explodeSiteName[1];
								$siteNamee = $explodeSiteName[0];
								$updateHdr = "update TransactionHDR set Site_Id = '$siteId', Site_Name = '$siteNamee' where SRNo = $lastTransHdrId ";
								mysqli_query($conn,$updateHdr);

								$verifierMobile = "";
								if($verifier_Role != null && $verifier_Role !=''){
									$sql2 = "SELECT el.Emp_Id, l.State FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where 
									l.Name = '$siteNamee' and l.Site_Id = '$siteId' and el.Role = '$verifier_Role' and l.Tenent_Id = $tenentId ";
									$result2 = mysqli_query($conn,$sql2);
									while ($row2 = mysqli_fetch_assoc($result2)) {
										$verifierMobile .= $row2["Emp_Id"].',';
										$circle = $row2["State"];
									}
									if($verifierMobile != ""){
										$verifierMobile = substr($verifierMobile, 0, strlen($verifierMobile)-1);

										$updateMapping = "update Mapping set Verifier = '$verifierMobile' where MappingId = $mappingId and Tenent_Id = $tenentId ";
										mysqli_query($conn,$updateMapping);
									}
								}
								
							}
						}

						if($supervisorName != "" && $supervisorMobile != ""){
							$insertSupervisor = "INSERT INTO `Employees`(`EmpId`, `Name`, `Password`, `Mobile`, `RoleId`, `State`, `RMId`, `FieldUser`, 
							`Tenent_Id`, `Registered`, `Update`, `Active`)";
							$insertSupervisor .= "VALUES ('$supervisorMobile', '$supervisorName', '$supervisorMobile', '$supervisorMobile', 54, '$circle', 
							'$empId', 0, $tenentId, current_timestamp, current_timestamp, 1)";
							mysqli_query($conn,$insertSupervisor);

							$updateMapping = "update Mapping set Approver = '$supervisorMobile' where MappingId = $mappingId and Tenent_Id = $tenentId ";
							mysqli_query($conn,$updateMapping);
						}
						
					}
				}
				else{
					$isAllSave = false;
					foreach($checklist as $k=>$v)
					{
						$answer=$v['value'];
						$chkp_idArray=explode("_",$v['Chkp_Id']);

						if(count($chkp_idArray) > 1){
							$chkp_id = $chkp_idArray[1];
						}
						else{
							$chkp_id = $chkp_idArray[0];
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

					}
				}
					
			}
			//Change in Mapping table from now onwards
			if($assignId != ""){
				$updateAssignTaskSql = "Update Mapping set ActivityId = '$activityId' where MappingId = $assignId";
				mysqli_query($conn,$updateAssignTaskSql);

			}
	}
	else{
		$sql = "SELECT r1.Verifier_Role, r1.Approver_Role FROM `Menu` r1 where r1.MenuId = '$mId' ";
		$result = mysqli_query($conn,$sql);
		$row = mysqli_fetch_assoc($result);
		$verifier_Role = $row["Verifier_Role"];
		$approver_Role = $row["Approver_Role"];

		$activitySql = "Insert into Activity(DId,MappingId,EmpId,MenuId,LocationId,Event,GeoLocation,Distance,MobileDateTime,MobileTimestamp,Tenent_Id)"
						." values ('$dId','$mapId','$empId','$mId','$lId','$event','$geolocation','$distance','$date1','$transactionId',$tenentId)";
		//echo $activitySql;
		if(mysqli_query($conn,$activitySql)){
			$activityId = mysqli_insert_id($conn);
		}
		
		if($checklist != null && count($checklist) != 0){
			$insertMapping = "INSERT INTO `Mapping`(`EmpId`, `MenuId`, `LocationId`, `Start`, `End`, `ActivityId`, `Tenent_Id`, `CreateDateTime`) 
			values ('$empId', '$mId', '$lId', curdate(), curdate(), '$activityId', '$tenentId', current_timestamp) ";
			mysqli_query($conn,$insertMapping);

			if($actId == null  && $actId == ''){
				$mappingId = $conn->insert_id;
				$insertInTransHdr="INSERT INTO `TransactionHDR` (`ActivityId`,`Status`,`Lat_Long`, `FakeGPS_App`) VALUES 
				('$activityId','Created','$geolocation','$fakeGpsMessage')";
				
				if(mysqli_query($conn,$insertInTransHdr)){
					$lastTransHdrId = $conn->insert_id;
					$acEmpId = "";
					$siteName = "";
					foreach($checklist as $k=>$v)
					{
						$answer=$v['value'];
						$chkp_idArray=explode("_",$v['Chkp_Id']);

						if(count($chkp_idArray) > 1){
							$chkp_id = $chkp_idArray[1];
							if($chkp_id == 4726 || $chkp_id == 4717 || $chkp_id == 4929){
								$siteName = $answer;
							}
						}
						else{
							$chkp_id = $chkp_idArray[0];
							if($chkp_id == 4726 || $chkp_id == 4717 || $chkp_id == 4929){
								$siteName = $answer;
							}
							if($chkp_id == 5196){
								$acEmpId = $answer;
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
							$sql2 = "SELECT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where l.Name = '$siteNamee' 
							and l.Site_Id = '$siteId' and el.Role = '$verifier_Role' and l.Tenent_Id = $tenentId ";
							$result2 = mysqli_query($conn,$sql2);
							while ($row2 = mysqli_fetch_assoc($result2)) {
								$verifierMobile .= $row2["Emp_Id"].',';
							}

							$otherSql = "SELECT * FROM `Employees` where RoleId in (46,59,60) and Active = 1";
							$otherResult = mysqli_query($conn,$otherSql);
							while ($otherRow = mysqli_fetch_assoc($otherResult)) {
								$verifierMobile .= $otherRow["EmpId"].',';
							}

							$verifierMobile = substr($verifierMobile, 0, strlen($verifierMobile)-1);
						}

						$approverMobile = "";
						if($approver_Role != null && $approver_Role !=''){
							$sql3 = "SELECT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where l.Name = '$siteNamee' 
							and l.Site_Id = '$siteId' and el.Role = '$approver_Role' and l.Tenent_Id = $tenentId";
							$result3 = mysqli_query($conn,$sql3);
							while ($row3 = mysqli_fetch_assoc($result3)) {
								$approverMobile .= $row3["Emp_Id"].',';
							}
							$approverMobile = substr($approverMobile, 0, strlen($approverMobile)-1);
						}

						$updateMapping = "update Mapping set Verifier = '$verifierMobile', Approver = '$approverMobile' where MappingId = $mappingId and 
						Tenent_Id = $tenentId ";
						mysqli_query($conn,$updateMapping);

					}

					if($acEmpId != ""){
						$explodeAcEmpId = explode(" --- ", $acEmpId);
						$acEmpId = $explodeAcEmpId[0];
						$acEmpName = $explodeAcEmpId[1];

						$updateMapping = "update Mapping set Verifier = '$acEmpId' where MappingId = $mappingId and Tenent_Id = $tenentId ";
						mysqli_query($conn,$updateMapping);
					}
					
				}
			}
			else{
				$isAllSave = false;
				$assignTech = "";
				$actName = "";
				$auditorStatus = "";
				$workStatus = "";
				$riskLevel = "";
				$rdhNdhStatus = ""; 
				$closureStatus = "";
				foreach($checklist as $k=>$v)
				{
					$answer=$v['value'];
					$chkp_idArray=explode("_",$v['Chkp_Id']);

					if(count($chkp_idArray) > 1){
						$chkp_id = $chkp_idArray[1];
						if($chkp_id == 5537){
							$assignTech = $answer;
						}
					}
					else{
						$chkp_id = $chkp_idArray[0];
						if($chkp_id == 5537){
							$assignTech = $answer;
						}
						else if($chkp_id == 5536){
							$actName = $answer;
						}
						else if($chkp_id == 5711){
							$auditorStatus = $answer;
						}
						else if($chkp_id == 5541 || $chkp_id == 5604 || $chkp_id == 5624 || $chkp_id == 5633 || $chkp_id == 5645 || $chkp_id == 5663 || $chkp_id == 5675){
							$workStatus = $answer;
						}
						else if($chkp_id == 5693){
							$riskLevel = $answer;
						}
						else if($chkp_id == 5718){
							$rdhNdhStatus = $answer;
						}
						else if($chkp_id == 5714){
							$closureStatus = $answer;
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

				}

				if($actName == "Approve"){
					// $explodeAssignTech = explode(" --- ", $assignTech);
					// $assignTechId = $explodeAssignTech[0];
					// $assignTechName = $explodeAssignTech[1];

					// $updateMapping = "UPDATE `Mapping` set `Fourth` = '$assignTechId' where `ActivityId` = $actId and `Tenent_Id` = $tenentId ";
					// mysqli_query($conn,$updateMapping);
				}
				else if($actName == "Reject"){
					// $updateMapping = "UPDATE `Mapping` set `Approver` = null,`Third` = null,`Fourth` = null,`Fifth` = null where `ActivityId` = $actId and `Tenent_Id` = $tenentId ";
					// mysqli_query($conn,$updateMapping);
				}

				if($riskLevel != "" && $riskLevel < 15){

				}
				else if($riskLevel != "" && $riskLevel >= 15){
					$sixthEmpId = "";
					$otherSql = "SELECT * FROM `Employees` where RoleId in (59,60) and Active = 1";
					$otherResult = mysqli_query($conn,$otherSql);
					while ($otherRow = mysqli_fetch_assoc($otherResult)) {
						$sixthEmpId .= $otherRow["EmpId"].',';
					}
					$sixthEmpId = substr($sixthEmpId, 0, strlen($sixthEmpId)-1);

					$updateMapping = "UPDATE `Mapping` set `Sixth` = '$sixthEmpId' where `ActivityId` = $actId and `Tenent_Id` = $tenentId ";
					mysqli_query($conn,$updateMapping);
				}
			}
				
		}
		if($actId != null && $actId != ''){

			$selectMappSql = "SELECT `Fourth`,`Fifth` from `Mapping` where `ActivityId` = $actId and `Tenent_Id` = $tenentId";
			$selectMappResult = mysqli_query($conn,$selectMappSql);
			$selectMappRow = mysqli_fetch_array($selectMappResult);
			$fourthEmpId = $selectMappRow["Fourth"];
			$fifthEmpId = $selectMappRow["Fifth"];
			$fourthEmpList = explode(",", $fourthEmpId);
			$isAuditor = false;
			$isCloser = false;
			if(in_array($empId,$fourthEmpList)) $isAuditor = true;
			if($fifthEmpId == $empId) $isCloser = true;

			$selectTransHdrSql = "Select * from `TransactionHDR`  where `ActivityId` = $actId";
			$selectTransHdrResult = mysqli_query($conn,$selectTransHdrSql);
			$thRow=mysqli_fetch_array($selectTransHdrResult);
			$trStatus = $thRow['Status'];
			if($activityId !=0){
				if($trStatus == 'PTW_01' && $actName == "Approve"){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_02', `VerifierActivityId` = '$activityId' where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
				}
				else if($trStatus == 'PTW_01' && $actName == "Reject"){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_99', `VerifierActivityId` = '$activityId' where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
				}
				else if($trStatus == 'PTW_02' && $workStatus == "Start"){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_03', `ApproverActivityId` = '$activityId' where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
				}
				else if($trStatus == 'PTW_02' && $workStatus == "Cancel"){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_98', `ApproverActivityId` = '$activityId' where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
				}
				else if($trStatus == 'PTW_03' && $riskLevel != "" && $riskLevel < 15){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_04', `ThirdActivityId` = '$activityId' where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
				}
				else if($trStatus == 'PTW_03' && $riskLevel != "" && $riskLevel >= 15){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_90', `ThirdActivityId` = '$activityId' where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
				}
				else if($trStatus == 'PTW_90' && $rdhNdhStatus == "Approve"){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_91', `SixthActivityId` = '$activityId' where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
				}
				else if($trStatus == 'PTW_90' && $rdhNdhStatus == "Reject"){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_99', `SixthActivityId` = '$activityId' where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
				}
				else if(($trStatus == 'PTW_04' || $trStatus == 'PTW_91') && $isAuditor && $auditorStatus == "No gap found"){
					$insertAudit = "INSERT INTO `PTWAudit`(`ActivityId`, `AuditActivityId`) VALUES ($actId,$activityId)";
					mysqli_query($conn,$insertAudit);
				}
				else if(($trStatus == 'PTW_04' || $trStatus == 'PTW_91') && $isAuditor && $auditorStatus == "Reject"){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_103'  where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
					$insertAudit = "INSERT INTO `PTWAudit`(`ActivityId`, `AuditActivityId`) VALUES ($actId,$activityId)";
					mysqli_query($conn,$insertAudit);
				}
				else if(($trStatus == 'PTW_04' || $trStatus == 'PTW_91') && $isCloser && $closureStatus != ""){
					$updateTransHdrSql = "Update `TransactionHDR` set `Status` = 'PTW_05', `FourthActivityId` = '$activityId' where `ActivityId` = $actId";
					mysqli_query($conn,$updateTransHdrSql);
				}
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

	file_put_contents('/var/www/trinityapplab.in/html/SpaceTeleinfra/log/Ptwlog_'.date("Y-m-d").'.log', date("Y-m-d H:i:s").' '.json_encode($output)."\n", FILE_APPEND);
	
}



?>