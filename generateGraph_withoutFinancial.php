<?php 
include("dbConfiguration.php");
$requestJson = file_get_contents('php://input');
$jsonData=json_decode($requestJson);
$loginEmpId = $jsonData->loginEmpId;
$loginEmpRole = $jsonData->loginEmpRole;
$period = $jsonData->period;
$incidentCategory = $jsonData->incidentCategory;
$quarter = $jsonData->quarter;
$graphType = $jsonData->graphType;

// Incident Management Graph
if($graphType == 1){
	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();
	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
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
		$count = $row["Count"];
		$totalCount = $totalCount + $count;
		foreach ($row as $key => $value) {
			if($key == "Category"){
				array_push($labelArr, ucfirst($value));
				$color = "";
				if($incidentCategory == ""){
					$color = getColorHexCode();
				}
				else{
					$color = getColorByCircle($value);
				}
				array_push($colorArr, $color);
			}
			else if($key == "Count"){
				array_push($dataArr, $value);
			}
		}
		$json = array('category' => ucfirst($row["Category"]), 'count' => $row["Count"]);
		array_push($tableData, $json);
	}

	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $dataArr, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 'totalCount' => $totalCount);
	echo json_encode($output);
}
// Frequent failing fiber cut circle wise
else if($graphType == 2){
	
	$imPeriod = getQuarterMonth($quarter);

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
	}

	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();
	$maxValue = 0;

	array_push($tableColumn,"Circle");
	array_push($tableColumn,$incidentCategory);

	$sql = "select t1.State as Category, sum(t1.repeatCount) as Count from (select t.State, t.Site_Id, (case when count(t.stateSiteId) < 2 then 0 else 1 end) repeatCount from (SELECT concat(State,'-',Site_Id) stateSiteId, State, Site_Id FROM Incident_Graph where Period in ('".$imPeriod."') and Incident_category = 'Fiber Cut' ".$filterSql.") t GROUP by t.stateSiteId) t1 GROUP by t1.State Order By t1.State";
	$sql = "select tt.Category, tt.Count from ($sql) tt order by tt.Count desc";
	$query = mysqli_query($conn,$sql);
	
	while ($row = mysqli_fetch_assoc($query)) {
		$count = $row["Count"];
		// if($count != 0){
			foreach ($row as $key => $value) {
				if($key == "Category"){
					array_push($labelArr, $value);
					$color = getColorByCircle($value);
					array_push($colorArr, $color);
				}
				else if($key == "Count"){
					array_push($dataArr, $value);
					if($value > $maxValue){
						$maxValue = $value;
					}
				}
			}
			$json = array('category' => $row["Category"], 'count' => $row["Count"]);
			array_push($tableData, $json);
		// }
	}

	if($maxValue > 10)
		$maxValue = round($maxValue + ($maxValue*10/100));
	else
		$maxValue = $maxValue + 1;

	$dataObj = array('data' => $dataArr, 'label' => $incidentCategory);
	$chartData = array();
	array_push($chartData, $dataObj);

	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 'maxValue' => $maxValue);
	echo json_encode($output);
}
// Fiber Cut Incident and MTTR of Fiber Cut
else if($graphType == 3){
	$labelArr = array();
	$dataArr = array();
	$dataArr1 = array();
	$colorArr = array();
	$colorArr1 = array();
	$tableColumn = array();
	$tableData = array();
	$maxValue = 0;
	$maxValue1 = 0;

	array_push($tableColumn,"Circle");
	array_push($tableColumn,"No of Incident");
	array_push($tableColumn,"MTTR(In Min)");

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and im.`Employee Id` = '$loginEmpId' ";
		$filterSql .= "and im.`Site Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
	}

	$sql = "select t.State as Circle, t.Incident_count, round(t.Incident_minute/t.Incident_count,0) as MTTR from (SELECT s.State, count(im.Circle) as Incident_count, sum(im.incident_minute) as Incident_minute FROM StateCityAreaMaster s left join Incident_MTTR im on s.State = im.Circle and im.Incident_Month = '$period' and im.`Incident category` = 'Fiber Cut' where s.State is not null ".$filterSql."  GROUP by s.State order by s.State) t";

	$sql = "select tt.Circle, tt.Incident_count, tt.MTTR from ($sql) tt order by tt.MTTR desc";
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$circle = $row["Circle"];
		$color = getColorByCircle($circle);

		foreach ($row as $key => $value) {
			if($key == "Circle"){
				array_push($labelArr, $value);
			}
			else if($key == "Incident_count"){
				array_push($dataArr, $value);
				
				array_push($colorArr, $color);
				if($value > $maxValue){
					$maxValue = $value;
				}
			}
			else if($key == "MTTR"){
				if($value == null)
					array_push($dataArr1, 0);
				else
					array_push($dataArr1, $value);

				array_push($colorArr1, $color);
				if($value > $maxValue1){
					$maxValue1 = $value;
				}
			}
		}
		$json = array('circle' => $row["Circle"], 'count' => $row["Incident_count"], 'mttr' => $row["MTTR"]);
		array_push($tableData, $json);
	}

	// $maxValue = round($maxValue + ($maxValue*20/100));
	// $maxValue1 = round($maxValue1 + ($maxValue1*10/100));
	$maxValue = $maxValue + 1;
	$maxValue1 = $maxValue1 + 50;

	$dataObj = array('data' => $dataArr, 'label' => 'No of Incident');
	$dataObj1 = array('data' => $dataArr1, 'label' => 'MTTR(In Min)');
	$chartData = array();
	array_push($chartData, $dataObj);
	array_push($chartData, $dataObj1);
	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'colorArr1' => $colorArr1, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 'maxValue' => $maxValue, 'maxValue1' => $maxValue1);
	echo json_encode($output);
}
// Preventive Maintenance
else if($graphType == 4){

	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();

	array_push($tableColumn,"Circle");
	array_push($tableColumn,"Total Sites");
	array_push($tableColumn,"PM done");
	array_push($tableColumn,"% Comp");

	$nonAdmin = "No";
	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$nonAdmin = "Yes";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
	}

	$sql = "SELECT t3.State, t3.Site_count, t3.PM_done, round((t3.PM_done/t3.Site_count)*100,0) as 'Done_Percentage' from (select s.State, s.Site_count, (case when t2.PM_done is null then 0 else t2.PM_done end) as PM_done from (SELECT l.State, count(l.Site_Id) as Site_count FROM Location l where l.Site_Id is not null and l.Site_Id != '' and (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and l.Tenent_Id = 2 and l.Is_Active = 1 GROUP BY l.State order by l.State) s left join (select t1.State, sum(t1.Single_count) as PM_done from (select t.State, t.Site_Id, t.State_site_id, count(t.State_site_id) as Done_count, (case when count(t.State_site_id) > 1 then 1 else count(t.State_site_id) end) as Single_count from (SELECT State, Site_Id, Site_Id as State_site_id FROM PM_Graph 
	where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_Date is null or RFI_Date < '".$quarterStartDate."') and Is_Site_Active = 1 ".$filterSql.") t GROUP by t.State_site_id) t1 GROUP by t1.State) t2 on s.State = t2.State ) t3 ";

	$gtSql = "SELECT gt1.State, gt1.Site_count, gt1.PM_done, round((gt1.PM_done/gt1.Site_count)*100,0) as 'Done_Percentage' from (select 'Total ' as State, sum(gt.Site_count) as Site_count, sum(gt.PM_done) as PM_done from (SELECT t3.State, t3.Site_count, t3.PM_done, round((t3.PM_done/t3.Site_count)*100,0) as 'Done_Percentage' from (select s.State, s.Site_count, (case when t2.PM_done is null then 0 else t2.PM_done end) as PM_done from (SELECT l.State, count(l.Site_Id) as Site_count FROM Location l where l.Site_Id is not null and l.Site_Id != '' and (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and l.Tenent_Id = 2 and l.Is_Active = 1 GROUP BY l.State) s left join (select t1.State, sum(t1.Single_count) as PM_done from (select t.State, t.Site_Id, t.State_site_id, count(t.State_site_id) as Done_count, (case when count(t.State_site_id) > 1 then 1 else count(t.State_site_id) end) as Single_count from (SELECT State, Site_Id, Site_Id as State_site_id FROM PM_Graph 
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
			foreach ($row as $key => $value) {
				if($key == "State"){
					array_push($labelArr, $value);
					$color = getColorByCircle($value);
					array_push($colorArr, $color);
				}
				else if($key == "Done_Percentage"){
					array_push($dataArr, $value);
				}
			}
			$json = array('circle' => $row["State"], 'siteCount' => $row["Site_count"], 'pmDone' => $row["PM_done"], 'donePercentage' => $row["Done_Percentage"]);
			array_push($tableData, $json);
		}
		else{
			$total = $donePercentage;
		}
			
	}
	if($nonAdmin == "Yes"){
		$total = $dataArr[0];
	}

	$dataObj = array('data' => $dataArr, 'label' => '% Comp');
	$chartData = array();
	array_push($chartData, $dataObj);

	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 
		'total' => $total);
	echo json_encode($output);
}
// Site Type wise PM
else if($graphType == 5){
	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);
	$siteType = $jsonData->siteType;
	
	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();

	array_push($tableColumn,"Circle");
	array_push($tableColumn,"Total Sites");
	array_push($tableColumn,"PM done");
	array_push($tableColumn,"% Comp");

	$nonAdmin = "No";
	$filterSql = "";
	$siteTypeSql = "";
	$gropupBySql = "";

	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$nonAdmin = "Yes";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
	}

	if($siteType != ""){
		$filterSql .= " and Site_CAT = '$siteType' ";
		$siteTypeSql .= " and l.Site_CAT = '$siteType' ";
		$gropupBySql .= ", l.Site_CAT";
	}

	

	$sql = "SELECT t3.State, t3.Site_count, t3.PM_done, round((t3.PM_done/t3.Site_count)*100,0) as 'Done_Percentage' from (select s.State, s.Site_count, (case when t2.PM_done is null then 0 else t2.PM_done end) as PM_done from (SELECT l.State, count(l.Site_Id) as Site_count FROM Location l where l.Site_Id is not null and l.Site_Id != '' ".$siteTypeSql." and (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and l.Tenent_Id = 2 and l.Is_Active = 1 GROUP BY l.State".$gropupBySql." order by l.State) s left join (select t1.State, sum(t1.Single_count) as PM_done from (select t.State, t.Site_Id, t.State_site_id, count(t.State_site_id) as Done_count, (case when count(t.State_site_id) > 1 then 1 else count(t.State_site_id) end) as Single_count from (SELECT State, Site_Id, Site_Id as State_site_id FROM PM_Graph 
	where PM_done_period in ('".$quarter."') and (RFI_Date_period is null or RFI_Date_period not in ('".$quarter."')) and (RFI_date is null or RFI_date < '".$quarterStartDate."') and Is_Site_Active = 1 ".$filterSql.") t GROUP by t.State_site_id) t1 GROUP by t1.State) t2 on s.State = t2.State ) t3 ";

	$gtSql = "SELECT gt1.State, gt1.Site_count, gt1.PM_done, round((gt1.PM_done/gt1.Site_count)*100,0) as 'Done_Percentage' from (select 'Total ' as State, sum(gt.Site_count) as Site_count, sum(gt.PM_done) as PM_done from (SELECT t3.State, t3.Site_count, t3.PM_done, round((t3.PM_done/t3.Site_count)*100,0) as 'Done_Percentage' from (select s.State, s.Site_count, (case when t2.PM_done is null then 0 else t2.PM_done end) as PM_done from (SELECT l.State, count(l.Site_Id) as Site_count FROM Location l where l.Site_Id is not null and l.Site_Id != '' ".$siteTypeSql." and (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and l.Tenent_Id = 2 and l.Is_Active = 1 GROUP BY l.State".$gropupBySql." ) s left join (select t1.State, sum(t1.Single_count) as PM_done from (select t.State, t.Site_Id, t.State_site_id, count(t.State_site_id) as Done_count, (case when count(t.State_site_id) > 1 then 1 else count(t.State_site_id) end) as Single_count from (SELECT State, Site_Id, Site_Id as State_site_id FROM PM_Graph 
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
			foreach ($row as $key => $value) {
				if($key == "State"){
					array_push($labelArr, $value);
					$color = getColorByCircle($value);
					array_push($colorArr, $color);
				}
				else if($key == "Done_Percentage"){
					array_push($dataArr, $value);
				}
			}
			$json = array('circle' => $row["State"], 'siteCount' => $row["Site_count"], 'pmDone' => $row["PM_done"], 'donePercentage' => $row["Done_Percentage"]);
			array_push($tableData, $json);
		}
		else{
			$total = $donePercentage;
		}
			
	}

	if($nonAdmin == "Yes"){
		$total = $dataArr[0];
	}

	$dataObj = array('data' => $dataArr, 'label' => '% Comp');
	$chartData = array();
	array_push($chartData, $dataObj);

	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 
		'total' => $total);
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
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and p.`Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
	}
	
	if($metroSiteType == "High_R_Site"){
		$sql = "SELECT 'High Revenue Site' as Site_Type, count(*) as Site_Count FROM Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and High_Revenue_Site = 1 and Is_Active = 1";
	}
	else if($metroSiteType == "ISQ"){
		$sql = "SELECT 'ISQ' as Site_Type, count(*) as Site_Count FROM Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and ISQ = 1 and Is_Active = 1";
	}
	else if($metroSiteType == "Retail_IBS"){
		$sql = "SELECT 'Retail IBS' as Site_Type, count(*) as Site_Count FROM Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Retail_IBS = 1 and Is_Active = 1";
	}
	else{
		$sql = "SELECT l.Airport_Metro as Site_Type, count(l.Airport_Metro) as Site_Count FROM Location l where (l.RFI_date is null or l.RFI_date < '".$quarterStartDate."') and l.Airport_Metro = '$metroSiteType' and Is_Active = 1 GROUP by l.Airport_Metro";
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

	array_push($labelArr, 'PM');
	array_push($labelArr, 'Pending');

	array_push($dataArr, $pmCount);
	array_push($dataArr, $pending);

	// $color1 = getColorHexCode();
	$color1 = "#00b050";
	array_push($colorArr, $color1);

	// $color2 = getColorHexCode();
	$color2 = "#ff33cc";
	array_push($colorArr, $color2);

	
	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $dataArr, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData);
	echo json_encode($output);
}
// Weekly PM
else if($graphType == 7){
	$monthNumber = date('m');
	$year = date('Y');
	$quarter = $jsonData->quarter;
	$quarterStartDate = getQuarterStartDate($quarter);
	$weakQuarter = $quarter;
	$siteType = $jsonData->siteType;
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
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
	}
	
	$rfiDateQuarter = getQuarterMonth($weakQuarter);
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
	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$dataArr1 = array();
	$dataArr2 = array();
	$tableColumn = array();
	$tableData = array();
	// $target = 72;

	array_push($tableColumn,"Week");
	array_push($tableColumn,"PM Done");

	$weekSql = implode(" UNION ", $sqlArr);

	$todayDate = date('Y-m-d');
	$sql = "SELECT t.`Week`, t.`WeekDate`, t.`PM_done`, wt.`Target` from ($weekSql) t left join `Weekly_Target` wt on t.`Week` = wt.`Weekly` ";
	$sql .= "where t.`WeekDate` <= '$todayDate' ";
	
	$query = mysqli_query($conn,$sql);
	$cumalative = 0;
	while ($row = mysqli_fetch_assoc($query)) {
		foreach ($row as $key => $value) {
			if($key == "Week"){
				array_push($labelArr, $value);
				$color = getColorHexCode();
				array_push($colorArr, $color);
			}
			else if($key == "PM_done"){
				array_push($dataArr, $value);
				if($value != 0){
					$cumalative = $cumalative + $value;
					array_push($dataArr1, $cumalative);	
				}
				else{
					array_push($dataArr1, 0);
				}
			}
			else if($key == "Target"){
				$target = $value;
				array_push($dataArr2, $target);
			}
		}

		$json = array('week' => $row["Week"], 'weekDate' => $row["WeekDate"], 'pmDone' => $row["PM_done"]);
		array_push($tableData, $json);
	}

	$dataObj = array('data' => $dataArr, 'label' => 'Weekly PM');
	$dataObj1 = array('data' => $dataArr1, 'label' => 'Cumulative');
	$dataObj2 = array('data' => $dataArr2, 'borderWidth' => 1, 'label' => 'Target');
	$chartData = array();
	array_push($chartData, $dataObj1);
	array_push($chartData, $dataObj);
	array_push($chartData, $dataObj2);

	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData);
	echo json_encode($output);
}
// PM punchpoint
else if($graphType == 8){

	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();
	$maxValue = 0;

	array_push($tableColumn,"Circle");
	array_push($tableColumn,"Punchpoint");

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and p.`Site Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
	}

	$sql = "select s.State, sum(case when p.Status = 'Not Ok' then 1 else 0 end) as `Punchpoint` FROM StateCityAreaMaster s left join Punchpoint_Report p on s.State = p.State and p.Period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') and p.Is_Site_Active = 1 where s.State is not null ".$filterSql." GROUP by s.State ORDER by s.State";

	$sql = "select tt.State, tt.Punchpoint from ($sql) tt order by tt.Punchpoint desc ";

	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		foreach ($row as $key => $value) {
			if($key == "State"){
				array_push($labelArr, $value);
				$color = getColorByCircle($value);
				array_push($colorArr, $color);
			}
			else if($key == "Punchpoint"){
				array_push($dataArr, $value);
				if($value > $maxValue){
					$maxValue = $value;
				}
			}
		}
		$json = array('state' => $row["State"], 'punchpoint' => $row["Punchpoint"]);
		array_push($tableData, $json);

	}

	$maxValue = round($maxValue + ($maxValue*10/100));
	// $maxValue = $maxValue + 100;
	$dataObj = array('data' => $dataArr, 'label' => 'Punchpoint');
	$chartData = array();
	array_push($chartData, $dataObj);

	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 'maxValue' => $maxValue);
	echo json_encode($output);
}
// Frequent failing fiber cut site wise
else if($graphType == 9){
	$quarter = getQuarterMonth($quarter);

	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();
	$maxValue = 0;
	array_push($tableColumn,"Circle");
	array_push($tableColumn,"Site Id");
	array_push($tableColumn,"Repeat Count");

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and `Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
	}
	$totalCount = 0;
	$sql = "SELECT  max(t.State) as Circle, t.Site_Id, sum(t.repeatCount) as RepeatCount from (SELECT State, Site_Id, count(Site_Id) as repeatCount FROM Incident_Graph where Period in ('".$quarter."') and Incident_category = 'Fiber Cut' ".$filterSql." GROUP by Site_Id) t GROUP by t.Site_Id";
	$sql = "select tt.Circle, tt.Site_Id, tt.RepeatCount from ($sql) tt order by tt.RepeatCount desc";
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$count = $row["RepeatCount"];
		if($count != 1){
			foreach ($row as $key => $value) {
				if($key == "Site_Id"){
					array_push($labelArr, $value);
					$color = getColorHexCode();
					array_push($colorArr, $color);
				}
				else if($key == "RepeatCount"){
					array_push($dataArr, $value);
					if($value > $maxValue){
						$maxValue = $value;
					}
				}
			}
			$json = array('circle' => $row["Circle"], 'siteId' => $row["Site_Id"], 'repeatCount' => $row["RepeatCount"]);
			array_push($tableData, $json);
			$totalCount = $totalCount + $count;
		}
	}
	if($maxValue > 10)
		$maxValue = round($maxValue + ($maxValue*10/100));
	else
		$maxValue = $maxValue + 1;
	
	$dataObj = array('data' => $dataArr, 'label' => 'Count');
	$chartData = array();
	array_push($chartData, $dataObj);

	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 'maxValue' => $maxValue, 'totalCount' => $totalCount);
	echo json_encode($output);
}

// Training Graph
else if($graphType == 10){
	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();
	$maxValue = 0;

	array_push($tableColumn,"State");
	array_push($tableColumn,"Percentage");

	$training = $jsonData->trainingName;
	$state = $jsonData->state;

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		$filterSql .= "and e.State = '$state' ";
	}

	$sql = "SELECT t4.State, t4.EmpStateCount, t4.PassCount, round((t4.PassCount/t4.EmpStateCount)*100,0) PassPercent from (select t3.State, t3.Color, count(t3.EmpId) EmpStateCount, sum(t3.PassCount) as PassCount from (select t2.State, t2.Color, t2.EmpId, (case when t2.Result = 'Pass' then 1 else 0 end) PassCount from (select t1.State, t1.Color, t1.EmpId, max(t1.Result) Result from (select t.State, t.Color, t.EmpId, tr.ServerDateTime, tr.Result from (SELECT s.State, s.Color, e.EmpId FROM StateCityAreaMaster s left join Employees e on s.State = e.State where s.State is not null ".$filterSql." and e.RoleId in (43,44,45) and e.Active = 1) t left join Training_Report tr on t.EmpId = tr.`Emp Id` and tr.`Training Name` = '$training') t1 GROUP BY t1.State,t1.EmpId) t2) t3 GROUP by t3.State) t4";

	$sql = "select tt.State, tt.EmpStateCount, tt.PassCount, tt.PassPercent from ($sql) tt order by tt.PassPercent desc";
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$passCount = $row["PassCount"];
		if($passCount != null){
			foreach ($row as $key => $value) {
				if($key == "State"){
					array_push($labelArr, $value);
					$color = getColorByCircle($value);
					array_push($colorArr, $color);
				}
				else if($key == "PassPercent"){
					array_push($dataArr, $value);
					if($value > $maxValue){
						$maxValue = $value;
					}
				}
			}
			$json = array('State' => $row["State"], 'percentage' => $row["PassPercent"]);
			array_push($tableData, $json);
		}
			
		
	}
	$maxValue = $maxValue + 10;
	
	$dataObj = array('data' => $dataArr, 'label' => 'Percentage');
	$chartData = array();
	array_push($chartData, $dataObj);

	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 'maxValue' => $maxValue);
	echo json_encode($output);
}

// Training Graph - old 2
// else if($graphType == 10){
// 	$colorArr = array();
// 	$labelArr = array();
// 	$dataArr = array();
// 	$tableColumn = array();
// 	$tableData = array();
// 	$maxValue = 0;

// 	array_push($tableColumn,"State");
// 	array_push($tableColumn,"Percentage");

// 	$training = $jsonData->trainingName;
// 	$state = $jsonData->state;

// 	$filterSql = "";
// 	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
// 		$filterSql .= "and State = '$state' ";
// 	}

// 	$stateSql = "SELECT s.State, s.Color, count(e.State) EmpCount FROM StateCityAreaMaster s left join Employees e on s.State = e.State and e.Active = 1 and e.RoleId in (43,44,45) where s.State is not null GROUP by e.State ORDER by s.State";
// 	$stateQuery = mysqli_query($conn,$stateSql);
// 	while ($stateRow = mysqli_fetch_assoc($stateQuery)){
// 		$loopState = $stateRow["State"];
// 		$color = $stateRow["Color"];
// 		if($color == null) $color = getColorByCircle($loopState);
// 		$empCount = $stateRow["EmpCount"];

// 		$stateEmpSql = "SELECT EmpId FROM Employees where State = '$loopState' ".$filterSql." and RoleId in (43,44,45) and Active = 1";
// 		$stateEmpQuery = mysqli_query($conn,$stateEmpSql);
// 		$passCount = 0;
// 		while ($stateEmpRow = mysqli_fetch_assoc($stateEmpQuery)){
// 			$empId = $stateEmpRow["EmpId"];

// 			$empTraningSql = "SELECT * FROM Training_Report where `Training Name` = '$training'  and `Emp Id` = '$empId' ORDER by ActivityId desc limit 0,1";
// 			$empTraningQuery = mysqli_query($conn,$empTraningSql);
// 			$empTrainingRowCount=mysqli_num_rows($empTraningQuery);
// 			if($empTrainingRowCount == 0){

// 			}
// 			else{
// 				$empTrainingRow = mysqli_fetch_assoc($empTraningQuery);
// 				$empResult = $empTrainingRow["Result"];
// 				if($empResult == "Pass"){
// 					$passCount++;
// 				}
// 			}
// 		}

// 		$passPercentage = round(($passCount/$empCount)*100);

// 		if($passPercentage > $maxValue){
// 			$maxValue = $passPercentage;
// 		}
// 		array_push($labelArr, $loopState);
// 		array_push($colorArr, $color);
// 		array_push($dataArr, $passPercentage);

// 		$json = array('State' => $loopState, 'percentage' => $passPercentage);
// 		array_push($tableData, $json);
// 	}
// 	$maxValue = $maxValue + 10;
	
// 	$dataObj = array('data' => $dataArr, 'label' => 'Percentage');
// 	$chartData = array();
// 	array_push($chartData, $dataObj);

// 	$output = array();
// 	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 'maxValue' => $maxValue);
// 	echo json_encode($output);
// }
// Training Graph - old
// else if($graphType == 10){
// 	$colorArr = array();
// 	$labelArr = array();
// 	$dataArr = array();
// 	$tableColumn = array();
// 	$tableData = array();
// 	$maxValue = 0;

// 	array_push($tableColumn,"State");
// 	array_push($tableColumn,"Percentage");

// 	$training = $jsonData->trainingName;
// 	$state = $jsonData->state;

// 	$filterSql = "";
// 	$filterSql1 = "";
// 	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
// 		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
// 		// $filterSql1 .= "and `Emp Id` = '$loginEmpId' ";

// 		$filterSql .= "and `State` = '$state' ";
// 		$filterSql1 .= "and `Emp Circle` = '$state' ";
// 	}

// 	$sql = "SELECT t3.State, t3.EmpStateCount, t3.PassCount, round((t3.PassCount/t3.EmpStateCount)*100,0) PassPercent from (select t.State, t.EmpStateCount, t2.PassCount from (SELECT State, count(State) as EmpStateCount FROM Employees where RoleId in (43,44,45) ".$filterSql." and Active = 1 GROUP by State) t left join (select t.`Emp Circle`, sum(case when tr.Result = 'Pass' then 1 else 0 end) PassCount from (SELECT max(ActivityId) as ActivityId, max(`Emp Circle`) as `Emp Circle` FROM Training_Report where `Training Name` = '$training' ".$filterSql1."  GROUP by `Emp Id`) t join Training_Report tr on t.ActivityId = tr.ActivityId GROUP by t.`Emp Circle`) t2 on t.State = t2.`Emp Circle` GROUP by t2.`Emp Circle`) t3 Order by t3.State";

// 	$sql = "select tt.State, tt.EmpStateCount, tt.PassCount, tt.PassPercent from ($sql) tt order by tt.PassPercent desc";
// 	$query = mysqli_query($conn,$sql);
// 	while ($row = mysqli_fetch_assoc($query)) {
// 		$passCount = $row["PassCount"];
// 		if($passCount != null){
// 			foreach ($row as $key => $value) {
// 				if($key == "State"){
// 					array_push($labelArr, $value);
// 					$color = getColorByCircle($value);
// 					array_push($colorArr, $color);
// 				}
// 				else if($key == "PassPercent"){
// 					array_push($dataArr, $value);
// 					if($value > $maxValue){
// 						$maxValue = $value;
// 					}
// 				}
// 			}
// 			$json = array('State' => $row["State"], 'percentage' => $row["PassPercent"]);
// 			array_push($tableData, $json);
// 		}
			
		
// 	}
// 	$maxValue = $maxValue + 10;
	
// 	$dataObj = array('data' => $dataArr, 'label' => 'Percentage');
// 	$chartData = array();
// 	array_push($chartData, $dataObj);

// 	$output = array();
// 	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 'maxValue' => $maxValue);
// 	echo json_encode($output);
// }

// Metro and Airport sites PM 2
else if($graphType == 11){
	$quarterStartDate = getQuarterStartDate($quarter);
	$quarter = getQuarterMonth($quarter);

	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();
	$maxValue = 0;
	array_push($tableColumn,"Site Type");
	array_push($tableColumn,"Percentage");

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
		// $filterSql .= "and `EmpId` = '$loginEmpId' ";
		$filterSql .= "and p.`Site_Id` in (SELECT l.Site_Id  FROM EmployeeLocationMapping el join Location l on el.LocationId = l.LocationId WHERE `Emp_Id` = '$loginEmpId')";
	}

	$sql = "SELECT t.Site_Type, t.Site_count, t.PM_done, round((t.PM_done/t.Site_count)*100,0) as 'Done_Percentage' from (SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Airport_Metro = 'Airport Site' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'Airport Site' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Airport_Metro = 'CMRL' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'CMRL' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Airport_Metro = 'DMRC' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'DMRC' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Airport_Metro = 'JMRC' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'JMRC' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Airport_Metro = 'LMRC' and Is_Active=1) as Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, p.Airport_Metro as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Airport_Metro = 'LMRC' and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Retail_IBS = 1 and Is_Active=1) Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, 'Retail IBS' as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.Retail_IBS = 1 and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and ISQ = 1 and Is_Active=1) Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, 'ISQ' as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.ISQ = 1 and p.Is_Site_Active =1) t GROUP by t.Site_Type
		UNION
		SELECT t.Site_Type, (select count(*) from Location where (RFI_date is null or RFI_date < '".$quarterStartDate."') and High_Revenue_Site = 1 and Is_Active=1) Site_count, count(t.Site_Type) as PM_done from (SELECT DISTINCT p.Site_Id, 'High_R_Site' as Site_Type FROM PM_Graph p where p.PM_done_period in ('".$quarter."') and (p.RFI_Date_period is null or p.RFI_Date_period not in ('".$quarter."')) and (p.RFI_date is null or p.RFI_date < '".$quarterStartDate."') ".$filterSql." and p.High_Revenue_Site = 1 and p.Is_Site_Active =1) t GROUP by t.Site_Type) t";

	$sql = "select tt.Site_Type, tt.Site_count, tt.PM_done, tt.Done_Percentage from ($sql) tt order by tt.Done_Percentage desc";

	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		foreach ($row as $key => $value) {
			if($key == "Site_Type"){
				array_push($labelArr, $value);
				$color = getColorHexCode();
				array_push($colorArr, $color);
			}
			else if($key == "Done_Percentage"){
				array_push($dataArr, $value);
				if($value > $maxValue){
					$maxValue = $value;
				}
			}
			$json = array('siteType' => $row["Site_Type"], 'percentage' => $row["Done_Percentage"]);
			array_push($tableData, $json);
		}
	}
	// $maxValue = $maxValue + 10;
	$maxValue = 110;
	
	$dataObj = array('data' => $dataArr, 'label' => 'Percentage');
	$chartData = array();
	array_push($chartData, $dataObj);

	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $chartData, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 'maxValue' => $maxValue);
	echo json_encode($output);
}
// Training Graph 2
else if($graphType == 12){
	$colorArr = array();
	$labelArr = array();
	$dataArr = array();
	$tableColumn = array();
	$tableData = array();

	array_push($tableColumn, "Emp Name");
	array_push($tableColumn, "Status");
	array_push($tableColumn, "Percentage");
	$tableStr = "<table class='table table-bordered mytable'> <thead> <tr> <th>Name</th> <th>Status</th> <th>Percentage</th> </tr> </thead>";
	$tableStr .= "<tbody>";

	$training = $jsonData->trainingName;
	$state = $jsonData->state;

	$filterSql = "";
	if($loginEmpRole != "Admin" && $loginEmpRole != "SpaceWorld" && $loginEmpRole != "Management" && $loginEmpRole != "CB"){
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
		$tableStr .= "<tr> <td>$empName</td> <td>$trResult</td> <td>$trPercentage</td> </tr> ";
		$json = array('empName' => $empName, 'status' => $trResult, 'percentage' => $trPercentage);
		array_push($tableData, $json);
		
	}
	$tableStr .= "</tbody>";
	$tableStr .= "</table>";

	if($empCount != 0)
		$passPercentage = round(($passCount/$empCount)*100);

	// array_push($tableData, $empCount);
	// array_push($tableData, $passCount);
	// array_push($tableData, $failCount);
	// array_push($tableData, $pendingCount);


	array_push($labelArr, $passCount." - Pass");
	array_push($colorArr, '#00b050');

	array_push($labelArr, $failCount." - Fail");
	array_push($colorArr, '#ff1d1d');

	array_push($labelArr, $pendingCount." - Pending");
	array_push($colorArr, '#ffc000');

	array_push($dataArr, $passCount);
	array_push($dataArr, $failCount);
	array_push($dataArr, $pendingCount);


	$output = array();
	$output = array('labelArr' => $labelArr, 'dataArr' => $dataArr, 'colorArr' => $colorArr, 'tableColumn' => $tableColumn, 'tableData' => $tableData, 
		'tableStr' => $tableStr, 'passPercentage' => $passPercentage);
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
		$sql = "SELECT Site_Id FROM `Location` where (RFI_date is null or RFI_date < '".$quarterStartDate."') and High_Revenue_Site = 1 and Is_Active = 1";
		$filterSql = " and High_Revenue_Site = 1";
	}
	else if($metroSiteType == "ISQ"){
		$sql = "SELECT Site_Id FROM `Location` where (RFI_date is null or RFI_date < '".$quarterStartDate."') and ISQ = 1 and Is_Active = 1";
		$filterSql = " and ISQ = 1";
	}
	else if($metroSiteType == "Retail_IBS"){
		$sql = "SELECT Site_Id FROM `Location` where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Retail_IBS = 1 and Is_Active = 1";
		$filterSql = " and Retail_IBS = 1";
	}
	else{
		$sql = "SELECT Site_Id FROM `Location` where (RFI_date is null or RFI_date < '".$quarterStartDate."') and Airport_Metro = '$metroSiteType' and Is_Active = 1";
		$filterSql = " and Airport_Metro = '$metroSiteType'";
	}
	$dataArr = array();
	$tableStr = "<table class='table table-bordered mytable'> <thead> <tr> <th>Site Id</th> <th>Status</th> </tr> </thead>";
	$tableStr .= "<tbody>";
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
		$tableStr .= "<tr> <td>$siteId</td> <td>$status</td> </tr> ";
		$json = array('siteId' => $siteId, 'status' => $status );
		array_push($dataArr, $json);
	}
	$tableStr .= "</tbody>";
	$tableStr .= "</table>";

	$output = array();
	$output = array('tableStr' => $tableStr, 'dataArr' => $dataArr);
	echo json_encode($output);
}
// Training Question
else if($graphType == 14){
	$trainingName = $jsonData->trainingName;
	$dataArr = array();
	$sql = "SELECT * FROM Training_Question where TrainingName = '$trainingName'";
	$query = mysqli_query($conn,$sql);
	while ($row = mysqli_fetch_assoc($query)) {
		$json = array('question' => $row["Question"], 'correctPercentage' => $row["CorrectPercentage"], 'inCorrectPercentage' => $row["InCorrectPercentage"]);
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
function getQuarterMonth($quarter){
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

function getQuarterStartDate($quarter){
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
?>