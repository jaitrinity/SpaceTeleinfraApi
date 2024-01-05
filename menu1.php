<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");
$empId=$_REQUEST['empId'];
$roleId=$_REQUEST['roleId'];

$menuArr = array();


if($roleId != null){
	$getTabSql= "Select * from Role where RoleId = $roleId";
}
$getTabResult = mysqli_query($conn,$getTabSql);
if(count($getTabResult) > 0){
	$tr = mysqli_fetch_Array($getTabResult);
	$tabId = $tr['TabId'];
	if($tabId != null || $tabId != ""){
		$tabSql = "Select * from Tab where Id in ($tabId) and Active = 1 order by Seq";
		$tabQuery=mysqli_query($conn,$tabSql);
		$hashMap = "";
		
		while($tabRow = mysqli_fetch_assoc($tabQuery)){

			//$menuArr = array();
			//array_push($menuArr,$tabRow["MenuId"]);
			//if($tabRow["AddBtnId"] != 0){
			//	array_push($menuArr,$tabRow["AddBtnId"]);
			//}

			//$newArr = array_unique($menuArr);
			//$newArr = array_values($newArr);
			
			//for($i=0;$i<count($newArr);$i++){
				$menuIds = $tabRow["MenuId"];
				$result1 = CallAPI("GET","http://www.trinityapplab.in/SalesForcePro/getTabMenu.php?menuId=$menuIds",true);
				$hashMap->{$tabRow["Id"]} = json_decode($result1);
			//}

		}

		/* $newArr = array_unique($menuArr);
		$newArr = array_values($newArr);
		$hashMap = "";
		for($i=0;$i<count($newArr);$i++){
			$menuIds = $newArr[$i];
			$result1 = CallAPI("GET","http://www.trinityapplab.in/SalesForcePro/getTabMenu.php?menuId=$menuIds",true);
			$hashMap->{$menuIds} = json_decode($result1);
		} */

		/*$addBtnSql = "Select * from Tab where Id in ($tabId) and IsAddBtn = 1 and Active = 1 order by Seq";
		$addBtnQuery=mysqli_query($conn,$addBtnSql);
		while($addBtnRow = mysqli_fetch_assoc($addBtnQuery)){
			$menuIds = $addBtnRow["MenuId"];
			$result1 = CallAPI("GET","http://www.trinityapplab.in/SalesForcePro/getTabMenu.php?menuId=$menuIds",true);
			$hashMap->{$addBtnRow ["Id"]} = json_decode($result1);
		}*/
		echo json_encode($hashMap);
	}
}

?>






<?php
function CallAPI($method, $url, $data)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
	//echo $result."\n";
    curl_close($curl);

    return $result;
}
?>