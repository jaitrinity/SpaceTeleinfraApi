<?php 
include("dbConfiguration.php");
$requestJson = file_get_contents('php://input');
$jsonData=json_decode($requestJson);
$loginEmpId = $jsonData->loginEmpId;
$loginEmpRole = $jsonData->loginEmpRole;
$period = $jsonData->period;
$incidentCategory = $jsonData->incidentCategory;
$quarter = $jsonData->quarter;
$financialYear = $jsonData->financialYear;
$graphType = $jsonData->graphType;

// Incident Management Graph
if($graphType == 1){
	$dataPoints = array();
	$tableColumn = array();
	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}
	$sql = "";
	if($incidentCategory == ""){
		$sql = "SELECT `Incident_category` as `Category`, COUNT(*) as `Count` FROM `Incident_Graph` where `Period` = '$period' ".$filterSql." GROUP by `Incident_category` ";
		array_push($tableColumn,"Incident Type");
		array_push($tableColumn,"Incident");
	}
	else{
		$sql = "SELECT `State` as `Category`, COUNT(*) as Count FROM `Incident_Graph` where `Period` = '$period' and `Incident_category` = '$incidentCategory' ".$filterSql." GROUP by `State` Order by `State`";
		array_push($tableColumn,"Circle");
		array_push($tableColumn,$incidentCategory);
	}

	$totalCount = 0;
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$state = $row["Category"];
		$count = $row["Count"];
		$color = getColorByCircle($state);

		$data = array('label' => $state, 'y' => $count, 'color' => $color);
		array_push($dataPoints, $data);
		
		
	}

	$output = array();
	$output = array('dataPoints' => $dataPoints, 'tableColumn' => $tableColumn);
	echo json_encode($output);
}
// Frequent failing fiber cut circle wise
else if($graphType == 2){
	
	$imPeriod = getQuarterMonth($quarter);

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}

	$dataArr = array();

	$sql = "select t1.State as Category, sum(t1.repeatCount) as Count from (select t.State, t.Site_Id, (case when count(t.stateSiteId) < 2 then 0 else 1 end) repeatCount from (SELECT concat(State,'-',Site_Id) stateSiteId, State, Site_Id FROM Incident_Graph where Period in ('".$imPeriod."') and Incident_category = 'Fiber Cut' ".$filterSql.") t GROUP by t.stateSiteId) t1 GROUP by t1.State Order By t1.State";
	$sql = "select tt.Category, tt.Count from ($sql) tt order by tt.Count desc";
	$query = mysqli_query($conn,$sql);
	
	while ($row = mysqli_fetch_assoc($query)) {
		$circle = $row["Category"];
		$count = $row["Count"];
		$color = getColorByCircle($circle);

		$data = array('label' => $circle, 'y' => intval($count), 'color' => $color);
		array_push($dataArr, $data);
		
	}

	$output = array();
	$output = array('dataArr' => $dataArr);
	echo json_encode($output);
}
// Fiber Cut Incident and MTTR of Fiber Cut
else if($graphType == 3){
	
	$dataArr = array();
	$dataArr1 = array();
	
	
	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and im.`Employee Id` = '$loginEmpId' ";
		$filterSql .= "and im.`Site Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}

	$sql = "select t.State as Circle, t.Incident_count, round(t.Incident_minute/t.Incident_count,0) as MTTR from (SELECT s.State, count(im.Circle) as Incident_count, sum(im.incident_minute) as Incident_minute FROM StateCityAreaMaster s left join Incident_MTTR im on s.State = im.Circle and im.Incident_Month = '$period' and im.`Incident category` = 'Fiber Cut' where s.State is not null ".$filterSql."  GROUP by s.State order by s.State) t";

	$sql = "select tt.Circle, tt.Incident_count, tt.MTTR from ($sql) tt order by tt.Circle";
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$circle = $row["Circle"];
		$inciCount = $row["Incident_count"];
		$mttr = $row["MTTR"];
		$color = getColorByCircle($circle);

		
		$json = array('label' => $circle, 'y' => intval($inciCount), 'color' => $color);
		array_push($dataArr, $json);

		$json1 = array('label' => $circle, 'y' => intval($mttr), 'color' => $color);
		array_push($dataArr1, $json1);
	}

	$output = array();
	$output = array('dataArr' => $dataArr, 'dataArr1' => $dataArr1);
	echo json_encode($output);
}
// Preventive Maintenance
else if($graphType == 4){

	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$dataArr = array();
	

	$nonAdmin = "No";
	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$nonAdmin = "Yes";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}

	$sql = "SELECT t3.State, t3.Site_count, t3.PM_done, round((t3.PM_done/t3.Site_count)*100,0) as 'Done_Percentage' from (select s.State, s.Site_count, (case when t2.PM_done is null then 0 else t2.PM_done end) as PM_done from (SELECT l.State, count(l.Site_Id) as Site_count FROM Location l where l.Site_Id is not null and l.Site_Id != '' and (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 GROUP BY l.State order by l.State) s left join (select t1.State, sum(t1.Single_count) as PM_done from (select t.State, t.Site_Id, t.State_site_id, count(t.State_site_id) as Done_count, (case when count(t.State_site_id) > 1 then 1 else count(t.State_site_id) end) as Single_count from (SELECT State, Site_Id, Site_Id as State_site_id FROM PM_Graph 
	where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_Date is null or RFI_Date < '".$quarterStartDate."') and Is_Site_Active = 1 ".$filterSql.") t GROUP by t.State_site_id) t1 GROUP by t1.State) t2 on s.State = t2.State ) t3 ";

	$gtSql = "SELECT gt1.State, gt1.Site_count, gt1.PM_done, round((gt1.PM_done/gt1.Site_count)*100,0) as 'Done_Percentage' from (select 'Total ' as State, sum(gt.Site_count) as Site_count, sum(gt.PM_done) as PM_done from (SELECT t3.State, t3.Site_count, t3.PM_done, round((t3.PM_done/t3.Site_count)*100,0) as 'Done_Percentage' from (select s.State, s.Site_count, (case when t2.PM_done is null then 0 else t2.PM_done end) as PM_done from (SELECT l.State, count(l.Site_Id) as Site_count FROM Location l where l.Site_Id is not null and l.Site_Id != '' and (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 GROUP BY l.State) s left join (select t1.State, sum(t1.Single_count) as PM_done from (select t.State, t.Site_Id, t.State_site_id, count(t.State_site_id) as Done_count, (case when count(t.State_site_id) > 1 then 1 else count(t.State_site_id) end) as Single_count from (SELECT State, Site_Id, Site_Id as State_site_id FROM PM_Graph 
where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_Date is null or RFI_Date < '".$quarterStartDate."') and Is_Site_Active = 1 ".$filterSql.") t GROUP by t.State_site_id) t1 GROUP by t1.State) t2 on s.State = t2.State ) t3) gt) gt1 ";

	$sql .= ' UNION '.$gtSql;
	$sql = "select tt.State, tt.Site_count, tt.PM_done, tt.Done_Percentage from ($sql) tt order by tt.Done_Percentage desc ";
	// echo $sql;

	$query = mysqli_query($conn,$sql);
	$total = "0";
	while ($row = mysqli_fetch_assoc($query)) {
		$state = $row["State"];
		$donePercentage = $row["Done_Percentage"];
		if($state != "Total "){
			$color = getColorByCircle($state);
			$json = array('label' => $state, 'y' => intval($donePercentage), 'color' => $color);
			array_push($dataArr, $json);
		}
		else{
			$total = $donePercentage;
		}
			
	}
	if($nonAdmin == "Yes"){
		$total = $dataArr[0];
	}


	$output = array();
	$output = array('dataArr' => $dataArr, 'total' => $total);
	echo json_encode($output);
}
// Site Type wise PM
else if($graphType == 5){
	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);
	$siteType = $jsonData->siteType;
	
	$dataArr = array();
	

	$nonAdmin = "No";
	$filterSql = "";
	$siteTypeSql = "";
	$gropupBySql = "";

	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$nonAdmin = "Yes";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}

	if($siteType != ""){
		$filterSql .= " and Site_CAT = '$siteType' ";
		$siteTypeSql .= " and l.Site_CAT = '$siteType' ";
		$gropupBySql .= ", l.Site_CAT";
	}

	

	$sql = "SELECT t3.State, t3.Site_count, t3.PM_done, round((t3.PM_done/t3.Site_count)*100,0) as 'Done_Percentage' from (select s.State, s.Site_count, (case when t2.PM_done is null then 0 else t2.PM_done end) as PM_done from (SELECT l.State, count(l.Site_Id) as Site_count FROM Location l where l.Site_Id is not null and l.Site_Id != '' ".$siteTypeSql." and (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 GROUP BY l.State".$gropupBySql." order by l.State) s left join (select t1.State, sum(t1.Single_count) as PM_done from (select t.State, t.Site_Id, t.State_site_id, count(t.State_site_id) as Done_count, (case when count(t.State_site_id) > 1 then 1 else count(t.State_site_id) end) as Single_count from (SELECT State, Site_Id, Site_Id as State_site_id FROM PM_Graph 
	where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_Site_Active = 1 ".$filterSql.") t GROUP by t.State_site_id) t1 GROUP by t1.State) t2 on s.State = t2.State ) t3 ";

	$gtSql = "SELECT gt1.State, gt1.Site_count, gt1.PM_done, round((gt1.PM_done/gt1.Site_count)*100,0) as 'Done_Percentage' from (select 'Total ' as State, sum(gt.Site_count) as Site_count, sum(gt.PM_done) as PM_done from (SELECT t3.State, t3.Site_count, t3.PM_done, round((t3.PM_done/t3.Site_count)*100,0) as 'Done_Percentage' from (select s.State, s.Site_count, (case when t2.PM_done is null then 0 else t2.PM_done end) as PM_done from (SELECT l.State, count(l.Site_Id) as Site_count FROM Location l where l.Site_Id is not null and l.Site_Id != '' ".$siteTypeSql." and (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 GROUP BY l.State".$gropupBySql." ) s left join (select t1.State, sum(t1.Single_count) as PM_done from (select t.State, t.Site_Id, t.State_site_id, count(t.State_site_id) as Done_count, (case when count(t.State_site_id) > 1 then 1 else count(t.State_site_id) end) as Single_count from (SELECT State, Site_Id, Site_Id as State_site_id FROM PM_Graph 
		where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_Site_Active = 1 ".$filterSql.") t GROUP by t.State_site_id) t1 GROUP by t1.State) t2 on s.State = t2.State ) t3) gt) gt1 ";

	$sql .= ' UNION '.$gtSql;

	$sql = "select tt.State, tt.Site_count, tt.PM_done, tt.Done_Percentage from ($sql) tt order by tt.Done_Percentage desc ";

	// echo $sql;

	$query = mysqli_query($conn,$sql);
	$total = "0";
	while ($row = mysqli_fetch_assoc($query)) {
		$state = $row["State"];
		$donePercentage = $row["Done_Percentage"];
		if($state != "Total "){
			$color = getColorByCircle($state);
			$json = array('label' => $state, 'y' => intval($donePercentage), 'color' => $color);
			array_push($dataArr, $json);
		}
		else{
			$total = $donePercentage;
		}
			
	}

	if($nonAdmin == "Yes"){
		$total = $dataArr[0];
	}

	$output = array();
	$output = array('dataArr' => $dataArr, 'total' => $total);
	echo json_encode($output);
}
// Metro and Airport sites PM
else if($graphType == 6){
	
	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);
	$metroSiteType = $jsonData->metroSiteType;

	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();

	array_push($tableColumn, $metroSiteType);
	array_push($tableColumn,"PM");
	array_push($tableColumn,"Pending");

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and p.`Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}
	
	if($metroSiteType == "High_R_Site"){
		$sql = "SELECT 'High Revenue Site' as Site_Type, count(*) as Site_Count FROM Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and High_Revenue_Site = 1 and Is_Active = 1";
	}
	else if($metroSiteType == "ISQ"){
		$sql = "SELECT 'ISQ' as Site_Type, count(*) as Site_Count FROM Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and ISQ = 1 and Is_Active = 1";
	}
	else if($metroSiteType == "Retail_IBS"){
		$sql = "SELECT 'Retail IBS' as Site_Type, count(*) as Site_Count FROM Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and Retail_IBS = 1 and Is_Active = 1";
	}
	else{
		$sql = "SELECT l.Airport_Metro as Site_Type, count(l.Airport_Metro) as Site_Count FROM Location l where (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and l.Airport_Metro = '$metroSiteType' and Is_Active = 1 GROUP by l.Airport_Metro";
	}
	$query = mysqli_query($conn,$sql);
	$rowCount = mysqli_num_rows($query);
	$siteCount = 0;
	if($rowCount != 0){
		$row = mysqli_fetch_assoc($query);
		$siteType = $row["Site_Type"];
		$siteCount = $row["Site_Count"];
		array_push($tableData, $siteCount);
	}

	if($metroSiteType == "High_R_Site"){
		$sql1 = "SELECT t.Site_Type, count(t.Site_Type) as Site_Count from (SELECT DISTINCT p.Site_Id, 'High Revenue Site' as Site_Type FROM PM_Graph p 
		where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') and p.Is_Site_Active = 1 ".$filterSql." and p.High_Revenue_Site = 1) t GROUP by t.Site_Type";
	}
	else if($metroSiteType == "ISQ"){
		$sql1 = "SELECT t.Site_Type, count(t.Site_Type) as Site_Count from (SELECT DISTINCT p.Site_Id, 'ISQ' as Site_Type FROM PM_Graph p 
		where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') and p.Is_Site_Active = 1 ".$filterSql." and p.ISQ = 1) t GROUP by t.Site_Type";
	}
	else if($metroSiteType == "Retail_IBS"){
		$sql1 = "SELECT t.Site_Type, count(t.Site_Type) as Site_Count from (SELECT DISTINCT p.Site_Id, 'Retail IBS' as Site_Type FROM PM_Graph p 
		where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') and p.Is_Site_Active = 1 ".$filterSql." and p.Retail_IBS = 1) t GROUP by t.Site_Type";
	}
	else{
		$sql1 = "SELECT t.Site_Type, count(t.Site_Type) as Site_Count from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p 
		where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') and p.Is_Site_Active = 1 ".$filterSql." and p.Airport_Metro = '$metroSiteType') t GROUP by t.Site_Type";
	}

	// echo $sql1;

	$query1 = mysqli_query($conn,$sql1);
	$row1Count = mysqli_num_rows($query1);
	$pmCount = 0;
	if($row1Count != 0){
		$row1 = mysqli_fetch_assoc($query1);
		// $siteType = $row1["Site_Type"];
		$pmCount = $row1["Site_Count"];
	}

	array_push($tableData, $pmCount);
	
	$pending = $siteCount - $pmCount;
	array_push($tableData, $pending);

	$json = array('label' => 'PM', 'y' => $pmCount);
	array_push($dataArr, $json);

	$json1 = array('label' => 'Pending', 'y' => $pending);
	array_push($dataArr, $json1);

	
	$output = array();
	$output = array('dataArr' => $dataArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData);
	echo json_encode($output);
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
	$dateStr = "";
	for($aa=0;$aa<count($allDate);$aa++){
		$ii = $aa + 1;
		$dateStr .= $allDate[$aa];
		// if($ii / 7 != 1) $dateStr .= ",";
		if($ii % 7 == 0) $dateStr .= ":";
		else $dateStr .= ",";
	}
	// echo "<pre>";print_r($allDate);
	$dateStr = rtrim($dateStr,",");

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}
	
	$rfiDateQuarter = getQuarterMonth($weekQuarter);
	$sqlArr = array();
	$weekArr = explode(":", $dateStr);
	for($j=0;$j<count($weekArr);$j++){
		$ww = "Week ".($j+1);
		$wwArr = explode(",", $weekArr[$j]);
		$weekDate = implode(",", $wwArr);
		$dd = implode("','", $wwArr);
		$tempSql = "SELECT '$ww' as `Week`, '".$weekDate."' as `WeekDate`, count(DISTINCT `Site_Id`) as `PM_done` FROM `PM_Graph` where date_format(`MobileDateTime`,'%Y-%m-%d') in ('".$dd."') and (RFI_Date_period is null or RFI_Date_period not in ('".$rfiDateQuarter."')) and (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_Site_Active = 1 ".$filterSql;
		array_push($sqlArr, $tempSql);
	}

	$dataArr = array();
	$dataArr1 = array();
	$dataArr2 = array();

	$weekSql = implode(" UNION ", $sqlArr);

	$todayDate = date('Y-m-d');
	$sql = "SELECT t.`Week`, t.`WeekDate`, t.`PM_done`, wt.`Target` from ($weekSql) t left join `Weekly_Target` wt on t.`Week` = wt.`Weekly` ";
	// $sql .= "where t.`WeekDate` <= '$todayDate' ";
	$sql .= "where t.`PM_done` > 0 ";
	
	$query = mysqli_query($conn,$sql);
	$cumalative = 0;
	while ($row = mysqli_fetch_assoc($query)) {
		$week = $row["Week"];
		$pmDone = $row["PM_done"];
		$cumalative = $cumalative + $pmDone;
		$target = $row["Target"];

		$json = array('label' => $week, 'y' => intval($pmDone));
		array_push($dataArr, $json);

		$json1 = array('label' => $week, 'y' => intval($cumalative));
		array_push($dataArr1, $json1);

		$json2 = array('label' => $week, 'y' => intval($target));
		array_push($dataArr2, $json2);	

	}

	$output = array();
	$output = array('dataArr' => $dataArr, 'dataArr1' => $dataArr1, 'dataArr2' => $dataArr2);
	echo json_encode($output);
}
// PM punchpoint
else if($graphType == 8){

	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$dataArr = array();


	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and p.`Site Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}

	$sql = "select s.State, sum(case when p.Status = 'Not Ok' then 1 else 0 end) as `Punchpoint` FROM StateCityAreaMaster s left join Punchpoint_Report p on s.State = p.State and p.Period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') and p.Is_Site_Active = 1 where s.State is not null ".$filterSql." GROUP by s.State ORDER by s.State";

	$sql = "select tt.State, tt.Punchpoint from ($sql) tt order by tt.Punchpoint desc ";

	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$state = $row["State"];
		$punchpoint = $row["Punchpoint"];
		$color = getColorByCircle($state);
		
		$json = array('state' => $state, 'punchpoint' => $punchpoint, 'color' => $color);
		array_push($dataArr, $json);

	}

	$output = array();
	$output = array('dataArr' => $dataArr);
	echo json_encode($output);
}
// Frequent failing fiber cut site wise
else if($graphType == 9){
	$quarter = getQuarterMonth($quarter);

	$tableColumn = array();
	$tableData = array();
	$maxValue = 0;
	array_push($tableColumn,"Circle");
	array_push($tableColumn,"Site Id");
	array_push($tableColumn,"Repeat Count");

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}
	$sql = "SELECT  max(t.State) as Circle, t.Site_Id, sum(t.repeatCount) as RepeatCount from (SELECT State, Site_Id, count(Site_Id) as repeatCount FROM Incident_Graph where Period in ('".$quarter."') and Incident_category = 'Fiber Cut' ".$filterSql." GROUP by Site_Id) t GROUP by t.Site_Id";
	$sql = "select tt.Circle, tt.Site_Id, tt.RepeatCount from ($sql) tt order by tt.RepeatCount desc";
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$count = $row["RepeatCount"];
		if($count != 1){
			$json = array('circle' => $row["Circle"], 'siteId' => $row["Site_Id"], 'repeatCount' => $row["RepeatCount"]);
			array_push($tableData, $json);
		}
	}
	

	$output = array();
	$output = array('tableColumn' => $tableColumn, 'tableData' => $tableData);
	echo json_encode($output);
}

// Training Graph
else if($graphType == 10){
	$dataArr = array();

	$training = $jsonData->trainingName;
	$state = $jsonData->state;

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		$filterSql .= "and e.State = '$state' ";
	}

	$sql = "SELECT t4.State, t4.EmpStateCount, t4.PassCount, round((t4.PassCount/t4.EmpStateCount)*100,0) PassPercent from (select t3.State, t3.Color, count(t3.EmpId) EmpStateCount, sum(t3.PassCount) as PassCount from (select t2.State, t2.Color, t2.EmpId, (case when t2.Result = 'Pass' then 1 else 0 end) PassCount from (select t1.State, t1.Color, t1.EmpId, max(t1.Result) Result from (select t.State, t.Color, t.EmpId, tr.ServerDateTime, tr.Result from (SELECT s.State, s.Color, e.EmpId FROM StateCityAreaMaster s left join Employees e on s.State = e.State where s.State is not null ".$filterSql." and e.RoleId in (43,44,45) and e.Active = 1) t left join Training_Report tr on t.EmpId = tr.`Emp Id` and tr.`Training Name` = '$training') t1 GROUP BY t1.State,t1.EmpId) t2) t3 GROUP by t3.State) t4";

	$sql = "select tt.State, tt.EmpStateCount, tt.PassCount, tt.PassPercent from ($sql) tt order by tt.PassPercent desc";
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$state = $row["State"];
		$passCount = $row["PassCount"];
		$passPercent = $row["PassPercent"];
		$color = getColorByCircle($state);
		if($passCount != null){
			$json = array('label' => $state, 'y' => intval($passPercent), 'color' => $color);
			array_push($dataArr, $json);
		}
			
		
	}
	
	$output = array();
	$output = array('dataArr' => $dataArr);
	echo json_encode($output);
}

// Metro and Airport sites PM 2
else if($graphType == 11){
	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$dataArr = array();

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and p.`Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId and l.Is_NBS_Site = 0 and l.Tenent_Id = 2 and l.Is_Active = 1 WHERE `Emp_Id` = '$loginEmpId')";
	}

	$sql = "SELECT t.Site_Type, t.Site_count, t.PM_done, round((t.PM_done/t.Site_count)*100,0) as 'Done_Percentage' from (SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and Airport_Metro = 'Airport Site' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'Airport Site' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and Airport_Metro = 'CMRL' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'CMRL' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and Airport_Metro = 'DMRC' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'DMRC' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and Airport_Metro = 'JMRC' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'JMRC' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and Airport_Metro = 'LMRC' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'LMRC' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and Retail_IBS = 1 and Is_Active=1) Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, 'Retail IBS' as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Retail_IBS = 1 and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and ISQ = 1 and Is_Active=1) Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, 'ISQ' as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.ISQ = 1 and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and High_Revenue_Site = 1 and Is_Active=1) Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, 'High_R_Site' as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.High_Revenue_Site = 1 and p.Is_Site_Active =1) t GROUP by t.Site_Type) t";

	$sql = "select tt.Site_Type, tt.Site_count, tt.PM_done, tt.Done_Percentage from ($sql) tt order by tt.Done_Percentage desc";

	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$donePercentage = $row["Done_Percentage"];
		$json = array('label' => $row["Site_Type"], 'y' => intval($donePercentage));
		array_push($dataArr, $json);
	}
	
	$output = array();
	$output = array('dataArr' => $dataArr);
	echo json_encode($output);
}
// Training Graph 2
else if($graphType == 12){
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();

	array_push($tableColumn, "Emp Name");
	array_push($tableColumn, "Status");
	array_push($tableColumn, "Percentage");


	$training = $jsonData->trainingName;
	$state = $jsonData->state;

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB" && $loginEmpRole != "Corporate OnM lead"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
	}

	$sql = "SELECT `EmpId`, `Name` from `Employees` where `State` = '$state' ".$filterSql." and `RoleId` in (43,44,45) and Active = 1 ";
	// echo $sql;
	$query = mysqli_query($conn,$sql);
	$empCount = mysqli_num_rows($query);
	$passCount = 0;
	$failCount = 0;
	$pendingCount = 0;
	while ($row = mysqli_fetch_assoc($query)) {
		$empName = $row["Name"];
		$empId = $row["EmpId"];
		$trResult = "NA";
		$trPercentage = "NA";
		$sql2 = "SELECT `Result`, `Percentage` FROM Training_Report where `Emp Id` = '$empId' and `Training Name` = '$training' ORDER by ServerDateTime desc LIMIT 0,1 ";
		$query2 = mysqli_query($conn,$sql2);
		$resultCount = mysqli_num_rows($query2);
		if($resultCount == 0){
			$pendingCount++;
		}
		else{
			$row2 = mysqli_fetch_assoc($query2);
			$trResult = $row2["Result"];
			$trPercentage = $row2["Percentage"].' %';
			if($trResult == 'Pass'){
				$passCount++;
			}
			else if($trResult == "Fail"){
				$failCount++;
			}
		}
	
		$json = array('name' => $empName, 'status' => $trResult, 'percentage' => $trPercentage);
		array_push($tableData, $json);
		
	}

	if($empCount != 0){
		$passPercentage = round(($passCount/$empCount)*100);
		$failPercentage = round(($failCount/$empCount)*100);
		$pendingPercentage = round(($pendingCount/$empCount)*100);
	}

	$json = array('type' => 'Pass', 'count' => $passCount, 'percentage' => $passPercentage);
	array_push($dataArr, $json);

	$json = array('type' => 'Fail', 'count' => $failCount, 'percentage' => $failPercentage);
	array_push($dataArr, $json);

	$json = array('type' => 'Pending', 'count' => $pendingCount, 'percentage' => $pendingPercentage);
	array_push($dataArr, $json);


	$output = array();
	$output = array('dataArr' => $dataArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData);
	echo json_encode($output);
}
// Metro and Airport sites PM 3
else if($graphType == 13){
	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);
	$metroSiteType = $jsonData->metroSiteType;

	$sql = "";
	$filterSql = "";
	if($metroSiteType == "High_R_Site"){
		$sql = "SELECT Site_Id FROM `Location` where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and High_Revenue_Site = 1 and Is_Active = 1";
		$filterSql = " and High_Revenue_Site = 1";
	}
	else if($metroSiteType == "ISQ"){
		$sql = "SELECT Site_Id FROM `Location` where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and ISQ = 1 and Is_Active = 1";
		$filterSql = " and ISQ = 1";
	}
	else if($metroSiteType == "Retail_IBS"){
		$sql = "SELECT Site_Id FROM `Location` where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and Retail_IBS = 1 and Is_Active = 1";
		$filterSql = " and Retail_IBS = 1";
	}
	else{
		$sql = "SELECT Site_Id FROM `Location` where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_NBS_Site = 0 and Airport_Metro = '$metroSiteType' and Is_Active = 1";
		$filterSql = " and Airport_Metro = '$metroSiteType'";
	}
	$dataArr = array();
	
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$siteId = $row["Site_Id"];
		$sql1 = "SELECT * FROM `PM_Graph` where Site_Id = '$siteId' and PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_Site_Active = 1 ".$filterSql;
		$query1 = mysqli_query($conn,$sql1);
		$rowcount = mysqli_num_rows($query1);
		$status = 'Not Done';
		if($rowcount != 0){
			$status = 'Done';
		}
		
		$json = array('siteId' => $siteId, 'status' => $status );
		array_push($dataArr, $json);
	}
	
	$output = array();
	$output = array('dataArr' => $dataArr);
	echo json_encode($output);
}
// Training Question
else if($graphType == 14){
	$trainingName = $jsonData->trainingName;
	$dataArr = array();
	$sql = "SELECT * FROM Training_Question where TrainingName = '$trainingName'";
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$json = array('question' => $row["Question"], 'correctPercent' => $row["CorrectPercentage"].'%', 'incorrectPercent' => $row["InCorrectPercentage"].'%');
		array_push($dataArr, $json);
	}
	$output = array();
	$output = array('dataArr' => $dataArr);
	echo json_encode($output);
}
?>

<?php 
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
function getColorHexCode(){
	$characters = "0123456789ABCDEF";
	$charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < 6; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return "#".$randomString;
}
function getColorByCircle($circle){
	global $conn;
	$sql = "SELECT `Color` FROM `StateCityAreaMaster` where `State` = '$circle'";
	$query = mysqli_query($conn,$sql);
	$row = mysqli_fetch_assoc($query);
	$color = $row["Color"];
	if($color == null){
		$color = getColorHexCode();
	}
	return $color;

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

// function getQuarterMonthListForWeeklyPMOld($quarter){
// 	$monthNumber = date('m');
// 	$year = date('Y');
// 	$quaterList = array();
// 	if($quarter == "Q1"){
// 		if($monthNumber < 4){
// 			$year = $year - 1;
// 		}
// 		array_push($quaterList, '4-'.$year);
// 		array_push($quaterList, '5-'.$year);
// 		array_push($quaterList, '6-'.$year);
// 	}
// 	else if($quarter == "Q2"){
// 		if($monthNumber < 4){
// 			$year = $year - 1;
// 		}
// 		array_push($quaterList, '7-'.$year);
// 		array_push($quaterList, '8-'.$year);
// 		array_push($quaterList, '9-'.$year);
// 	}
// 	else if($quarter == "Q3"){
// 		if($monthNumber < 4){
// 			$year = $year - 1;
// 		}
// 		array_push($quaterList, '10-'.$year);
// 		array_push($quaterList, '11-'.$year);
// 		array_push($quaterList, '12-'.$year);
// 	}
// 	else if($quarter == "Q4"){
// 		if($monthNumber > 3){
// 			$year = $year + 1;
// 		}
// 		array_push($quaterList, '1-'.$year);
// 		array_push($quaterList, '2-'.$year);
// 		array_push($quaterList, '3-'.$year);
// 	}
// 	return $quaterList;
// }

function getQuarterMonthListForWeeklyPM($quarter){
	global $financialYear;
	// if($financialYear == null){
	// 	return getQuarterMonthListForWeeklyPMOld($quarter);
	// }
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