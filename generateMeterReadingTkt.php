<?php 
include("dbConfiguration.php");
$yesterdayDate = date('Y-m-d', strtotime('-1 day'));
$sql = "SELECT l.LocationId, el.Emp_Id FROM Location l join EmployeeLocationMapping el on l.LocationId = el.LocationId and el.Role = 'Technician'  where date(l.Update_Date) = '$yesterdayDate' and l.Site_CAT = 'Small Cell' and l.Is_NBS_Site = 0";
// echo $sql;
$query = mysqli_query($conn,$sql);
$rowCount = mysqli_num_rows($query);
$dataArr = array();
if($rowCount !=0){
	while($row = mysqli_fetch_assoc($query)){
		$locId = $row["LocationId"];
		$empId = $row["Emp_Id"];
		$monthYear = date('m-Y', strtotime('-1 day'));

		$sql1 = "SELECT * FROM `Mapping` where `EmpId` = '$empId' and `LocationId` = '$locId' and date_format(`Start`,'%m-%Y') = '$monthYear' and `MenuId` = 293";
		$query1 = mysqli_query($conn,$sql1);
		$rowCount1 = mysqli_num_rows($query1);
		if($rowCount1 != 0){
			$sql2 = "INSERT into `Mapping`(`EmpId`, `MenuId`, `LocationId`, `Start`, `End`, `ActivityId`, `Tenent_Id`) VALUES ('$empId', 293, '$locId', '$yesterdayDate', last_day('$yesterdayDate'), 0, 2)";
			if(mysqli_query($conn,$sql2)){
				$data = array('code' => 200, 'message' => 'Success', 'empId' => $empId, 'locId' => $locId);
				array_push($dataArr, $data);
			}
			else{
				$data = array('code' => 500, 'message' => 'Error', 'empId' => $empId, 'locId' => $locId);
				array_push($dataArr, $data);
			}
		}
		else{
			$data = array('code' => 403, 'message' => 'Already exist', 'empId' => $empId, 'locId' => $locId);
			array_push($dataArr, $data);
		}
	}
	
}
else{
	$data = array('code' => 404, 'message' => 'No record found');
	array_push($dataArr, $data);
}
echo json_encode($dataArr);
?>