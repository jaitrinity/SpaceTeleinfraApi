<?php 

require_once 'dbConfiguration.php';
if (mysqli_connect_errno())
{
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}

?>
<?php
$t=date("YmdHis");
$target_dir = "/files/";

$activityId=$_REQUEST["trans_id"];
$company=$_REQUEST["company"];
$chk_id=$_REQUEST["chk_id"];
$depend_upon=$_REQUEST["depend_upon"];
$caption=$_REQUEST["caption"];
$timestamp = $_REQUEST["timestamp"];
$latitude = $_REQUEST["latitude"];
$longitude = $_REQUEST["longitude"];


$cpId = "";
$dependId = "";
$cpIdlist = explode("_",$chk_id);
$dIdlist = explode("_",$depend_upon);
if(count($cpIdlist) > 2){
	$cpId = $cpIdlist[1];
}
else{
	$cpId = $cpIdlist[0];
}
$dependId = $dIdlist[0];

$prevValue = "";
$fileName = $_FILES["attachment"]["name"];
$target_file = "files/".$t.$fileName;
	
	
if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) 
{
	$parts = explode('/', $_SERVER['REQUEST_URI']);
	$link = $_SERVER['HTTP_HOST']; 
	$fileURL = "http://".$link."/".$parts[1]."/".$target_file;
	
	$selectQuery = "Select Value from TransactionDTL where ActivityId = '$activityId' and ChkId = '$cpId'  and DependChkId = '$dependId' and Value like 'http%'";
	$selectData = mysqli_query($conn,$selectQuery);
	$rowcount = mysqli_num_rows($selectData);
	$query = "";
	if($rowcount > 0){
		$sr = mysqli_fetch_assoc($selectData);
		$prevValue = $sr['Value'];
		$query = "Update TransactionDTL set Value = '$prevValue,$fileURL', Latitude = '$latitude', Longitude = '$longitude' where ActivityId = '$activityId' 
		and ChkId = '$cpId'  and DependChkId = '$dependId'";	
	}
	else{
		$query = "Update TransactionDTL set Value = '$fileURL', Latitude = '$latitude', Longitude = '$longitude' where ActivityId = '$activityId' 
		and ChkId = '$cpId'  and DependChkId = '$dependId'";	
	}
	
	mysqli_query($conn,$query);

	$arr[]=array('error' => '200','message'=>'Save Successfully!','fileName'=> $fileName,'caption'=> $caption,'timestamp'=>$timestamp,'chk_id'=>$chk_id,'FileURL'=>$fileURL);
	header('Content-Type: application/json');
	echo json_encode($arr[0]);
} 
else 
{
	// $arr[]=array('error' => '201','message'=>'Error!','FileURL'=>'');
	$arr[]=array('error' => '201','message'=>'Error!','fileName'=> $fileName,'caption'=> $caption,'timestamp'=>$timestamp,'chk_id'=>$chk_id,'FileURL'=>'');

	header('Content-Type: application/json');
	echo json_encode($arr[0]);
    //echo "Sorry, there was an error uploading your file.";
    //exit();
}
?>