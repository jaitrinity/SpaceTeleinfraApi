<?php



$conn = mysqli_connect("localhost","root","f0rg0t","DemoOneNetwork");

 if (mysqli_connect_errno())

  {

  	echo "Failed to connect to MySQL: " . mysqli_connect_error();

  }
  else
  {
  $wrappedList = array();
	$sql = "Select * from Language where Active = 1";
	//echo $sql;
	$rs = mysqli_query($conn,$sql);

	if(mysqli_num_rows($rs) > 0){
		while($row=mysqli_fetch_array($rs)){
			$lObj = "";
			$lObj->langId  = $row['LanguageId'];
			$lObj->langName = $row['Name'];
			array_push($wrappedList,$lObj);

		}
	}
			
	header('Content-Type: application/json');
	echo json_encode($wrappedList);
	
  }

?>

