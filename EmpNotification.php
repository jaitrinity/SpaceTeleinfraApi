<?php

require_once 'dbConfiguration.php';

if (mysqli_connect_errno())

  {

  	echo "Failed to connect to MySQL: " . mysqli_connect_error();

  }

$mobile="";

if(isset($_REQUEST['mobile']))

{

	$mobile=$_REQUEST['mobile'];	

}

$sql = "Select Id,Subject,Body,url from Notification where Mobile = '$mobile' and Is_Active = 'Y'";

$rs=mysqli_query($conn,$sql);

$arr=array();

if(mysqli_num_rows($rs)>0){
	while($row = mysqli_fetch_assoc($rs))

	{

		extract($row);

		$arr[]=$row;					

	}	

}

header('Content-Type: application/json');
echo json_encode($arr);
