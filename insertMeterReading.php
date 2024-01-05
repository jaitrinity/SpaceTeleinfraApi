<?php 
include("dbConfiguration.php");
$todayDate = date("Y-m-d");
// $deleteSql = "DELETE FROM DiffMeterReading where date_format(CreateDate,'%Y-%m-%d') = '$todayDate'";
// mysqli_query($conn,$deleteSql);

$truncateSql = "TRUNCATE table `DiffMeterReading`";
mysqli_query($conn,$truncateSql);

// $insertType = "OneByOne";
// $insertType = "All";
// if($insertType == "All"){
// 	$sql = "SELECT DISTINCT `Site Id` FROM `Meter_Reading_Report` where (`Site Id` is not null or `Site Id` != '')";
// 	$query = mysqli_query($conn,$sql);
// 	$siteList = array();
// 	while($row = mysqli_fetch_assoc($query)){
// 		array_push($siteList,$row["Site Id"]);
// 	}

// 	$dataArr = array();
// 	for($i=0;$i<count($siteList);$i++){
// 		$siteId = $siteList[$i];

// 		$sql1 = "(SELECT 'Current' as Type, `ActivityId`, `Emp Id`, `Circle`, `City`, `Site Name`, `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Do you have sub meter?`, `Main Meter Reading`, `Main Meter Pic`, `Sub Meter Reading`, `Sub Meter Pic`, `Sub Meter Reading 2`, `Sub Meter Pic 2`, `Sub Meter Reading 3`, `Sub Meter Pic 3`, `Sub Meter Reading 4`, `Sub Meter Pic 4`, `Remark` FROM `Meter_Reading_Report` where `Site Id` = '$siteId' ORDER by `ActivityId` desc LIMIT 0,1)
// 			UNION
// 			(SELECT 'Previous' as Type, `ActivityId`, `Emp Id`, `Circle`, `City`, `Site Name`, `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Do you have sub meter?`, `Main Meter Reading`, `Main Meter Pic`, `Sub Meter Reading`, `Sub Meter Pic`, `Sub Meter Reading 2`, `Sub Meter Pic 2`, `Sub Meter Reading 3`, `Sub Meter Pic 3`, `Sub Meter Reading 4`, `Sub Meter Pic 4`, `Remark` FROM `Meter_Reading_Report` where `Site Id` = '$siteId' ORDER by `ActivityId` desc LIMIT 1,1)";
		
// 		$activityId = 0; $empId = "";
// 		$circle = ""; $city = ""; $siteName = ""; $siteId = ""; $siteType = ""; $submitBy = ""; $submitDate = ""; $haveSubMeter = "";
// 		$currentMain = 0; $previousMain = 0; $diffMain = 0;
// 		$mainMeterPic = "";
// 		$currentSub = 0; $previousSub = 0; $diffSub = 0;
// 		$subMeterPic = "";

// 		$subMeterReading2 = 0; $subMeterPic2 = "";
// 		$subMeterReading3 = 0; $subMeterPic3 = "";
// 		$subMeterReading4 = 0; $subMeterPic4 = "";
// 		$remark = "";

// 		$prevSubmitDate = "";

// 		$query1 = mysqli_query($conn,$sql1);
// 		while($row1 = mysqli_fetch_assoc($query1)){
// 			$type = $row1["Type"];
// 			if($type == "Current"){
// 				$activityId = $row1["ActivityId"];
// 				$empId = $row1["Emp Id"];
// 				$circle = $row1["Circle"];
// 				$city = $row1["City"];
// 				$siteName = $row1["Site Name"];
// 				$siteId = $row1["Site Id"];
// 				$siteType = $row1["Site Type"];
// 				$submitBy = $row1["Submit By"];
// 				$submitDate = $row1["Submit Date"];
// 				$haveSubMeter = $row1["Do you have sub meter?"];

// 				$currentMain = $row1["Main Meter Reading"] == null ? 0 : $row1["Main Meter Reading"];
// 				$mainMeterPic = $row1["Main Meter Pic"];

// 				$currentSub = $row1["Sub Meter Reading"] == null ? 0 : $row1["Sub Meter Reading"];
// 				$subMeterPic = $row1["Sub Meter Pic"];

// 				$subMeterReading2 = $row1["Sub Meter Reading 2"] == null ? 0 : $row1["Sub Meter Reading 2"];
// 				$subMeterPic2 = $row1["Sub Meter Pic 2"];
// 				$subMeterReading3 = $row1["Sub Meter Reading 3"] == null ? 0 : $row1["Sub Meter Reading 3"];
// 				$subMeterPic3 = $row1["Sub Meter Pic 3"];
// 				$subMeterReading4 = $row1["Sub Meter Reading 4"] == null ? 0 : $row1["Sub Meter Reading 4"];
// 				$subMeterPic4 = $row1["Sub Meter Pic 4"];
// 				$remark = $row1["Remark"];

// 			}
// 			else if($type == "Previous"){
// 				$previousMain = $row1["Main Meter Reading"] == null ? 0 : $row1["Main Meter Reading"];
// 				$previousSub = $row1["Sub Meter Reading"] == null ? 0 : $row1["Sub Meter Reading"];
// 				$prevSubmitDate = $row1["Submit Date"];
// 			}
// 		}
// 		$diffMain = $currentMain - $previousMain;
// 		$diffSub = $currentSub - $previousSub;

// 		$dataStr = "($activityId, '$empId', '$circle', '$city', '$siteName', '$siteId', '$siteType', '$submitBy', '$submitDate', '$prevSubmitDate', '$haveSubMeter', $currentMain, $previousMain, $diffMain, '$mainMeterPic', $currentSub, $previousSub, $diffSub, '$subMeterPic', $subMeterReading2, '$subMeterPic2', $subMeterReading3, '$subMeterPic3', $subMeterReading4, '$subMeterPic4', '$remark')";
// 		array_push($dataArr, $dataStr);
// 	}

// 	$output = "";
// 	$successList = array();
// 	$errorList = array();

// 	if(count($dataArr) !=0){
// 		$tableColumn = "INSERT INTO `DiffMeterReading`(`ActivityId`, `Emp Id`, `Circle`, `City`, `Site Name`, `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Previous Submit Date`, `Do you have sub meter?`, `Current Main Meter Reading`, `Previous Main Meter Reading`, `Diff Main Meter Reading`, `Current Main Meter Pic`, `Current Sub Meter Reading`, `Previous Sub Meter Reading`, `Diff Sub Meter Reading`, `Current Sub Meter Pic`, `Current Sub Meter Reading 2`, `Current Sub Meter Pic 2`, `Current Sub Meter Reading 3`, `Current Sub Meter Pic 3`, `Current Sub Meter Reading 4`, `Current Sub Meter Pic 4`, `Current Remark`) ";
// 		$tableData = implode(",", $dataArr);

// 		$insertSql = $tableColumn.' VALUES '.$tableData;
// 		if(mysqli_query($conn,$insertSql)){
// 			$insertRowCount = $conn->affected_rows;
// 			$res = "Successfully inserted ".$insertRowCount.' row(s)';
// 			array_push($successList,$res);
// 		}
// 		else{
// 			$res = "Something went wrong ";
// 			array_push($errorList,$res);
// 			$sql = $insertSql;
// 			array_push($errorList,$sql);
// 		}
// 	}
// 	else{
// 		$res = "No record found. ";
// 		array_push($successList,$res);
// 	}

// 	$output -> successList = $successList;
// 	$output -> errorList = $errorList;

// 	$result = array('hitDate' => $todayDate, 'output' => $output);
// 	echo json_encode($result);

// 	file_put_contents('/var/www/trinityapplab.in/html/SpaceTeleinfra/log/log_'.date("Y").'.log', json_encode($result)."\n", FILE_APPEND);
// }
// else if($insertType == "OneByOne"){
	$sql = "SELECT DISTINCT `Site Id` FROM `Meter_Reading_Report` where (`Site Id` is not null or `Site Id` != '')";
	$query = mysqli_query($conn,$sql);
	$siteList = array();
	while($row = mysqli_fetch_assoc($query)){
		array_push($siteList,$row["Site Id"]);
	}

	$tableColumn = "INSERT INTO `DiffMeterReading`(`ActivityId`, `Emp Id`, `Circle`, `City`, `Site Name`, `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Reading Date`, `Previous Submit Date`, `Previous Reading Date`, `Do you have sub meter?`, `Current Main Meter Reading`, `Previous Main Meter Reading`, `Diff Main Meter Reading`, `Main Meter Billing`, `Current Main Meter Pic`, `Current Sub Meter Reading`, `Previous Sub Meter Reading`, `Diff Sub Meter Reading`, `Sub Meter Billing`, `Current Sub Meter Pic`, `Current Sub Meter Reading 2`, `Current Sub Meter Pic 2`, `Current Sub Meter Reading 3`, `Current Sub Meter Pic 3`, `Current Sub Meter Reading 4`, `Current Sub Meter Pic 4`, `Current Remark`) ";

	$output = "";
	$insertCount = 0;
	$errorList = array();

	for($i=0;$i<count($siteList);$i++){
		$siteId = $siteList[$i];

		$sql1 = "(SELECT 'Current' as Type, `ActivityId`, `Emp Id`, `Circle`, `City`, `Site Name`, `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Reading Date`, `Do you have sub meter?`, `Main Meter Reading`, `Main Meter Pic`, `Sub Meter Reading`, `Sub Meter Pic`, `Sub Meter Reading 2`, `Sub Meter Pic 2`, `Sub Meter Reading 3`, `Sub Meter Pic 3`, `Sub Meter Reading 4`, `Sub Meter Pic 4`, `Remark` FROM `Meter_Reading_Report` where `Site Id` = '$siteId' ORDER by `ActivityId` desc LIMIT 0,1)
			UNION
			(SELECT 'Previous' as Type, `ActivityId`, `Emp Id`, `Circle`, `City`, `Site Name`, `Site Id`, `Site Type`, `Submit By`, `Submit Date`, `Reading Date`, `Do you have sub meter?`, `Main Meter Reading`, `Main Meter Pic`, `Sub Meter Reading`, `Sub Meter Pic`, `Sub Meter Reading 2`, `Sub Meter Pic 2`, `Sub Meter Reading 3`, `Sub Meter Pic 3`, `Sub Meter Reading 4`, `Sub Meter Pic 4`, `Remark` FROM `Meter_Reading_Report` where `Site Id` = '$siteId' ORDER by `ActivityId` desc LIMIT 1,1)";
		
		$activityId = 0; $empId = "";
		$circle = ""; $city = ""; $siteName = ""; $siteId = ""; $siteType = ""; $submitBy = ""; $submitDate = ""; $haveSubMeter = "";
		$currentMain = 0; $previousMain = 0; $diffMain = 0;
		$mainMeterPic = "";
		$currentSub = 0; $previousSub = 0; $diffSub = 0;
		$subMeterPic = "";

		$subMeterReading2 = 0; $subMeterPic2 = "";
		$subMeterReading3 = 0; $subMeterPic3 = "";
		$subMeterReading4 = 0; $subMeterPic4 = "";
		$remark = "";

		$readingDate = "";
		$prevSubmitDate = "";
		$prevReadingDate = "";

		$query1 = mysqli_query($conn,$sql1);
		while($row1 = mysqli_fetch_assoc($query1)){
			$type = $row1["Type"];
			if($type == "Current"){
				$activityId = $row1["ActivityId"];
				$empId = $row1["Emp Id"];
				$circle = $row1["Circle"];
				$city = $row1["City"];
				$siteName = $row1["Site Name"];
				$siteId = $row1["Site Id"];
				$siteType = $row1["Site Type"];
				$submitBy = $row1["Submit By"];
				$submitDate = $row1["Submit Date"];
				$readingDate = $row1["Reading Date"];
				$haveSubMeter = $row1["Do you have sub meter?"];

				$currentMain = $row1["Main Meter Reading"] == null ? 0 : $row1["Main Meter Reading"];
				$mainMeterPic = $row1["Main Meter Pic"];

				$currentSub = $row1["Sub Meter Reading"] == null ? 0 : $row1["Sub Meter Reading"];
				$subMeterPic = $row1["Sub Meter Pic"];

				$subMeterReading2 = $row1["Sub Meter Reading 2"] == null ? 0 : $row1["Sub Meter Reading 2"];
				$subMeterPic2 = $row1["Sub Meter Pic 2"];
				$subMeterReading3 = $row1["Sub Meter Reading 3"] == null ? 0 : $row1["Sub Meter Reading 3"];
				$subMeterPic3 = $row1["Sub Meter Pic 3"];
				$subMeterReading4 = $row1["Sub Meter Reading 4"] == null ? 0 : $row1["Sub Meter Reading 4"];
				$subMeterPic4 = $row1["Sub Meter Pic 4"];
				$remark = $row1["Remark"];

			}
			else if($type == "Previous"){
				$previousMain = $row1["Main Meter Reading"] == null ? 0 : $row1["Main Meter Reading"];
				$previousSub = $row1["Sub Meter Reading"] == null ? 0 : $row1["Sub Meter Reading"];
				$prevSubmitDate = $row1["Submit Date"];
				$prevReadingDate = $row1["Reading Date"];
			}
		}
		$diffMain = $currentMain - $previousMain;
		$diffSub = $currentSub - $previousSub;

		$mainMeterBilling = $diffMain * 10;
		$subMeterBilling = $diffSub * 10;

		$tableData = "($activityId, '$empId', '$circle', '$city', '$siteName', '$siteId', '$siteType', '$submitBy', '$submitDate', '$readingDate', '$prevSubmitDate', '$prevReadingDate', '$haveSubMeter', $currentMain, $previousMain, $diffMain, $mainMeterBilling, '$mainMeterPic', $currentSub, $previousSub, $diffSub, $subMeterBilling, '$subMeterPic', $subMeterReading2, '$subMeterPic2', $subMeterReading3, '$subMeterPic3', $subMeterReading4, '$subMeterPic4', '$remark')";

		$insertSql = $tableColumn.' VALUES '.$tableData;
		if(mysqli_query($conn,$insertSql)){
			$insertCount++;
		}
		else{
			$res = array('msg' => "Something went wrong while insert data of ".$activityId, 'sql' => $insertSql);
			array_push($errorList,$res);
		}
	}

	$output -> insertCount = $insertCount;
	$output -> errorList = $errorList;

	$result = array('hitDate' => $todayDate, 'output' => $output);
	echo json_encode($result);

	file_put_contents('/var/www/trinityapplab.in/html/SpaceTeleinfra/log/log_'.date("Y").'.log', json_encode($result)."\n", FILE_APPEND);
// }

?>