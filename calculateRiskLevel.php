<?php 
include("dbConfiguration.php");
$json = file_get_contents('php://input');
$jsonData=json_decode($json);
$riskValue = 0;
$riskLevel = "<div style='text-align:center'><span style='color:yellow'><i>$riskValue</i></span></div>";
$status = false;
if(count($jsonData) != 0){
	$filterSql = "";
	for($i=0;$i<count($jsonData);$i++){
		$key = $jsonData[$i]->key;
		$value = $jsonData[$i]->value;
		if($key == 5691 || $key == 5338){
			 $filterSql .= " and `Likelihood` = '$value'";
		}
		else if($key == 5692 || $key == 5339){
			$filterSql .= " and `Impact` = '$value'";
		}
	}

	$sql = "SELECT `RiskLevel` FROM `RiskAssessment` where 1=1".$filterSql;
	$query=mysqli_query($conn,$sql);
	$rowCount=mysqli_num_rows($query);
	if($rowCount !=0){
		$status = true;
		$row = mysqli_fetch_assoc($query);
		$riskValue = $row["RiskLevel"];
		if($riskValue >= 15){
			$riskLevel = "<div style='text-align:center'><span style='color:red'><i>$riskValue</i></span></div>";
		}
		else{
			$riskLevel = "<div style='text-align:center'><span style='color:#228B22'><i>$riskValue</i></span></div>";
		}
	}
}	

$output = array();
$output = array('status' => $status, 'riskValue' => $riskValue, 'riskLevel' => "$riskLevel");
echo json_encode($output);
?>