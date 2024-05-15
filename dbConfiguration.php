<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:content-type");
$conn=mysqli_connect("db_host","db_user","db_pass","db_name");
mysqli_set_charset($conn, 'utf8');
?>
