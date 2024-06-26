<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
require 'EmployeeTenentId.php';
$empId=$_REQUEST['empId'];
$roleId=$_REQUEST['roleId'];

$empTenObj = new EmployeeTenentId();
$tenentId = $empTenObj->getTenentIdByEmpId($conn,$empId);

$menuArr = array();
if($roleId == 10){ // For admin
	$sql = "SELECT `MenuId` FROM `Menu` where `Tenent_Id` = $tenentId ";
	$query=mysqli_query($conn,$sql);
	while($row = mysqli_fetch_assoc($query)){
		array_push($menuArr,$row["MenuId"]);
	}
}
else{
	$sql = "SELECT `MenuId` FROM `Mapping` WHERE `EmpId` = '$empId' and `Tenent_Id` = $tenentId  and `Active` = 1 ";
	$query=mysqli_query($conn,$sql);
	while($row = mysqli_fetch_assoc($query)){
		$menuId = $row["MenuId"];
		array_push($menuArr,$menuId);
	}

	$verifierSql = "SELECT `MenuId` FROM `Mapping` WHERE find_in_set('$empId',`Verifier`) <> 0 and `Tenent_Id` = $tenentId  and `Active` = 1 ";

	$approverSql = "SELECT `MenuId` FROM `Mapping` WHERE `Approver` = '$empId' and `Tenent_Id` = $tenentId  and `Active` = 1 ";

	$thirdSql = "SELECT `MenuId` FROM `Mapping` WHERE `Third` = '$empId' and `Tenent_Id` = $tenentId  and `Active` = 1 ";

	$fourthSql = "SELECT `MenuId` FROM `Mapping` WHERE find_in_set('$empId',`Fourth`) <> 0 and `Tenent_Id` = $tenentId  and `Active` = 1 ";

	$fifthSql = "SELECT `MenuId` FROM `Mapping` WHERE `Fifth` = '$empId' and `Tenent_Id` = $tenentId  and `Active` = 1 ";

	$sixthSql = "SELECT `MenuId` FROM `Mapping` WHERE find_in_set('$empId',`Sixth`) <> 0 and `Tenent_Id` = $tenentId  and `Active` = 1 ";

	$all = "$verifierSql UNION $approverSql UNION $thirdSql UNION $fourthSql UNION $fifthSql UNION $sixthSql";
	
	$allQuery=mysqli_query($conn,$all);
	while($allRow = mysqli_fetch_assoc($allQuery)){
		$allMenuId = $allRow["MenuId"];
		array_push($menuArr,$allMenuId);
	}

	$roleSql = "SELECT distinct MenuId FROM Role WHERE RoleId = '$roleId' ";
	$roleQuery=mysqli_query($conn,$roleSql);
	while($roleRow = mysqli_fetch_assoc($roleQuery)){
		$roleMenuId = $roleRow['MenuId'];
		$roleMenuIdExplode = explode(",",$roleMenuId);
		for($i=0;$i<count($roleMenuIdExplode);$i++){
			array_push($menuArr,$roleMenuIdExplode[$i]);
		}
		
	}
}
	

$newArr = array_unique($menuArr);
$newArr = array_values($newArr);

$menuIds = convertListInOperatorValue($newArr);
$chkIdString = "";
$menuSql = "SELECT `MenuId`,`CheckpointId`,`Verifier`,`Approver`,`Third`,`Fourth`,`Fifth`,`Sixth` FROM `Menu` WHERE `MenuId` in ($menuIds)";
$menuQuery=mysqli_query($conn,$menuSql);
while($menuRow = mysqli_fetch_assoc($menuQuery)){
		$chkId = $menuRow["CheckpointId"];
		$chkId = str_replace(":",",",$chkId);
		if($chkIdString == ""){
				$chkIdString .= $chkId;
		}
		else{
			$chkIdString .= ",".$chkId;
		}
		
		if($menuRow["Verifier"] != ""){
			$verifier = $menuRow["Verifier"];	
			$verifier = str_replace(":",",",$verifier);
			$chkIdString .= ",".$verifier;
		}
		if($menuRow["Approver"] != ""){
			$approver = $menuRow["Approver"];	
			$approver = str_replace(":",",",$approver);
			$chkIdString .= ",".$approver;
		}
		if($menuRow["Third"] != ""){
			$third = $menuRow["Third"];	
			$third = str_replace(":",",",$third);
			$chkIdString .= ",".$third;
		}
		if($menuRow["Fourth"] != ""){
			$fourth = $menuRow["Fourth"];	
			$fourth = str_replace(":",",",$fourth);
			$chkIdString .= ",".$fourth;
		}
		if($menuRow["Fifth"] != ""){
			$fifth = $menuRow["Fifth"];	
			$fifth = str_replace(":",",",$fifth);
			$chkIdString .= ",".$fifth;
		}
		if($menuRow["Sixth"] != ""){
			$sixth = $menuRow["Sixth"];	
			$sixth = str_replace(":",",",$sixth);
			$chkIdString .= ",".$sixth;
		}

}


$newCpArr = array_unique(array($chkIdString));
$newCpArr = array_values($newCpArr);

$cpIds = convertListInOperatorValue($newCpArr);
$responseArr = array();

$chkpointSql = "SELECT * FROM `Checkpoints` WHERE `CheckpointId` in ($cpIds)";
//echo $chkpointSql ;

	$chkpointQuery=mysqli_query($conn,$chkpointSql);
	while($chkpointRow = mysqli_fetch_assoc($chkpointQuery)){
		$json = new StdClass;
		$json -> chkpId = $chkpointRow["CheckpointId"];
		$json -> description = $chkpointRow["Description"];
		$json -> value = $chkpointRow["Value"];
		$json -> typeId = $chkpointRow["TypeId"];
		$json -> mandatory = $chkpointRow["Mandatory"];
		$json -> editable = $chkpointRow["Editable"];
		$json -> correct = $chkpointRow["Correct"];
		$json -> size = $chkpointRow["Size"];
		$json -> Score = $chkpointRow["Score"];
		$json -> language = $chkpointRow["Language"];
		$json -> Active = $chkpointRow["Active"];
		$json -> Is_Dept = $chkpointRow["Dependent"];
		$json -> Logic = $chkpointRow["Logic"];
		$json -> isGeofence = $chkpointRow["IsGeofence"];
		$json -> answer = "";

		if($chkpointRow['IsSql'] == 1){
		   // $empId = "34";
		    $valueSql = $chkpointRow["Value"];
		    $stmt = mysqli_prepare($conn,$valueSql);
		    mysqli_stmt_bind_param($stmt, 'si', $empId,$tenentId);
		    // mysqli_stmt_bind_param($stmt, 'i', $tenentId);
		    mysqli_stmt_execute($stmt);
		    mysqli_stmt_store_result($stmt);
		    mysqli_stmt_bind_result($stmt,$project);
		    if(mysqli_stmt_num_rows($stmt) > 0){
		       $valueArray = array();
		       while($v = mysqli_stmt_fetch($stmt)){
		            array_push($valueArray,$project);
		       }
		       $json -> value =implode(',',$valueArray); 
			
		    }
		    else{
		        $json -> value = "";    
		    }
		    mysqli_stmt_close($stmt);
		}
		else{
		    $json -> value = $chkpointRow["Value"];    
		}
	
		
		// getting of login checkpint id in loginChkIdArr
		$logic = $chkpointRow["Logic"];
		$isDependent = $chkpointRow["Dependent"];
		if($logic != "" && $isDependent == "1"){
			$chkpointLogicString = "";
			$logicChkIdString1 = "";
			$logicChkIdArr1 = array();			
			$logicArray = explode(":",$logic);
			
			for($l=0; $l < count($logicArray);$l++){
				if(trim($logicArray[$l]," ")!= ""){
					
					if($logicChkIdString1 == ""){
						$logicChkIdString1 .= trim($logicArray[$l]," ");
					}
					else{
						$logicChkIdString1 .= ",".trim($logicArray[$l]," ");
					}
					$csLogicString = "";
					$commaseperatedlogicArray = explode(",",$logicArray[$l]);
					for($csl=0;$csl<count($commaseperatedlogicArray);$csl++){
						if($csLogicString != ""){
							$csLogicString .= ",".$chkpointRow["CheckpointId"]."_".$commaseperatedlogicArray[$csl];
						}
						else{
							$csLogicString .= $chkpointRow["CheckpointId"]."_".$commaseperatedlogicArray[$csl]; 
						}
					}
					if($chkpointLogicString != ""){
						$chkpointLogicString .= ":".$csLogicString;
					}
					else{
						$chkpointLogicString .= $csLogicString; 
					}
					
				}
				else{
					if($chkpointLogicString != ""){
						$chkpointLogicString .= ": ";
					}
					else{
						$chkpointLogicString .= " "; 
					}
				}
			}
			if($logicChkIdString1 != ""){
		
				$logicChkIdArr1 = explode(",",$logicChkIdString1);
				$logicIds1 = convertListInOperatorValue($logicChkIdArr1);
				$newlogicArr1 = array_unique(array($logicIds1));
				$newlogicArr1 = array_values($newlogicArr1);
				
				$newlogicIds1 = convertListInOperatorValue($newlogicArr1);
				$logicSql = "SELECT * FROM `Checkpoints` WHERE `CheckpointId` in ($newlogicIds1) ";
				$logicQuery=mysqli_query($conn,$logicSql);
				while($logicRow = mysqli_fetch_assoc($logicQuery)){
					$logicJson = new StdClass;
					//$json -> chkpId = $logicRow["CheckpointId"];
					$logicJson -> chkpId = $chkpointRow["CheckpointId"]."_".$logicRow["CheckpointId"];
					$logicJson -> description = $logicRow["Description"];
					$logicJson -> value = $logicRow["Value"];
					$logicJson -> typeId = $logicRow["TypeId"];
					$logicJson -> mandatory = $logicRow["Mandatory"];
					$logicJson -> editable = $logicRow["Editable"];
					$logicJson -> correct = $logicRow["Correct"];
					$logicJson -> size = $logicRow["Size"];
					$logicJson -> Score = $logicRow["Score"];
					$logicJson -> language = $logicRow["Language"];
					$logicJson -> Active = $logicRow["Active"];
					$logicJson -> Is_Dept = $logicRow["Dependent"];
					$logicJson -> Logic = $logicRow["Logic"];
					$logicJson -> isGeofence = $logicRow["IsGeofence"];
					$logicJson -> answer = "";
					if($logicRow['IsSql'] == 1){
					   // $empId = "34";
					    $valueSql = $logicRow["Value"];
					    $stmt = mysqli_prepare($conn,$valueSql);
					    mysqli_stmt_bind_param($stmt, 'si', $empId,$tenentId);
					    // mysqli_stmt_bind_param($stmt, 'i', $tenentId);
					    mysqli_stmt_execute($stmt);
					    mysqli_stmt_store_result($stmt);
					    mysqli_stmt_bind_result($stmt,$project);
					    if(mysqli_stmt_num_rows($stmt) > 0){
					       $valueArray = array();
					       while($v = mysqli_stmt_fetch($stmt)){
					            array_push($valueArray,$project);
					       }
					       $logicJson -> value =implode(',',$valueArray); 
						
					    }
					    else{
					        $logicJson -> value = "";    
					    }
					    mysqli_stmt_close($stmt);
					}
					else{
					    $logicJson -> value = $logicRow["Value"];    
					}
					array_push($responseArr,$logicJson);
				}
			}
		
			$json -> Logic = $chkpointLogicString;
		}
		array_push($responseArr,$json);
		
	}
	
echo json_encode($responseArr);
?>

<?php
function convertListInOperatorValue($arrName){
	$inOperatorValue = "";
	for ($x = 0; $x < count($arrName); $x++) {
		if($arrName[$x] != ""){
			if($x == 0){
				$inOperatorValue .= $arrName[$x];
			}
			else{
				$inOperatorValue .= ",".$arrName[$x];
			}	
		}
		
		
	}
	return $inOperatorValue;
}
?>