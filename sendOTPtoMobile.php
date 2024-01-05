<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$json = file_get_contents('php://input');
$jsonData=json_decode($json);

$loginEmpId = $jsonData->loginEmpId;
$mobileNumber = $jsonData->mobileNumber;

$menuIdArr = [];
// $sql = "SELECT `EmpId` FROM `Employees` WHERE `EmpId`= '$loginEmpId' and `Mobile`= '$mobileNumber' ";
$sql = "SELECT `EmpId` FROM `Employees` WHERE `Mobile`= '$mobileNumber' ";
$query=mysqli_query($conn,$sql);
if(mysqli_num_rows($query) == 0){
	$output = array('responseDesc' => "No Record Found", 'wrappedList' => [], 'responseCode' => "102001");
}
else{
	$randomotp = rand(100000,999999);
	$wrappedList = [];
	array_push($wrappedList,$randomotp);
	sendOtp($mobileNumber,$randomotp);
	$output = array('responseDesc' => "SUCCESSFUL", 'wrappedList' => $wrappedList, 'responseCode' => "100000");
}

echo json_encode($output);
?>

<?php
function sendOtp($mobile,$taskotp)
{
	//api for sending the otp
	$mobile=$mobile;
	$newotp .= "$taskotp";
	$msg = "Your one time password (OTP) is ".$taskotp." for One network App. Do not disclose it to anyone.";
	//$username="trinitymobile";
	//$pass = "123456";
	$apiKey = "ae6fa4-5cab56-4bc26d-caa56f-b27aab";
	$mobileNumber =$mobile;
	$senderId = "TRIAPP";
	$message = "$msg";
	$route = "default";
	$st = true;
	$postData = array(
	    //'username' => $username,
	    //'pass'=> $pass,
	    'apiKey' => $apiKey,
	    'dest_mobileno' => $mobileNumber,
	    'message' => $message,
	    'senderid' => $senderId,
	    'route' => $route
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