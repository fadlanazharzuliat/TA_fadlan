<?php
include 'config.php';

$query = "
SELECT p.pasien_id, p.nama, p.no_kamar,

(SELECT aktivitas_sebelumnya 
 FROM data_jatuh 
 WHERE pasien_id = p.pasien_id 
 ORDER BY waktu_kejadian DESC 
 LIMIT 1) as aktivitas,

sr.ax, sr.ay, sr.az,
sr.gx, sr.gy, sr.gz,
sr.waktu_kejadian as waktu_sensor

FROM pasien p
LEFT JOIN sensor_realtime sr 
ON p.pasien_id = sr.pasien_id
";

$result = $conn->query($query);

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>