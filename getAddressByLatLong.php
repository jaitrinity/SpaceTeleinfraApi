<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
include("dbConfiguration.php");

$latLong = $_REQUEST["latLong"];

$latt = explode(",", $latLong)[0];
$longg = explode(",", $latLong)[1];

// $url = "https://maps.googleapis.com/maps/api/geocode/json?key=AIzaSyDXvfMPhjz1LnaKVKoIuyfHjoMIhysfxjo&latlng=".$latLong;
$url = "https://apis.mapmyindia.com/advancedmaps/v1/38wywkjm1wji9pobr5cczivktpwvysme/rev_geocode?lat=".$latt."&lng=".$longg;
$headers = array(
      "Content-type: application/json"
  );


// $ch = curl_init($url);
// curl_setopt_array($ch, array(
//   CURLOPT_POST => TRUE,
//   CURLOPT_RETURNTRANSFER => TRUE,
//   CURLOPT_HTTPHEADER => $headers,
//   CURLOPT_POSTFIELDS => $temp_string
// ));

$ch = curl_init($url);
curl_setopt_array($ch, array(
  CURLOPT_POST => FALSE,
  CURLOPT_RETURNTRANSFER => TRUE,
  CURLOPT_HTTPHEADER => $headers
));

$response = curl_exec($ch);
curl_close($ch);

echo $response;



?>