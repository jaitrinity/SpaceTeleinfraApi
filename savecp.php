<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
mysqli_set_charset($conn,'utf8');

$json = file_get_contents('php://input');
$jsonData=json_decode($json,true);
$req = $jsonData[0];
//echo json_encode($req);

 $mapId=$req['mappingId'];
 $empId=$req['Emp_id'];
 $mId=$req['M_Id'];
 $lId=$req['locationId'];
 $event=$req['event'];
 $geolocation=$req['geolocation'];
 $distance=$req['distance'];
 $mobiledatetime=$req['mobiledatetime'];
 
 $caption = $req['caption'];
 $transactionId = $req['timeStamp'];
 $checklist = $req['checklist'];
 $dId = $req['did'];
 $assignId = $req['assignId'];
 $actId = $req['activityId'];
 $lastTransHdrId = "";
 $activityId = 0;
 if($lId == ""){
 	$lId = '1';
 }

 if($mId == ''){
 	$mId = '0';
 }

 if($mapId == ''){
 	$mapId = '0';
 }
 
 if($actId == ''){
	 $actId = null;
 }
 
 if($event == 'Submit'){
	
	if($actId != null && $actId != ''){
		echo "inside if";
		$selectTransHdrSql = "Select * from TransactionHDR  where ActivityId = $actId";
		echo $selectTransHdrSql;
		$selectTransHdrResult = mysqli_query($conn,$selectTransHdrSql);
		$thRow=mysqli_fetch_array($selectTransHdrResult);
		if($thRow['Status'] == 'Created'){
				$updateTransHdrSql = "Update TransactionHDR set Verified_By = '$empId', Verified_Date = '$mobiledatetime',Status = 'Verified' where ActivityId = $actId";
		}
		else if($thRow['Status'] == 'Verified'){
			$updateTransHdrSql = "Update TransactionHDR set Approved_By = '$empId', Approved_Date = '$mobiledatetime',Status = 'Approved' where ActivityId = $actId";
		}
		echo $updateTransHdrSql;
		mysqli_query($conn,$updateTransHdrSql);
	}
	
	$output = "";
	if($lastTransHdrId != ""){
		$output -> error = "200";
		$output -> message = "success";
		$output -> TransID = $transactionId;
	}
	else{
		$output -> error = "0";
		$output -> message = "something wrong";
		$output -> TransID = $transactionId;
	}
	echo json_encode($output);
	
}



?>