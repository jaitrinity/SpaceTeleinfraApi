<?php
class EmployeeTenentId{
	function getTenentIdByEmpId($conn, $empId) {
		$sql = "SELECT * FROM `Employees` where `EmpId` = '$empId' and `Active` = 1 ";
		$result = mysqli_query($conn,$sql);
		$tenentId  = 0;
		while($row = mysqli_fetch_assoc($result)){
			$tenentId = $row["Tenent_Id"];
		}
		return $tenentId;
	}
	function getTenentIdByMobile($conn, $mobile) {
		$sql = "SELECT * FROM `Employees` where `Mobile` = '$mobile' and `Active` = 1 ";
		$result = mysqli_query($conn,$sql);
		$tenentId  = 0;
		while($row = mysqli_fetch_assoc($result)){
			$tenentId = $row["Tenent_Id"];
		}
		return $tenentId;
	}
	// function getEmployeeCirleName($conn, $empId, $tenentId){
	// 	$sql = "SELECT * FROM `Employees` where `EmpId` = '$empId' and `Tenent_Id` = $tenentId and `Active` = 1 ";
	// 	$result = mysqli_query($conn,$sql);
	// 	$circleName  = "";
	// 	while($row = mysqli_fetch_assoc($result)){
	// 		$circleName = $row["State"];
	// 	}
	// 	return $circleName;
	// }
}
?>