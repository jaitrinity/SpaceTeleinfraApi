<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];
$roleId=$_REQUEST['roleId'];

$menuArr = array();

$roleSql = "SELECT distinct MenuId FROM Role WHERE RoleId = '$roleId' ";
$roleQuery=mysqli_query($conn,$roleSql);
while($roleRow = mysqli_fetch_assoc($roleQuery)){
	$roleMenuId = $roleRow['MenuId'];
	$roleMenuIdExplode = explode(",",$roleMenuId);
	for($i=0;$i<count($roleMenuIdExplode);$i++){
		array_push($menuArr,$roleMenuIdExplode[$i]);
	}
}

$newArr = array_unique($menuArr);
$newArr = array_values($newArr);

$menuIds = convertListInOperatorValue($newArr);
$chkIdString = "";
$menuSql = "SELECT `MenuId`,`CheckpointId` FROM `Menu` WHERE `MenuId` in ($menuIds)";
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
}


$newCpArr = array_unique(array($chkIdString));
$newCpArr = array_values($newCpArr);

$cpIds = convertListInOperatorValue($newCpArr);
$responseArr = array();

$chkpointSql = "SELECT * FROM `Checkpoints` WHERE `CheckpointId` in ($cpIds)";

	$chkpointQuery=mysqli_query($conn,$chkpointSql);
	while($chkpointRow = mysqli_fetch_assoc($chkpointQuery)){
		$json = "";
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
		$json -> answer = "";
	
		array_push($responseArr,$json);
		
		// getting of login checkpint id in loginChkIdArr
		
		if($chkpointRow["Logic"] != "" && $chkpointRow["Dependent"] == "1"){
			getDependentCpDetail($chkpointRow["Logic"]);
		}	
	}
	
echo json_encode($responseArr);

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

function getDependentCpDetail($logic){
	global $responseArr;
	global $conn;
	//echo "logic- ".$logic."\n";
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
				
				}
		
			}
		//	echo $logicChkIdString1."\n";
			if($logicChkIdString1 != ""){
		
				$logicChkIdArr1 = explode(",",$logicChkIdString1);
				$logicIds1 = convertListInOperatorValue($logicChkIdArr1);
				$newlogicArr1 = array_unique(array($logicIds1));
				$newlogicArr1 = array_values($newlogicArr1);
				
				$newlogicIds1 = convertListInOperatorValue($newlogicArr1);
				$logicSql = "SELECT * FROM `Checkpoints` WHERE `CheckpointId` in ($newlogicIds1) ";
				$logicQuery=mysqli_query($conn,$logicSql);
				while($logicRow = mysqli_fetch_assoc($logicQuery)){
					$logicJson = "";
					//$json -> chkpId = $logicRow["CheckpointId"];
					$logicJson -> chkpId = $logicRow["CheckpointId"];
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
					$logicJson -> answer = "";
				//	echo $logicRow["CheckpointId"]."\n";
					array_push($responseArr,$logicJson);
					//echo $responseArr[1]->chkpId;
					if($logicRow["Logic"] != "" && $logicRow["Dependent"] == "1"){
					//	echo "recursive function";
						getDependentCpDetail($logicRow["Logic"]);
					}
				}
				
			}
		
			//$json -> Logic = $chkpointLogicString;
	//return $responseArr;
}
?>