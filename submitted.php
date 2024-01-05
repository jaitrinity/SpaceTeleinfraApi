<?php


require_once 'dbConfiguration.php';
if (mysqli_connect_errno())
{
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}


$empId=$_REQUEST['empId'];
$submitList = array();
$res = new StdClass;
// $historySql = "Select"
// 			." (case when m.Caption != '' then m.Caption else ( case when m.Sub != '' then m.Sub else m.Cat end) end) as Caption,"
// 			." l.Name as locationName,a.MobileDateTime as SubmitDateTime"
// 			." from Activity a"
// 			." join Menu m on (a.MenuId  = m.MenuId)"
// 			." join Location l on (a.LocationId = l.LocationId )"
// 			." where a.Event = 'Submit' and DATE_SUB(CURDATE(), INTERVAL  30 DAY)" 
// 			." and a.EmpId = '$empId' order by a.ServerDateTime desc ";

// $historySql = "Select"
// 			." (case when m.Caption != '' then m.Caption else ( case when m.Sub != '' then m.Sub else m.Cat end) end) as Caption,"
// 			." l.Name as locationName,a.MobileDateTime as SubmitDateTime"
// 			." from Activity a"
// 			." join Menu m on (a.MenuId  = m.MenuId)"
// 			." join Location l on (a.LocationId = l.LocationId )"
// 			." where a.Event = 'Submit' and DATE_SUB(CURDATE(), INTERVAL  30 DAY)" 
// 			." and a.EmpId = '$empId' order by a.MobileDateTime desc ";

$historySql = "Select"
			." (case when m.Caption != '' then m.Caption else ( case when m.Sub != '' then m.Sub else m.Cat end) end) as Caption,"
			." l.Name as locationName,a.MobileDateTime as SubmitDateTime,a.MenuId, a.Customer_Site_Id, (case when a.Verify_Final_Submit is null then 'NA' else a.Verify_Final_Submit end) as finalSubmit "
			." from Activity a"
			." join Menu m on (a.MenuId  = m.MenuId)"
			." join Location l on (a.LocationId = l.LocationId )"
			." where a.Event = 'Submit' and a.MobileDateTime >= DATE_SUB(CURDATE(), INTERVAL  30 DAY)" 
			." and a.EmpId = '$empId' order by a.MobileDateTime desc ";
			
$historyQuery = mysqli_query($conn,$historySql);
$historySize = mysqli_num_rows($historyQuery);

if($historySize > 0){
	while($hRow = mysqli_fetch_array($historyQuery)){
		$menuId = $hRow["MenuId"];
		$finalSubmit = $hRow["finalSubmit"];
		$hObj = new StdClass;
		$hObj->caption = $hRow['Caption'];
		$hObj->submitDateTime = $hRow['SubmitDateTime'];
		if($menuId == 279){
			if($finalSubmit == "Yes"){
				$hObj->locationName = $hRow['Customer_Site_Id'];
				array_push($submitList,$hObj);
			}
		}
		else{
			$hObj->submitDateTime = $hRow['SubmitDateTime'];
			array_push($submitList,$hObj);
		}
	}
		
	$res->status = "success";
}

else{
	$res->status = "No record found";
}
$res->submitList = $submitList;

header('Content-type:application/json');
echo json_encode($res);
 

?>