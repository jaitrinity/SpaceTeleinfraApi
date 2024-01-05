<?php 
include("dbConfiguration.php");
$sql = "SELECT * FROM `Employees`";
$query=mysqli_query($conn,$sql);
while($row = mysqli_fetch_assoc($query)){
	$id = $row["Id"];
	// $empId = $row["EmpId"];
	$password = $row["Password"];
	$encode = base64_encode($password);
	$decode = base64_decode($encode);
	echo $password." --- ".$encode.' --- '.$decode;
}


// $str = 'This is an encoded string';
// $encode = base64_encode($str);
// echo 'Encode : '.$encode.'<br>';
// $decode = base64_decode($encode);
// echo 'Decode : '.$decode;
?>