<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$json = file_get_contents('php://input');
$jsonData=json_decode($json);

// $appName = $jsonData->appName;
// $company = $jsonData->company;
$mobile = $jsonData->username;
$password = $jsonData->password;

// $request_string = "{
// 	\"appName\": \"$appName\",
// 	\"company\": \"$company\"
// }";

// $url = "http://www.trinityapplab.com/DemoOneNetwork/test_as_anyhost.php";
// $headers = array(
//                 "Content-type: application/json"
//             );


// $ch = curl_init($url);
// curl_setopt_array($ch, array(
//   CURLOPT_POST => TRUE,
//   CURLOPT_RETURNTRANSFER => TRUE,
//   CURLOPT_HTTPHEADER => $headers,
//   CURLOPT_POSTFIELDS => $request_string
// ));

// $response = curl_exec($ch);
// // echo $response.'----';
// curl_close($ch);

// $response = json_decode($response, true);
// $wrappedList = $response["wrappedList"];

$empArr = array();
// if(count($wrappedList) != 0){
	// $tenentId = $wrappedList[0]["tenentId"];

	// $sql = "SELECT `Employees`.*, `Role`.`Role` as roleName FROM `Employees` LEFT join `Role` on `Employees`.`RoleId` = `Role`.`RoleId` WHERE `Employees`.`EmpId` = '$empId' 
	// and `Employees`.`Password` = BINARY('$password') and `Employees`.`Tenent_Id` = $tenentId and `Employees`.`Active` = 1 ";

	$sql = "SELECT `Employees`.*, `Role`.`Role` as roleName FROM `Employees` LEFT join `Role` on `Employees`.`RoleId` = `Role`.`RoleId` 
	WHERE `Employees`.`Mobile` = '$mobile' and `Employees`.`Password` = BINARY('$password') and `Employees`.`Tenent_Id` is not null and `Employees`.`Active` = 1 ";
	$query=mysqli_query($conn,$sql);

	if(mysqli_num_rows($query) != 0){
		while($row = mysqli_fetch_assoc($query)){
			$empId = $row["EmpId"];
			$empName = $row["Name"];
			$empRoleId = $row["RoleId"];
			$roleName = $row["roleName"];
			$state = $row["State"];
			$rmId = $row["RMId"];
			$tenentId = $row["Tenent_Id"];
			
			$json = array(
				'empId' => $empId,
				'empName' => $empName,
				'empRoleId' => $empRoleId,
				'roleName' => $roleName,
				'state' => $state,
				'rmId' => $rmId,
				'tenentId' => $tenentId
			);
			array_push($empArr,$json);
		}
		$output = array();
		$output = array('responseCode' => '100000','responseDesc' => 'SUCCESSFUL','wrappedList' => $empArr);
		echo json_encode($output);
	}
	else{
		$output = array();
		$output = array('responseCode' => '102001','responseDesc' => 'Either mobile or password is incorrect, please try again.','wrappedList' => $empArr);
		echo json_encode($output);
	}
// }
// else{
// 	$output = array();
// 	$output = array('responseCode' => '102001','responseDesc' => 'Company name not Found','wrappedList' => $empArr);
// 	echo json_encode($output);
// }

?>