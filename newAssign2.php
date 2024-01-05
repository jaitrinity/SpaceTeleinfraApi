<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];

$wrappedListArray = array();


		
$assignSql = "SELECT mp.MenuId,mp.LocationId,mp.Start,mp.End,mp.MappingId,l.Name,l.GeoCoordinates,
		m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId
		FROM Mapping mp 
		left join Menu m  on (mp.MenuId = m.MenuId)
		left join Location l on (mp.LocationId = l.LocationId)
		WHERE mp.EmpId = '$empId' AND date(mp.Start) <= date(now()) AND date(mp.End) >= date(now())
		AND mp.ActivityId = 0 AND mp.Active = 1 ";
		
$assignQuery=mysqli_query($conn,$assignSql);
//$assignArray = array();
while($row = mysqli_fetch_assoc($assignQuery)){
	$assignObj = "";
	$assignObj->menuId = $row["MenuId"];
	$assignObj->locationId = $row["LocationId"];
	$assignObj->startDate = $row["Start"];
	$assignObj->endDate = $row["End"];
	$assignObj->assignId = $row["MappingId"];
	$assignObj->name = $row["Name"];
	$assignObj->latlong = $row["GeoCoordinates"];
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
	//echo $cpString;
	$acpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($acpString)";
	$acpQuery=mysqli_query($conn,$acpSql);
	$acpArray = array();
	while($acp = mysqli_fetch_assoc($acpQuery)){
		$acpObj = "";
		$acpObj->Chkp_Id = $acp['CheckpointId'];
		$acpObj->editable = $acp['Editable'];
		$acpObj->value = '';
		
		$adpArray = array();
		if($acp['Dependent'] == "1"){
			$acplogicArray = explode("::",$acp['Logic']);
			$acplogicString = "";
			for($acpl=0;$acpl < count($acplogicArray);$acpl++){
				if($acpl == 0 && $acplogicArray[$acpl] != null && $acplogicArray[$acpl] != ""){
					$acplogicString .= $acplogicArray[$acpl];
				}
				else if($acplogicArray[$acpl] != null && $acplogicArray[$acpl] != ""){
					$acplogicString .= ",".$acplogicArray[$acpl];
				}
				
			}
			$adpSql = " Select c.* from
						Checkpoints c where c.CheckpointId in ($acplogicString)";
			
			//echo $adpSql;
			$adpQuery = mysqli_query($conn,$adpSql);
			while($adp = mysqli_fetch_assoc($adpQuery)){
				$adpObj = "";
				$adpObj->Chkp_Id = $adp['CheckpointId'];
				$adpObj->editable = $adp['Editable'];
				$adpObj->value = "";
				array_push($adpArray,$adpObj);
			}
		}
		$acpObj->Dependents = $adpArray;
		
		array_push($acpArray,$acpObj);
		
	}
	$assignObj->value = $acpArray;
	array_push($wrappedListArray,$assignObj);
}

$verifierSql = "Select mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.GeoCoordinates,m.Verifier,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and mp.Verifier = '$empId' and h.Status = 'Created'
				and h.VerifierActivityId is null";				

$verifierQuery=mysqli_query($conn,$verifierSql);

while($v = mysqli_fetch_assoc($verifierQuery)){
	$vObj = "";
	$vObj->menuId = $v["MenuId"];
	$vObj->locationId = $v["LocationId"];
	$vObj->startDate = $v["Start"];
	$vObj->endDate = $v["End"];
	$vObj->assignId = "";
	$vObj->name = $v["Name"];
	$vObj->latlong = $v["GeoCoordinates"];
	$vObj->activityId = $v["ActivityId"];
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
							(Select d.ChkId,d.Value from TransactionDTL d
							where d.ActivityId = '".$v['ActivityId']."' and d.DependChkId = (".$fcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$fdpQuery = mysqli_query($conn,$fdpSql);
			while($fdp = mysqli_fetch_assoc($fdpQuery)){
				$fdpObj = "";
				$fdpObj->Chkp_Id = $fdp['CheckpointId'];
				$fdpObj->editable = '0';
				$fdpObj->value = $fdp['answer'];
				array_push($fdpArray,$fdpObj);
			}
		}
		$fcpObj->Dependents = $fdpArray;
		array_push($cpArray,$fcpObj);
	} 
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
			$vcplogicArray = explode("::",$vcp['Logic']);
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
				$vdpObj->value = "";
				array_push($vdpArray,$vdpObj);
			}
		}
		$vcpObj->Dependents = $vdpArray;
		array_push($cpArray,$vcpObj);
	} 
	
	$vObj->value = $cpArray;
	array_push($wrappedListArray,$vObj);
}



echo json_encode($wrappedListArray);
?>