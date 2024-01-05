<?php
include("dbConfiguration.php");
$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$loginEmpId = $jsonData->loginEmpId;
$menuId = $jsonData->menuId;
$transactionId = $jsonData->transactionId;
$verifierTId = $jsonData->verifierTId;
$approvedTId = $jsonData->approvedTId;
$thirdActivityId = $jsonData->thirdActivityId;
$fourthActivityId = $jsonData->fourthActivityId;
$fifthActivityId = $jsonData->fifthActivityId;
$status = $jsonData->status;

if($menuId == 303 || $menuId == 304 || $menuId == 305 || $menuId == 306 || $menuId == 307 || $menuId == 308 || $menuId == 309 || $menuId == 310){
	$ptwResult = CallAPI("POST","http://www.trinityapplab.in/SpaceTeleinfra/ptwGetMenuTrasactionsDet.php",$json);
 	echo $ptwResult;
 	return;
}

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
	// $verifierSql = "SELECT * FROM `Mapping` where `ActivityId` = '$transactionId' and `MenuId` = '$menuId' and `Verifier` = '$loginEmpId' ";
	$verifierSql = "SELECT * FROM `Mapping` where `ActivityId` = '$transactionId' and `MenuId` = '$menuId' and find_in_set('$loginEmpId',`Verifier`) <> 0 ";
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
	// $approverSql = "SELECT * FROM `Mapping` where `ActivityId` = '$transactionId' and `MenuId` = '$menuId' and `Approver` = '$loginEmpId' ";
	$approverSql = "SELECT * FROM `Mapping` where `ActivityId` = '$transactionId' and `MenuId` = '$menuId' and find_in_set('$loginEmpId',`Approver`) <> 0 ";
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
		$jsonDett = new StdClass;
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
				$jsonDet = new StdClass;
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
						$jsonDettt = new StdClass;
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
		$jsonDet = new StdClass;
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
if($isVerifier && $status == "Created"){
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
if($verifierTId != null)
$verifyDetList = prepareStatusDet($conn,$verifierTId,$verifiedCpId);

if($approvedTId != null)
$approveDetList = prepareStatusDet($conn,$approvedTId,$approvedCpId);

if($thirdActivityId != null)
$thirdDetList = prepareStatusDet($conn,$thirdActivityId,$thirdCpId);

if($fourthActivityId != null)
$fourthDetList = prepareStatusDet($conn,$fourthActivityId,$fourthCpId);

if($fifthActivityId != null)
$fifthDetList = prepareStatusDet($conn,$fifthActivityId,$fifthCpId);

$actionCheckpointList = [];
if($pendingForVerify == "Yes" && $verifierCheckpointIdList != ""){
	$actionCheckpointList = prepareActionCheckpointDet($conn, $verifierCheckpointIdList);
}
else if($pendingForVerify == "No" && $pendingForApprove == "Yes" && $approverCheckpointIdList != ""){
	$actionCheckpointList = prepareActionCheckpointDet($conn, $approverCheckpointIdList);
}

$output = array();
$wrappedList = [];

$json = new StdClass;
$json -> pendingForApprove = $pendingForApprove;
$json -> menuId = $menuId;
// $json -> transactionId = $activityId;
// $json -> dateTime = $serverDateTime;
$json -> myRoleForTask = $myRoleForTask;
$json -> transactionDetList = $transactionDetList;
$json -> verifyDetList = $verifyDetList;
$json -> approveDetList = $approveDetList;
$json -> thirdDetList = $thirdDetList;
$json -> fourthDetList = $fourthDetList;
$json -> fifthDetList = $fifthDetList;
$json -> sixthDetList = [];
$json -> auditDetList = [];
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

array_push($wrappedList,$json);

// $output = array('wrappedList' => $wrappedList, 'responseDesc' => 'SUCCESSFUL', 'responseCode' => '100000', 'count' => $level);
$output = array('wrappedList' => $wrappedList, 'responseDesc' => 'SUCCESSFUL', 'responseCode' => '100000');
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

		$jsonDet = new StdClass;
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
			$jsonDett = new StdClass;
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
			$json = new StdClass;
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
					$jsonDettt = new StdClass;
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

			$jsonDet = new StdClass;
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

			$jsonDet = new StdClass;
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