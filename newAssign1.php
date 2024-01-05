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

$tempJson = new StdClass;
$ptwResult = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/ptwNewAssign.php?empId=$empId",$tempJson);
$ptwResult=json_decode($ptwResult,true);
if(count($ptwResult) !=0){
	for($ii=0;$ii<count($ptwResult); $ii++){
		$ptwObj = $ptwResult[$ii];
		array_push($wrappedListArray,$ptwObj);
	}
}

$beforeDate = date('Y-m-d', strtotime('-7 day'));
	
$assignSql = "SELECT mp.MenuId,mp.LocationId,mp.Start,mp.End,mp.MappingId,l.Name,l.Site_Id,l.GeoCoordinates,
		m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId, m.GeoFence, mp.Customer_Site_Id
		FROM Mapping mp 
		left join Menu m  on (mp.MenuId = m.MenuId)
		left join Location l on (mp.LocationId = l.LocationId)
		WHERE mp.EmpId = '$empId' and mp.MenuId not in (303,304,305,306,307,308,309,310) AND date(mp.Start) <= date(now()) AND date(mp.End) >= date(now())
		AND mp.ActivityId = 0 AND mp.Active = 1 ";
		
$assignQuery=mysqli_query($conn,$assignSql);
//$assignArray = array();
while($row = mysqli_fetch_assoc($assignQuery)){
	$menuGeoFence = $row["GeoFence"];
	$menuGeoFenceExplode = explode(":", $menuGeoFence);
	$menuLocationId = $menuGeoFenceExplode[0];
	$menuLocationDistance = "";
	if(count($menuGeoFenceExplode) > 1)
		$menuLocationDistance = $menuGeoFenceExplode[1];
	$menuLocationIdExplode = explode(",", $menuLocationId);
	$locationId = $row["LocationId"];
	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

	$assignObj = new StdClass;
	$assignObj->menuId = $row["MenuId"];
	$assignObj->locationId = $row["LocationId"];
	$assignObj->startDate = $row["Start"];
	$assignObj->endDate = $row["End"];
	$assignObj->assignId = $row["MappingId"];
	if($row["MenuId"] == 293)
		$assignObj->name = $row["Site_Id"].' - '.$row["Name"];
	else if($row["MenuId"] == 279)
		$assignObj->name = $row["Name"].'-'.$row["Customer_Site_Id"];
	else
		$assignObj->name = $row["Name"];
	$assignObj->latlong = $row["GeoCoordinates"];
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
		$assignObj->Caption = $row['Sub'];
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

		
$verifierSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.GeoCoordinates,m.Verifier,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId, m.GeoFence, mp.Customer_Site_Id, h.VerifierActivityId, h.Verify_Final_Submit, h.Nominal_Latlong
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				left join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and date(mp.Start) >= '$beforeDate' and mp.MenuId not in (303,304,305,306,307,308,309,310) and find_in_set('$empId',mp.Verifier) <> 0 and h.Status = 'Created' and (h.Verify_Final_Submit is null or h.Verify_Final_Submit = 'No') AND mp.Active = 1";				

$verifierQuery=mysqli_query($conn,$verifierSql);

while($v = mysqli_fetch_assoc($verifierQuery)){
	$menuGeoFence = $v["GeoFence"];
	$menuGeoFenceExplode = explode(":", $menuGeoFence);
	$menuLocationId = $menuGeoFenceExplode[0];
	$menuLocationDistance = "";
	if(count($menuGeoFenceExplode) > 1)
		$menuLocationDistance = $menuGeoFenceExplode[1];
	$menuLocationIdExplode = explode(",", $menuLocationId);
	$locationId = $v["LocationId"];
	$verifyFinalSubmit = $v["Verify_Final_Submit"] == null ? "NA" : $v["Verify_Final_Submit"];
	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

	$vObj = new StdClass;
	$vObj->menuId = $v["MenuId"];
	$vObj->locationId = $v["LocationId"];
	$vObj->startDate = $v["Start"];
	$vObj->endDate = $v["End"];
	$vObj->assignId = "";
	if($v["MenuId"] != 279){
		$vObj->name = $v["Name"].'-'.$v["ActivityId"];
	}else{
		$vObj->name = $v["Customer_Site_Id"];
	}
	if($v["MenuId"] != 279){
		$vObj->latlong = $v["GeoCoordinates"];
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
		$vObj->Caption = $v['Sub'];
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
		$fcpObj = new StdClass;
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
				$fdpObj = new StdClass;
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
			$fcpObj = new StdClass;
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
					$fdpObj = new StdClass;
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
			$vcpObj = new StdClass;
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
					$vdpObj = new StdClass;
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

$approverSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.GeoCoordinates,m.Verifier,m.Approver,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId,m.GeoFence
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				left join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and date(mp.Start) >= '$beforeDate' and mp.MenuId not in (303,304,305,306,307,308,309,310) and find_in_set('$empId',mp.Approver) <> 0 and h.Status = 'Verified' and h.ApproverActivityId is null";				

$approverQuery=mysqli_query($conn,$approverSql);
while($ap = mysqli_fetch_assoc($approverQuery)){
	$menuGeoFence = $ap["GeoFence"];
	$menuGeoFenceExplode = explode(":", $menuGeoFence);
	$menuLocationId = $menuGeoFenceExplode[0];
	$menuLocationDistance = "";
	if(count($menuGeoFenceExplode) > 1)
		$menuLocationDistance = $menuGeoFenceExplode[1];
	$menuLocationIdExplode = explode(",", $menuLocationId);
	$locationId = $ap["LocationId"];
	$locationIdIndex = array_search($locationId, $menuLocationIdExplode);

	$apObj = new StdClass;
	$apObj->menuId = $ap["MenuId"];
	$apObj->locationId = $ap["LocationId"];
	$apObj->startDate = $ap["Start"];
	$apObj->endDate = $ap["End"];
	$apObj->assignId = "";
	$apObj->name = $ap["Name"].'-'.$ap["ActivityId"];
	$apObj->latlong = $ap["GeoCoordinates"];
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
		$apObj->Caption = $ap['Sub'];
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
		$apfcpObj = new StdClass;
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
				$apfdpObj = new StdClass;
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
		$apvcpObj = new StdClass;
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
				$apvdpObj = new StdClass;
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
		$apcpObj = new StdClass;
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
				$apdpObj = new StdClass;
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

$thirdSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.GeoCoordinates,m.Verifier,m.Approver,m.Third,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId,h.ApproverActivityId
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				left join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and date(mp.Start) >= '$beforeDate' and mp.MenuId not in (303,304,305,306,307,308,309,310) and mp.Third = '$empId' and h.Status = 'Approved' and h.ThirdActivityId is null";
				
$thirdQuery=mysqli_query($conn,$thirdSql);
while($ap = mysqli_fetch_assoc($thirdQuery)){
	$apObj = new StdClass;
	$apObj->menuId = $ap["MenuId"];
	$apObj->locationId = $ap["LocationId"];
	$apObj->startDate = $ap["Start"];
	$apObj->endDate = $ap["End"];
	$apObj->assignId = "";
	$apObj->name = $ap["Name"];
	$apObj->latlong = $ap["GeoCoordinates"];
	$apObj->GeoFence = $configGeoFence;
	$apObj->activityId = $ap["ActivityId"];
	$iconArr = explode(",",$ap['Icons']);
	if($ap['Caption'] != ''){
			$apObj->Caption = $ap['Caption'];
			$apObj->Icon = $iconArr[2];
	}
	else if($ap['Sub'] != ''){
		$apObj->Caption = $ap['Sub'];
		$apObj->Icon = $iconArr[1];
	}
	else{
		$apObj->Caption = $ap['Cat'];
		$apObj->Icon = $iconArr[0];
	}
	
	$apObj->checkpointId = $ap['CheckpointId'].":".$ap['Verifier'].":".$ap['Approver'].":".$ap['Third'];
	
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
	$cpArray = array();
	$apfilledCpString = str_replace(":",",",$ap['CheckpointId']);
	$apverifierCpString = str_replace(":",",",$ap['Verifier']);
	$apapproverCpString = str_replace(":",",",$ap['Approver']);
	$thirdCpString = str_replace(":",",",$ap['Third']);
	getFilledCheckpoint($ap['ActivityId'],$apfilledCpString);
	getFilledCheckpoint($ap['VerifierActivityId'],$apverifierCpString);
	getFilledCheckpoint($ap['ApproverActivityId'],$apapproverCpString);
	getNonFilledCheckpoint($thirdCpString);
	$apObj->value = $cpArray;
	array_push($wrappedListArray,$apObj);	
}

$fourthSql = "SELECT mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.GeoCoordinates,m.Verifier,m.Approver,m.Third,m.Fourth,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId,h.ApproverActivityId,h.ThirdActivityId
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				left join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and date(mp.Start) >= '$beforeDate' and mp.MenuId not in (303,304,305,306,307,308,309,310) and mp.Fourth = '$empId' and h.Status = 'STATUS_03' and h.FourthActivityId is null";				

$fourthQuery=mysqli_query($conn,$fourthSql);
while($ap = mysqli_fetch_assoc($fourthQuery)){
	$apObj = new StdClass;
	$apObj->menuId = $ap["MenuId"];
	$apObj->locationId = $ap["LocationId"];
	$apObj->startDate = $ap["Start"];
	$apObj->endDate = $ap["End"];
	$apObj->assignId = "";
	$apObj->name = $ap["Name"];
	$apObj->latlong = $ap["GeoCoordinates"];
	$apObj->GeoFence = $configGeoFence;
	$apObj->activityId = $ap["ActivityId"];
	$iconArr = explode(",",$ap['Icons']);
	if($ap['Caption'] != ''){
			$apObj->Caption = $ap['Caption'];
			$apObj->Icon = $iconArr[2];
	}
	else if($ap['Sub'] != ''){
		$apObj->Caption = $ap['Sub'];
		$apObj->Icon = $iconArr[1];
	}
	else{
		$apObj->Caption = $ap['Cat'];
		$apObj->Icon = $iconArr[0];
	}
	
	$apObj->checkpointId = $ap['CheckpointId'].":".$ap['Verifier'].":".$ap['Approver'].":".$ap['Third'].":".$ap['Fourth'];

	
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
	$cpArray = array();
	$apfilledCpString = str_replace(":",",",$ap['CheckpointId']);
	$apverifierCpString = str_replace(":",",",$ap['Verifier']);
	$apapproverCpString = str_replace(":",",",$ap['Approver']);
	$thirdCpString = str_replace(":",",",$ap['Third']);
	$fourthCpString = str_replace(":",",",$ap['Fourth']);
	getFilledCheckpoint($ap['ActivityId'],$apfilledCpString);
	getFilledCheckpoint($ap['VerifierActivityId'],$apverifierCpString);
	getFilledCheckpoint($ap['ApproverActivityId'],$apapproverCpString);
	getFilledCheckpoint($ap['ThirdActivityId'],$thirdCpString);
	getNonFilledCheckpoint($fourthCpString);
	$apObj->value = $cpArray;
	array_push($wrappedListArray,$apObj);	

}
// $wrappedListArray = array_slice($wrappedListArray, 0, 5); 
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
		$fcpObj = new StdClass;
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
							where d.ActivityId = '".$filledActivityId."' and d.DependChkId = (".$fcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$fdpQuery = mysqli_query($conn,$fdpSql);
			while($fdp = mysqli_fetch_assoc($fdpQuery)){
				$fdpObj = new StdClass;
				$fdpObj->Chkp_Id = $fcp['CheckpointId']."_".$fdp['CheckpointId'];
				$fdpObj->editable = '0';
				$fdpObj->value = $fdp['answer'];
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
		$vcpObj = new StdClass;
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
				$vdpObj = new StdClass;
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