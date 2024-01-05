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
$period = $jsonData->period;
$incidentCategory = $jsonData->incidentCategory;
$quarter = $jsonData->quarter;
$financialYear = $jsonData->financialYear;
$siteType = $jsonData->siteType;
$trainingName = $jsonData->trainingName;
$metroSiteType = $jsonData->metroSiteType;
$graphType = $jsonData->graphType;
$millisecond = $jsonData->millisecond;
$currentTime = time();
// if($currentTime >= $millisecond){
// 	sessionExpired();
// }
// else 
if($graphType == 1){
	$filterSql = "";
	if($incidentCategory != ""){
		$filterSql .= " and `Incident category` = '$incidentCategory' ";
	}
	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `Site Id`, `Site Name`, `Incident category`, `Material Damaged`, `Incident Date`, `Incident Time`, `Incident description`, `Location (Lat Long)`, `Entered By`, `Approved status By L1`, `Approved status By L2` FROM (select @sr:=0) as sr, `Incident_Management_Report` where `Period` = '$period' ".$filterSql;

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=Incident_Management_Graph.csv');
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
else if($graphType == 2){
	$quarter = getQuarterMonth($quarter);

	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, im.`Circle`, im.`Site Id`, im.`Site Name`, im.`Incident category`, im.`Material Damaged`, im.`Incident Date`, im.`Incident Time`, im.`Incident description`, im.`Location (Lat Long)`, im.`Entered By`, im.`Approved status By L1`, im.`Approved status By L2` from (SELECT t.Site_Id, sum(t.repeatCount) as RepeatCount from (SELECT Site_Id, count(Site_Id) as repeatCount FROM Incident_Graph where Period in ('".$quarter."') and Incident_category = 'Fiber Cut' GROUP by Site_Id) t GROUP by t.Site_Id) t1 join Incident_Management_Report im on t1.Site_Id = im.`Site Id`, (select @sr:=0) as sr where t1.RepeatCount !=1  and im.Period in ('".$quarter."') ";

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=Frequent_failing_fiber_cut_circle_wise.csv');
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
else if($graphType == 3){
	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `Site Id`, `Site Name`, `Incident category`, `Incident Date`, `Incident Time`, `Incident description`, `Location (Lat Long)`, `Entered By`, `Approved status By L1`, `L1 Close Date` as `Incident Close Date`, `L1 Close Time` as `Incident Close Time`, `L1 Close Remark` as `Incident Close Description`, `incident_minute` as `Total Incident (in Min)` FROM (select @sr:=0) as sr, `Incident_MTTR` where Incident_Month = '$period' and `Incident category` = 'Fiber Cut' ";

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=MTTR_of_Fiber_Cut.csv');
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
// Preventive Maintenance
else if($graphType == 4){

	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `Site_Id`, `Site_Name`, `Site_CAT` as `Site Category`, `Site Type`, `PM Done Date`, `Airtel Site Id`, `Airtel Load`, `MTNL/BSNL Site Id`, `MTNL/BSNL Load`, `VIL Site Id`, `VIL Load`, `RJIO Site Id`, `RJIO Load`, `No. of FE`, `Serial No. OF FE 1`, `Refilling date of FE 1`, `Expiry date of FE 1`, `Serial No. OF FE 2`, `Refilling date of FE 2`, `Expiry date of FE 2`, `Serial No. OF FE 3`, `Refilling date of FE 3`, `Expiry date of FE 3`, `Serial No. OF FE 4`, `Refilling date of FE 4`, `Expiry date of FE 4`, `Serial No. OF FE 5`, `Refilling date of FE 5`, `Expiry date of FE 5`, `Pole Type`, `No. of Pole`, `Pole Height`, `Airtel RRH`, `Airtel MW`, `Airtel GSM`, `MTNL/BSNL RRH`, `MTNL/BSNL MW`, `MTNL/BSNL GSM`, `VIL RRH`, `VIL MW`, `VIL GSM`, `RJIO RRH`, `RJIO MW`, `RJIO GSM`, `SMPS Make`, `No. of RM`, `No. of faulty`, `BB Make & Model`, `No. of BB`, `SOC &SOH status`, `Capacity in AH 1`, `Capacity in AH 2`, `Capacity in AH 3`, `PM done By`, `PM approved By:L1` FROM (select @sr:=0) as sr, `PM_Report` where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_Date is null or RFI_Date < '".$quarterStartDate."') and Is_Site_Active=1";

	// echo $sql;

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=Preventive_Maintenance.csv');
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
// Site Type wise PM
else if($graphType == 5){

	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$filterSql = "";
	if($siteType != ""){
		$siteType = str_replace("plus","+", $siteType);
		$filterSql .= " and `Site_CAT` = '$siteType' ";
	}

	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `Site_Id`, `Site_Name`, `Site_CAT` as `Site Category`, `Site Type`, `PM Done Date`, `Airtel Site Id`, `Airtel Load`, `MTNL/BSNL Site Id`, `MTNL/BSNL Load`, `VIL Site Id`, `VIL Load`, `RJIO Site Id`, `RJIO Load`, `No. of FE`, `Serial No. OF FE 1`, `Refilling date of FE 1`, `Expiry date of FE 1`, `Serial No. OF FE 2`, `Refilling date of FE 2`, `Expiry date of FE 2`, `Serial No. OF FE 3`, `Refilling date of FE 3`, `Expiry date of FE 3`, `Serial No. OF FE 4`, `Refilling date of FE 4`, `Expiry date of FE 4`, `Serial No. OF FE 5`, `Refilling date of FE 5`, `Expiry date of FE 5`, `Pole Type`, `No. of Pole`, `Pole Height`, `Airtel RRH`, `Airtel MW`, `Airtel GSM`, `MTNL/BSNL RRH`, `MTNL/BSNL MW`, `MTNL/BSNL GSM`, `VIL RRH`, `VIL MW`, `VIL GSM`, `RJIO RRH`, `RJIO MW`, `RJIO GSM`, `SMPS Make`, `No. of RM`, `No. of faulty`, `BB Make & Model`, `No. of BB`, `SOC &SOH status`, `Capacity in AH 1`, `Capacity in AH 2`, `Capacity in AH 3`, `PM done By`, `PM approved By:L1` FROM (select @sr:=0) as sr, `PM_Report` where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_Date is null or RFI_Date < '".$quarterStartDate."') and Is_Site_Active=1 ".$filterSql;

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=Site_Type_wise_PM_Status.csv');
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
// Metro and Airport sites PM
else if($graphType == 6){
	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$filterSql = "";

	if($metroSiteType == "High_R_Site"){
		$filterSql .= " and `High_Revenue_Site` = 1 ";
	}
	else if($metroSiteType == "ISQ"){
		$filterSql .= " and `ISQ` = 1 ";
	}
	else if($metroSiteType == "Retail_IBS"){
		$filterSql .= " and `Retail_IBS` = 1 ";
	}
	else{
		$filterSql .= " and `Airport_Metro` = '$metroSiteType' ";
	}

	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `Site_Id`, `Site_Name`, `Site_CAT` as `Site Category`, `Site Type`, `PM Done Date`, `Airtel Site Id`, `Airtel Load`, `MTNL/BSNL Site Id`, `MTNL/BSNL Load`, `VIL Site Id`, `VIL Load`, `RJIO Site Id`, `RJIO Load`, `No. of FE`, `Serial No. OF FE 1`, `Refilling date of FE 1`, `Expiry date of FE 1`, `Serial No. OF FE 2`, `Refilling date of FE 2`, `Expiry date of FE 2`, `Serial No. OF FE 3`, `Refilling date of FE 3`, `Expiry date of FE 3`, `Serial No. OF FE 4`, `Refilling date of FE 4`, `Expiry date of FE 4`, `Serial No. OF FE 5`, `Refilling date of FE 5`, `Expiry date of FE 5`, `Pole Type`, `No. of Pole`, `Pole Height`, `Airtel RRH`, `Airtel MW`, `Airtel GSM`, `MTNL/BSNL RRH`, `MTNL/BSNL MW`, `MTNL/BSNL GSM`, `VIL RRH`, `VIL MW`, `VIL GSM`, `RJIO RRH`, `RJIO MW`, `RJIO GSM`, `SMPS Make`, `No. of RM`, `No. of faulty`, `BB Make & Model`, `No. of BB`, `SOC &SOH status`, `Capacity in AH 1`, `Capacity in AH 2`, `Capacity in AH 3`, `PM done By`, `PM approved By:L1` FROM (select @sr:=0) as sr, `PM_Report` where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_Date is null or RFI_Date < '".$quarterStartDate."') and Is_Site_Active=1 ".$filterSql;

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=Airport_Metro_PM_Status.csv');
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
// Weekly PM
else if($graphType == 7){
	$monthNumber = date('m');
	$year = date('Y');
	$quarter = $jsonData->quarter;
	$quarterStartDate = getQuarterStartDate($quarter);
	$weekQuarter = $quarter;
	$quaterList = getQuarterMonthListForWeeklyPM($quarter);

	for($i=0;$i<count($quaterList);$i++){
		$qua = $quaterList[$i];
		$quaExplode = explode("-", $qua);
		$month = $quaExplode[0];
		if($month<10)$month = '0'.$month;
		$year = $quaExplode[1];
		$days = cal_days_in_month(CAL_GREGORIAN,$month,$year);
		$start = 1; $end = 7;
		$total = 0;
		while($days>$total){
		    $week[] = get_week_array($start,$end);
		    $total = $total+$end;
		    $start = $total+1;
		    $end = 7;
		}
	}

	$allDate = array();
	for($w=0;$w<count($week);$w++){
		$wwArr = $week[$w];
		for($ww=0;$ww<count($wwArr);$ww++){
			array_push($allDate, $wwArr[$ww]);
		}
	}

	$dd = implode("','", $allDate);

	$rfiDateQuarter = getQuarterMonth($weekQuarter);
	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `Site_Id`, `Site_Name`, `Site_CAT` as `Site Category`, `Site Type`, `PM Done Date`, `Airtel Site Id`, `Airtel Load`, `MTNL/BSNL Site Id`, `MTNL/BSNL Load`, `VIL Site Id`, `VIL Load`, `RJIO Site Id`, `RJIO Load`, `No. of FE`, `Serial No. OF FE 1`, `Refilling date of FE 1`, `Expiry date of FE 1`, `Serial No. OF FE 2`, `Refilling date of FE 2`, `Expiry date of FE 2`, `Serial No. OF FE 3`, `Refilling date of FE 3`, `Expiry date of FE 3`, `Serial No. OF FE 4`, `Refilling date of FE 4`, `Expiry date of FE 4`, `Serial No. OF FE 5`, `Refilling date of FE 5`, `Expiry date of FE 5`, `Pole Type`, `No. of Pole`, `Pole Height`, `Airtel RRH`, `Airtel MW`, `Airtel GSM`, `MTNL/BSNL RRH`, `MTNL/BSNL MW`, `MTNL/BSNL GSM`, `VIL RRH`, `VIL MW`, `VIL GSM`, `RJIO RRH`, `RJIO MW`, `RJIO GSM`, `SMPS Make`, `No. of RM`, `No. of faulty`, `BB Make & Model`, `No. of BB`, `SOC &SOH status`, `Capacity in AH 1`, `Capacity in AH 2`, `Capacity in AH 3`, `PM done By`, `PM approved By:L1` FROM (select @sr:=0) as sr, `PM_Report` where date_format(`PM Done Date`,'%Y-%m-%d') in ('".$dd."') and (RFI_Date_period is null or RFI_Date_period not in ('".$rfiDateQuarter."')) and (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_Site_Active = 1";

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=Weekly_PM_progress_graph.csv');
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
// PM punchpoint
else if($graphType == 8){
	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, p.`Circle`, p.`Site_Id`, p.`Site_Name`, p.`Site_CAT` as `Site Category`, p.`Site Type`, p.`PM Done Date`, p.`Airtel Site Id`, p.`Airtel Load`, p.`MTNL/BSNL Site Id`, p.`MTNL/BSNL Load`, p.`VIL Site Id`, p.`VIL Load`, p.`RJIO Site Id`, p.`RJIO Load`, p.`No. of FE`, p.`Serial No. OF FE 1`, p.`Refilling date of FE 1`, p.`Expiry date of FE 1`, p.`Serial No. OF FE 2`, p.`Refilling date of FE 2`, p.`Expiry date of FE 2`, p.`Serial No. OF FE 3`, p.`Refilling date of FE 3`, p.`Expiry date of FE 3`, p.`Serial No. OF FE 4`, p.`Refilling date of FE 4`, p.`Expiry date of FE 4`, p.`Serial No. OF FE 5`, p.`Refilling date of FE 5`, p.`Expiry date of FE 5`, p.`Pole Type`, p.`No. of Pole`, p.`Pole Height`, p.`Airtel RRH`, p.`Airtel MW`, p.`Airtel GSM`, p.`MTNL/BSNL RRH`, p.`MTNL/BSNL MW`, p.`MTNL/BSNL GSM`, p.`VIL RRH`, p.`VIL MW`, p.`VIL GSM`, p.`RJIO RRH`, p.`RJIO MW`, p.`RJIO GSM`, p.`SMPS Make`, p.`No. of RM`, p.`No. of faulty`, p.`BB Make & Model`, p.`No. of BB`, p.`SOC &SOH status`, p.`Capacity in AH 1`, p.`Capacity in AH 2`, p.`Capacity in AH 3`, p.`PM done By`, p.`PM approved By:L1` FROM (select @sr:=0) as sr, Punchpoint_Report pr join PM_Report p on pr.ActivityId = p.ActivityId where pr.Period in ('".$quarter."') and (pr.RFI_Date_period is null or pr.RFI_Date_period not in ('".$quarter."')) and (pr.RFI_date is null or pr.RFI_date < '".$quarterStartDate."') and pr.Is_Site_Active = 1 GROUP by pr.ActivityId ";

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=PM_punchpoint.csv');
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
else if($graphType == 9){
	$quarter = getQuarterMonth($quarter);

	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, im.`Circle`, im.`Site Id`, im.`Site Name`, im.`Incident category`, im.`Material Damaged`, im.`Incident Date`, im.`Incident Time`, im.`Incident description`, im.`Location (Lat Long)`, im.`Entered By`, im.`Approved status By L1`, im.`Approved status By L2` from (SELECT t.Site_Id, sum(t.repeatCount) as RepeatCount from (SELECT Site_Id, count(Site_Id) as repeatCount FROM Incident_Graph where Period in ('".$quarter."') and Incident_category = 'Fiber Cut' GROUP by Site_Id) t GROUP by t.Site_Id) t1 join Incident_Management_Report im on t1.Site_Id = im.`Site Id`, (select @sr:=0) as sr where t1.RepeatCount !=1  and im.Period in ('".$quarter."') ";

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=Frequent_failing_fiber_cut_site_wise.csv');
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
else if($graphType == 10){
	$trainingName = str_replace("nnn","&", $trainingName);
	$sql = "SELECT `Emp Circle` as State, `Training Name`, `Submit By`, `Submit Date`, `Total Question`, `Correct`, `Incorrect`, `Percentage`, `Result` FROM `Training_Report` where `Training Name` = '$trainingName'";

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
// Metro and Airport sites PM 2
else if($graphType == 11){
	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$filterSql = "";

	$filterSql .= " and (`Airport_Metro` in ('Airport Site','CMRL','DMRC','JMRC','LMRC') ";
	$filterSql .= " or `High_Revenue_Site` = 1 ";
	$filterSql .= " or `ISQ` = 1 ";
	$filterSql .= " or `Retail_IBS` = 1) ";
	

	$sql = "SELECT (@sr := @sr+1) as `Sr. No.`, `Circle`, `Site_Id`, `Site_Name`, `Site_CAT` as `Site Category`, `Site Type`, `PM Done Date`, `Airtel Site Id`, `Airtel Load`, `MTNL/BSNL Site Id`, `MTNL/BSNL Load`, `VIL Site Id`, `VIL Load`, `RJIO Site Id`, `RJIO Load`, `No. of FE`, `Serial No. OF FE 1`, `Refilling date of FE 1`, `Expiry date of FE 1`, `Serial No. OF FE 2`, `Refilling date of FE 2`, `Expiry date of FE 2`, `Serial No. OF FE 3`, `Refilling date of FE 3`, `Expiry date of FE 3`, `Serial No. OF FE 4`, `Refilling date of FE 4`, `Expiry date of FE 4`, `Serial No. OF FE 5`, `Refilling date of FE 5`, `Expiry date of FE 5`, `Pole Type`, `No. of Pole`, `Pole Height`, `Airtel RRH`, `Airtel MW`, `Airtel GSM`, `MTNL/BSNL RRH`, `MTNL/BSNL MW`, `MTNL/BSNL GSM`, `VIL RRH`, `VIL MW`, `VIL GSM`, `RJIO RRH`, `RJIO MW`, `RJIO GSM`, `SMPS Make`, `No. of RM`, `No. of faulty`, `BB Make & Model`, `No. of BB`, `SOC &SOH status`, `Capacity in AH 1`, `Capacity in AH 2`, `Capacity in AH 3`, `PM done By`, `PM approved By:L1` FROM (select @sr:=0) as sr, `PM_Report` where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_Site_Active = 1 ".$filterSql;

	// echo $sql;

	$result = mysqli_query($conn,$sql);
	$row=mysqli_fetch_assoc($result);
	$columnName = array();
	foreach ($row as $key => $value) {
		array_push($columnName, $key);
	}

	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=Airport_Metro_PM_Status_2.csv');
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

?>

<?php
header('Content-Type: text/html');
function sessionExpired(){
	echo "<h1>Session Expired.</h1>";
}
function get_week_array($start,$end){
    global $month, $year, $days;
    for($i=0;$i<$end;$i++){
        if($start<10)$array[] = $year.'-'.$month.'-0'.$start;
        else $array[] = $year.'-'.$month.'-'.$start;
        $start = $start+1;
        if($start==$days+1)break;
    }
    return $array;
}
function getQuarterMonthOld($quarter){
	$monthNumber = date('m');
	$year = date('Y');
	$quaterList = array();
	if($quarter == "Q1"){
		if($monthNumber < 4){
			$year = $year - 1;
		}
		array_push($quaterList, 'Apr-'.$year);
		array_push($quaterList, 'May-'.$year);
		array_push($quaterList, 'Jun-'.$year);
	}
	else if($quarter == "Q2"){
		if($monthNumber < 4){
			$year = $year - 1;
		}
		array_push($quaterList, 'Jul-'.$year);
		array_push($quaterList, 'Aug-'.$year);
		array_push($quaterList, 'Sep-'.$year);
	}
	else if($quarter == "Q3"){
		if($monthNumber < 4){
			$year = $year - 1;
		}
		array_push($quaterList, 'Oct-'.$year);
		array_push($quaterList, 'Nov-'.$year);
		array_push($quaterList, 'Dec-'.$year);
	}
	else if($quarter == "Q4"){
		if($monthNumber > 3){
			$year = $year + 1;
		}
		array_push($quaterList, 'Jan-'.$year);
		array_push($quaterList, 'Feb-'.$year);
		array_push($quaterList, 'Mar-'.$year);
	}

	$quarter = implode("','", $quaterList);
	return $quarter;

}

function getQuarterMonth($quarter){
	global $financialYear;
	if($financialYear == null){
		return getQuarterMonthOld($quarter);
	}
	$fny = explode(" - ", $financialYear);
	$fnyStart = $fny[0];
	$fnyEnd = $fny[1];
	$quaterList = array();
	if($quarter == "Q1"){
		array_push($quaterList, 'Apr-'.$fnyStart);
		array_push($quaterList, 'May-'.$fnyStart);
		array_push($quaterList, 'Jun-'.$fnyStart);
	}
	else if($quarter == "Q2"){
		array_push($quaterList, 'Jul-'.$fnyStart);
		array_push($quaterList, 'Aug-'.$fnyStart);
		array_push($quaterList, 'Sep-'.$fnyStart);
	}
	else if($quarter == "Q3"){
		array_push($quaterList, 'Oct-'.$fnyStart);
		array_push($quaterList, 'Nov-'.$fnyStart);
		array_push($quaterList, 'Dec-'.$fnyStart);
	}
	else if($quarter == "Q4"){
		array_push($quaterList, 'Jan-'.$fnyEnd);
		array_push($quaterList, 'Feb-'.$fnyEnd);
		array_push($quaterList, 'Mar-'.$fnyEnd);
	}
	$quarter = implode("','", $quaterList);
	return $quarter;

}

function getQuarterStartDateOld($quarter){
	$monthNumber = date('m');
	$year = date('Y');
	$quarterStartDate = "";
	if($quarter == "Q1"){
		if($monthNumber < 4){
			$year = $year - 1;
		}
		$quarterStartDate = $year."-04-01";
	}
	else if($quarter == "Q2"){
		if($monthNumber < 4){
			$year = $year - 1;
		}
		$quarterStartDate = $year."-07-01";
	}
	else if($quarter == "Q3"){
		if($monthNumber < 4){
			$year = $year - 1;
		}
		$quarterStartDate = $year."-10-01";
	}
	else if($quarter == "Q4"){
		if($monthNumber > 3){
			$year = $year + 1;
		}
		$quarterStartDate = $year."-01-01";
	}
	return $quarterStartDate;
}

function getQuarterStartDate($quarter){
	global $financialYear;
	if($financialYear == null){
		return getQuarterStartDateOld($quarter);
	}
	$fny = explode(" - ", $financialYear);
	$fnyStart = $fny[0];
	$fnyEnd = $fny[1];
	$quarterStartDate = "";
	if($quarter == "Q1"){
		$quarterStartDate = $fnyStart."-04-01";
	}
	else if($quarter == "Q2"){
		$quarterStartDate = $fnyStart."-07-01";
	}
	else if($quarter == "Q3"){
		$quarterStartDate = $fnyStart."-10-01";
	}
	else if($quarter == "Q4"){
		$quarterStartDate = $fnyEnd."-01-01";
	}
	return $quarterStartDate;
}

function getQuarterMonthListForWeeklyPMOld($quarter){
	$monthNumber = date('m');
	$year = date('Y');
	$quaterList = array();
	if($quarter == "Q1"){
		if($monthNumber < 4){
			$year = $year - 1;
		}
		array_push($quaterList, '4-'.$year);
		array_push($quaterList, '5-'.$year);
		array_push($quaterList, '6-'.$year);
	}
	else if($quarter == "Q2"){
		if($monthNumber < 4){
			$year = $year - 1;
		}
		array_push($quaterList, '7-'.$year);
		array_push($quaterList, '8-'.$year);
		array_push($quaterList, '9-'.$year);
	}
	else if($quarter == "Q3"){
		if($monthNumber < 4){
			$year = $year - 1;
		}
		array_push($quaterList, '10-'.$year);
		array_push($quaterList, '11-'.$year);
		array_push($quaterList, '12-'.$year);
	}
	else if($quarter == "Q4"){
		if($monthNumber > 3){
			$year = $year + 1;
		}
		array_push($quaterList, '1-'.$year);
		array_push($quaterList, '2-'.$year);
		array_push($quaterList, '3-'.$year);
	}
	return $quaterList;
}

function getQuarterMonthListForWeeklyPM($quarter){
	global $financialYear;
	if($financialYear == null){
		return getQuarterMonthListForWeeklyPMOld($quarter);
	}
	$fny = explode(" - ", $financialYear);
	$fnyStart = $fny[0];
	$fnyEnd = $fny[1];
	$quaterList = array();
	if($quarter == "Q1"){
		array_push($quaterList, '4-'.$fnyStart);
		array_push($quaterList, '5-'.$fnyStart);
		array_push($quaterList, '6-'.$fnyStart);
	}
	else if($quarter == "Q2"){
		array_push($quaterList, '7-'.$fnyStart);
		array_push($quaterList, '8-'.$fnyStart);
		array_push($quaterList, '9-'.$fnyStart);
	}
	else if($quarter == "Q3"){
		array_push($quaterList, '10-'.$fnyStart);
		array_push($quaterList, '11-'.$fnyStart);
		array_push($quaterList, '12-'.$fnyStart);
	}
	else if($quarter == "Q4"){
		array_push($quaterList, '1-'.$fnyEnd);
		array_push($quaterList, '2-'.$fnyEnd);
		array_push($quaterList, '3-'.$fnyEnd);
	}
	return $quaterList;
}
?>