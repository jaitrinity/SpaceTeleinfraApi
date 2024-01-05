<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];

$wrappedListArray = array();

$assignSql = "SELECT a.MenuId,a.LocationId,a.StartDate,a.EndDate,a.AssignId,l.Name,l.GeoCoordinates,
		m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId
		FROM Assign a 
		left join Menu m  on (a.MenuId = m.MenuId)
		left join Location l on (a.LocationId = l.LocationId)
		WHERE a.EmpId = '$empId' AND date(a.StartDate) <= date(now()) AND date(a.EndDate) >= date(now())
		AND a.ActivityId is  null AND a.Active = 1 ";
		
$assignQuery=mysqli_query($conn,$assignSql);
//$assignArray = array();
while($row = mysqli_fetch_assoc($assignQuery)){
	$assignObj = "";
	$assignObj->menuId = $row["MenuId"];
	$assignObj->locationId = $row["LocationId"];
	$assignObj->startDate = $row["StartDate"];
	$assignObj->endDate = $row["EndDate"];
	$assignObj->assignId = $row["AssignId"];
	$assignObj->name = $row["Name"];
	$assignObj->latlong = $row["GeoCoordinates"];
	$assignObj->transId = '';
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
	$assignObj->checkpointId = $row['CheckpointId'];
	$assignObj->Editable = "";
	$acpString = $assignObj->checkpointId;
	$acpString = str_replace(":",",",$acpString);
	//echo $cpString;
	$acpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($acpString)";
	$acpQuery=mysqli_query($conn,$acpSql);
	$acpArray = array();
	$acplogicString = "";
	while($acp = mysqli_fetch_assoc($acpQuery)){
		$acpObj = "";
		$acpObj->chkpId = $acp['CheckpointId'];
		$acpObj->description = $acp['Description'];
		$acpObj->value = $acp['Value'];
		$acpObj->typeId = $acp['TypeId'];
		$acpObj->mandatory = $acp['Mandatory'];
		$acpObj->Editable = $acp['Editable'];
		$acpObj->correct = $acp['Correct'];
		$acpObj->size = $acp['Size'];
		$acpObj->Active = $acp['Active'];
		$acpObj->Is_Dept = $acp['Dependent'];
		$acpObj->Logic = $acp['Logic'];
		$acpObj->answer = '';
		
		array_push($acpArray,$acpObj);
		//$adpArray = array();
		
		if($acpObj->Is_Dept == "1"){
			$acplogicArray = explode("::",$acp['Logic']);
			for($acpl=0;$acpl < count($acplogicArray);$acpl++){
				if($acplogicString == "" && $acplogicArray[$acpl] != null && $acplogicArray[$acpl] != ""){
					$acplogicString .= $acplogicArray[$acpl];
				}
				else if($acplogicArray[$acpl] != null && $acplogicArray[$acpl] != ""){
					$acplogicString .= ",".$acplogicArray[$acpl];
				}
				/*if($acpl == 0 && $acplogicArray[$acpl] != null && $acplogicArray[$acpl] != ""){
					$acplogicString .= $acplogicArray[$acpl];
				}
				else if($acplogicArray[$acpl] != null && $acplogicArray[$acpl] != ""){
					$acplogicString .= ",".$acplogicArray[$acpl];
				}*/
			}
		}
		//$acpObj->dependent = $adpArray;
			
	}
	$adpSql = " Select c.* from
				Checkpoints c where c.CheckpointId in ($acplogicString)";
	
	//echo $adpSql;
	$adpQuery = mysqli_query($conn,$adpSql);
	while($adp = mysqli_fetch_assoc($adpQuery)){
		$adpObj = "";
		$adpObj->chkpId = $adp['CheckpointId'];
		$adpObj->description = $adp['Description'];
		$adpObj->value = $adp['Value'];
		$adpObj->typeId = $adp['TypeId'];
		$adpObj->mandatory = $adp['Mandatory'];
		$adpObj->Editable = $adp['Editable'];
		$adpObj->correct = $adp['Correct'];
		$adpObj->size = $adp['Size'];
		$adpObj->Active = $adp['Active'];
		$adpObj->Is_Dept = $adp['Dependent'];
		$adpObj->Logic = "";
		$adpObj->answer = "";
		array_push($acpArray,$adpObj);
	}
	$assignObj->value = $acpArray;
	array_push($wrappedListArray,$assignObj);
}

$verifierMappingSql = "Select distinct ma.MenuId,ma.LocationId from Mapping ma where ma.Verifier = '$empId'";
$verifierMappingQuery=mysqli_query($conn,$verifierMappingSql);
$verifierMenuIdString = "";
$verifierLocationIdString = "";
$cnt = 0;
while($vm = mysqli_fetch_assoc($verifierMappingQuery)){
	if($cnt == 0){
		$verifierMenuIdString .= $vm['MenuId'];
		$verifierLocationIdString .= $vm['LocationId'];
	}
	else{
		$verifierMenuIdString .= ','.$vm['MenuId'];
		$verifierLocationIdString .= ','.$vm['LocationId'];
	}
	$cnt++;
}
$verifierSql = "SELECT h.TransactionId,a.MenuId,a.LocationId,l.Name,l.GeoCoordinates,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,m.Verifier
				from TransactionHDR h 
				join Activity a on (h.ActivityId = a.ActivityId)
				join Menu m on (m.MenuId = a.MenuId)
				join Location l on (a.LocationId = l.LocationId)
				where a.Event = 'Submit'
				and a.MenuId in ($verifierMenuIdString)
				and a.LocationId in ($verifierLocationIdString)";

//echo $verifierSql;				
$verifierQuery=mysqli_query($conn,$verifierSql);
//$assignArray = array();

while($v = mysqli_fetch_assoc($verifierQuery)){
	$vObj = "";
	$vObj->menuId = $v["MenuId"];
	$vObj->locationId = $v["LocationId"];
	$vObj->startDate = "";
	$vObj->endDate = "";
	$vObj->assignId = "";
	$vObj->name = $v["Name"];
	$vObj->latlong = $v["GeoCoordinates"];
	$vObj->transId = $v["TransactionId"];
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
	$vObj->Editable = "";
	$cpArray = array();
	$filledCpString = str_replace(":",",",$v['CheckpointId']);
	$verifierCpString = str_replace(":",",",$v['Verifier']);
	//$verifierCpString = $v['Verifier'];
	$filledcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.TransactionId = '".$v[TransactionId]."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($filledCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	//echo $filledcpSql;
	$filledcpQuery=mysqli_query($conn,$filledcpSql);
	while($fcp = mysqli_fetch_assoc($filledcpQuery)){
		$fcpObj = "";
		$fcpObj->chkpId = $fcp['CheckpointId'];
		$fcpObj->description = $fcp['Description'];
		$fcpObj->value = $fcp['Value'];
		$fcpObj->typeId = $fcp['TypeId'];
		$fcpObj->mandatory = $fcp['Mandatory'];
		$fcpObj->Editable = '0';
		$fcpObj->correct = $fcp['Correct'];
		$fcpObj->size = $fcp['Size'];
		$fcpObj->Active = $fcp['Active'];
		$fcpObj->Is_Dept = $fcp['Dependent'];
		$fcpObj->Logic = $fcp['Logic'];
		$fcpObj->answer = $fcp['answer'];
		
		if($fcpObj->Is_Dept == "1"){
			$fdpArray = array();
			$fdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value from TransactionDTL d
							where d.TransactionId = '".$v[TransactionId]."' and d.DependChkId = (".$fcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$fdpQuery = mysqli_query($conn,$fdpSql);
			while($fdp = mysqli_fetch_assoc($fdpQuery)){
				$fdpObj = "";
				$fdpObj->chkpId = $fdp['CheckpointId'];
				$fdpObj->description = $fdp['Description'];
				$fdpObj->value = $fdp['Value'];
				$fdpObj->typeId = $fdp['TypeId'];
				$fdpObj->mandatory = $fdp['Mandatory'];
				$fdpObj->Editable = '0';
				$fdpObj->correct = $fdp['Correct'];
				$fdpObj->size = $fdp['Size'];
				$fdpObj->Active = $fdp['Active'];
				$fdpObj->Is_Dept = $fdp['Dependent'];
				$fdpObj->Logic = "";
				$fdpObj->answer = $fdp['answer'];
				array_push($fdpArray,$fdpObj);
			}
			$fcpObj->dependent = $fdpArray;
		}
		array_push($cpArray,$fcpObj);
	} 
	 $verifiercpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($verifierCpString)";
	//echo $verifiercpSql;
	$verifiercpQuery=mysqli_query($conn,$verifiercpSql);
	//$vdpArray = array();
	$vcplogicString = "";
	while($vcp = mysqli_fetch_assoc($verifiercpQuery)){
		$vcpObj = "";
		$vcpObj->chkpId = $vcp['CheckpointId'];
		 $vcpObj->description = $vcp['Description'];
		$vcpObj->value = $vcp['Value'];
		$vcpObj->typeId = $vcp['TypeId'];
		$vcpObj->mandatory = $vcp['Mandatory'];
		$vcpObj->Editable = $vcp['Editable'];
		$vcpObj->correct = $vcp['Correct'];
		$vcpObj->size = $vcp['Size'];
		$vcpObj->Active = $vcp['Active'];
		$vcpObj->Is_Dept = $vcp['Dependent'];
		$vcpObj->Logic = $vcp['Logic'];
		$vcpObj->answer = "";
		array_push($cpArray,$vcpObj);
		
		if($vcpObj->Is_Dept == "1"){
			$vcplogicArray = explode("::",$vcp['Logic']);
			
			for($vcpl=0;$vcpl< count($vcplogicArray);$vcpl++){
				if($vcplogicString == "" && $vcplogicArray[$vcpl] != null && $vcplogicArray[$vcpl] != ""){
					$vcplogicString .= $vcplogicArray[$vcpl];
				}
				else if($vcplogicArray[$vcpl] != null && $vcplogicArray[$vcpl] != ""){
					$vcplogicString .= ",".$vcplogicArray[$vcpl];
				}
				/*if($vcpl == 0  && $vcplogicArray[$vcpl] != null && $vcplogicArray[$vcpl] != ""){
					$vcplogicString .= $vcplogicArray[$vcpl];
				}
				else if($vcplogicArray[$vcpl] != null && $vcplogicArray[$vcpl] != ""){
					$vcplogicString .= ",".$vcplogicArray[$vcpl];
				}*/
				
			}
		}
	} 
	$vdpSql = " Select c.* from
				Checkpoints c where c.CheckpointId in ($vcplogicString)";
					
	$vdpQuery = mysqli_query($conn,$vdpSql);
	while($vdp = mysqli_fetch_assoc($vdpQuery)){
		$vdpObj = "";
		$vdpObj->chkpId = $vdp['CheckpointId'];
		$vdpObj->description = $vdp['Description'];
		$vdpObj->value = $vdp['Value'];
		$vdpObj->typeId = $vdp['TypeId'];
		$vdpObj->mandatory = $vdp['Mandatory'];
		$vdpObj->Editable = $vdp['Editable'];
		$vdpObj->correct = $vdp['Correct'];
		$vdpObj->size = $vdp['Size'];
		$vdpObj->Active = $vdp['Active'];
		$vdpObj->Is_Dept = $vdp['Dependent'];
		$vdpObj->Logic = "";
		$vdpObj->answer = "";
		array_push($cpArray,$vdpObj);
	}
		
		//$vcpObj->dependent = $vdpArray;
		
	
	
	$vObj->value = $cpArray;
	array_push($wrappedListArray,$vObj);
}
echo json_encode($wrappedListArray);
?>