<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];

$wrappedListArray = array();

$approverSql = "Select mp.ActivityId,mp.MenuId,mp.LocationId,l.Name,l.GeoCoordinates,m.Verifier,m.Approver,mp.Start,mp.End,
				m.Caption,m.Sub,m.Cat,m.Icons,m.CheckpointId,h.VerifierActivityId
				from Mapping mp
				join TransactionHDR h on (mp.ActivityId = h.ActivityId)
				join Menu m on (m.MenuId = mp.MenuId)
				join Location l on (mp.LocationId = l.LocationId)
				where mp.ActivityId !=0 and mp.Approver = '$empId' and h.Status = 'Verified'
				and h.ApproverActivityId is null";				

$approverQuery=mysqli_query($conn,$approverSql);

while($ap = mysqli_fetch_assoc($approverQuery)){
	$apObj = "";
	$apObj->menuId = $ap["MenuId"];
	$apObj->locationId = $ap["LocationId"];
	$apObj->startDate = $ap["Start"];
	$apObj->endDate = $ap["End"];
	$apObj->assignId = "";
	$apObj->name = $ap["Name"];
	$apObj->latlong = $ap["GeoCoordinates"];
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
	
	
	
	$apObj->checkpointId = $ap['CheckpointId'].':'.$ap['Verifier'].':'.$ap['Approver'];
	
	$apDataSend = "";
	
	if($ap['CheckpointId'] != ""){
		$apcpIdArray1 = explode(":",$ap['CheckpointId']);	
		for($apcpId = 0; $apcpId < count($apcpIdArray1); $apcpId++){
			if($apcpId == 0){
				$apDataSend .= "0";
			}
			else{
				$apDataSend .= ":0";
			}	
		}
	}
	if($ap['Verifier'] != ""){
		$apcpIdArray2 = explode(":",$ap['Verifier']);
		for($apcpId = 0; $apcpId < count($apcpIdArray2); $apcpId++){
			$apDataSend .= ":0";	
		}
	}
	if($ap['Approver'] != ""){
		$apcpIdArray3 = explode(":",$ap['Approver']);
		for($apcpId = 0; $apcpId < count($apcpIdArray3); $apcpId++){
			$apDataSend .= ":1";	
		}
	}
	
	$apObj->isDataSend = $apDataSend;
	
	$cpArray = array();
	$filledCpString = str_replace(":",",",$ap['CheckpointId']);
	$verifierCpString = str_replace(":",",",$ap['Verifier']);
	$approverCpString = str_replace(":",",",$ap['Approver']);
	$filledcpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$ap['ActivityId']."' and d.DependChkId = 0
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
							where d.ActivityId = '".$ap['ActivityId']."' and d.DependChkId = (".$fcp['CheckpointId'].")
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
	
	$verifiercpSql = "Select r2.*,r1.* 
					 from
					 (Select d.ChkId,d.Value as answer from TransactionDTL d
					 where d.ActivityId = '".$ap['VerifierActivityId']."' and d.DependChkId = 0
					 )r1
					 right join 
					 (Select c.* from Checkpoints c
					 where c.CheckpointId in ($verifierCpString)
					 ) r2 on (r1.ChkId = r2.CheckpointId)";
	while($vcp = mysqli_fetch_assoc($verifiercpQuery)){
		$vcpObj = "";
		$vcpObj->Chkp_Id = $vcp['CheckpointId'];
		$vcpObj->editable = '0';
		if($vcpObj['answer'] != null){
			$vcpObj->value = $vcp['answer'];
		}
		else{
			$vcpObj->value = "";
		}
		
		$vdpArray = array();
		if($vcp['Dependent'] == "1"){
			$vdpSql = " Select r1.*,c.* from
							(Select d.ChkId,d.Value from TransactionDTL d
							where d.ActivityId = '".$ap['VerifierActivityId']."' and d.DependChkId = (".$vcp['CheckpointId'].")
							) r1
							join Checkpoints c on (r1.ChkId = c.CheckpointId)";
							
			$vdpQuery = mysqli_query($conn,$vdpSql);
			while($vdp = mysqli_fetch_assoc($vdpQuery)){
				$vdpObj = "";
				$vdpObj->Chkp_Id = $vdp['CheckpointId'];
				$vdpObj->editable = '0';
				$vdpObj->value = $vdp['answer'];
				array_push(vdpObj);
			}
		}
		$fcpObj->Dependents = $vdpArray;
		array_push($cpArray,$vcpObj);
	}



	
	 $approvercpSql = "Select c.* from Checkpoints c where c.CheckpointId in ($approverCpString)";
	//echo $verifiercpSql;
	$approvercpQuery=mysqli_query($conn,$approvercpSql);
	while($apcp = mysqli_fetch_assoc($approvercpQuery)){
		$apcpObj = "";
		$apcpObj->Chkp_Id = $apcp['CheckpointId'];
		$apcpObj->editable = $apcp['Editable'];
		$apcpObj->value = "";
		$apdpArray = array();
		if($apcp['Dependent'] == "1"){
			$apcplogicArray = explode("::",$apcp['Logic']);
			$apcplogicString = "";
			for($apcpl=0;$vcpl< count($apcplogicArray);$apcpl++){
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
		array_push($cpArray,$apcpObj);
	} 
	
	$apcpObj->value = $cpArray;
	array_push($wrappedListArray,$apcpObj);
}
echo json_encode($wrappedListArray);
?>