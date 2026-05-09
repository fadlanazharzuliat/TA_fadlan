<?php
include 'config.php';

$pasien_id = $_GET['pasien_id'];

$gx = $_GET['gx'];
$gy = $_GET['gy'];
$gz = $_GET['gz'];

$ax = $_GET['ax'];
$ay = $_GET['ay'];
$az = $_GET['az'];

$sql = "INSERT INTO sensor_realtime 
(pasien_id, waktu_kejadian, gx, gy, gz, ax, ay, az)
VALUES 
('$pasien_id', NOW(), '$gx', '$gy', '$gz', '$ax', '$ay', '$az')
ON DUPLICATE KEY UPDATE
waktu_kejadian = NOW(),
gx='$gx', gy='$gy', gz='$gz',
ax='$ax', ay='$ay', az='$az'";

$conn->query($sql);

echo "OK";
?>