<?php
include("dbConfiguration.php");

function getSafeRequestValue($key){
			 $val = $_REQUEST[$key];
			 return isset($val)? $val:"";
		 }


	
	$emp_id=getSafeRequestValue("emp_id");
	$datetime=getSafeRequestValue("timestamp");

	$latitude=getSafeRequestValue("latitude");

	$longitude=getSafeRequestValue("longitude");
	
	//$signal = getSafeRequestValue("signal");
	$battery = getSafeRequestValue("battery");
	$comment = getSafeRequestValue("comment");
	$tags = getSafeRequestValue("tags");


$date = new DateTime($datetime);

$d=$date->format('Y-m-d H:i:s');


	
	$query="insert into movement(datetime,did,latitude,longitude,Activity,status,battery,comment)
	values('$d',$emp_id,$latitude,$longitude,'tracking','$tags','$battery','$comment')";
	
//echo $query;
	if(mysqli_query($conn,$query))
	{
		$arr = array("success" => "1", "error" => "0", "success_msg" => "Records inserted successfully", "error_msg" => "success");
		$input="input-".$_SERVER['REQUEST_URI'];
	}
	else
	{
		$arr = array("success" => "0", "error" => "1", "success_msg"=>"connection error", "error_msg" => "Could not insert record");
		$input="input-".$_SERVER['REQUEST_URI'];
	}
	header('Content-Type: application/json');	
	echo json_encode($arr);
	
	mysqli_close($conn);

?>
