<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
require 'EmployeeTenentId.php';
//echo 'hello';
$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$empTenObj = new EmployeeTenentId();

// $tenentId=$jsonData->tenentId;
$mobile=$jsonData->mobile;
$tenentId=$empTenObj->getTenentIdByMobile($conn,$mobile);
$empName=$jsonData->empName;
if($empName == null) $empName = $jsonData->name;
$make=$jsonData->Make;
$model=$jsonData->Model;
$appVer=$jsonData->AppVer;
$os = $jsonData->os;
$token= $jsonData->token;
$osVer = $jsonData->osVer;
$networkType = $jsonData->networkType;

$empId = "";
$roleId = "";
$fieldUser = "";
$otpCount = "";
$msgStatus = "";
$empStatus = "";
$otpStatus = "";
$output = "";

$confSql = "Select * from configuration";
$confQuery = mysqli_query($conn, $confSql);
$conf = mysqli_fetch_assoc($confQuery);
$sql = "SELECT e.`EmpId`,e.`RoleId`,e.`FieldUser`,o.`OtpCount` FROM  Employees e left join OTP o on (e.`Mobile` = o.`Mobile_Number`) WHERE e.`Mobile` = '$mobile' and e.`Active` = 1 ";
$query=mysqli_query($conn,$sql);
$rowcount=mysqli_num_rows($query);
if($rowcount > 0){
	$row = mysqli_fetch_assoc($query);
	$empId = $row['EmpId'];
	$roleId = $row['RoleId'];
	$fieldUser = $row['FieldUser'];
	if($row['OtpCount'] == null ){
		$empStatus = "update";
		$otpStatus = "insert";
	}
	else if($row['OtpCount'] < $conf['OTPCount']){
		$empStatus = "update";
		$otpStatus = "update";
	}
	else{
		// failure
		$empStatus = "limitExceed";
	}
}
else{
	$empStatus = "";
}
if($empStatus == "insert"){
	$pass = substr($empName, 0,3).substr($mobile, 0,3);
	$empSql = "insert into `Employees` (`EmpId`,`Name`,`Password`,`Mobile`,`Tenent_Id`,`Registered`) values ('$mobile','$empName','$pass','$mobile',$tenentId,NOW())";
	mysqli_query($conn,$empSql);
	$empId = mysqli_insert_id($conn);
	$roleId = "1";
	$fieldUser = "1";	
}
else if($empStatus == "update"){
	// $empSql = "Update `Employees` set `Name` = '$empName',`Update` = NOW() where `Mobile` = '$mobile'";
	// $empSql = "Update `Employees` set `Update` = NOW() where `Mobile` = '$mobile'";
	// mysqli_query($conn,$empSql);
}
else if($empStatus == "limitExceed"){
	$output -> status = 'Limit Exceeded';
	$output -> code = 0;
	$output -> empId = $empId."";
	$output -> roleId = $roleId;
	echo json_encode($output);
	exit();
}

if($empStatus != ""){
	$taskotp = "";
	// Default OTP(1234) to given number. Check number in `DefaultOtpNumber` column of `configuration` table.
	$mobileStr = $conf["DefaultOtpNumber"];
	$mobileArr = explode(",", $mobileStr);
	if(in_array($mobile,$mobileArr)){
		$taskotp = 1234;	
	}
	else{
		$randomotp = rand(1000,9999);
		$taskotp = $randomotp;	
	}
	// for not send OTP to given number.
	if(in_array($mobile,$mobileArr)){
		$msgStatus = true;
	}
	else{
		$msgStatus = sendOtp($mobile,$taskotp,$conn);
	}
	
	if($msgStatus == true){
		if($otpStatus == "insert"){
			$otpSql = "insert into `OTP` (`Mobile_Number`,`OTP`,`OtpCount`) values ('$mobile', '$taskotp', 1)";
		}
		else if($otpStatus == "update"){
			$otpSql = " update `OTP` set `OTP` = '$taskotp', `OtpCount` = `OtpCount` + 1 where `Mobile_Number` = '$mobile' ";
		}
		mysqli_query($conn,$otpSql);
		$deviceId = "";
		$deviceStatus = "";
		$chkDeviceQuery = mysqli_query($conn,"select * from Devices where EmpId = '$empId' and Mobile = '$mobile' and Model = '$model'");
		if(mysqli_num_rows($chkDeviceQuery)>0)
		{
			//echo "updated";
			$deviceStatus = "Updated";
			$deviceSql = "Update Devices set Token = '$token', Name='$empName', Make = '$make', OS = '$os', OSVer = '$osVer',
			AppVer = '$appVer', NetworkType = '$networkType', Active = 1,`Update` = Now(), `Tenent_Id` = $tenentId
			where EmpId = '$empId' and Mobile = '$mobile' and Model = '$model'";
			//echo $deviceSql;
		}
		else
		{
			//echo "inserted";
			$deviceStatus = "Inserted";
			$deviceSql = "insert into Devices (`EmpId`,`Mobile`,`Token`,`Name`,`Make`,`Model`,`OS`,`OSVer`,`AppVer`,`NetworkType`,`Active`,`Registered`,`Update`,`Tenent_Id`)
			values ('$empId','$mobile','$token','$empName','$make','$model','$os','$osVer','$appVer','$networkType',1,Now(),Now(),$tenentId)";
										
		}

		// echo $deviceSql;
				
		if(mysqli_query($conn,$deviceSql)){
			if($deviceStatus == "Updated"){
				$dRow = mysqli_fetch_assoc($chkDeviceQuery);
				$deviceId = $dRow['DeviceId'];
			}
			else{
				$deviceId = mysqli_insert_id($conn);
			}
			$output -> status = 'Success';
			$output -> code = 200;
			$output -> empId = $empId."";
			$output -> roleId = $roleId;
			$output -> fieldUser = $fieldUser;
			$output -> inf = $conf['inf'];
			$output -> conn = $conf['conf'];
			$output -> Start = $conf['start'];
			$output -> End = $conf['end'];
			$output -> Battery = $conf['Battery'];
			$output -> did = "$deviceId";
			$output -> otp = $taskotp;
		}
		else{
			$output -> status = 'Device Failure';
			$output -> code = 0;
			$output -> empId = $empId."";
			$output -> roleId = $roleId;
		}
		
	}
	else{
		$output -> status = 'Otp Failure';
		$output -> code = 0;
		$output -> empId = $empId."";
		$output -> roleId = $roleId;
	}
}
else{
	$output -> status = 'Employee not found';
	$output -> code = 0;
}

echo json_encode($output);



	
?>

<?php
function sendOtp($mobile,$taskotp,$conn)
{
	//api for sending the otp
	$mobile=$mobile;
	$newotp .= "$taskotp";
	$appName = "Galaxy Spin";
	// $msg = "Your one time password (OTP) is ".$taskotp." for One Network App. Do not disclose it to anyone.";
	$msg = "Your one time password (OTP) is ".$newotp." for ".$appName." application.";
	$apikey = "ae6fa4-5cab56-4bc26d-caa56f-b27aab";
	$mobileNumber =$mobile;
	$senderId = "TRIAPP";
	$message = "$msg";
	$route = "default";
	$st = true;
	$postData = array(
            'apikey' => $apikey,	
	    'dest_mobileno' => $mobileNumber,
	    'message' => $message,
	    'senderid' => $senderId,
	    'route' => $route,
	    'response' => "Y",
	    'msgtype' => "TXT"
	);
	$url="http://www.smsjust.com/sms/user/urlsms.php";
	// init the resource
	$ch = curl_init();
	curl_setopt_array($ch, array(
	    CURLOPT_URL => $url,
	    CURLOPT_RETURNTRANSFER => true,
	    CURLOPT_POST => true,
	    CURLOPT_POSTFIELDS => $postData
	    //,CURLOPT_FOLLOWLOCATION => true

	));
		//Ignore SSL certificate verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	//get response
	$output = curl_exec($ch);
	//Print error if any
	if(curl_errno($ch))
	{
	    echo 'error:' . curl_error($ch);
		$st = false;
	}
	curl_close($ch);
	return $st;
	
}

?>