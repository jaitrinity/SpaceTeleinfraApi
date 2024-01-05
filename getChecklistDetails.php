<?php

$json_str = file_get_contents('php://input');
$jsonw = json_decode($json_str,true);

require_once 'dbConfiguration.php';
//$conn = mysqli_connect("localhost","Demo","d@m0@123","Azure");
// for setting sql multilanguage selecting

mysqli_set_charset($conn,'utf8');

// Check connection

if (mysqli_connect_errno())

  {

	  echo "Failed to connect to MySQL: " . mysqli_connect_error();
	  exit();

  }

 ?> 
 
 <?php
 
 $empRole = $jsonw['role'];
 
 $json = "";
$json->returnCode = "0";
$json->returnMsg = "Failure";
$json->errorMsg = "";
$json->wrappedList = array();
$wrappedListArray = array();
$commonObj = "";
$cpArray = array();
$checklistArray = array();

 
 //$menuSql = "Select r.M_Id from Employee_Master e left join Role r on (e.Role_id = r.role) where e.Mobile_No = '9968421536'";
 $menuSql = "Select r.Mid from Role r  where r.Role = '$empRole'";
 $menuQuery = mysqli_query($conn,$menuSql);
 $menuCount = mysqli_num_rows($menuQuery);
 
 if($menuCount == 1){
	$m = mysqli_fetch_Array($menuQuery);
	$mId = $m['Mid'];
	
	$cpStringSql = "Select * from Menu m where m.MeId in ( $mId ) and Active = 1";
	$cpStringQuery = mysqli_query($conn,$cpStringSql);

	$cpString = "";
	
	$cpString1 = "";
	
	while($c = mysqli_fetch_Array($cpStringQuery)){
		$clObj = "";
		$clObj->mId = $c['MeId'];
		$clObj->category = $c['Cat'];
		$clObj->subCategory = $c['Sub'];
		$clObj->caption = $c['Caption'];
		$clObj->cpId = $c['ChkId'];
		$clObj->icon = $c['Icons'];
		$clObj->geofence = $c['GeoFence'];
		$clObj->verifier = $c['Verifier'];
		$clObj->approver = $c['Approver'];
		array_push($checklistArray,$clObj);
		
		$cpString .= str_replace(':','',$c['ChkId']).",";
	}
	
	$cpString1 = substr($cpString,0,strlen($cpString)-1);
	//echo "$scpString1";
	$cpSql = "Select * from Checkpoints c where c.ChkpId in ( $cpString1 )";
	//echo $cpSql;
	$cpQuery = mysqli_query($conn,$cpSql);
	
	$dependString = "0";
	while($cp = mysqli_fetch_Array($cpQuery)){
		
		$str1 = "";
		$cpJsonObj = "";
		if($cp['Correct']==null)
		{
			$cp['Correct'] = "";
		}
		if($cp['Size']==null)
		{
			$cp['Size'] = "";
		}
		if($cp['Score']==null)
		{
			$cp['Score'] = "";
		}
		if($cp['Logic']==null)
		{
			$cp['Logic'] = "";
		}
		
		if( $cp['Logic'] != ""){
			$logicArrayList = explode(":",$cp['Logic']);
			$logic1 = "";
			foreach($logicArrayList as $l){
				if($l != null && $l != "" && $l != " "){
					if($logic1 == ""){
						$logic1 = $l;
					}
					else{
						$logic1 = $logic1.",".$l;
					}
				}
			}
			
			$dependString = $dependString.",".$logic1;
			
		}
	
		$cpJsonObj->chkpId = $cp['ChkpId'];
		$cpJsonObj->description = $cp['Description'];
		
		$cpJsonObj->tyId = $cp['TyID'];
		$cpJsonObj->mandatory = $cp['Mandatory'];
		$cpJsonObj->correct = $cp['Correct'];
		$cpJsonObj->editable = $cp['Editable'];
		$cpJsonObj->size = $cp['Size'];
		$cpJsonObj->score = $cp['Score'];
		$cpJsonObj->language = $cp['Language'];
		$cpJsonObj->active = $cp['Active'];
		$cpJsonObj->isDept = $cp['Dependent'];
		$cpJsonObj->logic = $cp['Logic'];
		//$cpJsonObj->answer = "";
		
		$str1 = $cp['Value'];
		$cpJsonObj->value = $str1;
		
		
		array_push($cpArray,$cpJsonObj);
	}
	//$dependString = substr($dependString,1,strlen($dependString));
	$dependSql = "Select * from Checkpoints c where c.ChkpId in ( $dependString )";
	$dependQuery = mysqli_query($conn,$dependSql);
	//echo $dependString;
	while($cp = mysqli_fetch_Array($dependQuery)){
		//echo "Depend cp";	
		$str1 = "";
		$cpJsonObj = "";
		if($cp['Correct']==null)
		{
			$cp['Correct'] = "";
		}
		if($cp['Size']==null)
		{
			$cp['Size'] = "";
		}
		if($cp['Score']==null)
		{
			$cp['Score'] = "";
		}
		
		$cpJsonObj->chkpId = $cp['ChkpId'];
		$cpJsonObj->description = $cp['Description'];
		
		$cpJsonObj->tyId = $cp['TyID'];
		$cpJsonObj->mandatory = $cp['Mandatory'];
		$cpJsonObj->correct = $cp['Correct'];
		$cpJsonObj->editable = $cp['Editable'];
		$cpJsonObj->size = $cp['Size'];
		$cpJsonObj->score = $cp['Score'];
		$cpJsonObj->language = $cp['Language'];
		$cpJsonObj->active = $cp['Active'];
		$cpJsonObj->isDept = $cp['Dependent'];
		$cpJsonObj->logic = $cp['Logic'];
		
		$str1 = $cp['Value'];
		$cpJsonObj->value = $str1;
		
		array_push($cpArray,$cpJsonObj);
	}
	
	$commonObj->menu = $checklistArray;
	$commonObj->chekpoints = $cpArray;
	array_push($wrappedListArray,$commonObj);
	$json->returnCode = "200";
	$json->returnMsg = "Success";
	$json->wrappedList = $wrappedListArray;
	
 }
 else{
	 $json->errorMsg = "Role Not Found";
 }
 
header('Content-type:application/json');
echo json_encode($json);
 
 
?>
