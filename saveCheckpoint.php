<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
mysqli_set_charset($conn,'utf8');

include("dbConfiguration.php");
require 'EmployeeTenentId.php';

$json = file_get_contents('php://input');
file_put_contents('/var/www/trinityapplab.in/html/SpaceTeleinfra/log/log_'.date("Y-m-d").'.log', date("Y-m-d H:i:s").' '.$json."\n", FILE_APPEND);
$jsonData=json_decode($json,true);
$req = $jsonData[0];
//echo json_encode($req);

 $mapId=$req['mappingId'];
 $empId=$req['Emp_id'];
 $mId=$req['M_Id'];
 $lId=$req['locationId'];
 $event=$req['event'];
 $geolocation=$req['geolocation'];
 $geolocation = str_replace(",", "/", $geolocation);
 $distance=$req['distance'];
 $mobiledatetime=$req['mobiledatetime'];
 $fakeGpsMessage=$req['fakeGpsMessage'];

 //$date = date_create_from_format("Y-m-d h:i:s A","$mobiledatetime");
 //$date1 = date_format($date,"Y-m-d H:i:s");
 
 $caption = $req['caption'];
 $transactionId = $req['timeStamp'];
 $checklist = $req['checklist'];
 $dId = $req['did'];
 $assignId = $req['assignId'];
 $actId = $req['activityId'];
 $lastTransHdrId = "";
 $activityId = 0;


 if($mId == 300 || $mId == 301){
 	$result1 = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/executiveSaveCheckpoint.php",$json);
 	echo $result1;
 	return;
 }
 else if($mId == 303 || $mId == 304 || $mId == 305 || $mId == 306 || $mId == 307 || $mId == 308 || $mId == 309 || $mId == 310){
 	$ptwResult = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/ptwSaveCheckpoint.php",$json);
 	echo $ptwResult;
 	return;
 }

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
 	if($mId != 279){
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
 	}
 	$classObj = new EmployeeTenentId();
	$tenentId = $classObj->getTenentIdByEmpId($conn,$empId);

	if($actId == null  && $actId == ''){
		for($inc=0;$inc<4;$inc++){
			if($mId != 279){
				$inc = 3;
			}
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
			
			$isFinallySubmit = "";
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
						$customerId = "";
						$acEmpId = "";
						$lat = "";
						$long = "";
						$siteName = "";
						foreach($checklist as $k=>$v)
						{
							$answer=$v['value'];
							$chkp_idArray=explode("_",$v['Chkp_Id']);

							if(count($chkp_idArray) > 1){
								$chkp_id = $chkp_idArray[1];
								if($chkp_id == 4726 || $chkp_id == 4717 || $chkp_id == 4929 || $chkp_id == 5719 || $chkp_id == 5753){
									$siteName = $answer;
								}
							}
							else{
								$chkp_id = $chkp_idArray[0];
								if($chkp_id == 4726 || $chkp_id == 4717 || $chkp_id == 4929 || $chkp_id == 5719 || $chkp_id == 5753){
									$siteName = $answer;
								}
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
								$sql2 = "SELECT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where l.Name = '$siteNamee' and l.Site_Id = '$siteId' and el.Role = '$verifier_Role' and l.Tenent_Id = $tenentId and el.Is_Active = 1";
								$result2 = mysqli_query($conn,$sql2);
								while ($row2 = mysqli_fetch_assoc($result2)) {
									$verifierMobile .= $row2["Emp_Id"].',';
								}
								$verifierMobile = substr($verifierMobile, 0, strlen($verifierMobile)-1);
							}

							$approverMobile = "";
							if($approver_Role != null && $approver_Role !=''){
								$sql3 = "SELECT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where l.Name = '$siteNamee' and l.Site_Id = '$siteId' and el.Role = '$approver_Role' and l.Tenent_Id = $tenentId and el.Is_Active = 1";
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

							$updateHdr = "update `TransactionHDR` set `Assign_To` = '$acEmpName' where `SRNo` = $lastTransHdrId ";
							mysqli_query($conn,$updateHdr);
						}

						if($customerId != ""){

							$updateHdr = "update `TransactionHDR` set `Customer_Site_Id` = '$customerId' where `SRNo` = $lastTransHdrId ";
							mysqli_query($conn,$updateHdr);

							$updateMapping = "update Mapping set Customer_Site_Id = '$customerId' where MappingId = $mappingId and 
							Tenent_Id = $tenentId ";
							mysqli_query($conn,$updateMapping);

							$updateActivity = "update `Activity` set `Customer_Site_Id` = '$customerId' where `ActivityId` = $lastTransHdrId ";
							mysqli_query($conn,$updateActivity);
						}

						if($lat != '' && $long !=''){
							$updateHdr = "update `TransactionHDR` set `Nominal_Latlong` = '$lat/$long' where `SRNo` = $lastTransHdrId ";
							mysqli_query($conn,$updateHdr);
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
							if($chkp_id == 5197){
								$isFinallySubmit = $answer;
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
				}

				if($mId == 277){

					$isAnyOutage = ""; 
					$siteId = ""; 
					$siteName = ""; 
					$siteType = ""; $opcoAffected = ""; $outageStartDate = ""; $outageStartTime = ""; $outageEndDate = ""; $outageEndTime = "";
					foreach($checklist as $k=>$v)
					{
						$answer=$v['value'];
						$chkp_idArray=explode("_",$v['Chkp_Id']);

						if(count($chkp_idArray) > 1){
							$chkp_id = $chkp_idArray[1];
							if($chkp_id == 4726){
								$explodeSiteName = explode(" --- ", $answer);
								$siteId = $explodeSiteName[1];
								$siteName = $explodeSiteName[0];
							}
							else if($chkp_id == 5058){
								$siteType = $answer;
							}
							else if($chkp_id == 4732){
								$opcoAffected = $answer;
							}
							else if($chkp_id == 4733 || $chkp_id == 5139){
								$outageStartDate = $answer;
							}
							else if($chkp_id == 4765){
								$outageStartTime = $answer;
							}
							else if($chkp_id == 5057){
								$outageEndDate = $answer;
							}
							else if($chkp_id == 4766){
								$outageEndTime = $answer;
							}
						}
						else{
							$chkp_id = $chkp_idArray[0];
							if($chkp_id == 4781){
								$isAnyOutage = $answer;
							}
						}	
					}
					$explodeStartDate = explode("/", $outageStartDate);
					$dd = str_pad($explodeStartDate[0], 2, '0', STR_PAD_LEFT);
					$mm = str_pad($explodeStartDate[1], 2, '0', STR_PAD_LEFT);
					$yy = $explodeStartDate[2];
					$outageStartDateNew = $yy.'-'.$mm.'-'.$dd;
					$period =  date("M-Y", strtotime($outageStartDateNew));


					if($isAnyOutage == "Yes"){
						$dualSql = "select (case when TIMESTAMPDIFF(Minute,t1.outage_start_datetime,t1.outage_end_datetime) is null then 0 else TIMESTAMPDIFF(Minute,t1.outage_start_datetime,t1.outage_end_datetime) end) as outage_minute, (case when find_in_set('Airtel','$opcoAffected') <> 0 then 'Airtel' else '' end) as `Airtel`, (case when find_in_set('BSNL','$opcoAffected') <> 0 then 'BSNL' else '' end) as `BSNL`, (case when find_in_set('VIL','$opcoAffected') <> 0 then 'VIL' else '' end) as `VIL`, (case when find_in_set('RJIO','$opcoAffected') <> 0 then 'RJIO' else '' end) as `RJIO` from (select concat(t.outage_start_date,' ',t.outage_start_time) outage_start_datetime, concat(t.outage_end_date,' ',t.outage_end_time) outage_end_datetime from (SELECT date_format(STR_TO_DATE('$outageStartDate','%d/%m/%Y'),'%Y-%m-%d') as `outage_start_date`, STR_TO_DATE('$outageStartTime','%h:%i %p') as `outage_start_time`, date_format(STR_TO_DATE('$outageEndDate','%d/%m/%Y'),'%Y-%m-%d') as `outage_end_date`, STR_TO_DATE('$outageEndTime','%h:%i %p') as `outage_end_time` FROM DUAL) t ) t1 ";
						// echo $dualSql;
						$qualQuery=mysqli_query($conn,$dualSql);
						$dualRow = mysqli_fetch_assoc($qualQuery);
						$outage_minute = $dualRow["outage_minute"];
						$airtel = $dualRow["Airtel"];
						$bsnl = $dualRow["BSNL"];
						$vil = $dualRow["VIL"];
						$rjio = $dualRow["RJIO"];
						$app = "";
						if($airtel != '') $app .= ", `Airtel` = '$airtel'";
						if($bsnl != '') $app .= ", `MTNL/BSNL` = '$bsnl'";
						if($vil != '') $app .= ", `VIL` = '$vil'";
						if($rjio != '') $app .= ", `RJIO` = '$rjio'";

						$uptageSql = "SELECT `Day$dd` FROM `Outage_Uptime` where `Site Id` = '$siteId' and `Period` = '$period' ";
						$outageQuery=mysqli_query($conn,$uptageSql);
						$outageRow = mysqli_fetch_assoc($outageQuery);
						$preOutage = $outageRow["Day$dd"];
						if($preOutage == null || $preOutage == '') $preOutage = 0;
						$outage_minute = ($outage_minute + $preOutage);

						$updateSql = "UPDATE `Outage_Uptime` set `Day$dd` = $outage_minute, `Site Type` = '$siteType'".$app." where `Site Id` in ('".$siteId."') and `Period` = '$period' ";
						// echo $updateSql;
						mysqli_query($conn,$updateSql);

						// --- update 0 rest of $siteId site id. ----Start----
						$empSiteList = [];
						$empSiteSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$empId' and el.Is_Active = 1 ";
						$empLocQuery=mysqli_query($conn,$empSiteSql);
						while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
							array_push($empSiteList,$empLocRow["Site_Id"]);
						}
						$el = implode("','", $empSiteList);
						$updateOutageSql = "UPDATE `Outage_Uptime` set `Day$dd` = 0 where `Site Id` in ('".$el."') and `Day$dd` is null and `Period` = '$period' ";
						mysqli_query($conn,$updateOutageSql);
						// --- update 0 rest of $siteId site id. ----End----
					}
					else if($isAnyOutage == "No"){
						$empSiteList = [];
						$empSiteSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$empId' and el.Is_Active = 1 ";
						$empLocQuery=mysqli_query($conn,$empSiteSql);
						while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
							array_push($empSiteList,$empLocRow["Site_Id"]);
						}
						$el = implode("','", $empSiteList);
						$updateSql = "UPDATE `Outage_Uptime` set `Day$dd` = 0 where `Site Id` in ('".$el."') and `Period` = '$period' ";
						mysqli_query($conn,$updateSql);
					}
				}

				if($mId == 283 || $mId == 285 || $mId == 287 || $mId == 313 || $mId == 314){
					$trSql = "SELECT `CheckpointId`, `Question_Type`, `Passing_Correct_Question` FROM `Menu` where `MenuId` = $mId ";
					$trQuery = mysqli_query($conn,$trSql);
					$trRow = mysqli_fetch_assoc($trQuery);
					$trCheckpoint = $trRow["CheckpointId"];
					$explodeTrCp = explode(":", $trCheckpoint);
					$quesTy = $trRow["Question_Type"];
					$explodeQuesTy = explode(":", $quesTy);
					$pre = $explodeQuesTy[0];
					// $media = $explodeTrCp[1];
					$post = $explodeQuesTy[2];
					$pCurrQues = $trRow["Passing_Correct_Question"];
					$explodePassCurrQues = explode(":", $pCurrQues);
					if($pre == "Y"){
						$prePassQues = $explodePassCurrQues[0];
						$preCpId = $explodeTrCp[0];
						$preMarksSql = "select sum(t.Marks) as `PreMarks` from (SELECT d.Value, c.Correct, (case when d.Value = c.Correct then 1 else 0 end) Marks FROM TransactionDTL d join Checkpoints c on d.ChkId = c.CheckpointId where ChkId in (".$preCpId.") and ActivityId = $activityId) t ";
						$preMarksQuery = mysqli_query($conn,$preMarksSql);
						$preMarksRow = mysqli_fetch_assoc($preMarksQuery);
						$preResult = "Fail";
						$preMarks = $preMarksRow["PreMarks"];
						if($preMarks >= $prePassQues){
							$preResult = "Pass";
						}
						$preQuesCount = count(explode(",", $preCpId));
						$prePercent = ($preMarks / $preQuesCount) * 100;
						$prePercent = round($prePercent);

						$preTrainingMarks = "update `TransactionHDR` set `Pre_Ques_Correct_Count` = $preMarks, `Pre_Percentage` = $prePercent, `Pre_Result` = '$preResult' 
						where `ActivityId` = $activityId ";
						mysqli_query($conn,$preTrainingMarks);

					}
					if($post == "Y"){
						$postPassQues = $explodePassCurrQues[1];
						$postCpId = "";
						if(count($explodeTrCp) < 2){
							$postCpId = $explodeTrCp[0];
						}
						else{
							$postQuesIndex = count($explodeTrCp)-1;
							$postCpId = $explodeTrCp[$postQuesIndex];
						}
						$postMarksSql = "select sum(t.Marks) as `PostMarks` from (SELECT d.Value, c.Correct, (case when d.Value = c.Correct then 1 else 0 end) Marks FROM TransactionDTL d join Checkpoints c on d.ChkId = c.CheckpointId where ChkId in (".$postCpId.") and ActivityId = $activityId) t ";
						$postMarksQuery = mysqli_query($conn,$postMarksSql);
						$postMarksRow = mysqli_fetch_assoc($postMarksQuery);
						$postResult = "Fail";
						$postMarks = $postMarksRow["PostMarks"];
						if($postMarks >= $postPassQues){
							$postMarks = "Pass";
						}
						$postQuesCount = count(explode(",", $postCpId));
						$postPercent = ($postMarks / $postQuesCount) * 100;
						$postPercent = round($postPercent);

						$postTrainingMarks = "update `TransactionHDR` set `Post_Ques_Correct_Count` = $postMarks, `Post_Percentage` = $postPercent, `Post_Result` = '$postResult' 
						where `ActivityId` = $activityId ";
						mysqli_query($conn,$postTrainingMarks);
					}
				}
					
			}
			//Change in Mapping table from now onwards
			if($assignId != ""){
				$updateAssignTaskSql = "Update Mapping set ActivityId = '$activityId' where MappingId = $assignId";
				mysqli_query($conn,$updateAssignTaskSql);

				$ss = "SELECT l.Name, l.Site_Id FROM Mapping m join Location l on m.LocationId = l.LocationId where MappingId = $assignId";
				$qq = mysqli_query($conn,$ss);
				$rr = mysqli_fetch_assoc($qq);
				$sN = $rr["Name"];
				$sI = $rr["Site_Id"];

				$updateHdr1 = "update TransactionHDR set Site_Id = '$sI', Site_Name = '$sN' where ActivityId = $activityId ";
				mysqli_query($conn,$updateHdr1);

			}
			if($actId != null && $actId != ''){
				$selectTransHdrSql = "Select * from TransactionHDR  where ActivityId = $actId";
				$selectTransHdrResult = mysqli_query($conn,$selectTransHdrSql);
				$thRow=mysqli_fetch_array($selectTransHdrResult);
				if($thRow['Status'] == 'Created'){
					if($isFinallySubmit != 'No'){
						$updateTransHdrSql = "Update TransactionHDR set Status = 'Verified', VerifierActivityId = '$activityId', Verify_Final_Submit = '$isFinallySubmit' where ActivityId = $actId";
					}
					else{
						$updateTransHdrSql = "Update TransactionHDR set VerifierActivityId = '$activityId', Verify_Final_Submit = '$isFinallySubmit' 
						where ActivityId = $actId";
					}
				}
				else if($thRow['Status'] == 'Verified'){
					if($isFinallySubmit != 'No'){
						$updateTransHdrSql = "Update TransactionHDR set Status = 'Approved',ApproverActivityId = '$activityId' where ActivityId = $actId";
					}
				}
				
				mysqli_query($conn,$updateTransHdrSql);
			}
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
		
		$isFinallySubmit = "";
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
							if($chkp_id == 4726 || $chkp_id == 4717 || $chkp_id == 4929 || $chkp_id == 5719 || $chkp_id == 5753){
								$siteName = $answer;
							}
						}
						else{
							$chkp_id = $chkp_idArray[0];
							if($chkp_id == 4726 || $chkp_id == 4717 || $chkp_id == 4929 || $chkp_id == 5719 || $chkp_id == 5753){
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
							$sql2 = "SELECT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where l.Name = '$siteNamee' and l.Site_Id = '$siteId' and el.Role = '$verifier_Role' and l.Tenent_Id = $tenentId and el.Is_Active = 1";
							$result2 = mysqli_query($conn,$sql2);
							while ($row2 = mysqli_fetch_assoc($result2)) {
								$verifierMobile .= $row2["Emp_Id"].',';
							}
							$verifierMobile = substr($verifierMobile, 0, strlen($verifierMobile)-1);
						}

						$approverMobile = "";
						if($approver_Role != null && $approver_Role !=''){
							$sql3 = "SELECT el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId where l.Name = '$siteNamee' and l.Site_Id = '$siteId' and el.Role = '$approver_Role' and l.Tenent_Id = $tenentId and el.Is_Active = 1";
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
				foreach($checklist as $k=>$v)
				{
					$answer=$v['value'];
					$chkp_idArray=explode("_",$v['Chkp_Id']);

					if(count($chkp_idArray) > 1){
						$chkp_id = $chkp_idArray[1];
					}
					else{
						$chkp_id = $chkp_idArray[0];
						if($chkp_id == 5197){
							$isFinallySubmit = $answer;
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
			}

			if($mId == 277){

				$isAnyOutage = ""; 
				$siteId = ""; 
				$siteName = ""; 
				$siteType = ""; $opcoAffected = ""; $outageStartDate = ""; $outageStartTime = ""; $outageEndDate = ""; $outageEndTime = "";
				foreach($checklist as $k=>$v)
				{
					$answer=$v['value'];
					$chkp_idArray=explode("_",$v['Chkp_Id']);

					if(count($chkp_idArray) > 1){
						$chkp_id = $chkp_idArray[1];
						if($chkp_id == 4726){
							$explodeSiteName = explode(" --- ", $answer);
							$siteId = $explodeSiteName[1];
							$siteName = $explodeSiteName[0];
						}
						else if($chkp_id == 5058){
							$siteType = $answer;
						}
						else if($chkp_id == 4732){
							$opcoAffected = $answer;
						}
						else if($chkp_id == 4733 || $chkp_id == 5139){
							$outageStartDate = $answer;
						}
						else if($chkp_id == 4765){
							$outageStartTime = $answer;
						}
						else if($chkp_id == 5057){
							$outageEndDate = $answer;
						}
						else if($chkp_id == 4766){
							$outageEndTime = $answer;
						}
					}
					else{
						$chkp_id = $chkp_idArray[0];
						if($chkp_id == 4781){
							$isAnyOutage = $answer;
						}
					}	
				}
				$explodeStartDate = explode("/", $outageStartDate);
				$dd = str_pad($explodeStartDate[0], 2, '0', STR_PAD_LEFT);
				$mm = str_pad($explodeStartDate[1], 2, '0', STR_PAD_LEFT);
				$yy = $explodeStartDate[2];
				$outageStartDateNew = $yy.'-'.$mm.'-'.$dd;
				$period =  date("M-Y", strtotime($outageStartDateNew));


				if($isAnyOutage == "Yes"){
					$dualSql = "select (case when TIMESTAMPDIFF(Minute,t1.outage_start_datetime,t1.outage_end_datetime) is null then 0 else TIMESTAMPDIFF(Minute,t1.outage_start_datetime,t1.outage_end_datetime) end) as outage_minute, (case when find_in_set('Airtel','$opcoAffected') <> 0 then 'Airtel' else '' end) as `Airtel`, (case when find_in_set('BSNL','$opcoAffected') <> 0 then 'BSNL' else '' end) as `BSNL`, (case when find_in_set('VIL','$opcoAffected') <> 0 then 'VIL' else '' end) as `VIL`, (case when find_in_set('RJIO','$opcoAffected') <> 0 then 'RJIO' else '' end) as `RJIO` from (select concat(t.outage_start_date,' ',t.outage_start_time) outage_start_datetime, concat(t.outage_end_date,' ',t.outage_end_time) outage_end_datetime from (SELECT date_format(STR_TO_DATE('$outageStartDate','%d/%m/%Y'),'%Y-%m-%d') as `outage_start_date`, STR_TO_DATE('$outageStartTime','%h:%i %p') as `outage_start_time`, date_format(STR_TO_DATE('$outageEndDate','%d/%m/%Y'),'%Y-%m-%d') as `outage_end_date`, STR_TO_DATE('$outageEndTime','%h:%i %p') as `outage_end_time` FROM DUAL) t ) t1 ";
					// echo $dualSql;
					$qualQuery=mysqli_query($conn,$dualSql);
					$dualRow = mysqli_fetch_assoc($qualQuery);
					$outage_minute = $dualRow["outage_minute"];
					$airtel = $dualRow["Airtel"];
					$bsnl = $dualRow["BSNL"];
					$vil = $dualRow["VIL"];
					$rjio = $dualRow["RJIO"];
					$app = "";
					if($airtel != '') $app .= ", `Airtel` = '$airtel'";
					if($bsnl != '') $app .= ", `MTNL/BSNL` = '$bsnl'";
					if($vil != '') $app .= ", `VIL` = '$vil'";
					if($rjio != '') $app .= ", `RJIO` = '$rjio'";

					$uptageSql = "SELECT `Day$dd` FROM `Outage_Uptime` where `Site Id` = '$siteId' and `Period` = '$period' ";
					$outageQuery=mysqli_query($conn,$uptageSql);
					$outageRow = mysqli_fetch_assoc($outageQuery);
					$preOutage = $outageRow["Day$dd"];
					if($preOutage == null || $preOutage == '') $preOutage = 0;
					$outage_minute = ($outage_minute + $preOutage);

					$updateSql = "UPDATE `Outage_Uptime` set `Day$dd` = $outage_minute, `Site Type` = '$siteType'".$app." where `Site Id` in ('".$siteId."') and `Period` = '$period' ";
					// echo $updateSql;
					mysqli_query($conn,$updateSql);

					// --- update 0 rest of $siteId site id. ----Start----
					$empSiteList = [];
					$empSiteSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$empId' and el.Is_Active = 1 ";
					$empLocQuery=mysqli_query($conn,$empSiteSql);
					while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
						array_push($empSiteList,$empLocRow["Site_Id"]);
					}
					$el = implode("','", $empSiteList);
					$updateOutageSql = "UPDATE `Outage_Uptime` set `Day$dd` = 0 where `Site Id` in ('".$el."') and `Day$dd` is null and `Period` = '$period' ";
					mysqli_query($conn,$updateOutageSql);
					// --- update 0 rest of $siteId site id. ----End----
				}
				else if($isAnyOutage == "No"){
					$empSiteList = [];
					$empSiteSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$empId' and el.Is_Active = 1 ";
					$empLocQuery=mysqli_query($conn,$empSiteSql);
					while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
						array_push($empSiteList,$empLocRow["Site_Id"]);
					}
					$el = implode("','", $empSiteList);
					$updateSql = "UPDATE `Outage_Uptime` set `Day$dd` = 0 where `Site Id` in ('".$el."') and `Period` = '$period' ";
					mysqli_query($conn,$updateSql);
				}
			}
				
		}
		//Change in Mapping table from now onwards
		if($assignId != ""){
			$updateAssignTaskSql = "Update Mapping set ActivityId = '$activityId' where MappingId = $assignId";
			mysqli_query($conn,$updateAssignTaskSql);

		}
		if($actId != null && $actId != ''){
			$selectTransHdrSql = "Select * from TransactionHDR  where ActivityId = $actId";
			$selectTransHdrResult = mysqli_query($conn,$selectTransHdrSql);
			$thRow=mysqli_fetch_array($selectTransHdrResult);
			if($thRow['Status'] == 'Created'){
				if($isFinallySubmit != 'No'){
					$updateTransHdrSql = "Update TransactionHDR set Status = 'Verified', VerifierActivityId = '$activityId', Verify_Final_Submit = '$isFinallySubmit' where ActivityId = $actId";
				}
				else{
					$updateTransHdrSql = "Update TransactionHDR set VerifierActivityId = '$activityId', Verify_Final_Submit = '$isFinallySubmit' 
					where ActivityId = $actId";
				}
			}
			else if($thRow['Status'] == 'Verified'){
				if($isFinallySubmit != 'No'){
					$updateTransHdrSql = "Update TransactionHDR set Status = 'Approved',ApproverActivityId = '$activityId' where ActivityId = $actId";
				}
			}
			
			mysqli_query($conn,$updateTransHdrSql);
		}
	}


	
	$output = new StdClass;
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

	if($configLogStatus == 1){
		$logResSql = "INSERT INTO `Save_Logs`(`Api_Name`, `Emp_Id`, `Data_Type`, `Data_Json`, `Mobile_Datetime`, `Server_Datetime`) VALUES ('saveCheckpoint.php', '$empId', 'Response', '".json_encode($output)."', '$mobiledatetime', current_timestamp)";
	 	mysqli_query($conn,$logResSql);
	}
		
	
}



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