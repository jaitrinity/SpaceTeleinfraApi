<?php
// include("dbConfiguration.php");
// $empSql = "SELECT * FROM `Temp` where `Emp_Id` = '9326718717' ";
// $empSql = "SELECT * FROM `Temp` ";
// $empQuery = mysqli_query($conn,$empSql);

// $output = array();
// while($empRow = mysqli_fetch_assoc($empQuery)){
// 	$empId = $empRow["Emp_Id"];
// 	// $updateSql = "UPDATE Activity a join Employees_Audit ea on a.EmpId = ea.EmpId and ea.IsUpdatedRow = 1 set a.AuditId = ea.AuditId where a.EmpId = '$empId' and a.AuditId is null";
// 	$updateSql = "UPDATE Activity a join Employees_Reference er on a.EmpId = er.EmpId and er.IsUpdatedRow = 1 set a.RefId = er.RefId where a.EmpId = '$empId' and a.RefId is null";
// 	// echo $updateSql;
// 	$json = new StdClass;
// 	$json -> empId = $empId;
// 	if(mysqli_query($conn,$updateSql)){
// 		$updateRowCount = mysqli_affected_rows($conn);
// 		$json -> status = "Ok - ".$updateRowCount;
// 	}
// 	else{
// 		$json -> status = "Not Ok";
// 	}
// 	array_push($output, $json);
// }
// echo json_encode($output);

// $currentTime = time();
// echo $currentTime;

// header('Content-Type: text/html');
// $targetFolder = "files/";
// $parts = explode('/', $_SERVER['REQUEST_URI']);
// $domain = $_SERVER['HTTP_HOST']; 
// $protocal = $_SERVER['HTTPS'];
// $httpPro = "http";
// if($protocal == "on" || $protocal == 1){
// 	$httpPro = 'https';
// }
// $fileURL = $httpPro."://".$domain."/".$parts[1]."/".$targetFolder;
// for($i=0;$i<count($parts);$i++){
// 	echo $i.' -- '.$parts[$i].'<br>';
// }

// echo $protocal."<br>";
// echo $targetFolder."<br>";
// echo $parts."<br>";
// echo $domain."<br>";
// echo $fileURL."<br>";

$arrList = array();
for($i=0;$i<10;$i++){
	array_push($arrList, 'Jai '.$i);
}
$imp = implode("','", $arrList);
echo $imp;
?>