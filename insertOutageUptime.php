<?php 
include("dbConfiguration.php");
$todayDatetime = date("d-M-Y h:i:s A");
$period = date("M-Y");
$sql = "SELECT COUNT(*) as row_count FROM `Outage_Uptime` where Period = '$period' ";
$query=mysqli_query($conn,$sql);
$row = mysqli_fetch_assoc($query);
$row_count = $row["row_count"];
$output = "";
if($row_count == 0){
	// $sql = "insert into Outage_Uptime (`Site Name`, `Site Id`, `Circle`, `Period`)
	// SELECT Name, Site_Id, State, '$period' as period FROM `Location` where Tenent_Id = 2 and Site_Id is not null ";

	// $sql = "insert into Outage_Uptime (`Site Name`, `Site Id`, `Site Category`, `Circle`, `Period`)
	// SELECT Name, Site_Id, Site_CAT, State, '$period' as Period FROM `Location` where Tenent_Id = 2 and (Site_Id is not null and Site_Id != '') and Is_Active = 1 ";

	$sql = "insert into Outage_Uptime (`Location_Id`, `Site Name`, `Site Id`, `Site Category`, `Circle`, `Is_Active`, `Period`)
	SELECT LocationId, Name, Site_Id, Site_CAT, State, Is_Active, '$period' as Period FROM `Location` where Tenent_Id = 2 and (Site_Id is not null and Site_Id != '') ";

	if(mysqli_query($conn,$sql)){
		$output -> status = true;
		$output -> message = "Successfully inserted data of ".$period." period.";
	}
	else{
		$output -> status = false;
		$output -> message = "Something went wrong will inserting data of ".$period." period.";
	}
	
}else{
	$output -> status = false;
	$output -> message = "Record Alreary Exist in table of ".$period." period.";
}
$result = array('hitDatetime' => $todayDatetime, 'output' => $output);
echo json_encode($result);

file_put_contents('/var/www/trinityapplab.in/html/SpaceTeleinfra/log/log_'.date("Y").'.log', json_encode($result)."\n", FILE_APPEND);
	
?>