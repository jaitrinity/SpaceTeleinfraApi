<?php 
	// $autoResult = CallAPI("GET","http://www.trinityapplab.in/SpaceTeleinfra/autoCancelPtw.php","");
	// echo $autoResult;

	$autoResult = CallAPI("GET","http://www.trinityapplab.in/SpaceTeleinfra/insertMeterReading.php","");
	echo $autoResult;
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