<?php
$conn = new mysqli("localhost","root","","gelang_monitoring");

$data = json_decode(file_get_contents("php://input"), true);

$pasien_id = $data['pasien_id'];

$ax = $data['ax'];
$ay = $data['ay'];
$az = $data['az'];

$gx = $data['gx'];
$gy = $data['gy'];
$gz = $data['gz'];

$now = date("Y-m-d H:i:s");

# simpan histori
$conn->query("
INSERT INTO data_sensor
(pasien_id,waktu_kejadian,accelerometer_x,accelerometer_y,accelerometer_z,gyroscope_x,gyroscope_y,gyroscope_z)
VALUES
($pasien_id,'$now',$ax,$ay,$az,$gx,$gy,$gz)
");

# update realtime
$conn->query("
INSERT INTO sensor_realtime
(pasien_id,waktu_kejadian,ax,ay,az,gx,gy,gz)
VALUES
($pasien_id,'$now',$ax,$ay,$az,$gx,$gy,$gz)
ON DUPLICATE KEY UPDATE
waktu_kejadian='$now',
ax=$ax, ay=$ay, az=$az,
gx=$gx, gy=$gy, gz=$gz
");

echo "OK";
?>