<?php 
include("dbConfiguration.php");
function getSafeRequestValue($key){
	$val = $_REQUEST[$key];
	return isset($val)? $val:"";
}

$jsonData = getSafeRequestValue('jsonData');
$jsonData=json_decode($jsonData);
$loginEmpId = $jsonData->loginEmpId;
$loginEmpRole = $jsonData->loginEmpRole;
$fromDate = $jsonData->fromDate;
$toDate = $jsonData->toDate;
$reportType = $jsonData->reportType;
$millisecond = $jsonData->millisecond;
$currentTime = time();
// if($currentTime >= $millisecond){
// 	unauthorizedAccess();
// }
// // Uptime Report
// else {
	if($reportType == 0){
		$minuteInDay = 1440;
		$todayDate = date("Y-m-d");
		$monthYear = $jsonData->monthYear;
		$month = explode("-", $monthYear)[0];
		$year = explode("-", $monthYear)[1];
		$period =  date("M-Y", strtotime('01-'.$month.'-'.$year));
		$isMatchDate = false;
		$noOfDays = 0;
		$daysInMonth=cal_days_in_month(CAL_GREGORIAN,$month,$year);
		// echo $daysInMonth;
		$filterSql = "";
		if($loginEmpRole != 'Admin' && $loginEmpRole != 'SpaceWorld' && $loginEmpRole != "Management" && $loginEmpRole != "Corporate OnM lead"){
			$empSiteList = [];
			$empLocSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$loginEmpId' ";
			$empLocQuery=mysqli_query($conn,$empLocSql);
			while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
				array_push($empSiteList,$empLocRow["Site_Id"]);
			}
			$el = implode("','", $empSiteList);
			$filterSql .= "and `Site Id` in ('".$el."') ";
		}
		$dateCol = "";
		$dateCol1 = "";
		$outage = "";
		$grandSql = "";
		// $sql1 = "select `Site Name`, `Site Id`, `Site Type`, `Circle`, `Airtel`, `MTNL/BSNL`, `VIL`, `RJIO`, `Sector`, ";
		$sql1 = "select `Site Name`, `Site Id`, `Site Category`, `Circle`, `Airtel`, `MTNL/BSNL`, `VIL`, `RJIO`, `Sector`, ";
		$month = str_pad($month, 2, '0', STR_PAD_LEFT);
		for($i=1;$i<=$daysInMonth;$i++){
			$ii = str_pad($i, 2, '0', STR_PAD_LEFT);
			$sqlDate = $year."-".$month."-".$ii;
			// if $monthYear current monthYear 
			if($sqlDate == $todayDate) {
				$isMatchDate = true;
				$noOfDays = $ii-1; // (n-1)
			}
			$showDate = date_format(date_create($sqlDate),"d-M-Y");
			$sql1 .= "`Day$ii` as `$showDate`";
			$grandSql .= "sum(`$showDate`) as `$showDate`";
			$outage .= "(case when t.`$showDate` is not null then t.`$showDate` else 0 end) ";
			$dateCol .= "t.`$showDate` ";
			$dateCol1 .= "t1.`$showDate` ";
			if($i<$daysInMonth){
				$sql1 .= ", ";
				$grandSql .= ", ";
				$outage .= "+ ";
				$dateCol .= ", ";
				$dateCol1 .= ", ";
			}
		}

		// if $monthYear not current monthYear 
		if($isMatchDate == false) $noOfDays = $daysInMonth;
		
		$sql1 .= " FROM `Outage_Uptime` WHERE `Is_Active` = 1 and `Period` = '$period' ".$filterSql;

		$totalOutage .= "SELECT t.`Site Name`, t.`Site Id`, t.`Site Category`, t.`Circle`, t.`Airtel`, t.`MTNL/BSNL`, t.`VIL`, t.`RJIO`, t.`Sector`, 
		'$noOfDays' as `No. of Days`, ($outage) as `Total Outage`, ".$dateCol. " from (".$sql1.") t ";

		$sitewiseAverage = "SELECT t1.`Site Name`, t1.`Site Id`, t1.`Site Category`, t1.`Circle`, t1.`Airtel`, t1.`MTNL/BSNL`, t1.`VIL`, t1.`RJIO`, t1.`Sector`, 
		 t1.`No. of Days`, t1.`Total Outage`, round(($minuteInDay*t1.`Sector`*t1.`No. of Days`-t1.`Total Outage`)/($minuteInDay*t1.`Sector`*t1.`No. of Days`)*100,2) as `Site wise Average Availability`, ".$dateCol1." from ($totalOutage) t1 ";
		//echo $sitewiseAverage;

		$unionSql = "SELECT '' as `Site Name`, '' as `Site Id`, '' as `Site Category`, '' as `Circle`, '' as Airtel, '' as `MTNL/BSNL`, '' as VIL, '' as RJIO, '' as `Sector`, 
		'' as `No. of Days`, '' as `Total Outage` ,'' as `Site wise Average Availability`, ".$grandSql;
		$unionSql .= " from ($sql1) t ";
		$sql = "(".$sitewiseAverage.") UNION (".$unionSql.")";
		// echo $sql.'---';
		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Uptime_Report.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Incident Report
	else if($reportType == 1){
		$filterSql = "";
		if($loginEmpRole != 'Admin' && $loginEmpRole != 'SpaceWorld' && $loginEmpRole != "Management" && $loginEmpRole != "Corporate OnM lead"){
			// $filterSql .= "and (`Employee Id` = '$loginEmpId' or `Verifier_Emp_Id` = '$loginEmpId' or `Approver_Emp_Id` = '$loginEmpId') ";
			$empSiteList = [];
			$empLocSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$loginEmpId' ";
			$empLocQuery=mysqli_query($conn,$empLocSql);
			while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
				array_push($empSiteList,$empLocRow["Site_Id"]);
			}
			$el = implode("','", $empSiteList);
			$filterSql .= "and `Site Id` in ('".$el."') ";
		}

		$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `Site Name`, `Site Id`, `Incident category`, `Material Damaged`, `Incident Date`, `Incident Time`, `Incident description`, `Location (Lat Long)`, `Entered By`, `Approved status By L1`, `Approved status By L2` FROM (select @sr:=0) as sr, `Incident_Management_Report` where 1=1 ".$filterSql;
		if($fromDate != "")
			$sql .= "and `Incident Date` >= '$fromDate' ";
		if($toDate != "")
			$sql .= "and `Incident Date` <= '$toDate' ";
		$sql .= "order by `ActivityId` desc";
		// echo $sql.'---';
		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Incident_Management_Report.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// PM Report
	else if($reportType == 2){
		$filterSql = "";
		if($loginEmpRole != 'Admin' && $loginEmpRole != 'SpaceWorld' && $loginEmpRole != "Management" && $loginEmpRole != "Corporate OnM lead"){
			// $filterSql .= "and (`EmpId` = '$loginEmpId' or `Verifier_Emp_Id` = '$loginEmpId' or `Approver_Emp_Id` = '$loginEmpId') ";
			$empSiteList = [];
			$empLocSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$loginEmpId' ";
			$empLocQuery=mysqli_query($conn,$empLocSql);
			while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
				array_push($empSiteList,$empLocRow["Site_Id"]);
			}
			$el = implode("','", $empSiteList);
			$filterSql .= "and `Site_Id` in ('".$el."') ";
		}

		// $sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `Site_Name`, `Site_Id`, `Site Type`, `PM Done Date`, `Airtel Site Id`, `Airtel Load`, `MTNL/BSNL Site Id`, `MTNL/BSNL Load`, `VIL Site Id`, `VIL Load`, `RJIO Site Id`, `RJIO Load`, `No. of FE`, `Serial No. OF FE 1`, `Refilling date of FE 1`, `Expiry date of FE 1`, `Serial No. OF FE 2`, `Refilling date of FE 2`, `Expiry date of FE 2`, `Serial No. OF FE 3`, `Refilling date of FE 3`, `Expiry date of FE 3`, `Serial No. OF FE 4`, `Refilling date of FE 4`, `Expiry date of FE 4`, `Serial No. OF FE 5`, `Refilling date of FE 5`, `Expiry date of FE 5`, `Pole Type`, `No. of Pole`, `Pole Height`, `Airtel RRH`, `Airtel MW`, `Airtel GSM`, `MTNL/BSNL RRH`, `MTNL/BSNL MW`, `MTNL/BSNL GSM`, `VIL RRH`, `VIL MW`, `VIL GSM`, `RJIO RRH`, `RJIO MW`, `RJIO GSM`, `SMPS Make`, `No. of RM`, `No. of faulty`, `BB Make & Model`, `No. of BB`, `SOC &SOH status`, `Capacity in AH 1`, `Capacity in AH 2`, `Capacity in AH 3`, `PM done By`, `PM approved By:L1` FROM (select @sr:=0) as sr, `PM_Report` where 1=1 ".$filterSql;

		$sql = "SELECT `Circle`, `Site_Name`, `Site_Id`, `Site Type`, `PM Done Date`, `Airtel Site Id`, `Airtel Load`, `MTNL/BSNL Site Id`, `MTNL/BSNL Load`, `VIL Site Id`, `VIL Load`, `RJIO Site Id`, `RJIO Load`, `No. of FE`, `Serial No. OF FE 1`, `Refilling date of FE 1`, `Expiry date of FE 1`, `Serial No. OF FE 2`, `Refilling date of FE 2`, `Expiry date of FE 2`, `Serial No. OF FE 3`, `Refilling date of FE 3`, `Expiry date of FE 3`, `Serial No. OF FE 4`, `Refilling date of FE 4`, `Expiry date of FE 4`, `Serial No. OF FE 5`, `Refilling date of FE 5`, `Expiry date of FE 5`, `Pole Type`, `No. of Pole`, `Pole Height`, `Airtel RRH`, `Airtel MW`, `Airtel GSM`, `MTNL/BSNL RRH`, `MTNL/BSNL MW`, `MTNL/BSNL GSM`, `VIL RRH`, `VIL MW`, `VIL GSM`, `RJIO RRH`, `RJIO MW`, `RJIO GSM`, `SMPS Make`, `No. of RM`, `No. of faulty`, `BB Make & Model`, `No. of BB`, `SOC &SOH status`, `Capacity in AH 1`, `Capacity in AH 2`, `Capacity in AH 3`, `PM done By`, `PM approved By:L1` FROM `PM_Report` where 1=1 ".$filterSql;

		if($fromDate != "")
			$sql .= "and DATE_FORMAT(`PM Done Date`,'%Y-%m-%d') >= '$fromDate' ";
		if($toDate != "")
			$sql .= "and DATE_FORMAT(`PM Done Date`,'%Y-%m-%d') <= '$toDate' ";
		$sql .= "order by `ActivityId` desc";
		
		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=PM_Report.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Outage Category Report
	else if($reportType == 3){
		$filterSql = "";
		if($loginEmpRole != 'Admin' && $loginEmpRole != 'SpaceWorld' && $loginEmpRole != "Management" && $loginEmpRole != "Corporate OnM lead"){
			// $filterSql .= "and (`EmpId` = '$loginEmpId' or `Verifier_Emp_Id` = '$loginEmpId' or `Approver_Emp_Id` = '$loginEmpId') ";
			$empSiteList = [];
			$empLocSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$loginEmpId' ";
			$empLocQuery=mysqli_query($conn,$empLocSql);
			while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
				array_push($empSiteList,$empLocRow["Site_Id"]);
			}
			$el = implode("','", $empSiteList);
			$filterSql .= "and `site_Id` in ('".$el."') ";
		}
		$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `site_id` as `Site Id`, `site_name` as `Site Name`, `site_type` as `Site Type`, `opco_affected` as `OPCO Affected`, `outage_Category` as `Outage Category`, `outage_start_datetime` as `Outage Start Datetime`, `outage_end_datetime` as `Outage End Datetime`, `outage_minute` as `Outage Minute`, `outage_RCA` as `Outage RCA` FROM Uptime_Report, (select @sr:=0) as sr where 1=1 ".$filterSql;
		if($fromDate != "")
			$sql .= "and `outage_start_date` >= '$fromDate' ";
		if($toDate != "")
			$sql .= "and `outage_start_date` <= '$toDate' ";
		$sql .= "order by `ActivityId` desc";

		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Outage_Category_Report.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Meter Reading Report
	else if($reportType == 4){
		$filterSql = "";
		if($loginEmpRole != 'Admin' && $loginEmpRole != 'SpaceWorld' && $loginEmpRole != "Management" && $loginEmpRole != "Corporate OnM lead"){
			// $filterSql .= "and m.`Emp Id` = '$loginEmpId' ";
			$empSiteList = [];
			$empLocSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$loginEmpId' ";
			$empLocQuery=mysqli_query($conn,$empLocSql);
			while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
				array_push($empSiteList,$empLocRow["Site_Id"]);
			}
			$el = implode("','", $empSiteList);
			$filterSql .= "and m.`Site Id` in ('".$el."') ";
		}
		// $sql = "SELECT `Site Name`, `Site Id`, `Submit By`, `Submit Date`, `Do you have sub meter?`, `Main Meter Reading`, `Sub Meter Reading` FROM `Meter_Reading_Report` WHERE 1=1 ".$filterSql;

		// $sql = "SELECT m.`ActivityId`, l.`State` as `Circle`, l.`City`, m.`Site Name`, m.`Site Id`, l.`Site_Type` as `Site Type`, m.`Submit By`, m.`Submit Date`, m.`Do you have sub meter?`, m.`Main Meter Reading`, m.`Sub Meter Reading`, m.`Sub Meter Reading 2`, m.`Sub Meter Reading 3`, m.`Sub Meter Reading 4`, m.`Remark` FROM `Meter_Reading_Report` m join `Location` l on m.`Site Id` = l.`Site_Id` and m.`Site Name` = l.`Name` WHERE 1=1 ".$filterSql;

		// $sql = "SELECT m.`ActivityId`, l.`State` as `Circle`, l.`City`, m.`Site Name`, m.`Site Id`, l.`Site_Type` as `Site Type`, m.`Submit By`, m.`Submit Date`, m.`Do you have sub meter?`, m.`Main Meter Reading`, m.`Main Meter Pic`, m.`Sub Meter Reading`, m.`Sub Meter Pic`, m.`Sub Meter Reading 2`, m.`Sub Meter Pic 2`, m.`Sub Meter Reading 3`, m.`Sub Meter Pic 3`, m.`Sub Meter Reading 4`, m.`Sub Meter Pic 4`, m.`Remark` FROM `Meter_Reading_Report` m join `Location` l on m.`Site Id` = l.`Site_Id` and m.`Site Name` = l.`Name` WHERE 1=1 ".$filterSql;

		// $sql = "SELECT m.`ActivityId`, m.`Circle`, m.`City`, m.`Site Name`, m.`Site Id`, m.`Site Type`, m.`Submit By`, m.`Submit Date`, m.`Do you have sub meter?`, m.`Main Meter Reading`, m.`Sub Meter Reading`, m.`Sub Meter Reading 2`, m.`Sub Meter Reading 3`, m.`Sub Meter Reading 4`, m.`Remark` FROM `Meter_Reading_Report` m WHERE 1=1 ".$filterSql;

		$sql = "SELECT m.`ActivityId`, m.`Circle`, m.`City`, m.`Site Name`, REPLACE(m.`Site Id`,' ','') as `Site Id`, m.`Site Type`, m.`Submit By`, m.`Submit Date`, m.`Do you have sub meter?`, m.`Main Meter Reading`, m.`Main Meter Pic`, m.`Sub Meter Reading`, m.`Sub Meter Pic`, m.`Remark` FROM `Meter_Reading_Report` m WHERE 1=1 ".$filterSql;

		

		if($fromDate != "")
			$sql .= "and DATE_FORMAT(m.`ServerDateTime`,'%Y-%m-%d') >= '$fromDate' ";
		if($toDate != "")
			$sql .= "and DATE_FORMAT(m.`ServerDateTime`,'%Y-%m-%d') <= '$toDate' ";
		$sql .= "order by m.`ActivityId` desc";

		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Meter_Reading_Report.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Training Report
	else if($reportType == 5){
		$filterSql = "";
		if($loginEmpRole != 'Admin' && $loginEmpRole != 'SpaceWorld' && $loginEmpRole != "Management" && $loginEmpRole != "Corporate OnM lead"){
			$filterSql .= "and `Emp Id` = '$loginEmpId' ";
		}
		$sql = "SELECT `Training Name`, `Submit By`, `Submit Date`, `Total Question`, `Correct`, `Incorrect`, `Percentage`, `Result` FROM `Training_Report` WHERE 1=1 ".$filterSql;

		if($fromDate != "")
			$sql .= "and DATE_FORMAT(`ServerDateTime`,'%Y-%m-%d') >= '$fromDate' ";
		if($toDate != "")
			$sql .= "and DATE_FORMAT(`ServerDateTime`,'%Y-%m-%d') <= '$toDate' ";
		$sql .= "order by `ActivityId` desc";

		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Training_Report.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Punchpoint Report
	else if($reportType == 6){
		$filterSql = "";
		if($loginEmpRole != 'Admin' && $loginEmpRole != 'SpaceWorld' && $loginEmpRole != "Management" && $loginEmpRole != "Corporate OnM lead"){
			// $filterSql .= "and `EmpId` = '$loginEmpId' ";
			$empSiteList = [];
			$empLocSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$loginEmpId' ";
			$empLocQuery=mysqli_query($conn,$empLocSql);
			while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
				array_push($empSiteList,$empLocRow["Site_Id"]);
			}
			$el = implode("','", $empSiteList);
			$filterSql .= "and `Site Id` in ('".$el."') ";
		}
		$sql = "SELECT `ActivityId` as Report_Id, `Site Id`, `Site Name`, `Submit By`, `Submit Date`, `Description`, `Status`, `Remark` 
		FROM `Punchpoint_Report` WHERE 1=1 ".$filterSql;

		if($fromDate != "")
			$sql .= "and DATE_FORMAT(`MobileDateTime`,'%Y-%m-%d') >= '$fromDate' ";
		if($toDate != "")
			$sql .= "and DATE_FORMAT(`MobileDateTime`,'%Y-%m-%d') <= '$toDate' ";
		$sql .= "order by `ActivityId` desc";

		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Punchpoint_Report.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Export location
	else if($reportType == 7){
		$locType = $jsonData->locType;
		$filterSql = "";
		if($locType == "NBS"){
			$filterSql .= " and `Is_NBS_Site` = 1 ";
		}
		else{
			$filterSql .= " and `Is_NBS_Site` = 0 ";
		}
		$sql = "SELECT `LocationId`, `State`, `Name` as `Site Name`, `Site_Id`, `Site_Type` as `Site Type`, `Site_CAT` as `Site Category`, `Airport_Metro` as `Airport/Metro`, `RFI_date`, `High_Revenue_Site`, `ISQ`, `Retail_IBS`, `GeoCoordinates`, `Is_Active` FROM `Location` where LocationId != 1 ".$filterSql." and Tenent_Id = 2";
		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Location.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Export Employee location mapping
	else if($reportType == 8){
		$locType = $jsonData->locType;
		$filterSql = "";
		if($locType == "NBS"){
			$filterSql .= " and loc.`Is_NBS_Site` = 1 ";
		}
		else{
			$filterSql .= " and loc.`Is_NBS_Site` = 0 ";
		}

		$sql = "SELECT loc.State, loc.Name as `Site Name`, loc.Site_Id as `Site Id`, emp.Name as `Employee Name`, empLoc.Role FROM EmployeeLocationMapping empLoc join Location loc on empLoc.LocationId = loc.LocationId left join Employees emp on empLoc.Emp_Id = emp.EmpId left join Role ro on empLoc.Role = ro.Role where empLoc.`Tenent_Id` = 2 ".$filterSql;

		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=EmployeeLocationMapping.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Export Exployee
	else if($reportType == 9){
		$sql = "SELECT e.`Name` as `Emp Name`, e.`Mobile`, r.`Role`, e.`State`, e.`Active` FROM `Employees` e left join `Role` r on e.`RoleId` = r.`RoleId` where e.`Tenent_Id` = 2 ";

		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Employee.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Site Survey Report
	else if($reportType == 10){
		$filterSql = "";
		if($loginEmpRole != 'CBH'){
			$filterSql .= " and t.EmpId = '$loginEmpId' ";
		}
		if($fromDate != ""){
			$filterSql .= " and DATE_FORMAT(t.`Date & Time`,'%Y-%m-%d') >= '$fromDate' ";
		}
		if($toDate != ""){
			$filterSql .= " and DATE_FORMAT(t.`Date & Time`,'%Y-%m-%d') <= '$toDate' ";
		}
		// $sql = "select t.ActivityId, t.EmpId, t.Name, t.`STIPL Id`, t.`OPCO Id`, t.`SAQ Assigned`, t.`Date & Time`, t.Status, max(case when d.ChkId = 4939 then d.Value end) `Owner`, max(case when d.ChkId = 4942 then d.Value end) `Mobile`, max(case when d.ChkId = 4956 then d.Value end) `Site Latitude`, max(case when d.ChkId = 4957 then d.Value end) `Site Latitude`, max(case when d.ChkId = 5048 then d.Value end) `Remark`  from (SELECT a.ActivityId, a.EmpId, e.Name, max(case when d.ChkId = 4928 then d.Value end) as `STIPL Id`, max(case when d.ChkId = 4927 then d.Value end) as `OPCO Id`, h.Assign_To as `SAQ Assigned`, a.MobileDateTime as `Date & Time`, h.VerifierActivityId, (case when h.`Verify_Final_Submit` is null and h.`TransactionStatus` = 1 then 'Pending' when h.`Verify_Final_Submit` = 'No' and h.`TransactionStatus` = 1 then 'In Progress'  when h.`Verify_Final_Submit` = 'Yes' and h.`TransactionStatus` = 1 then 'Completed' else 'Closed' end) as `Status` FROM Activity a join TransactionHDR h on a.ActivityId = h.ActivityId left join TransactionDTL d on h.ActivityId = d.ActivityId left join Employees e on a.EmpId = e.EmpId WHERE MenuId = 279 AND Event = 'Submit' GROUP by a.ActivityId) t left join TransactionDTL d on t.VerifierActivityId = d.ActivityId where 1=1 ".$filterSql." GROUP by t.ActivityId";


		$sql = "select t.ActivityId, t.EmpId, t.Name, t.`STIPL Id`, t.`OPCO Id`, t.`SAQ Assigned`, t.`Date & Time` as `Assign Date`, a1.MobileDateTime as `Visit Date`, t.Status, max(case when d.ChkId = 4939 then d.Value end) `Owner`, max(case when d.ChkId = 4942 then d.Value end) `Mobile`, max(case when d.ChkId = 4956 then d.Value end) `Site Latitude`, max(case when d.ChkId = 4957 then d.Value end) `Site Longitude`, max(case when d.ChkId = 5048 then d.Value end) `Remark`  from (SELECT a.ActivityId, a.EmpId, e.Name, max(case when d.ChkId = 4928 then d.Value end) as `STIPL Id`, max(case when d.ChkId = 4927 then d.Value end) as `OPCO Id`, h.Assign_To as `SAQ Assigned`, a.MobileDateTime as `Date & Time`, h.VerifierActivityId, (case when h.`Verify_Final_Submit` is null and h.`TransactionStatus` = 1 then 'Pending' when h.`Verify_Final_Submit` = 'No' and h.`TransactionStatus` = 1 then 'In Progress'  when h.`Verify_Final_Submit` = 'Yes' and h.`TransactionStatus` = 1 then 'Completed' else 'Closed' end) as `Status` FROM Activity a join TransactionHDR h on a.ActivityId = h.ActivityId left join TransactionDTL d on h.ActivityId = d.ActivityId left join Employees e on a.EmpId = e.EmpId WHERE MenuId = 279 AND Event = 'Submit' GROUP by a.ActivityId) t left join TransactionDTL d on t.VerifierActivityId = d.ActivityId left join Activity a1 on t.VerifierActivityId = a1.ActivityId where 1=1 ".$filterSql." GROUP by t.ActivityId";

		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=SiteSurveyReport.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Management Visit report
	else if($reportType == 11){
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=ManagementVisitReport.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output,array("ActivityId","SiteName","SiteID","SiteTYPE(IBS/OD/IBS+OD)","Circle","LastPMdoneBy","LastPMdoneDate","SitevisitedBy","Date","Site Lat,long","Airtel","Voda","JIO","BSNL/MTNL","EMF Signage Board at Site","Pole condition at Site(Check for rusting/damage of pole/Nut & Bolts)","No. of Poles at Site","Is the site free from unwanted materials and garbage ?? (Leftover materials during deployment / O&M shall not be left at the site)","Fire extinguisher provided and maintained in working condition","24*7 access at site","Met with Owner/ Builder/LO. or Owner/ Builder representative at site","Is the tower maintained free from bee-hives or bird nests?? (Inspection is needed from four positions to identify the location of bee-hives and birds nests)","Gasket of ODC (GAP & Crack should not be present)/Proper door alignment for ODC","Is there any other punch point?","Punch points (Remarks)"));

		$filterSql = "";
		if($loginEmpRole != 'Admin' && $loginEmpRole != 'SpaceWorld' && $loginEmpRole != "Management" && $loginEmpRole != "Corporate OnM lead"){
			$empSiteList = [];
			$empLocSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$loginEmpId' ";
			$empLocQuery=mysqli_query($conn,$empLocSql);
			while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
				array_push($empSiteList,$empLocRow["Site_Id"]);
			}
			$el = implode("','", $empSiteList);
			$filterSql .= "and `Site_Id` in ('".$el."') ";
		}
		$sql = "SELECT `ActivityId`, `Site_Name`, `Site_Id`, `Site_Type`, `Circle`, `SitevisitedBy`, `Date`, `Site Lat,long`, `Airtel`, `Voda`, `Jio`, `MTNL/BSNL`, `Col_2`, `Col_3`, `Col_4`, `Col_5`, `Col_6`, `Col_7`, `Col_8`, `Col_9`, `Col_10`, `Col_11`, `Punchpoint Remark` FROM `Management_Visit_Report` WHERE 1=1 ".$filterSql;

		if($fromDate != ""){
			$sql .= " and DATE_FORMAT(`Date`,'%Y-%m-%d') >= '$fromDate' ";
		}
		if($toDate != ""){
			$sql .= " and DATE_FORMAT(`Date`,'%Y-%m-%d') <= '$toDate' ";
		}
		$result = mysqli_query($conn,$sql);
		while($row=mysqli_fetch_assoc($result)){
			$siteId = $row["Site_Id"];
			$sql1 = "SELECT e.Name, a.MobileDateTime FROM TransactionHDR h join Activity a on h.ActivityId = a.ActivityId join Employees e on a.EmpId = e.EmpId where h.Site_Id = '$siteId' and a.MenuId = '274' and a.Event = 'Submit' ORDER by a.MobileDateTime desc  LIMIT 0,1";
			// echo $sql1.'------';
			$result1 = mysqli_query($conn,$sql1);
			$row1 = mysqli_fetch_assoc($result1);
			$lastPMDoneBy = $row1["Name"];
			$lastPMDoneDate = $row1["MobileDateTime"];

			$jsonData = array('col0'=> $row["ActivityId"], 'col1' => $row["Site_Name"], 'col2'=> $siteId, 'col3'=> $row["Site_Type"], 'col4'=> $row["Circle"], 'col5'=> $lastPMDoneBy, 'col6'=> $lastPMDoneDate, 'col7'=> $row["SitevisitedBy"], 'col8'=> $row["Date"], 'col9'=> $row["Site Lat,long"], 'col10'=> $row["Airtel"], 'col11'=> $row["Voda"], 'col12'=> $row["Jio"], 'col13'=> $row["MTNL/BSNL"], 'col14'=> $row["Col_2"], 'col15'=> $row["Col_3"], 'col16'=> $row["Col_4"], 'col17'=> $row["Col_5"], 'col18'=> $row["Col_6"], 'col19'=> $row["Col_7"], 'col20'=> $row["Col_8"], 'col21'=> $row["Col_9"], 'col22'=> $row["Col_10"], 'col23'=> $row["Col_11"], 'col24'=> $row["Punchpoint Remark"]);
			fputcsv($output, $jsonData);
		}
		
	}
	// Export Vendor
	else if($reportType == 12){
		$sql = "SELECT `EmpId` as `Code`, `Name`, `VendorType` as `Type`, `State`, `Mobile`, `Active` FROM `Employees` where `RoleId` = 53 and `Tenent_Id` = 2 ";

		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Vendor.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Export Raise
	else if($reportType == 13){
		$rmId = $jsonData->rmId;
		$filterSql = "";
		if($loginEmpRole != "SpaceWorld" && $loginEmpRole != "PTW Admin"){
			$filterSql = " and (e1.`RMId` = '$loginEmpId' or e1.`RMId` = '$rmId') ";
		}
		$sql = "SELECT e1.`Name`, e1.`Mobile`, e1.`Whatsapp_Number` as `Whatsapp`, e1.`AadharCard_Number` as `Aadhar card`, e2.`Name` as `Vendor Name`, e1.`Active` FROM `Employees` e1 join `Employees` e2 on e1.`RMId` = e2.`EmpId` where e1.`RoleId` = 61 ".$filterSql." ";

		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Raiser.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Export Supervisor
	else if($reportType == 14){
		$rmId = $jsonData->rmId;
		$filterSql = "";
		if($loginEmpRole != "SpaceWorld" && $loginEmpRole != "PTW Admin"){
			$filterSql = " and (e1.`RMId` = '$loginEmpId' or e1.`RMId` = '$rmId') ";
		}
		$sql = "SELECT e1.`Name`, e1.`Mobile`, e1.`Whatsapp_Number` as `Whatsapp`, e1.`AadharCard_Number` as `Aadhar card`, e2.`Name` as `Vendor Name`, e1.`Active` FROM `Employees` e1 join `Employees` e2 on e1.`RMId` = e2.`EmpId` where e1.`RoleId` = 58 ".$filterSql." ";
		
		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Supervisor.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// Meter Reading Report 2
	else if($reportType == 15){
		// header('Content-Type: text/csv; charset=utf-8');
		// header('Content-Disposition: attachment; filename=Meter_Reading_Report_2.csv');
		// $output = fopen('php://output', 'w');

		// $excelHeaderArr = array("Circle", "City", "Site Name", "Site Id", "Site Type", "Submit By", "Submit Date", "Do you have sub meter?", "Current Main Meter Reading", "Previous Main Meter Reading", "Diff Main Meter Reading", "Current Main Meter Pic", "Current Sub Meter Reading", "Previous Sub Meter Reading", "Diff Sub Meter Reading", "Current Sub Meter Pic", "Current Sub Meter Reading 2", "Current Sub Meter Pic 2", "Current Sub Meter Reading 3", "Current Sub Meter Pic 3", "Current Sub Meter Reading 4", "Current Sub Meter Pic 4", "Current Remark");
		// fputcsv($output,$excelHeaderArr);

		// $sql = "SELECT DISTINCT `Site Id` FROM `Meter_Reading_Report` where (`Site Id` is not null or `Site Id` != '')";
		// $query = mysqli_query($conn,$sql);
		// $siteList = array();
		// while($row = mysqli_fetch_assoc($query)){
		// 	array_push($siteList,$row["Site Id"]);
		// }

		// for($i=0;$i<count($siteList);$i++){
		// 	$siteId = $siteList[$i];

		// 	$sql1 = "(SELECT 'Current' as Type, `Circle`, `City`, `Site Name`, `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Do you have sub meter?`, `Main Meter Reading`, `Main Meter Pic`, `Sub Meter Reading`, `Sub Meter Pic`, `Sub Meter Reading 2`, `Sub Meter Pic 2`, `Sub Meter Reading 3`, `Sub Meter Pic 3`, `Sub Meter Reading 4`, `Sub Meter Pic 4`, `Remark` FROM `Meter_Reading_Report` where `Site Id` = '$siteId' ORDER by `ActivityId` desc LIMIT 0,1)
		// 		UNION
		// 		(SELECT 'Previous' as Type, `Circle`, `City`, `Site Name`, `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Do you have sub meter?`, `Main Meter Reading`, `Main Meter Pic`, `Sub Meter Reading`, `Sub Meter Pic`, `Sub Meter Reading 2`, `Sub Meter Pic 2`, `Sub Meter Reading 3`, `Sub Meter Pic 3`, `Sub Meter Reading 4`, `Sub Meter Pic 4`, `Remark` FROM `Meter_Reading_Report` where `Site Id` = '$siteId' ORDER by `ActivityId` desc LIMIT 1,1)";
			
		// 	$circle = ""; $city = ""; $siteName = ""; $siteId = ""; $siteType = ""; $submitBy = ""; $submitDate = ""; $haveSubMeter = "";
		// 	$currentMain = 0; $previousMain = 0; $diffMain = 0;
		// 	$mainMeterPic = "";
		// 	$currentSub = 0; $previousSub = 0; $diffSub = 0;
		// 	$subMeterPic = "";

		// 	$subMeterReading2 = ""; $subMeterPic2 = "";
		// 	$subMeterReading3 = ""; $subMeterPic3 = "";
		// 	$subMeterReading4 = ""; $subMeterPic4 = "";
		// 	$remark = "";

		// 	$query1 = mysqli_query($conn,$sql1);
		// 	while($row1 = mysqli_fetch_assoc($query1)){
		// 		$type = $row1["Type"];
		// 		if($type == "Current"){
		// 			$circle = $row1["Circle"];
		// 			$city = $row1["City"];
		// 			$siteName = $row1["Site Name"];
		// 			$siteId = $row1["Site Id"];
		// 			$siteType = $row1["Site Type"];
		// 			$submitBy = $row1["Submit By"];
		// 			$submitDate = $row1["Submit Date"];
		// 			$haveSubMeter = $row1["Do you have sub meter?"];

		// 			$currentMain = $row1["Main Meter Reading"];
		// 			$mainMeterPic = $row1["Main Meter Pic"];
		// 			$currentSub = $row1["Sub Meter Reading"];
		// 			$subMeterPic = $row1["Sub Meter Pic"];

		// 			$subMeterReading2 = $row1["Sub Meter Reading 2"];
		// 			$subMeterPic2 = $row1["Sub Meter Pic 2"];
		// 			$subMeterReading3 = $row1["Sub Meter Reading 3"];
		// 			$subMeterPic3 = $row1["Sub Meter Pic 3"];
		// 			$subMeterReading4 = $row1["Sub Meter Reading 4"];
		// 			$subMeterPic4 = $row1["Sub Meter Pic 4"];
		// 			$remark = $row1["Remark"];

		// 		}
		// 		else if($type == "Previous"){
		// 			$previousMain = $row1["Main Meter Reading"];
		// 			$previousSub = $row1["Sub Meter Reading"];
		// 		}
		// 	}
		// 	$diffMain = $currentMain - $previousMain;
		// 	$diffSub = $currentSub - $previousSub;


		// 	$dataArr = array($circle, $city, $siteName, $siteId, $siteType, $submitBy, $submitDate, $haveSubMeter, $currentMain, $previousMain, $diffMain, $mainMeterPic, $currentSub, $previousSub, $diffSub, $subMeterPic, $subMeterReading2, $subMeterPic2, $subMeterReading3, $subMeterPic3, $subMeterReading4, $subMeterPic4, $remark);
		// 	fputcsv($output, $dataArr);

		// }

		// $sql = "SELECT `Circle`, `Site Name`, `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Do you have sub meter?`, `Current Main Meter Reading`, `Previous Main Meter Reading`, `Diff Main Meter Reading`, `Main Meter Billing`, `Current Main Meter Pic`, `Current Sub Meter Reading`, `Previous Sub Meter Reading`, `Diff Sub Meter Reading`, `Sub Meter Billing`, `Current Sub Meter Pic`, `Current Sub Meter Reading 2`, `Current Sub Meter Pic 2`, `Current Sub Meter Reading 3`, `Current Sub Meter Pic 3`, `Current Sub Meter Reading 4`, `Current Sub Meter Pic 4`, `Current Remark` FROM `DiffMeterReading` ";

		$filterSql = "";
		if($loginEmpRole != 'Admin' && $loginEmpRole != 'SpaceWorld' && $loginEmpRole != "Management" && $loginEmpRole != "Corporate OnM lead"){
			$empSiteList = [];
			$empLocSql = "SELECT l.Site_Id FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId where el.Emp_Id = '$loginEmpId' ";
			$empLocQuery=mysqli_query($conn,$empLocSql);
			while($empLocRow = mysqli_fetch_assoc($empLocQuery)){
				array_push($empSiteList,$empLocRow["Site_Id"]);
			}
			$el = implode("','", $empSiteList);
			$filterSql .= "and `Site Id` in ('".$el."') ";
		}

		$sql = "SELECT `ActivityId`, `Circle`, `Site Name`, REPLACE(`Site Id`, ' ','') as `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Reading Date`, `Previous Submit Date`, `Previous Reading Date`, `Do you have sub meter?`, `Current Main Meter Reading`, `Previous Main Meter Reading`, `Diff Main Meter Reading`, `Main Meter Billing`, `Current Main Meter Pic`, `Current Sub Meter Reading`, `Previous Sub Meter Reading`, `Diff Sub Meter Reading`, `Sub Meter Billing`, `Current Sub Meter Pic`, `Current Remark` FROM `DiffMeterReading` where 1=1 ".$filterSql;

		if($fromDate != "")
			$sql .= "and DATE_FORMAT(`Submit Date`,'%Y-%m-%d') >= '$fromDate' ";
		if($toDate != "")
			$sql .= "and DATE_FORMAT(`Submit Date`,'%Y-%m-%d') <= '$toDate' ";
		
		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Meter_Reading_Report_2.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
	// PTW Report
	else if($reportType == 16){
		$sql = "SELECT rd.ActivityId, rd.Circle, rd.Site_Id as `Site Id`, rd.Site_Name as `Site Name`, rd.Site_Type as `Site Type`, rd.PtwType as `PTW Type`, rd.ActivityType as `Activity`, rd.PtwRaiseDate as `PTW Raise Date`, rd.WorkStartDatetime as `Work Start Datetime`, rd.WorkEndDatetime as `Work End Datetime`, (case when rd.PtwStatus = 'Approved by' or rd.PtwStatus = 'Rejected by' then concat(rd.PtwStatus,' ',ad.AppByRole) else rd.PtwStatus end) as `Status of PTW`, (case when rd.PtwStatus = 'Cancel by Auditor' then audd.Observation when (rd.PtwStatus = 'Rejected by' or rd.PtwStatus = 'Cancelled') then ad.AppRemark else ad.ReasonOfCancel end)  as `Reason of Rejection`, rd.VendorName as `Vendor Name`, rd.PartnerType as `Partner Type`, rd.PtwRaiserName as `PTW Raiser Name`, rd.PtwRaiserMobileNo as `PTW Raiser Mobile Number`, rd.SupervisorName as `Supervisor Name`, rd.SupervisorAadhar as `Supervisor Aadhar Card Number`, rd.SupervisorMobile as `Supervisor Mobile`, rd.SuperVisorWhatsapp as `Supervisor Whatsapp`, sd.WorkStartDateTime as `Site Assessmet Datetime`, se.SiteEvaluateDate as `Site Risk Assessment Datetime`, rd.IsPoAvailable as `Is PO Available ?`, rd.PoNumber as `PO Number`, rd.NoOfWorkersRequiredAtRaiserStage as `No of Workers required at raiser stage`, ad.AppByName as `Approved by Name`, ad.AppByMobile as `Mobile Number`, ad.AppRemark as `Remarks by Approver`, ad.AssignTechNameMobile as `Assign Technician Mobile and Name`, sd.TotalCheckpoint as `PTW Check list Total Points`, sd.YesCount as `PTW Check list Yes Points Counts`, sd.NoCount as `PTW Check list No Points Counts`, sd.NaCount as `PTW Check list NA Points Counts`, se.RiskCount as `Risk Level Initial count`, se.RiskAppBy as `Risk Level Approved by Name`, pc.ReasonForClosure as `Reason for Closure`, sd.TotalWorker as `Total Workers`, aud.FirstAuditBy as `Auditer Name 1`, aud.FirstMobile as `Auditer Mobile No 1`, aud.FirstEmpRole as `Auditer Role 1`, aud.FirstCircle as `Circle of Auditer 1`, aud.FirstAudDate as `Audit Date 1`, aud.FirstModeOfAudit as `Mode of Audit 1`, aud.FirstObservation as `Audit Remarks 1`, aud.SecondAuditBy as `Auditer Name 2`, aud.SecondMobile as `Auditer Mobile No 2`, aud.SecondEmpRole as `Auditer Role 2`, aud.SecondCircle as `Circle of Auditer 2`, aud.SecondAudDate as `Audit Date 2`, aud.SecondModeOfAudit as `Mode of Audit 2`, aud.SecondObservation as `Audit Remarks 2`, aud.ThirdAuditBy as `Auditer Name 3`, aud.ThirdMobile as `Auditer Mobile No 3`, aud.ThirdEmpRole as `Auditer Role 3`, aud.ThirdCircle as `Circle of Auditer 3`, aud.ThirdAudDate as `Audit Date 3`, aud.ThirdModeOfAudit as `Mode of Audit 3`, aud.ThirdObservation as `Audit Remarks 3`, aud.FourthAuditBy as `Auditer Name 4`, aud.FourthMobile as `Auditer Mobile No 4`, aud.FourthEmpRole as `Auditer Role 4`, aud.FourthCircle as `Circle of Auditer 4`, aud.FourthAudDate as `Audit Date 4`, aud.FourthModeOfAudit as `Mode of Audit 4`, aud.FourthObservation as `Audit Remarks 4`, aud.FifthAuditBy as `Auditer Name 5`, aud.FifthMobile as `Auditer Mobile No 5`, aud.FifthEmpRole as `Auditer Role 5`, aud.FifthCircle as `Circle of Auditer 5`, aud.FifthAudDate as `Audit Date 5`, aud.FifthModeOfAudit as `Mode of Audit 5`, aud.FifthObservation as `Audit Remarks 5`  FROM PTWRaiseDetails rd left join PTWApprovedDetails ad on rd.ActivityId = ad.ActivityId left join PTWStartDetails sd on rd.ActivityId = sd.ActivityId left join PTWSiteEvaluate se on rd.ActivityId = se.ActivityId left join PTWClosure pc on ad.ActivityId = pc.ActivityId left join PTWAuditDetails aud on ad.ActivityId = aud.ActivityId left join PTWAudits audd on ad.ActivityId = audd.ActivityId and audd.AuditStatus = 'Reject' order by rd.ActivityId desc";
		
		$result = mysqli_query($conn,$sql);
		$row=mysqli_fetch_assoc($result);
		$columnName = array();
		foreach ($row as $key => $value) {
			array_push($columnName, $key);
		}

		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=PTWReport.csv');
		$output = fopen('php://output', 'w');
		fputcsv($output, $columnName);

		mysqli_data_seek($result, 0);
		while($row=mysqli_fetch_assoc($result)){
			$exportData = array();
			foreach ($columnName as $key => $value) {
				array_push($exportData, $row[$value]);
			}
			fputcsv($output, $exportData);
		}
	}
// }

?>
<?php
header('Content-Type: text/html');
function unauthorizedAccess(){
	echo "<h1>Session Expired.</h1>";
}
?>