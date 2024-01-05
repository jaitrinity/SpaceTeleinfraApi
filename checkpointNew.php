<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];
$roleId=$_REQUEST['roleId'];
$sql = "SELECT `MenuId` FROM `Mapping` WHERE `EmpId` = '$empId'  and `Active` = 1 ";
$query=mysqli_query($conn,$sql);

$menuArr = array();
while($row = mysqli_fetch_assoc($query)){
	$menuId = $row["MenuId"];
	if(!in_array($menuId, $menuArr)){
		array_push($menuArr,$menuId);
	}
}

$roleSql = "SELECT `MenuId` FROM `Role` WHERE `RoleId` = '$roleId' ";
$roleQuery=mysqli_query($conn,$roleSql);
while($roleRow = mysqli_fetch_assoc($roleQuery)){
	$roleMenuId = $roleRow["MenuId"];
	$roleMenuIdExplode = explode(",", $roleMenuId);
	for($i=0;$i<count($roleMenuIdExplode);$i++){
		if(!in_array($roleMenuIdExplode[$i], $menuArr)){
			array_push($menuArr,$roleMenuIdExplode[$i]);
		}
	}
	
}

$ssignSql = "SELECT distinct `MenuId` FROM `Assign` WHERE `EmpId` = '$empId' AND date(`StartDate`) <= date(now()) AND date(`EndDate`) >= date(now()) AND `ActivityId` is  null 
AND `Active` = 1 ";
$assignQuery=mysqli_query($conn,$ssignSql);
while($assignRow = mysqli_fetch_assoc($assignQuery)){
	$assignMenuId = $assignRow["MenuId"];
	if(!in_array($assignMenuId, $menuArr)){
		array_push($menuArr,$assignMenuId);
	}
}

$chkIdArr = array();
$loginChkIdArr = array();
for ($x = 0; $x < count($menuArr); $x++) {
	$menuSql = "SELECT `CheckpointId`,`Verifier`,`Approver` FROM `Menu` WHERE `MenuId` = $menuArr[$x] and `Active` = 1 ";
	$menuQuery=mysqli_query($conn,$menuSql);
	while($menuRow = mysqli_fetch_assoc($menuQuery)){
		$chkId = $menuRow["CheckpointId"];
		$verifier = $menuRow["Verifier"];
		$approver = $menuRow["Approver"];
		$chkId = str_replace(":",",",$chkId);
		$c = explode(",",$chkId);
		$v = explode(",",$verifier);
		$a = explode(",",$approver);
		for ($y = 0; $y < count($c); $y++) {
			if(!in_array($c[$y], $chkIdArr)){
				array_push($chkIdArr,$c[$y]);
			}
		}
		for ($y = 0; $y < count($v); $y++) {
			if(!in_array($v[$y], $chkIdArr)){
				array_push($chkIdArr,$v[$y]);
			}
		}
		for ($y = 0; $y < count($a); $y++) {
			if(!in_array($a[$y], $chkIdArr)){
				array_push($chkIdArr,$a[$y]);
			}
		}

	}
}
$responseArr = "";

for ($x = 0; $x < count($chkIdArr); $x++) {
	$chkpointSql = "SELECT * FROM `Checkpoints` WHERE `CheckpointId` = $chkIdArr[$x]  ";
	$chkpointQuery=mysqli_query($conn,$chkpointSql);
	while($chkpointRow = mysqli_fetch_assoc($chkpointQuery)){
		//$obj = "";
		$json = "";
		//$json -> chkpId = $chkIdArr[$x];
		
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
		$responseArr->{$chkIdArr[$x]} = $json;
		//array_push($responseArr,$json);
		//array_push($responseArr,$obj);
		// getting of login checkpint id in loginChkIdArr
		$logic = explode("::", $chkpointRow["Logic"]);
		for ($y = 0; $y < count($logic); $y++) {
			$logic1 = explode(",", $logic[$y]);
			for ($z = 0; $z < count($logic1); $z++) {
				if(!in_array($logic1[$z], $chkIdArr) && !in_array($logic1[$z], $loginChkIdArr) ){
					array_push($loginChkIdArr,$logic1[$z]);
				}
			}
		}
	}	
}

$obj1 = "";
// get chechpoint of logic
for ($x = 0; $x < count($loginChkIdArr); $x++) {
	$chkpointSql = "SELECT * FROM `Checkpoints` WHERE `CheckpointId` = $loginChkIdArr[$x] ";
	$chkpointQuery=mysqli_query($conn,$chkpointSql);
	while($chkpointRow = mysqli_fetch_assoc($chkpointQuery)){
		
		$json = "";
		//$json -> chkpId = $loginChkIdArr[$x];
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
		//$obj1->{$loginChkIdArr[$x]} = $json;
		$responseArr->{$chkIdArr[$x]} = $json;
		//array_push($responseArr,$json);
		//array_push($responseArr,$obj);
	}
}
//array_push($responseArr,$obj);
echo json_encode($responseArr);
?>