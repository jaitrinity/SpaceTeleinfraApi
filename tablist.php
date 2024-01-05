<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];
$roleId=$_REQUEST['roleId'];


// Check connection

if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
	exit();
}

mysqli_set_charset($conn,'utf8');



$res="";
	
$flag = 0;
$wrappedListArray = array();
$res->wrappedList = $wrappedListArray;
$res->responseCode = "0";
$res->responseMsg = "Failure";
$tabId = "";

$addBtnId = "0";

if($roleId != null){
	$getTabSql= "Select * from Role where RoleId = $roleId";
}
$getTabResult = mysqli_query($conn,$getTabSql);
if(count($getTabResult) > 0){
	$tr = mysqli_fetch_Array($getTabResult);
	$tabId = $tr['TabId'];
	//echo $tabId;
	if($tabId != null || $tabId != ""){
		$tabSql= "Select * from Tab where Id in ($tabId) and IsAddBtn = 0 and Active = 1 order by Seq";
		$tabResult = mysqli_query($conn,$tabSql);
		
		if(count($tabResult) > 0){
			while($t = mysqli_fetch_Array($tabResult)){
				$tObj = "";
				$tObj->tId = $t['Id'];
				$tObj->tabName = $t['TabName'];
				$tObj->IsVisible = $t['IsVisible'];
				$tObj->icon = $t['Icon'];
				$tObj->addBtnId = $t['AddBtnId'];
				array_push($wrappedListArray,$tObj);
			}	
			$flag = 1;
		}
		$addBtnSql= "Select * from Tab where Id in ($tabId) and IsAddBtn = 1 and Active = 1 order by Seq";
		$addBtnResult = mysqli_query($conn,$addBtnSql);
		
		if(count($addBtnResult) > 0){
			while($abr = mysqli_fetch_Array($addBtnResult)){
				$addBtnId = $abr['Id'];
			}	
			$flag = 1;
		}


	}
}



if($flag = 1){
	$res->wrappedList = $wrappedListArray;
	$res->addBtnId = $addBtnId;
	$res->responseCode = "200";
	$res->responseMsg = "Success";	
}

header('Content-type:application/json');
echo json_encode($res);
?>