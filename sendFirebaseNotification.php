<?php
define('API_ACCESS_KEY','');
 $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
 
/* require('conf.php');

$dSql = "Select m.EmpId,max(d.Token) as token
		from Mapping m
		join Devices d on (m.EmpId = d.EmpId)
		where date(m.CreateDateTime) = date(Now()) and m.ActivityId = 0
		group by m.EmpId";
$dQuery = mysqli_query($conn,$dSql);
$dCount = mysqli_num_rows($dQuery);

if($dCount > 0){
	while($dRow = mysqli_fetch_assoc($dQuery)){
		$token = $dRow['token'];
		sendNotification($token);
	}
}
*/

$token = "dchQ92g0S-6VwdxRHuOKGX:APA91bGZTqfTQUnyBVyLk8scCFi0WKmqeDS_CXBVWneb0wcSKIbJU5xnY41oa-UMA_HgA-FRqzVJYzXqneSa7EgXcCYjMVlYxMiseU7n-D7CJZ7UJbvqTfnGNVOMHp9X9fHPSD3AnHGd";
//$token = "di1gdmyyyug:APA91bE6Mvih8PjMctVW7eS5UkQCQtwvchm6acRTumBytyn1TApMhImVvHrnlB2HvnCFqyIaT7pKU4qoHue3dUCKm6INjmK0QJ2x-eMkoJFqCYUN5qbrplveygCACY8vv8D6o-hqrvMi";

sendNotification($token);

function sendNotification($token){

	global $fcmUrl;

	$notification = [
            'title' =>'FSR Notification',
            'body' => 'Hi User',
            'icon' =>'myIcon', 
            'sound' => 'mySound'
        ];
        $extraNotificationData = ["message" => $notification,"moredata" =>'dd'];

        $fcmNotification = [
            //'registration_ids' => $tokenList, //multple token array
            'to'        => $token, //single token
            'notification' => $notification,
            'data' => $extraNotificationData
        ];

        $headers = [
            'Authorization: key=' . API_ACCESS_KEY,
            'Content-Type: application/json'
        ];

	echo json_encode($fcmNotification);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);

	if(curl_errno($ch))
	{
	    echo 'error:' . curl_error($ch);
	}

        curl_close($ch);

        echo $result;

}
    
		
?>
