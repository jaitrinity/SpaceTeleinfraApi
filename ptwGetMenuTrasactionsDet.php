<?php
include("dbConfiguration.php");
$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$loginEmpId = $jsonData->loginEmpId;
$fillingByEmpId = $jsonData->fillingByEmpId;
$menuId = $jsonData->menuId;
$transactionId = $jsonData->transactionId;
$verifierTId = $jsonData->verifierTId;
$approvedTId = $jsonData->approvedTId;
$thirdActivityId = $jsonData->thirdActivityId;
$fourthActivityId = $jsonData->fourthActivityId;
$fifthActivityId = $jsonData->fifthActivityId;
$sixthActivityId = $jsonData->sixthActivityId;
$status = $jsonData->status;

$refIdSql = "SELECT `RefId` FROM `Activity` where `ActivityId` = $transactionId";
$refIdQuery = mysqli_query($conn,$refIdSql);
$refIdRow = mysqli_fetch_assoc($refIdQuery);
$refId = $refIdRow["RefId"];

$vendorSql = "SELECT e.Name as RaiseName, (case when e1.Name is null then e.Name else e1.Name end) as VendorName FROM Employees_Reference e left join Employees_Reference e1 on e.RMId = e1.EmpId WHERE e.EmpId = '$fillingByEmpId' and e.RefId = $refId";
$vendorQuery = mysqli_query($conn,$vendorSql);
$vendorRow = mysqli_fetch_assoc($vendorQuery);
$vendorName = $vendorRow["VendorName"];

$checkpointsList = [];
$cpSql = "select distinct `CheckpointId` from `Menu` where `MenuId` in ($menuId) ORDER BY FIELD(`MenuId`,$menuId) ";
$cpQuery=mysqli_query($conn,$cpSql);
while($cpRow = mysqli_fetch_assoc($cpQuery)){
	$cId = $cpRow["CheckpointId"];
	$cId = str_replace(":", ",", $cId);
	// echo $cId;
	$explodeCid = explode(",", $cId);

	for($i = 0;$i<count($explodeCid); $i++){
		$loopCId = $explodeCid[$i];
		$sql2 = "SELECT `Description`,`TypeId` FROM `Checkpoints` where `CheckpointId` = $loopCId  ";
		$query2=mysqli_query($conn,$sql2);
		while($row2 = mysqli_fetch_assoc($query2)){
			$cpJson = "";
			$cpJson = array('checkpointId' => $loopCId, 'description' => $row2["Description"], 'typeId' => $row2["TypeId"]);
			array_push($checkpointsList,$cpJson);
		}

	}
}


$verifierCheckpointIdList = "";
$verifiedCpId = "";
$myRoleForTask = "";
$isVerifier = false;
$locationId = "";
if($verifierTId == null){
	$verifierSql = "SELECT * FROM `Mapping` where `ActivityId` = '$transactionId' and `MenuId` = '$menuId' and find_in_set('$loginEmpId',`Verifier`) <> 0 and `Active` = 1 ";
	$verifierQuery=mysqli_query($conn,$verifierSql);
	while($row1 = mysqli_fetch_assoc($verifierQuery)){
		$locationId = $row1["LocationId"];
	}
	// echo mysqli_num_rows($verifierQuery);
	if(mysqli_num_rows($verifierQuery) !=0){
		$isVerifier = true;
		if($isVerifier){
			$verifierCheckpointIdList = getVerifierAndApproverCheckpointId($conn,$menuId,'Verifier');
		}
		// $verifyDetList = getVerifierCheckpoint($conn, $menuId);
	}
}
else{
	$verifiedCpId = getVerifierAndApproverCheckpointId($conn,$menuId,'Verifier');
}


$approverCheckpointIdList = "";
$approvedCpId = "";
$isApprover = false;
if($approvedTId == null){
	$approverSql = "SELECT * FROM `Mapping` where `ActivityId` = '$transactionId' and `MenuId` = '$menuId' and `Approver` = '$loginEmpId' ";
	$approverQuery=mysqli_query($conn,$approverSql);
	if($locationId == ""){
		while($row1 = mysqli_fetch_assoc($approverQuery)){
			$locationId = $row1["LocationId"];
		}
	}
		
	if(mysqli_num_rows($approverQuery) !=0){
		$isApprover = true;
		if($isApprover){
			$approverCheckpointIdList = getVerifierAndApproverCheckpointId($conn,$menuId,'Approver');
		}
		// $approveDetList = getApproverCheckpoint($conn, $menuId);
	}
}
else{
	$approvedCpId = getVerifierAndApproverCheckpointId($conn,$menuId,'Approver');
}

$thirdCheckpointIdList = "";
$thirdCpId = "";
if($thirdActivityId == null){

}
else{
	$thirdCpId = getVerifierAndApproverCheckpointId($conn,$menuId,'Third');
}

$fourthCheckpointIdList = "";
$fourthCpId = "";
if($fourthActivityId == null){

}
else{
	$fourthCpId = getVerifierAndApproverCheckpointId($conn,$menuId,'Fourth');
}

$fifthCheckpointIdList = "";
$fifthCpId = "";
if($fifthActivityId == null){

}
else{
	$fifthCpId = getVerifierAndApproverCheckpointId($conn,$menuId,'Fifth');
}

$sixthCheckpointIdList = "";
$sixthCpId = "";
if($sixthActivityId == null){

}
else{
	$sixthCpId = getVerifierAndApproverCheckpointId($conn,$menuId,'Sixth');
}



$sql = "SELECT `Checkpoints`.`CheckpointId`, `Checkpoints`.`Description`, `TransactionDTL`.`Value`, `TransactionDTL`.`DependChkId`, 
`TransactionDTL`.`Lat_Long`, `TransactionDTL`.`Date_time`, `Checkpoints`.`Value` as cp_options, `Checkpoints`.`TypeId` FROM  `TransactionDTL` join `Checkpoints` on  `TransactionDTL`.`ChkId` = `Checkpoints`.`CheckpointId`  WHERE `TransactionDTL`.`ActivityId` = '$transactionId' 
order by `TransactionDTL`.`SRNo` ";
$query=mysqli_query($conn,$sql);

$dependCheckpointDetList = [];
while($roww = mysqli_fetch_assoc($query)){

	$checkpointIdd = $roww["CheckpointId"];
	$descriptionn = $roww["Description"];
	$valuee = $roww["Value"];
	$typeIdd = $roww["TypeId"];
	$dependChkIdd = $roww["DependChkId"];
	$imgLatLongg = explode(":", $roww["Lat_Long"])[0];
	$imgDatetimee = explode(",", $roww["Date_time"])[0] ;
	if($dependChkIdd != 0){
		$jsonDett = "";
		$jsonDett -> checkpointId = $checkpointIdd;
		$jsonDett -> checkpoint = $descriptionn;
		$jsonDett -> value = $valuee;
		$jsonDett -> imgLatLong = $imgLatLongg;
		$jsonDett -> imgDatetime = $imgDatetimee;
		$jsonDett -> typeId = $typeIdd;
		$jsonDett -> dependChkId = $dependChkIdd;
		
		array_push($dependCheckpointDetList,$jsonDett);
	}
}

mysqli_data_seek( $query, 0 );

$transactionDetList = [];
$sr = 1;
$index = 0;
for($ii = 0;$ii<count($checkpointsList);$ii++){
	$isFind = false;
	$cpId = $checkpointsList[$ii]["checkpointId"];
	$cpDescription = $checkpointsList[$ii]["description"];
	$cpTypeId = $checkpointsList[$ii]["typeId"];
	// mysqli_data_seek( $query, $index );
	mysqli_data_seek( $query, 0 );
	while($row = mysqli_fetch_array($query)){
		$checkpointId = $row["CheckpointId"];
		// echo $checkpointIdd.'----------';
		if($cpId == $checkpointId){
			$description = $row["Description"];
			$value = $row["Value"];
			$imgLatLong = explode(":", $row["Lat_Long"])[0];
			$imgDatetime = explode(",", $row["Date_time"])[0] ;
			$dependChkId = $row["DependChkId"];
			$cp_options = $row["cp_options"];
			$typeId = $row["TypeId"];

			$forVerifier = "No";
			$forApprover = "No";
			if($verifierCheckpointIdList != "") $forVerifier = "Yes";
			if($approverCheckpointIdList != "") $forApprover = "Yes";

			if($dependChkId == 0){
				$jsonDet = "";
				$jsonDet -> srNumber = $sr;
				$jsonDet -> checkpointId = $checkpointId;
				$jsonDet -> checkpoint = $description;
				$jsonDet -> value = $value;
				$jsonDet -> imgLatLong = $imgLatLong;
				$jsonDet -> imgDatetime = $imgDatetime;
				$jsonDet -> typeId = $typeId;
				$jsonDet -> forVerifier = $forVerifier;
				$jsonDet -> forApprover = $forApprover;

				array_push($transactionDetList,$jsonDet);

				$depSrNo = 1;
				for($j=0;$j<count($dependCheckpointDetList);$j++){
					$dependentChpId = $dependCheckpointDetList[$j]->checkpointId;
					$dependentChp = $dependCheckpointDetList[$j]->checkpoint;
					$dependenChpValue = $dependCheckpointDetList[$j]->value;
					$dependenTypeId = $dependCheckpointDetList[$j]->typeId;
					$dependenImgLatLong = $dependCheckpointDetList[$j]->imgLatLong;
					$dependenImgDatetime = $dependCheckpointDetList[$j]->imgDatetime;
					$dependenDependChkId = $dependCheckpointDetList[$j]->dependChkId;
					
					if($checkpointId == $dependenDependChkId){
						$jsonDettt = "";
						$jsonDettt -> srNumber = $sr.'.'.$depSrNo;
						$jsonDettt -> checkpointId = $dependentChpId;
						$jsonDettt -> checkpoint = $dependentChp;
						$jsonDettt -> value = $dependenChpValue;
						$jsonDettt -> imgLatLong = $dependenImgLatLong;
						$jsonDettt -> imgDatetime = $dependenImgDatetime;
						$jsonDettt -> typeId = $dependenTypeId;
						$jsonDettt -> forVerifier = $forVerifier;
						$jsonDettt -> forApprover = $forApprover;
						array_push($transactionDetList,$jsonDettt);

						$depSrNo++;
					}	
				}

				$sr++;
			}

			$index++;
			$isFind = true;
			break;
		}
	}

	if(!$isFind){
		$jsonDet = "";
		$jsonDet -> srNumber = "";
		$jsonDet -> checkpointId = $cpId;
		$jsonDet -> checkpoint = $cpDescription;
		$jsonDet -> value = "";
		$jsonDet -> imgLatLong = "";
		$jsonDet -> imgDatetime = "";
		$jsonDet -> typeId = $cpTypeId;
		$jsonDet -> forVerifier = "";
		$jsonDet -> forApprover = "";

		array_push($transactionDetList,$jsonDet);
	}
}


// this while is for show dependent checkpoint in new line with . seperater
// $transactionDetList = [];
// $sr = 1;
// while($row = mysqli_fetch_assoc($query)){
// 	$checkpointId = $row["CheckpointId"];
// 	$description = $row["Description"];
// 	$value = $row["Value"];
// 	$imgLatLong = $row["Latitude"].','.$row["Longitude"];
// 	$imgDatetime = $row["Date_time"];
// 	$dependChkId = $row["DependChkId"];
// 	$cp_options = $row["cp_options"];
// 	$typeId = $row["TypeId"];

// 	$forVerifier = "No";
// 	$forApprover = "No";
// 	if($verifierCheckpointIdList != "") $forVerifier = "Yes";
// 	if($approverCheckpointIdList != "") $forApprover = "Yes";

// 	if($dependChkId == 0){
// 		$jsonDet = "";
// 		$jsonDet -> srNumber = $sr;
// 		$jsonDet -> checkpointId = $checkpointId;
// 		$jsonDet -> checkpoint = $description;
// 		$jsonDet -> value = $value;
// 		$jsonDet -> imgLatLong = $imgLatLong;
// 		$jsonDet -> imgDatetime = $imgDatetime;
// 		$jsonDet -> typeId = $typeId;
// 		$jsonDet -> forVerifier = $forVerifier;
// 		$jsonDet -> forApprover = $forApprover;

// 		array_push($transactionDetList,$jsonDet);

// 		$depSrNo = 1;
// 		for($j=0;$j<count($dependCheckpointDetList);$j++){
// 			$dependentChpId = $dependCheckpointDetList[$j]->checkpointId;
// 			$dependentChp = $dependCheckpointDetList[$j]->checkpoint;
// 			$dependenChpValue = $dependCheckpointDetList[$j]->value;
// 			$dependenTypeId = $dependCheckpointDetList[$j]->typeId;
// 			$dependenImgLatLong = $dependCheckpointDetList[$j]->imgLatLong;
// 			$dependenImgDatetime = $dependCheckpointDetList[$j]->imgDatetime;
// 			$dependenDependChkId = $dependCheckpointDetList[$j]->dependChkId;
			
// 			if($checkpointId == $dependenDependChkId){
// 				$jsonDettt = "";
// 				$jsonDettt -> srNumber = $sr.'.'.$depSrNo;
// 				$jsonDettt -> checkpointId = $dependentChpId;
// 				$jsonDettt -> checkpoint = $dependentChp;
// 				$jsonDettt -> value = $dependenChpValue;
// 				$jsonDettt -> imgLatLong = $dependenImgLatLong;
// 				$jsonDettt -> imgDatetime = $dependenImgDatetime;
// 				$jsonDettt -> typeId = $dependenTypeId;
// 				$jsonDettt -> forVerifier = $forVerifier;
// 				$jsonDettt -> forApprover = $forApprover;
// 				array_push($transactionDetList,$jsonDettt);

// 				$depSrNo++;
// 			}	
// 		}

// 		$sr++;
// 	}
// }

// uncommect when dependend checkpoint will show logether
// $sr = 1;
// while($row = mysqli_fetch_assoc($query)){
// 	// echo "hi";
// 	$checkpointId = $row["CheckpointId"];
// 	$description = $row["Description"];
// 	$value = $row["Value"];
// 	$dependChkId = $row["DependChkId"];
// 	$cp_options = $row["cp_options"];
// 	$typeId = $row["TypeId"];

// 	$forVerifier = "No";
// 	$forApprover = "No";
// 	if($verifierCheckpointIdList != "") $forVerifier = "Yes";
// 	if($approverCheckpointIdList != "") $forApprover = "Yes";

// 	// echo $dependChkId;
// 	if($dependChkId == 0){
// 		$jsonDet = "";
// 		$jsonDet -> optionList = [];
// 		$jsonDet -> srNumber = $sr;
// 		$jsonDet -> typeId = $typeId;
// 		$jsonDet -> checkpointId = $checkpointId;
// 		$jsonDet -> checkpoint = $description;
// 		// $jsonDet -> options = "";
// 		$jsonDet -> value = $value;
// 		$jsonDet -> forVerifier = $forVerifier;
// 		$jsonDet -> forApprover = $forApprover;

// 		for($j=0;$j<count($dependCheckpointDetList);$j++){
// 			$dependentChpId = $dependCheckpointDetList[$j]->checkpointId;
// 			$dependentChp = $dependCheckpointDetList[$j]->checkpoint;
// 			$dependenChpValue = $dependCheckpointDetList[$j]->value;
// 			$dependenDependChkId = $dependCheckpointDetList[$j]->dependChkId;

// 			// echo $checkpointId.'-----------'.$dependenDependChkId;

// 			if($checkpointId == $dependenDependChkId){

// 				$jsonDet -> dependentChpId = $dependentChpId;
// 				$jsonDet -> dependentChp = $dependentChp;
// 				$jsonDet -> dependenChpValue = $dependenChpValue;
// 			}	
// 		}
// 		array_push($transactionDetList,$jsonDet);

// 		$sr++;
// 	}
// }
	

$pendingForApprove = "No";
$pendingForVerify = "No";

$myRoleForTask = "";
if($isVerifier){
	$myRoleForTask = "Verifier";
}
else if($isApprover){
	$myRoleForTask = "Approver";
}
if($isVerifier && ($status == "Created" || $status == "PTW_01")){
	$pendingForApprove = "Yes";
	$pendingForVerify = "Yes";
}
if($isVerifier && $status == "Verified"){
	$pendingForApprove = "Yes";
}
if($isApprover && $status == "Created"){
	$pendingForApprove = "Yes";
	$pendingForVerify = "Yes";
}
if($isApprover && $status == "Verified"){
	$pendingForApprove = "Yes";
}


$verifyDetList = [];
$approveDetList = [];
$thirdDetList = [];
$fourthDetList = [];
$fifthDetList = [];
$sixthDetList = [];
if($verifierTId != null)
$verifyDetList = prepareStatusDet($conn,$verifierTId,$verifiedCpId);

if($approvedTId != null)
$approveDetList = prepareStatusDet($conn,$approvedTId,$approvedCpId);

if($thirdActivityId != null)
$thirdDetList = prepareStatusDet($conn,$thirdActivityId,$thirdCpId);

$auditDetList = [];
if($menuId == 303 || $menuId == 304 || $menuId == 305 || $menuId == 306 || $menuId == 307 || $menuId == 308 || $menuId == 309 || $menuId == 310){
	if($fourthActivityId != null)
		$fourthDetList = prepareStatusDet($conn,$fourthActivityId,$fifthCpId);

	$auditSql = "SELECT p.ActivityId, p.AuditActivityId, a.MobileDateTime, a.EmpId, e.Name, a.MenuId, m.Fourth as AuditChkId FROM PTWAudit p join Activity a on p.AuditActivityId = a.ActivityId join Employees_Reference e on a.EmpId = e.EmpId and a.RefId = e.RefId join Menu m on a.MenuId = m.MenuId where p.ActivityId = $transactionId ORDER by p.AuditActivityId";
	$auditResult = mysqli_query($conn,$auditSql);
	while($auditRow = mysqli_fetch_assoc($auditResult)){
		$auditBy = $auditRow["Name"];
		$auditDate = $auditRow["MobileDateTime"];
		$auditActId = $auditRow["AuditActivityId"];
		$auditChkId = $auditRow["AuditChkId"];
		$auditChkDet = prepareStatusDet($conn,$auditActId,$auditChkId);
		$auditJson = array('auditBy' => $auditBy, 'auditDate' => $auditDate, 'auditChkDet' => $auditChkDet);
		array_push($auditDetList, $auditJson);
	}

	if($sixthActivityId != null)
		$sixthDetList = prepareStatusDet($conn,$sixthActivityId,$sixthCpId);
}
else{
	if($fourthActivityId != null)
		$fourthDetList = prepareStatusDet($conn,$fourthActivityId,$fourthCpId);

	if($fifthActivityId != null)
		$fifthDetList = prepareStatusDet($conn,$fifthActivityId,$fifthCpId);

	if($sixthActivityId != null)
		$sixthDetList = prepareStatusDet($conn,$sixthActivityId,$sixthCpId);
}
	

$actionCheckpointList = [];
if($pendingForVerify == "Yes" && $verifierCheckpointIdList != ""){
	$actionCheckpointList = prepareActionCheckpointDet($conn, $verifierCheckpointIdList);
}
else if($pendingForVerify == "No" && $pendingForApprove == "Yes" && $approverCheckpointIdList != ""){
	$actionCheckpointList = prepareActionCheckpointDet($conn, $approverCheckpointIdList);
}

$output = array();
$wrappedList = [];

$json = "";
$json -> pendingForApprove = $pendingForApprove;
$json -> menuId = $menuId;
$json -> transactionId = $activityId;
$json -> dateTime = $serverDateTime;
$json -> myRoleForTask = $myRoleForTask;
$json -> transactionDetList = $transactionDetList;
$json -> verifyDetList = $verifyDetList;
$json -> approveDetList = $approveDetList;
$json -> thirdDetList = $thirdDetList;
$json -> fourthDetList = $fourthDetList;
$json -> fifthDetList = $fifthDetList;
$json -> sixthDetList = $sixthDetList;
$json -> auditDetList = $auditDetList;
// $json -> topFirstCheckpointDesc = "";
// $json -> topThirdCheckpointDesc = "";
// $json -> verifiedBy = "";
// $json -> approvedBy = "";
// $json -> topFirstKey = "";
// $json -> topSecondCheckpointValue = "";
$json -> actionCheckpointList = $actionCheckpointList;
// $json -> topSecondKey = "";
// $json -> topFirstCheckpointValue = "";
$json -> pendingForVerify = $pendingForVerify;
$json -> locationId = $locationId;
// $json -> verifierTId = "";
// $json -> approvedTId = "";
// $json -> topSecondCheckpointDesc = "";
// $json -> topThirdCheckpointValue = "";
// $json -> topThirdKey = "";
// $json -> status = "";
$json -> vendorName = $vendorName;
array_push($wrappedList,$json);

$output = array('wrappedList' => $wrappedList, 'responseDesc' => 'SUCCESSFUL', 'responseCode' => '100000', 'count' => $level);
echo json_encode($output);
?>

<?php
function prepareActionCheckpointDet($conn, $commaSeparateCp){
	$actionCheckpointList = [];
	$explodeVcp = implode(',',explode(",", $commaSeparateCp));
	$checkpointSql = "SELECT * FROM `Checkpoints` where `CheckpointId` in ($explodeVcp) ORDER BY FIELD(`CheckpointId`,$explodeVcp) ";
	// echo $checkpointSql;
	$checkpointQuery=mysqli_query($conn,$checkpointSql);
	$sr = 1;
	while($checkpointRow = mysqli_fetch_assoc($checkpointQuery)){
		$checkpointId = $checkpointRow["CheckpointId"];
		// echo $checkpointId;
		$description = $checkpointRow["Description"];
		$value = $checkpointRow["Value"];
		$typeId = $checkpointRow["TypeId"];
		$dependent = $checkpointRow["Dependent"];
		$logic = $checkpointRow["Logic"];
		$size = $checkpointRow["Size"];

		$logicCpArr = array();
		if($dependent == 1){
			$logicCheckpoint = explode(":", $logic);
			for($j=0;$j<count($logicCheckpoint);$j++){
				if($logicCheckpoint[$j] != " "){
					$explodeLogicCheckpoint = explode(",", $logicCheckpoint[$j]);
					for($jj=0;$jj<count($logicCheckpoint);$jj++){
						$logicCpSql = "SELECT * FROM `Checkpoints` where `CheckpointId` in (".$logicCheckpoint[$jj].") ORDER BY FIELD(`CheckpointId`,$logicCheckpoint[$jj]) ";
						$logicCpQuery=mysqli_query($conn,$logicCpSql);
						while($logicCpRow = mysqli_fetch_assoc($logicCpQuery)){
							if($logicCpRow['IsSql'] == 1){
								$valueSql = $logicCpRow["Value"];
		    					$stmt = mysqli_prepare($conn,$valueSql);
		    					mysqli_stmt_bind_param($stmt, 'si', $empId,$tenentId);
							    mysqli_stmt_execute($stmt);
							    mysqli_stmt_store_result($stmt);
							    mysqli_stmt_bind_result($stmt,$project);
							    if(mysqli_stmt_num_rows($stmt) > 0){
							       $valueArray = array();
							       while($v = mysqli_stmt_fetch($stmt)){
							            array_push($valueArray,$project);
							       }
							       $sqlValue = implode(',',$valueArray); 
								
							    }
							    else{
							        $sqlValue = "";    
							    }
							    mysqli_stmt_close($stmt);

								$logicJson = array(
									'checkpointId' => $logicCpRow["CheckpointId"],
									'description' => $logicCpRow["Description"],
									'value' => $sqlValue,
									'typeId' => $logicCpRow["TypeId"]

								);
								array_push($logicCpArr,$logicJson);
							}
							else{
								$logicJson = array(
									'checkpointId' => $logicCpRow["CheckpointId"],
									'description' => $logicCpRow["Description"],
									'value' => $logicCpRow["Value"],
									'typeId' => $logicCpRow["TypeId"]

								);
								array_push($logicCpArr,$logicJson);
							}
							
						}
					}
				}
					
			}
				
		}

		$jsonDet = "";
		$jsonDet -> srNumber = $sr;
		$jsonDet -> typeId = $typeId;
		$jsonDet -> checkpointId = $checkpointId;
		$jsonDet -> checkpoint = $description;
		$jsonDet -> value = $value;
		$jsonDet -> size = $size;
		$jsonDet -> logic = $logic;
		$jsonDet -> logicCpArr = $logicCpArr;
		
		array_push($actionCheckpointList,$jsonDet);

		$sr++;
	}

	return $actionCheckpointList;
}

function prepareStatusDet($conn, $transId, $cpId){
	// $sql = "SELECT `Checkpoints`.`CheckpointId`, `Checkpoints`.`Description`, `TransactionDTL`.`Value`, `TransactionDTL`.`DependChkId`, `TransactionDTL`.`Lat_Long`, `TransactionDTL`.`Date_time`, `Checkpoints`.`Value` as cp_options, `Checkpoints`.`TypeId` FROM  `Checkpoints` left join `TransactionDTL` on  `Checkpoints`.`CheckpointId` = `TransactionDTL`.`ChkId` and `TransactionDTL`.`ActivityId` = $transId  WHERE `Checkpoints`.`CheckpointId` in ($cpId) ORDER BY FIELD(`Checkpoints`.`CheckpointId`,$cpId) ";

	$sql = "SELECT `Checkpoints`.`CheckpointId`, `Checkpoints`.`Description`, `TransactionDTL`.`Value`, `TransactionDTL`.`DependChkId`, `TransactionDTL`.`Lat_Long`, `TransactionDTL`.`Date_time`, `Checkpoints`.`Value` as cp_options, `Checkpoints`.`TypeId` FROM  `TransactionDTL` join `Checkpoints` on  `TransactionDTL`.`ChkId` = `Checkpoints`.`CheckpointId`  WHERE `TransactionDTL`.`ActivityId` = '$transId' order by `TransactionDTL`.`SRNo`";
	

	$query=mysqli_query($conn,$sql);

	$dependCheckpointDetList = [];
	while($roww = mysqli_fetch_assoc($query)){
		$checkpointIdd = $roww["CheckpointId"];
		$descriptionn = $roww["Description"];
		$valuee = $roww["Value"];
		$imgLatLongg = explode(":", $roww["Lat_Long"])[0];
		$imgDatetimee = explode(",", $roww["Date_time"])[0];
		$typeIdd = $roww["TypeId"];
		$dependChkIdd = $roww["DependChkId"];
		if($dependChkIdd != 0){
			$jsonDett = "";
			$jsonDett -> checkpointId = $checkpointIdd;
			$jsonDett -> checkpoint = $descriptionn;
			$jsonDett -> value = $valuee;
			$jsonDett -> typeId = $typeIdd;
			$jsonDett -> imgLatLong = $imgLatLongg;
			$jsonDett -> imgDatetime = $imgDatetimee;
			$jsonDett -> dependChkId = $dependChkIdd;
			
			array_push($dependCheckpointDetList,$jsonDett);
		}
	}

	mysqli_data_seek( $query, 0);

	

	$statusDetList = [];
	$sr = 1;
	while($row = mysqli_fetch_assoc($query)){

		$imgLatLong = explode(":", $row["Lat_Long"])[0] ;
		$imgDatetime = explode(",", $row["Date_time"])[0] ;
		$dependChkId = $row["DependChkId"];
		if($dependChkId == 0){
			$json = "";
			$json -> optionList = [];
			$json -> srNumber = $sr;
			$json -> typeId = $row["TypeId"];
			$json -> checkpointId = $row["CheckpointId"];
			$json -> checkpoint = $row["Description"];
			// $json -> forApprover = "";
			// $json -> forVerifier = "";
			$json -> options = $row["cp_options"];
			$json -> value = $row["Value"];
			$json -> imgLatLong = $imgLatLong;
			$json -> imgDatetime = $imgDatetime;

			array_push($statusDetList,$json);

			$depSrNo = 1;
			for($j=0;$j<count($dependCheckpointDetList);$j++){
				$dependentChpId = $dependCheckpointDetList[$j]->checkpointId;
				$dependentChp = $dependCheckpointDetList[$j]->checkpoint;
				$dependenChpValue = $dependCheckpointDetList[$j]->value;
				$dependenTypeId = $dependCheckpointDetList[$j]->typeId;
				$dependenDependChkId = $dependCheckpointDetList[$j]->dependChkId;
				$dependenImgLatLong = $dependCheckpointDetList[$j]->imgLatLong;
				$dependenImgDatetime = $dependCheckpointDetList[$j]->imgDatetime;

				if($row["CheckpointId"] == $dependenDependChkId){
					$jsonDettt = "";
					$jsonDettt -> srNumber = $sr.'.'.$depSrNo;
					// $jsonDettt -> srNumber = $checkpointId.'.'.$dependentChpId;
					$jsonDettt -> checkpointId = $dependentChpId;
					$jsonDettt -> checkpoint = $dependentChp;
					$jsonDettt -> value = $dependenChpValue;
					$jsonDettt -> typeId = $dependenTypeId;
					// $jsonDettt -> forVerifier = $forVerifier;
					// $jsonDettt -> forApprover = $forApprover;
					// $jsonDet -> dependenChpValue = $dependenChpValue;
					$jsonDettt -> imgLatLong = $dependenImgLatLong;
					$jsonDettt -> imgDatetime = $dependenImgDatetime;
					array_push($statusDetList,$jsonDettt);

					$depSrNo++;
				}	
			}

			$sr++;
		}		

	}

	return $statusDetList;

}

function getVerifierCheckpoint($conn, $menuId){
	$verifyDetList = [];
	$sql = "SELECT `Verifier` FROM `Menu` where `MenuId` = '$menuId' ";
	$query=mysqli_query($conn,$sql);
	// echo mysqli_num_rows($query);
	while($row = mysqli_fetch_assoc($query)){
		$verifierCheckpoint = $row["Verifier"];

		$explodeVcp = implode(',',explode(",", $verifierCheckpoint));
		$checkpointSql = "SELECT * FROM `Checkpoints` where `CheckpointId` in ($explodeVcp) ORDER BY FIELD(`CheckpointId`,$explodeVcp)  ";
		$checkpointQuery=mysqli_query($conn,$checkpointSql);
		$sr = 1;
		while($checkpointRow = mysqli_fetch_assoc($checkpointQuery)){
			$checkpointId = $checkpointRow["CheckpointId"];
			// echo $checkpointId;
			$description = $checkpointRow["Description"];
			$value = $checkpointRow["Value"];
			$typeId = $checkpointRow["TypeId"];

			$jsonDet = "";
			$jsonDet -> srNumber = $sr;
			$jsonDet -> typeId = $typeId;
			$jsonDet -> checkpointId = $checkpointId;
			$jsonDet -> checkpoint = $description;
			$jsonDet -> value = $value;
			
			array_push($verifyDetList,$jsonDet);

			$sr++;
		}

	}

	return $verifyDetList;


}
function getApproverCheckpoint($conn, $menuId){
	$approveDetList = [];
	$sql = "SELECT `Approver` FROM `Menu` where `MenuId` = '$menuId' ";
	$query=mysqli_query($conn,$sql);
	while($row = mysqli_fetch_assoc($query)){
		$approverCheckpoint = $row["Approver"];

		$explodeAcp = implode(',',explode(",", $approverCheckpoint));

		$checkpointSql = "SELECT * FROM `Checkpoints` where `CheckpointId` in ($explodeAcp) ORDER BY FIELD(`CheckpointId`,$explodeAcp)  ";
		$checkpointQuery=mysqli_query($conn,$checkpointSql);
		$sr = 1;
		while($checkpointRow = mysqli_fetch_assoc($checkpointQuery)){
			$checkpointId = $checkpointRow["CheckpointId"];
			$description = $checkpointRow["Description"];
			$value = $checkpointRow["Value"];
			$typeId = $checkpointRow["TypeId"];

			$jsonDet = "";
			$jsonDet -> srNumber = $sr;
			$jsonDet -> typeId = $typeId;
			$jsonDet -> checkpointId = $checkpointId;
			$jsonDet -> checkpoint = $description;
			$jsonDet -> value = $value;
			
			array_push($approveDetList,$jsonDet);

			$sr++;


		}

	}

	return $approveDetList;

}

function getVerifierAndApproverCheckpointId($conn, $menuId, $type){
	$checkpointList = "";
	// $sql = "";
	// if($type == "Verifier"){
	// 	$sql = "SELECT DISTINCT `Verifier` as checkpointId FROM `Menu` where `MenuId` = $menuId ";
	// }
	// else if($type == "Approver"){
	// 	$sql = "SELECT DISTINCT `Approver` as checkpointId FROM `Menu` where `MenuId` = $menuId ";
	// }

	$sql = "SELECT DISTINCT `$type` as checkpointId FROM `Menu` where `MenuId` = $menuId ";

	$query=mysqli_query($conn,$sql);
	while($row = mysqli_fetch_assoc($query)){
		$checkpointList = $row["checkpointId"];
	}
	return $checkpointList;
}
?>