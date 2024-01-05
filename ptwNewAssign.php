<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");

$geofenceSql = "SELECT `Geofence` FROM `configuration`";
$geofenceQuery=mysqli_query($conn,$geofenceSql);
$geofenceRow = mysqli_fetch_assoc($geofenceQuery);
$configGeoFence = $geofenceRow["Geofence"];


$empId=$_REQUEST['empId'];
$wrappedListArray = array();
	
$assignSql = "SELECT mp.MenuId,mp.LocationId,mp.Start,mp.End,mp.MappingId,l.Name,l.Site_Id,l.GeoCoordinates,
		m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId, m.GeoFence, mp.Customer_Site_Id
		FROM Mapping mp 
		left join Menu m  on (mp.MenuId = m.MenuId)
		left join Location l on (mp.LocationId = l.LocationId)
		WHERE mp.EmpId = '$empId' and mp.MenuId in (303,304,305,306,307,308,309,310) AND date(mp.Start) <= date(now()) AND date(mp.End) >= date(now())
		AND mp.ActivityId = 0 AND mp.Active = 1 ";
		
$assignQuery=mysqli_query($conn,$assignSql);
while($row = mysqli_fetch_assoc($assignQuery)){
	$menuGeoFence = $row["GeoFence"];
	$menuGeoFenceExplode = explode(":", $menuGeoFence);
	$menuLocationId = $menuGeoFenceExplode[0];
	$menuLocationDistance = $menuGeoFenceExplode[1];
	$menuLocationIdExplode = explode(",", $menuLocationId);
	$locationId = $row["LocationId"];
	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

	$assignObj = "";
	$assignObj->menuId = $row["MenuId"];
	$assignObj->locationId = $row["LocationId"];
	$assignObj->assignId = $row["MappingId"];
	$ptw = "";
	$status = "";
	if($row["MenuId"] == 303 || $row["MenuId"] == 304 || $row["MenuId"] == 305 || $row["MenuId"] == 306 || $row["MenuId"] == 307 || $row["MenuId"] == 308 || $row["MenuId"] == 309 || $row["MenuId"] == 310){
		$assignObj->name = $row["Site_Id"];
		$assignObj->startDate = $row["WorkStartDatetime"];
		$assignObj->endDate = $row["WorkEndDatetime"];
		$ptw = "PTW - ";
		$status = "";
	}
	else{
		$assignObj->name = $row["Name"].'-'.$row["Customer_Site_Id"];
		$assignObj->startDate = $row["Start"];
		$assignObj->endDate = $row["End"];
	}
	// $assignObj->latlong = $row["GeoCoordinates"];
	$geoCoordinate = str_replace(",", "/", $row["GeoCoordinates"]);
	$assignObj->latlong = $geoCoordinate;
	if($locationIdIndex >= 0 && ($menuLocationDistance != null && trim($menuLocationDistance,' ') != '')){
		$assignObj->GeoFence = $menuLocationDistance;
	}else{
		$assignObj->GeoFence = $configGeoFence;
	}
	
	$assignObj->activityId = '';
	$iconArr = explode(",",$row['Icons']);
	if($row['Caption'] != ''){
			$assignObj->Caption = $row['Caption'];
			$assignObj->Icon = $iconArr[2];
	}
	else if($row['Sub'] != ''){
		$assignObj->Caption = $ptw.$row['Sub'].$status;
		$assignObj->Icon = $iconArr[1];
	}
	else{
		$assignObj->Caption = $row['Cat'];
		$assignObj->Icon = $iconArr[0];
	}
	$isDataSend = "";
	$cpIdArray = explode(":",$row['CheckpointId']);
	for($cpId = 0; $cpId < count($cpIdArray); $cpId++){
		if($cpId == 0){
				$isDataSend .= "1";
		}
		else{
			$isDataSend .= ":1";
		}
		
	}
	$assignObj->isDataSend = $isDataSend;
	$assignObj->checkpointId = $row['CheckpointId'];
	$acpString = $assignObj->checkpointId;
	$acpString = str_replace(":",",",$acpString);
	$acpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($acpString)";
	$acpQuery=mysqli_query($conn,$acpSql);
	//$acpArray = array();
	//$assignObj->value = array();
	array_push($wrappedListArray,$assignObj);
}

		
$verifierSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.Site_Id,l.GeoCoordinates,m.Verifier,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId, m.GeoFence,mp.Customer_Site_Id, h.VerifierActivityId,h.Verify_Final_Submit,h.Nominal_Latlong,h.WorkStartDatetime,h.WorkEndDatetime
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				left join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and mp.MenuId in (303,304,305,306,307,308,309,310) and find_in_set('$empId',mp.Verifier) <> 0 and h.Status in ('Created','PTW_01')
				and (h.Verify_Final_Submit is null or h.Verify_Final_Submit = 'No') AND mp.Active = 1";				

$verifierQuery=mysqli_query($conn,$verifierSql);

while($v = mysqli_fetch_assoc($verifierQuery)){
	$menuGeoFence = $v["GeoFence"];
	$menuGeoFenceExplode = explode(":", $menuGeoFence);
	$menuLocationId = $menuGeoFenceExplode[0];
	$menuLocationDistance = $menuGeoFenceExplode[1];
	$menuLocationIdExplode = explode(",", $menuLocationId);
	$locationId = $v["LocationId"];
	$verifyFinalSubmit = $v["Verify_Final_Submit"] == null ? "NA" : $v["Verify_Final_Submit"];
	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

	$vObj = "";
	$vObj->menuId = $v["MenuId"];
	$vObj->locationId = $v["LocationId"];
	$vObj->assignId = "";
	$ptw = "";
	$status = "";
	if($v["MenuId"] == 279){
		$vObj->name = $v["Customer_Site_Id"];
	}
	else if($v["MenuId"] == 303 || $v["MenuId"] == 304 || $v["MenuId"] == 305 || $v["MenuId"] == 306 || $v["MenuId"] == 307 || $v["MenuId"] == 308 || $v["MenuId"] == 309 || $v["MenuId"] == 310){
		$vObj->name = $v["Site_Id"];
		$vObj->startDate = $v["WorkStartDatetime"];
		$vObj->endDate = $v["WorkEndDatetime"];
		$ptw = "PTW - ";
		$status = " (Approval)";
	}
	else{
		$vObj->name = $v["Name"];
		$vObj->startDate = $v["Start"];
		$vObj->endDate = $v["End"];
	}
	// if($v["MenuId"] != 279){
	// 	$vObj->name = $v["Name"];
	// }else{
	// 	$vObj->name = $v["Customer_Site_Id"];
	// }
	if($v["MenuId"] != 279){
		// $vObj->latlong = $v["GeoCoordinates"];
		$geoCoordinate = str_replace(",", "/", $v["GeoCoordinates"]);
		$vObj->latlong = $geoCoordinate;
	}
	else{
		$vObj->latlong = $v["Nominal_Latlong"];
	}
	if($locationIdIndex >= 0 && ($menuLocationDistance != null && trim($menuLocationDistance,' ') != '')){
		$vObj->GeoFence = $menuLocationDistance;
	}else{
		$vObj->GeoFence = $configGeoFence;
	}
	$vObj->activityId = $v["ActivityId"];
	$vObj->verifierActivityId = $v["VerifierActivityId"];
	$iconArr = explode(",",$v['Icons']);
	if($v['Caption'] != ''){
			$vObj->Caption = $v['Caption'];
			$vObj->Icon = $iconArr[2];
	}
	else if($v['Sub'] != ''){
		$vObj->Caption = $ptw.$v['Sub'].$status;
		$vObj->Icon = $iconArr[1];
	}
	else{
		$vObj->Caption = $v['Cat'];
		$vObj->Icon = $iconArr[0];
	}
	
	$vObj->checkpointId = $v['CheckpointId'].':'.$v['Verifier'];
	
	$visDataSend = "";
	$vcpIdArray = explode(":",$vObj->checkpointId);
	for($vcpId = 0; $vcpId < count($vcpIdArray); $vcpId++){
		if($vcpId == 0){
			$visDataSend .= "0";
		}
		else if($vcpId == count($vcpIdArray)-1){
			$visDataSend .= ":1";
		}
		else{
			$visDataSend .= ":0";
		}	
	}
	$vObj->isDataSend = $visDataSend;
	
	$cpArray = array();
	$filledCpString = str_replace(":",",",$v['CheckpointId']);
	$verifierCpString = str_replace(":",",",$v['Verifier']);
	//$verifierCpString = $v['Verifier'];
	$filledcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$v['ActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($filledCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	//echo $filledcpSql;
	$filledcpQuery=mysqli_query($conn,$filledcpSql);
	while($fcp = mysqli_fetch_assoc($filledcpQuery)){
		$fcpObj = "";
		$fcpObj->Chkp_Id = $fcp['CheckpointId'];
		$fcpObj->editable = '0';
		if($fcp['answer'] != null){
			$fcpObj->value = $fcp['answer'];
		}
		else{
			$fcpObj->value = "";
		}
		
		$fdpArray = array();
		if($fcp['Dependent'] == "1"){
			$fdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$v['ActivityId']."' and d.DependChkId = (".$fcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$fdpQuery = mysqli_query($conn,$fdpSql);
			while($fdp = mysqli_fetch_assoc($fdpQuery)){
				$fdpObj = "";
				$fdpObj->Chkp_Id = $fcp['CheckpointId']."_".$fdp['CheckpointId'];
				$fdpObj->editable = '0';
				$fdpObj->value = $fdp['answer'];
				array_push($fdpArray,$fdpObj);
			}
		}
		$fcpObj->Dependents = $fdpArray;
		array_push($cpArray,$fcpObj);
	} 

	if($verifyFinalSubmit == "No"){
		$filledcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$v['VerifierActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($verifierCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
		//echo $filledcpSql;
		$filledcpQuery=mysqli_query($conn,$filledcpSql);
		while($fcp = mysqli_fetch_assoc($filledcpQuery)){
			$fcpObj = "";
			$fcpObj->Chkp_Id = $fcp['CheckpointId'];
			$fcpObj->editable = '1';
			if($fcp['answer'] != null){
				$fcpObj->value = $fcp['answer'];
			}
			else{
				$fcpObj->value = "";
			}
			
			$fdpArray = array();
			if($fcp['Dependent'] == "1"){
				$fdpSql = " Select r1.*,c.* from
								(Select d.ChkId,d.Value as answer from TransactionDTL d
								where d.ActivityId = '".$v['VerifierActivityId']."' and d.DependChkId = (".$fcp['CheckpointId'].")
								) r1
								join Checkpoints c on (r1.ChkId = c.CheckpointId)";
								
				$fdpQuery = mysqli_query($conn,$fdpSql);
				while($fdp = mysqli_fetch_assoc($fdpQuery)){
					$fdpObj = "";
					$fdpObj->Chkp_Id = $fcp['CheckpointId']."_".$fdp['CheckpointId'];
					$fdpObj->editable = '1';
					$fdpObj->value = $fdp['answer'];
					array_push($fdpArray,$fdpObj);
				}
			}
			$fcpObj->Dependents = $fdpArray;
			array_push($cpArray,$fcpObj);
		}
	}

	if($verifyFinalSubmit == "NA")
	{
		$verifiercpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($verifierCpString)";
		//echo $verifiercpSql;
		$verifiercpQuery=mysqli_query($conn,$verifiercpSql);
		while($vcp = mysqli_fetch_assoc($verifiercpQuery)){
			$vcpObj = "";
			$vcpObj->Chkp_Id = $vcp['CheckpointId'];
			$vcpObj->editable = $vcp['Editable'];
			$vcpObj->value = "";
			$vdpArray = array();
			if($vcp['Dependent'] == "1"){
				$vcplogicArray = explode(":",trim($vcp['Logic']," "));
				$vcplogicString = "";
				for($vcpl=0;$vcpl< count($vcplogicArray);$vcpl++){
					if($vcpl == 0  && $vcplogicArray[$vcpl] != null && $vcplogicArray[$vcpl] != ""){
						$vcplogicString .= $vcplogicArray[$vcpl];
					}
					else if($vcplogicArray[$vcpl] != null && $vcplogicArray[$vcpl] != ""){
						$vcplogicString .= ",".$vcplogicArray[$vcpl];
					}
					
				}
				$vdpSql = " Select c.* from Checkpoints c where c.CheckpointId in ($vcplogicString)";
								
				$vdpQuery = mysqli_query($conn,$vdpSql);
				while($vdp = mysqli_fetch_assoc($vdpQuery)){
					$vdpObj = "";
					$vdpObj->Chkp_Id = $vdp['CheckpointId'];
					$vdpObj->editable = $vdp['Editable'];
					$vdpObj->value = "";
					array_push($vdpArray,$vdpObj);
				}
			}
			$vcpObj->Dependents = $vdpArray;
			array_push($cpArray,$vcpObj);
		} 
	}
	
	$vObj->value = $cpArray;
	array_push($wrappedListArray,$vObj);
}

$approverSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.Site_Id,l.GeoCoordinates,m.Verifier,m.Approver,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId,m.GeoFence,h.WorkStartDatetime,h.WorkEndDatetime
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				left join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and mp.MenuId in (303,304,305,306,307,308,309,310) and mp.Approver = '$empId' and h.Status in ('Verified','PTW_02')
				and h.ApproverActivityId is null";				

$approverQuery=mysqli_query($conn,$approverSql);
while($ap = mysqli_fetch_assoc($approverQuery)){
	$menuGeoFence = $ap["GeoFence"];
	$menuGeoFenceExplode = explode(":", $menuGeoFence);
	$menuLocationId = $menuGeoFenceExplode[0];
	$menuLocationDistance = $menuGeoFenceExplode[1];
	$menuLocationIdExplode = explode(",", $menuLocationId);
	$locationId = $ap["LocationId"];
	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

	$apObj = "";
	$apObj->menuId = $ap["MenuId"];
	$apObj->locationId = $ap["LocationId"];
	$apObj->assignId = "";
	$ptw = "";
	$status = "";
	if($ap["MenuId"] == 303 || $ap["MenuId"] == 304 || $ap["MenuId"] == 305 || $ap["MenuId"] == 306 || $ap["MenuId"] == 307 || $ap["MenuId"] == 308 || $ap["MenuId"] == 309 || $ap["MenuId"] == 310){
		$apObj->name = $ap["Site_Id"];
		$apObj->startDate = $ap["WorkStartDatetime"];
		$apObj->endDate = $ap["WorkEndDatetime"];
		$ptw = "PTW - ";
		$status = " (Start)";
	}
	else{
		$apObj->name = $ap["Name"];
		$apObj->startDate = $ap["Start"];
		$apObj->endDate = $ap["End"];
	}
	// $apObj->latlong = $ap["GeoCoordinates"];
	$geoCoordinate = str_replace(",", "/", $ap["GeoCoordinates"]);
	$apObj->latlong = $geoCoordinate;
	if($locationIdIndex >= 0 && ($menuLocationDistance != null && trim($menuLocationDistance,' ') != '')){
		$apObj->GeoFence = $menuLocationDistance;
	}else{
		$apObj->GeoFence = $configGeoFence;
	}
	$apObj->activityId = $ap["ActivityId"];
	$iconArr = explode(",",$ap['Icons']);
	if($ap['Caption'] != ''){
			$apObj->Caption = $ap['Caption'];
			$apObj->Icon = $iconArr[2];
	}
	else if($ap['Sub'] != ''){
		$apObj->Caption = $ptw.$ap['Sub'].$status;
		$apObj->Icon = $iconArr[1];
	}
	else{
		$apObj->Caption = $ap['Cat'];
		$apObj->Icon = $iconArr[0];
	}
	
	$apObj->checkpointId = $ap['CheckpointId'].":".$ap['Verifier'].":".$ap['Approver'];
	
	$apisDataSend = "";
	$apcpIdArray = explode(":",$apObj->checkpointId);
	for($apcpId = 0; $apcpId < count($apcpIdArray); $apcpId++){
		if($apcpId == 0){
			$apisDataSend .= "0";
		}
		else if($apcpId == count($apcpIdArray)-1){
			$apisDataSend .= ":1";
		}
		else{
			$apisDataSend .= ":0";
		}	
	}
	$apObj->isDataSend = $apisDataSend;
	$apcpArray = array();
	$apfilledCpString = str_replace(":",",",$ap['CheckpointId']);
	$apverifierCpString = str_replace(":",",",$ap['Verifier']);
	$apapproverCpString = str_replace(":",",",$ap['Approver']);

	$apfilledcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$ap['ActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($apfilledCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	//echo $apfilledcpSql;
	$apfilledcpQuery=mysqli_query($conn,$apfilledcpSql);
	while($apfcp = mysqli_fetch_assoc($apfilledcpQuery)){
		$apfcpObj = "";
		$apfcpObj->Chkp_Id = $apfcp['CheckpointId'];
		$apfcpObj->editable = '0';
		if($apfcp['answer'] != null){
			$apfcpObj->value = $apfcp['answer'];
		}
		else{
			$apfcpObj->value = "";
		}
		
		$apfdpArray = array();
		if($apfcp['Dependent'] == "1"){
			$apfdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$ap['ActivityId']."' and d.DependChkId = (".$apfcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$apfdpQuery = mysqli_query($conn,$apfdpSql);
			while($apfdp = mysqli_fetch_assoc($apfdpQuery)){
				$apfdpObj = "";
				$apfdpObj->Chkp_Id = $apfcp['CheckpointId']."_".$apfdp['CheckpointId'];
				$apfdpObj->editable = '0';
				$apfdpObj->value = $apfdp['answer'];
				array_push($apfdpArray,$apfdpObj);
			}
		}
		$apfcpObj->Dependents = $apfdpArray;
		array_push($apcpArray,$apfcpObj);
	}
	$apverifiedcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$ap['VerifierActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($apverifierCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	//echo $apverifiedcpSql;
	$apverifiedcpQuery=mysqli_query($conn,$apverifiedcpSql);
	 
	 while($apvcp = mysqli_fetch_assoc($apverifiedcpQuery)){
		$apvcpObj = "";
		$apvcpObj->Chkp_Id = $apvcp['CheckpointId'];
		$apvcpObj->editable = '0';
		if($apvcp['answer'] != null){
			$apvcpObj->value = $apvcp['answer'];
		}
		else{
			$apvcpObj->value = "";
		}
		
		$apvdpArray = array();
		if($apvcp['Dependent'] == "1"){
			$apvdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$ap['VerifierActivityId']."' and d.DependChkId = (".$apvcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$apvdpQuery = mysqli_query($conn,$apvdpSql);
			while($apvdp = mysqli_fetch_assoc($apvdpQuery)){
				$apvdpObj = "";
				$apvdpObj->Chkp_Id = $apvcp['CheckpointId']."_".$apvdp['CheckpointId'];
				$apvdpObj->editable = '0';
				$apvdpObj->value = $apvdp['answer'];
				array_push($apvdpArray,$apvdpObj);
			}
		}
		$apvcpObj->Dependents = $apvdpArray;
		array_push($apcpArray,$apvcpObj);
	}
	
	$apapprovercpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($apapproverCpString)";
	$apapprovercpQuery=mysqli_query($conn,$apapprovercpSql);
	while($apcp = mysqli_fetch_assoc($apapprovercpQuery)){
		$apcpObj = "";
		$apcpObj->Chkp_Id = $apcp['CheckpointId'];
		$apcpObj->editable = $apcp['Editable'];
		$apcpObj->value = "";
		$apdpArray = array();
		if($apcp['Dependent'] == "1"){
			$apcplogicArray = explode(":",trim($apcp['Logic']," "));
			$apcplogicString = "";
			for($apcpl=0;$apcpl< count($apcplogicArray);$apcpl++){
				if($apcpl == 0  && $apcplogicArray[$apcpl] != null && $apcplogicArray[$apcpl] != ""){
					$apcplogicString .= $apcplogicArray[$apcpl];
				}
				else if($apcplogicArray[$apcpl] != null && $apcplogicArray[$apcpl] != ""){
					$apcplogicString .= ",".$apcplogicArray[$apcpl];
				}
				
			}
			$apdpSql = " Select c.* from
							   Checkpoints c where c.CheckpointId in ($apcplogicString)";
							
			$apdpQuery = mysqli_query($conn,$apdpSql);
			while($apdp = mysqli_fetch_assoc($apdpQuery)){
				$apdpObj = "";
				$apdpObj->Chkp_Id = $apdp['CheckpointId'];
				$apdpObj->editable = $apdp['Editable'];
				$apdpObj->value = "";
				array_push($apdpArray,$apdpObj);
			}
		}
		$apcpObj->Dependents = $apdpArray;
		array_push($apcpArray,$apcpObj);
	}

 	
	$apObj->value = $apcpArray;
	array_push($wrappedListArray,$apObj);	

}

$thirdSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.Site_Id,l.GeoCoordinates,m.Verifier,m.Approver,m.Third,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId,h.ApproverActivityId,m.GeoFence,h.WorkStartDatetime,h.WorkEndDatetime
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				left join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and mp.MenuId in (303,304,305,306,307,308,309,310) and mp.Third = '$empId' and h.Status in ('PTW_03')
				and h.ThirdActivityId is null";	

$thirdQuery=mysqli_query($conn,$thirdSql);
while($th = mysqli_fetch_assoc($thirdQuery)){
	$menuGeoFence = $th["GeoFence"];
	$menuGeoFenceExplode = explode(":", $menuGeoFence);
	$menuLocationId = $menuGeoFenceExplode[0];
	$menuLocationDistance = $menuGeoFenceExplode[1];
	$menuLocationIdExplode = explode(",", $menuLocationId);
	$locationId = $th["LocationId"];
	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

	$thObj = "";
	$thObj->menuId = $th["MenuId"];
	$thObj->locationId = $th["LocationId"];
	$thObj->assignId = "";
	$ptw = "";
	$status = "";
	if($th["MenuId"] == 303 || $th["MenuId"] == 304 || $th["MenuId"] == 305 || $th["MenuId"] == 306 || $th["MenuId"] == 307 || $th["MenuId"] == 308 || $th["MenuId"] == 309 || $th["MenuId"] == 310){
		$thObj->name = $th["Site_Id"];
		$thObj->startDate = $th["WorkStartDatetime"];
		$thObj->endDate = $th["WorkEndDatetime"];
		$ptw = "PTW - ";
		$status = " (SA)";
	}
	else{
		$thObj->name = $th["Name"];
		$thObj->startDate = $th["Start"];
		$thObj->endDate = $th["End"];
	}
	// $thObj->latlong = $th["GeoCoordinates"];
	$geoCoordinate = str_replace(",", "/", $th["GeoCoordinates"]);
	$thObj->latlong = $geoCoordinate;
	if($locationIdIndex >= 0 && ($menuLocationDistance != null && trim($menuLocationDistance,' ') != '')){
		$thObj->GeoFence = $menuLocationDistance;
	}else{
		$thObj->GeoFence = $configGeoFence;
	}
	$thObj->activityId = $th["ActivityId"];
	$iconArr = explode(",",$th['Icons']);
	if($th['Caption'] != ''){
			$thObj->Caption = $th['Caption'];
			$thObj->Icon = $iconArr[2];
	}
	else if($th['Sub'] != ''){
		$thObj->Caption = $ptw.$th['Sub'].$status;
		$thObj->Icon = $iconArr[1];
	}
	else{
		$thObj->Caption = $th['Cat'];
		$thObj->Icon = $iconArr[0];
	}

	$thObj->checkpointId = $th['CheckpointId'].":".$th['Verifier'].":".$th['Approver'].":".$th['Third'];

	$thisDataSend = "";
	$thcpIdArray = explode(":",$thObj->checkpointId);
	for($thcpId = 0; $thcpId < count($thcpIdArray); $thcpId++){
		if($thcpId == 0){
			$thisDataSend .= "0";
		}
		else if($thcpId == count($thcpIdArray)-1){
			$thisDataSend .= ":1";
		}
		else{
			$thisDataSend .= ":0";
		}	
	}
	$thObj->isDataSend = $thisDataSend;
	$thcpArray = array();
	$thfilledCpString = str_replace(":",",",$th['CheckpointId']);
	$thverifierCpString = str_replace(":",",",$th['Verifier']);
	$thapproverCpString = str_replace(":",",",$th['Approver']);
	$thirdCpString = str_replace(":",",",$th['Third']);

	$apfilledcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$th['ActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($thfilledCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";

	$apfilledcpQuery=mysqli_query($conn,$apfilledcpSql);
	while($apfcp = mysqli_fetch_assoc($apfilledcpQuery)){
		$apfcpObj = "";
		$apfcpObj->Chkp_Id = $apfcp['CheckpointId'];
		$apfcpObj->editable = '0';
		if($apfcp['answer'] != null){
			$apfcpObj->value = $apfcp['answer'];
		}
		else{
			$apfcpObj->value = "";
		}
		
		$apfdpArray = array();
		if($apfcp['Dependent'] == "1"){
			$apfdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$th['ActivityId']."' and d.DependChkId = (".$apfcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$apfdpQuery = mysqli_query($conn,$apfdpSql);
			while($apfdp = mysqli_fetch_assoc($apfdpQuery)){
				$apfdpObj = "";
				$apfdpObj->Chkp_Id = $apfcp['CheckpointId']."_".$apfdp['CheckpointId'];
				$apfdpObj->editable = '0';
				$apfdpObj->value = $apfdp['answer'];
				array_push($apfdpArray,$apfdpObj);
			}
		}
		$apfcpObj->Dependents = $apfdpArray;
		array_push($thcpArray,$apfcpObj);
	}

	$apverifiedcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$th['VerifierActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($thverifierCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	//echo $apverifiedcpSql;
	$apverifiedcpQuery=mysqli_query($conn,$apverifiedcpSql);
	 
	 while($apvcp = mysqli_fetch_assoc($apverifiedcpQuery)){
		$apvcpObj = "";
		$apvcpObj->Chkp_Id = $apvcp['CheckpointId'];
		$apvcpObj->editable = '0';
		if($apvcp['answer'] != null){
			$apvcpObj->value = $apvcp['answer'];
		}
		else{
			$apvcpObj->value = "";
		}
		
		$apvdpArray = array();
		if($apvcp['Dependent'] == "1"){
			$apvdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$th['VerifierActivityId']."' and d.DependChkId = (".$apvcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$apvdpQuery = mysqli_query($conn,$apvdpSql);
			while($apvdp = mysqli_fetch_assoc($apvdpQuery)){
				$apvdpObj = "";
				$apvdpObj->Chkp_Id = $apvcp['CheckpointId']."_".$apvdp['CheckpointId'];
				$apvdpObj->editable = '0';
				$apvdpObj->value = $apvdp['answer'];
				array_push($apvdpArray,$apvdpObj);
			}
		}
		$apvcpObj->Dependents = $apvdpArray;
		array_push($thcpArray,$apvcpObj);
	}

	$apapprovedcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$th['ApproverActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($thapproverCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	//echo $apverifiedcpSql;
	$apapprovedcpQuery=mysqli_query($conn,$apapprovedcpSql);
	 
	 while($apvcp = mysqli_fetch_assoc($apapprovedcpQuery)){
		$apvcpObj = "";
		$apvcpObj->Chkp_Id = $apvcp['CheckpointId'];
		$apvcpObj->editable = '0';
		if($apvcp['answer'] != null){
			$apvcpObj->value = $apvcp['answer'];
		}
		else{
			$apvcpObj->value = "";
		}
		
		$apvdpArray = array();
		if($apvcp['Dependent'] == "1"){
			$apvdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$th['ApproverActivityId']."' and d.DependChkId = (".$apvcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$apvdpQuery = mysqli_query($conn,$apvdpSql);
			while($apvdp = mysqli_fetch_assoc($apvdpQuery)){
				$apvdpObj = "";
				$apvdpObj->Chkp_Id = $apvcp['CheckpointId']."_".$apvdp['CheckpointId'];
				$apvdpObj->editable = '0';
				$apvdpObj->value = $apvdp['answer'];
				array_push($apvdpArray,$apvdpObj);
			}
		}
		$apvcpObj->Dependents = $apvdpArray;
		array_push($thcpArray,$apvcpObj);
	}

	$apapprovercpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($thirdCpString)";
	$apapprovercpQuery=mysqli_query($conn,$apapprovercpSql);
	while($apcp = mysqli_fetch_assoc($apapprovercpQuery)){
		$apcpObj = "";
		$apcpObj->Chkp_Id = $apcp['CheckpointId'];
		$apcpObj->editable = $apcp['Editable'];
		$apcpObj->value = "";
		$apdpArray = array();
		if($apcp['Dependent'] == "1"){
			$apcplogicArray = explode(":",trim($apcp['Logic']," "));
			$apcplogicString = "";
			for($apcpl=0;$apcpl< count($apcplogicArray);$apcpl++){
				if($apcpl == 0  && $apcplogicArray[$apcpl] != null && $apcplogicArray[$apcpl] != ""){
					$apcplogicString .= $apcplogicArray[$apcpl];
				}
				else if($apcplogicArray[$apcpl] != null && $apcplogicArray[$apcpl] != ""){
					$apcplogicString .= ",".$apcplogicArray[$apcpl];
				}
				
			}
			$apdpSql = " Select c.* from
							   Checkpoints c where c.CheckpointId in ($apcplogicString)";
							
			$apdpQuery = mysqli_query($conn,$apdpSql);
			while($apdp = mysqli_fetch_assoc($apdpQuery)){
				$apdpObj = "";
				$apdpObj->Chkp_Id = $apdp['CheckpointId'];
				$apdpObj->editable = $apdp['Editable'];
				$apdpObj->value = "";
				array_push($apdpArray,$apdpObj);
			}
		}
		$apcpObj->Dependents = $apdpArray;
		array_push($thcpArray,$apcpObj);
	}


	$thObj->value = $thcpArray;
	array_push($wrappedListArray,$thObj);
}

$fourthSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,mp.Fourth as auditorEmpId,mp.Fifth as fifthEmpId,l.Name,l.Site_Id,l.GeoCoordinates,m.Verifier,m.Approver,m.Third,m.Fourth,m.Fifth,m.Sixth,mp.Start,mp.End,m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId,h.ApproverActivityId,h.ThirdActivityId,h.SixthActivityId,m.GeoFence,h.WorkStartDatetime,h.WorkEndDatetime 
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				left join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and mp.MenuId in (303,304,305,306,307,308,309,310) and (find_in_set('$empId',mp.Fourth) <> 0 or find_in_set('$empId',mp.Fifth) <> 0) and h.Status in ('PTW_04','PTW_91')
				and h.FourthActivityId is null";	

// $fourthSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,mp.Fourth as auditorEmpId,mp.Fifth as fifthEmpId,l.Name,l.Site_Id,l.GeoCoordinates,m.Verifier,m.Approver,m.Third,m.Fourth,m.Fifth,m.Sixth,mp.Start,mp.End,m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId,h.ApproverActivityId,h.ThirdActivityId,h.SixthActivityId,m.GeoFence,h.WorkStartDatetime,h.WorkEndDatetime, (select count(*)+1 from PTWAudit pa where pa.ActivityId = mp.ActivityId) as CurrentAuditNo, (select count(*) from PTWAudit pa where pa.ActivityId = mp.ActivityId) as DoneAuditCount
// 				from Mapping mp
// 				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
// 				join Menu m on (m.MenuId = mp.MenuId)
// 				left join Location l on (mp.LocationId = l.LocationId)
// 				where mp.ActivityId !=0 and mp.MenuId in (303,304,305,306,307,308,309,310) and (find_in_set('$empId',mp.Fourth) <> 0 or find_in_set('$empId',mp.Fifth) <> 0) and h.Status in ('PTW_04','PTW_91')
// 				and h.FourthActivityId is null";

$fourthQuery=mysqli_query($conn,$fourthSql);
while($fo = mysqli_fetch_assoc($fourthQuery)){
	$menuGeoFence = $fo["GeoFence"];
	$menuGeoFenceExplode = explode(":", $menuGeoFence);
	$menuLocationId = $menuGeoFenceExplode[0];
	$menuLocationDistance = $menuGeoFenceExplode[1];
	$menuLocationIdExplode = explode(",", $menuLocationId);
	$locationId = $fo["LocationId"];
	$auditorEmpId = $fo["auditorEmpId"];
	// $currentAuditNo = $fo["CurrentAuditNo"]; // Current running audit number
	// $doneAuditCount = $fo["DoneAuditCount"]; // Total done audit count
	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

	$auditEmpList = explode(",", $auditorEmpId);
	$isAuditor = false;
	if(in_array($empId,$auditEmpList)){
		$isAuditor = true;
	}

	$foObj = "";
	$foObj->menuId = $fo["MenuId"];
	$foObj->locationId = $fo["LocationId"];
	$foObj->assignId = "";
	$ptw = "";
	$status = "";
	if($fo["MenuId"] == 303 || $fo["MenuId"] == 304 || $fo["MenuId"] == 305 || $fo["MenuId"] == 306 || $fo["MenuId"] == 307 || $fo["MenuId"] == 308 || $fo["MenuId"] == 309 || $fo["MenuId"] == 310){
		$foObj->name = $fo["Site_Id"];
		$foObj->startDate = $fo["WorkStartDatetime"];
		$foObj->endDate = $fo["WorkEndDatetime"];
		$ptw = "PTW - ";
		if($isAuditor){
			// $status = " (Audit - $currentAuditNo)";
			$status = " (Audit)";
		}
		else
			$status = " (Closer)";
	}
	else{
		$foObj->name = $fo["Name"];
		$foObj->startDate = $fo["Start"];
		$foObj->endDate = $fo["End"];
	}
	// $foObj->latlong = $fo["GeoCoordinates"];
	$geoCoordinate = str_replace(",", "/", $fo["GeoCoordinates"]);
	$foObj->latlong = $geoCoordinate;
	if($locationIdIndex >= 0 && ($menuLocationDistance != null && trim($menuLocationDistance,' ') != '')){
		$foObj->GeoFence = $menuLocationDistance;
	}else{
		$foObj->GeoFence = $configGeoFence;
	}
	$foObj->activityId = $fo["ActivityId"];
	$iconArr = explode(",",$fo['Icons']);
	if($fo['Caption'] != ''){
			$foObj->Caption = $fo['Caption'];
			$foObj->Icon = $iconArr[2];
	}
	else if($fo['Sub'] != ''){
		$foObj->Caption = $ptw.$fo['Sub'].$status;
		$foObj->Icon = $iconArr[1];
	}
	else{
		$foObj->Caption = $fo['Cat'];
		$foObj->Icon = $iconArr[0];
	}

	$sixthActivityId = $fo["SixthActivityId"];
	if($sixthActivityId == null){
		if($isAuditor)
			$foObj->checkpointId = $fo['CheckpointId'].":".$fo['Verifier'].":".$fo['Approver'].":".$fo['Third'].":".$fo['Fourth'];
		else
			$foObj->checkpointId = $fo['CheckpointId'].":".$fo['Verifier'].":".$fo['Approver'].":".$fo['Third'].":".$fo['Fifth'];
	}
	else{
		if($isAuditor)
			$foObj->checkpointId = $fo['CheckpointId'].":".$fo['Verifier'].":".$fo['Approver'].":".$fo['Third'].":".$fo['Sixth'].":".$fo['Fourth'];
		else
			$foObj->checkpointId = $fo['CheckpointId'].":".$fo['Verifier'].":".$fo['Approver'].":".$fo['Third'].":".$fo['Sixth'].":".$fo['Fifth'];
	}

		

	$foisDataSend = "";
	$focpIdArray = explode(":",$foObj->checkpointId);
	for($focpId = 0; $focpId < count($focpIdArray); $focpId++){
		if($focpId == 0){
			$foisDataSend .= "0";
		}
		else if($focpId == count($focpIdArray)-1){
			$foisDataSend .= ":1";
		}
		else{
			$foisDataSend .= ":0";
		}	
	}
	$foObj->isDataSend = $foisDataSend;
	$focpArray = array();
	$fofilledCpString = str_replace(":",",",$fo['CheckpointId']);
	$foverifierCpString = str_replace(":",",",$fo['Verifier']);
	$foapproverCpString = str_replace(":",",",$fo['Approver']);
	$thirdApprovedCpString = str_replace(":",",",$fo['Third']);
	$sixthApprovedCpString = str_replace(":",",",$fo['Sixth']);
	if($isAuditor)
		$fourthCpString = str_replace(":",",",$fo['Fourth']);
	else
		$fourthCpString = str_replace(":",",",$fo['Fifth']);

	$apfilledcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$fo['ActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($fofilledCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";

	$supervisorMobile = "";
	$supervisorWhatsapp = "";
	$apfilledcpQuery=mysqli_query($conn,$apfilledcpSql);
	while($apfcp = mysqli_fetch_assoc($apfilledcpQuery)){
		$apfcpObj = "";
		$apfcpObj->Chkp_Id = $apfcp['CheckpointId'];
		$apfcpObj->editable = '0';
		if($apfcp['answer'] != null){
			$apfcpObj->value = $apfcp['answer'];
		}
		else{
			$apfcpObj->value = "";
		}

		if($apfcp['CheckpointId'] == 5521){
			$supervisorMobile = $apfcp['answer'];
		}
		else if($apfcp['CheckpointId'] == 5522){
			$supervisorWhatsapp = $apfcp['answer'];
		}
		
		$apfdpArray = array();
		if($apfcp['Dependent'] == "1"){
			$apfdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$fo['ActivityId']."' and d.DependChkId = (".$apfcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$apfdpQuery = mysqli_query($conn,$apfdpSql);
			while($apfdp = mysqli_fetch_assoc($apfdpQuery)){
				$apfdpObj = "";
				$apfdpObj->Chkp_Id = $apfcp['CheckpointId']."_".$apfdp['CheckpointId'];
				$apfdpObj->editable = '0';
				$apfdpObj->value = $apfdp['answer'];
				array_push($apfdpArray,$apfdpObj);
			}
		}
		$apfcpObj->Dependents = $apfdpArray;
		array_push($focpArray,$apfcpObj);
	}

	$apverifiedcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$fo['VerifierActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($foverifierCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	//echo $apverifiedcpSql;
	$apverifiedcpQuery=mysqli_query($conn,$apverifiedcpSql);
	 
	 while($apvcp = mysqli_fetch_assoc($apverifiedcpQuery)){
		$apvcpObj = "";
		$apvcpObj->Chkp_Id = $apvcp['CheckpointId'];
		$apvcpObj->editable = '0';
		if($apvcp['answer'] != null){
			$apvcpObj->value = $apvcp['answer'];
		}
		else{
			$apvcpObj->value = "";
		}
		
		$apvdpArray = array();
		if($apvcp['Dependent'] == "1"){
			$apvdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$fo['VerifierActivityId']."' and d.DependChkId = (".$apvcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$apvdpQuery = mysqli_query($conn,$apvdpSql);
			while($apvdp = mysqli_fetch_assoc($apvdpQuery)){
				$apvdpObj = "";
				$apvdpObj->Chkp_Id = $apvcp['CheckpointId']."_".$apvdp['CheckpointId'];
				$apvdpObj->editable = '0';
				$apvdpObj->value = $apvdp['answer'];
				array_push($apvdpArray,$apvdpObj);
			}
		}
		$apvcpObj->Dependents = $apvdpArray;
		array_push($focpArray,$apvcpObj);
	}

	$apapprovedcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$fo['ApproverActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($foapproverCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	//echo $apverifiedcpSql;
	$apapprovedcpQuery=mysqli_query($conn,$apapprovedcpSql);
	 
	 while($apvcp = mysqli_fetch_assoc($apapprovedcpQuery)){
		$apvcpObj = "";
		$apvcpObj->Chkp_Id = $apvcp['CheckpointId'];
		$apvcpObj->editable = '0';
		if($apvcp['answer'] != null){
			$apvcpObj->value = $apvcp['answer'];
		}
		else{
			$apvcpObj->value = "";
		}
		
		$apvdpArray = array();
		if($apvcp['Dependent'] == "1"){
			$apvdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$fo['ApproverActivityId']."' and d.DependChkId = (".$apvcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$apvdpQuery = mysqli_query($conn,$apvdpSql);
			while($apvdp = mysqli_fetch_assoc($apvdpQuery)){
				$apvdpObj = "";
				$apvdpObj->Chkp_Id = $apvcp['CheckpointId']."_".$apvdp['CheckpointId'];
				$apvdpObj->editable = '0';
				$apvdpObj->value = $apvdp['answer'];
				array_push($apvdpArray,$apvdpObj);
			}
		}
		$apvcpObj->Dependents = $apvdpArray;
		array_push($focpArray,$apvcpObj);
	}

	$thapprovedcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$fo['ThirdActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($thirdApprovedCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	//echo $apverifiedcpSql;
	$thapprovedcpQuery=mysqli_query($conn,$thapprovedcpSql);
	 
	 while($apvcp = mysqli_fetch_assoc($thapprovedcpQuery)){
		$apvcpObj = "";
		$apvcpObj->Chkp_Id = $apvcp['CheckpointId'];
		$apvcpObj->editable = '0';
		if($apvcp['answer'] != null){
			$apvcpObj->value = $apvcp['answer'];
		}
		else{
			$apvcpObj->value = "";
		}
		
		$apvdpArray = array();
		if($apvcp['Dependent'] == "1"){
			$apvdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$fo['ThirdActivityId']."' and d.DependChkId = (".$apvcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$apvdpQuery = mysqli_query($conn,$apvdpSql);
			while($apvdp = mysqli_fetch_assoc($apvdpQuery)){
				$apvdpObj = "";
				$apvdpObj->Chkp_Id = $apvcp['CheckpointId']."_".$apvdp['CheckpointId'];
				$apvdpObj->editable = '0';
				$apvdpObj->value = $apvdp['answer'];
				array_push($apvdpArray,$apvdpObj);
			}
		}
		$apvcpObj->Dependents = $apvdpArray;
		array_push($focpArray,$apvcpObj);
	}

	if($sixthActivityId != null){
		$sixthFilledCpSql = "Select r2.*,r1.* 
						 from
						 (Select d.ChkId,d.Value as answer from TransactionDTL d
						 where d.ActivityId = '".$fo['SixthActivityId']."' and d.DependChkId = 0
						 )r1
						 right join 
						 (Select c.* from Checkpoints c
						 where c.CheckpointId in ($sixthApprovedCpString)
						 ) r2 on (r1.ChkId = r2.CheckpointId)";
		$sixthFilledCpQuery=mysqli_query($conn,$sixthFilledCpSql);
		 
		while($sixthcp = mysqli_fetch_assoc($sixthFilledCpQuery)){
			$sixthcpObj = "";
			$sixthcpObj->Chkp_Id = $sixthcp['CheckpointId'];
			$sixthcpObj->editable = '0';
			if($sixthcp['answer'] != null){
				$sixthcpObj->value = $sixthcp['answer'];
			}
			else{
				$sixthcpObj->value = "";
			}
			
			$sixthdpArray = array();
			if($sixth['Dependent'] == "1"){
				$sixrthdpSql = " Select r1.*,c.* from
								(Select d.ChkId,d.Value as answer from TransactionDTL d
								where d.ActivityId = '".$fo['SixthActivityId']."' and d.DependChkId = (".$sixthcp['CheckpointId'].")
								) r1
								join Checkpoints c on (r1.ChkId = c.CheckpointId)";
								
				$sixthdpQuery = mysqli_query($conn,$sixthdpSql);
				while($sixthdp = mysqli_fetch_assoc($sixthdpQuery)){
					$sixthdpObj = "";
					$sixthdpObj->Chkp_Id = $sixthcp['CheckpointId']."_".$sixthdp['CheckpointId'];
					$sixthdpObj->editable = '0';
					$sixthdpObj->value = $sixthdp['answer'];
					array_push($sixthdpArray,$sixthdpObj);
				}
			}
			$sixthcpObj->Dependents = $sixthdpArray;
			array_push($focpArray,$sixthcpObj);
		}
	}
	
	$apapprovercpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($fourthCpString)";
	$apapprovercpQuery=mysqli_query($conn,$apapprovercpSql);
	while($apcp = mysqli_fetch_assoc($apapprovercpQuery)){
		$apcpObj = "";
		if($apcp['CheckpointId'] == 5707){
			$apcpObj->Chkp_Id = $apcp['CheckpointId'];
			$apcpObj->editable = '0';
			$apcpObj->value = "$supervisorMobile";
			$apdpArray = array();
			$apcpObj->Dependents = $apdpArray;
			array_push($focpArray,$apcpObj);
		}
		else if($apcp['CheckpointId'] == 5708){
			$apcpObj->Chkp_Id = $apcp['CheckpointId'];
			$apcpObj->editable = '0';
			$apcpObj->value = "$supervisorWhatsapp";
			$apdpArray = array();
			$apcpObj->Dependents = $apdpArray;
			array_push($focpArray,$apcpObj);
		}
		else{
			$apcpObj->Chkp_Id = $apcp['CheckpointId'];
			$apcpObj->editable = $apcp['Editable'];
			$apcpObj->value = "";
			$apdpArray = array();
			if($apcp['Dependent'] == "1"){
				$apcplogicArray = explode(":",trim($apcp['Logic']," "));
				$apcplogicString = "";
				for($apcpl=0;$apcpl< count($apcplogicArray);$apcpl++){
					if($apcpl == 0  && $apcplogicArray[$apcpl] != null && $apcplogicArray[$apcpl] != ""){
						$apcplogicString .= $apcplogicArray[$apcpl];
					}
					else if($apcplogicArray[$apcpl] != null && $apcplogicArray[$apcpl] != ""){
						$apcplogicString .= ",".$apcplogicArray[$apcpl];
					}
					
				}
				$apdpSql = " Select c.* from
								   Checkpoints c where c.CheckpointId in ($apcplogicString)";
								
				$apdpQuery = mysqli_query($conn,$apdpSql);
				while($apdp = mysqli_fetch_assoc($apdpQuery)){
					$apdpObj = "";
					$apdpObj->Chkp_Id = $apdp['CheckpointId'];
					$apdpObj->editable = $apdp['Editable'];
					$apdpObj->value = "";
					array_push($apdpArray,$apdpObj);
				}
			}
			$apcpObj->Dependents = $apdpArray;
			array_push($focpArray,$apcpObj);
		}
	}


	$foObj->value = $focpArray;
	array_push($wrappedListArray,$foObj);
}

// $fifthSql = "Select mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.GeoCoordinates,m.Verifier,m.Approver,m.Third,m.Fourth,m.Fifth,mp.Start,mp.End,
// 				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId,h.ApproverActivityId,h.ThirdActivityId,h.FourthActivityId,m.GeoFence
// 				from Mapping mp
// 				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
// 				join Menu m on (m.MenuId = mp.MenuId)
// 				left join Location l on (mp.LocationId = l.LocationId)
// 				where mp.ActivityId !=0 and mp.MenuId in (303,304,305,306,307,308,309,310) and mp.Fifth = '$empId' and h.Status in ('PTW_05')
// 				and h.FifthActivityId is null";	

// $fifthQuery=mysqli_query($conn,$fifthSql);
// while($five = mysqli_fetch_assoc($fifthQuery)){
// 	$menuGeoFence = $five["GeoFence"];
// 	$menuGeoFenceExplode = explode(":", $menuGeoFence);
// 	$menuLocationId = $menuGeoFenceExplode[0];
// 	$menuLocationDistance = $menuGeoFenceExplode[1];
// 	$menuLocationIdExplode = explode(",", $menuLocationId);
// 	$locationId = $five["LocationId"];
// 	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

// 	$dataObj = "";
// 	$dataObj->menuId = $five["MenuId"];
// 	$dataObj->locationId = $five["LocationId"];
// 	$dataObj->startDate = $five["Start"];
// 	$dataObj->endDate = $five["End"];
// 	$dataObj->assignId = "";
// 	$dataObj->name = $five["Name"];
//	// $dataObj->latlong = $five["GeoCoordinates"];
//	$geoCoordinate = str_replace(",", "/", $five["GeoCoordinates"]);
//	$dataObj->latlong = $geoCoordinate;
// 	if($locationIdIndex >= 0 && ($menuLocationDistance != null && trim($menuLocationDistance,' ') != '')){
// 		$dataObj->GeoFence = $menuLocationDistance;
// 	}else{
// 		$dataObj->GeoFence = $configGeoFence;
// 	}
// 	$dataObj->activityId = $five["ActivityId"];
// 	$iconArr = explode(",",$five['Icons']);
// 	if($five['Caption'] != ''){
// 			$dataObj->Caption = $five['Caption'];
// 			$dataObj->Icon = $iconArr[2];
// 	}
// 	else if($five['Sub'] != ''){
// 		$dataObj->Caption = $five['Sub'];
// 		$dataObj->Icon = $iconArr[1];
// 	}
// 	else{
// 		$dataObj->Caption = $five['Cat'];
// 		$dataObj->Icon = $iconArr[0];
// 	}

// 	$dataObj->checkpointId = $five['CheckpointId'].":".$five['Verifier'].":".$five['Approver'].":".$five['Third'].":".$five['Fourth'].":".$five['Fifth'];

// 	$fiisDataSend = "";
// 	$ficpIdArray = explode(":",$dataObj->checkpointId);
// 	for($ficpId = 0; $ficpId < count($ficpIdArray); $ficpId++){
// 		if($ficpId == 0){
// 			$fiisDataSend .= "0";
// 		}
// 		else if($ficpId == count($ficpIdArray)-1){
// 			$fiisDataSend .= ":1";
// 		}
// 		else{
// 			$fiisDataSend .= ":0";
// 		}	
// 	}
// 	$dataObj->isDataSend = $fiisDataSend;
// 	$cpArray = array();
// 	$fifilledCpString = str_replace(":",",",$five['CheckpointId']);
// 	$fiverifierCpString = str_replace(":",",",$five['Verifier']);
// 	$fiapproverCpString = str_replace(":",",",$five['Approver']);
// 	$thirdApprovedCpString = str_replace(":",",",$five['Third']);
// 	$fourthApprovedCpString = str_replace(":",",",$five['Fourth']);
// 	$fifthCpString = str_replace(":",",",$five['Fifth']);
// 	getFilledCheckpoint($five['ActivityId'],$fifilledCpString);
// 	getFilledCheckpoint($five['VerifierActivityId'],$fiverifierCpString);
// 	getFilledCheckpoint($five['ApproverActivityId'],$fiapproverCpString);
// 	getFilledCheckpoint($five['ThirdActivityId'],$thirdApprovedCpString);
// 	getFilledCheckpoint($five['FourthActivityId'],$fourthApprovedCpString);
// 	getNonFilledCheckpoint($fifthCpString);
// 	$dataObj->value = $cpArray;
// 	array_push($wrappedListArray,$dataObj);
// }

$sixthSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.Site_Id,l.GeoCoordinates,m.Verifier,m.Approver,m.Third,m.Sixth,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId,h.ApproverActivityId,h.ThirdActivityId,h.WorkStartDatetime,h.WorkEndDatetime
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				left join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and mp.MenuId in (303,304,305,306,307,308,309,310) and find_in_set('$empId',mp.Sixth) <> 0 and h.Status = 'PTW_90'
				and h.SixthActivityId is null";				

$sixthQuery=mysqli_query($conn,$sixthSql);
while($six = mysqli_fetch_assoc($sixthQuery)){
	$menuGeoFence = $six["GeoFence"];
	$menuGeoFenceExplode = explode(":", $menuGeoFence);
	$menuLocationId = $menuGeoFenceExplode[0];
	$menuLocationDistance = $menuGeoFenceExplode[1];
	$menuLocationIdExplode = explode(",", $menuLocationId);
	$locationId = $six["LocationId"];
	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

	$dataObj = "";
	$dataObj->menuId = $six["MenuId"];
	$dataObj->locationId = $six["LocationId"];
	$dataObj->assignId = "";
	$ptw = "";
	$status = "";
	if($six["MenuId"] == 303 || $six["MenuId"] == 304 || $six["MenuId"] == 305 || $six["MenuId"] == 306 || $six["MenuId"] == 307 || $six["MenuId"] == 308 || $six["MenuId"] == 309 || $six["MenuId"] == 310){
		$dataObj->name = $six["Site_Id"];
		$dataObj->startDate = $six["WorkStartDatetime"];
		$dataObj->endDate = $six["WorkEndDatetime"];
		$ptw = "PTW - ";
		// $status = " (SA)";
		$status = " (High Risk)";
	}
	else{
		$dataObj->name = $six["Name"];
		$dataObj->startDate = $six["Start"];
		$dataObj->endDate = $six["End"];
	}
	// $dataObj->latlong = $six["GeoCoordinates"];
	$geoCoordinate = str_replace(",", "/", $six["GeoCoordinates"]);
	$dataObj->latlong = $geoCoordinate;
	if($locationIdIndex >= 0 && ($menuLocationDistance != null && trim($menuLocationDistance,' ') != '')){
		$dataObj->GeoFence = $menuLocationDistance;
	}else{
		$dataObj->GeoFence = $configGeoFence;
	}
	$dataObj->activityId = $six["ActivityId"];
	$iconArr = explode(",",$six['Icons']);
	if($six['Caption'] != ''){
			$dataObj->Caption = $six['Caption'];
			$dataObj->Icon = $iconArr[2];
	}
	else if($six['Sub'] != ''){
		$dataObj->Caption = $ptw.$six['Sub'].$status;
		$dataObj->Icon = $iconArr[1];
	}
	else{
		$dataObj->Caption = $six['Cat'];
		$dataObj->Icon = $iconArr[0];
	}

	$dataObj->checkpointId = $six['CheckpointId'].":".$six['Verifier'].":".$six['Approver'].":".$six['Third'].":".$six['Sixth'];

	$fiisDataSend = "";
	$ficpIdArray = explode(":",$dataObj->checkpointId);
	for($ficpId = 0; $ficpId < count($ficpIdArray); $ficpId++){
		if($ficpId == 0){
			$fiisDataSend .= "0";
		}
		else if($ficpId == count($ficpIdArray)-1){
			$fiisDataSend .= ":1";
		}
		else{
			$fiisDataSend .= ":0";
		}	
	}
	$dataObj->isDataSend = $fiisDataSend;
	$cpArray = array();
	$fifilledCpString = str_replace(":",",",$six['CheckpointId']);
	$fiverifierCpString = str_replace(":",",",$six['Verifier']);
	$fiapproverCpString = str_replace(":",",",$six['Approver']);
	$thirdFilledCpString = str_replace(":",",",$six['Third']);
	$sixthCpString = str_replace(":",",",$six['Sixth']);
	getFilledCheckpoint($six['ActivityId'],$fifilledCpString);
	getFilledCheckpoint($six['VerifierActivityId'],$fiverifierCpString);
	getFilledCheckpoint($six['ApproverActivityId'],$fiapproverCpString);
	getFilledCheckpoint($six['ThirdActivityId'],$thirdFilledCpString);
	getNonFilledCheckpoint($sixthCpString);
	$dataObj->value = $cpArray;
	array_push($wrappedListArray,$dataObj);

}
echo json_encode($wrappedListArray);
?>

<?php 
function getFilledCheckpoint($filledActivityId, $filledCheckpointId){
	global $cpArray;
	global $conn;
	$filledcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$filledActivityId."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($filledCheckpointId)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	$filledcpQuery=mysqli_query($conn,$filledcpSql);
	while($fcp = mysqli_fetch_assoc($filledcpQuery)){
		$fcpObj = "";
		$fcpObj->Chkp_Id = $fcp['CheckpointId'];
		$fcpObj->editable = '0';
		$fcpObj->action = null;
		if($fcp['answer'] != null){
			$fcpObj->value = $fcp['answer'];
		}
		else{
			$fcpObj->value = "";
		}
		
		$fdpArray = array();
		if($fcp['Dependent'] == "1"){
			$fdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value as answer from TransactionDTL d
							where d.ActivityId = '".$filledActivityId."' and d.DependChkId = (".$fcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$fdpQuery = mysqli_query($conn,$fdpSql);
			while($fdp = mysqli_fetch_assoc($fdpQuery)){
				$fdpObj = "";
				$fdpObj->Chkp_Id = $fcp['CheckpointId']."_".$fdp['CheckpointId'];
				$fdpObj->editable = '0';
				$fdpObj->value = $fdp['answer'];
				$fdpObj->action = null;
				array_push($fdpArray,$fdpObj);
			}
		}
		$fcpObj->Dependents = $fdpArray;
		array_push($cpArray,$fcpObj);
	}

}
function getNonFilledCheckpoint($nonFilledCheckpoint){
	global $cpArray;
	global $conn;
	$verifiercpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($nonFilledCheckpoint)";
	$verifiercpQuery=mysqli_query($conn,$verifiercpSql);
	while($vcp = mysqli_fetch_assoc($verifiercpQuery)){
		$vcpObj = "";
		$vcpObj->Chkp_Id = $vcp['CheckpointId'];
		$vcpObj->editable = $vcp['Editable'];
		$vcpObj->action = $vcp['action'];
		$vcpObj->value = "";
		$vdpArray = array();
		if($vcp['Dependent'] == "1"){
			$vcplogicArray = explode(":",trim($vcp['Logic']," "));
			$vcplogicString = "";
			for($vcpl=0;$vcpl< count($vcplogicArray);$vcpl++){
				if($vcpl == 0  && $vcplogicArray[$vcpl] != null && $vcplogicArray[$vcpl] != ""){
					$vcplogicString .= $vcplogicArray[$vcpl];
				}
				else if($vcplogicArray[$vcpl] != null && $vcplogicArray[$vcpl] != ""){
					$vcplogicString .= ",".$vcplogicArray[$vcpl];
				}
				
			}
			$vdpSql = " Select c.* from
							   Checkpoints c where c.CheckpointId in ($vcplogicString)";
							
			$vdpQuery = mysqli_query($conn,$vdpSql);
			while($vdp = mysqli_fetch_assoc($vdpQuery)){
				$vdpObj = "";
				$vdpObj->Chkp_Id = $vdp['CheckpointId'];
				$vdpObj->editable = $vdp['Editable'];
				$vdpObj->action = $vdp['action'];
				$vdpObj->value = "";
				array_push($vdpArray,$vdpObj);
			}
		}
		$vcpObj->Dependents = $vdpArray;
		array_push($cpArray,$vcpObj);
	} 

}
?>