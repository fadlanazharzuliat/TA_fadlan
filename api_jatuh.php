<?php
include 'config.php';

// ===== AMBIL DATA =====
$pasien_id = $_GET['pasien_id'];
$aktivitas = $_GET['aktivitas'];

// ===== SIMPAN KE DB =====
$query = "INSERT INTO data_jatuh (pasien_id, aktivitas_sebelumnya) 
          VALUES ('$pasien_id', '$aktivitas')";
$conn->query($query);

// ===== AMBIL NAMA PASIEN =====
$q = $conn->query("SELECT nama, no_kamar FROM pasien WHERE pasien_id='$pasien_id'");
$data = $q->fetch_assoc();

$nama = $data['nama'] ?? 'Tidak diketahui';
$kamar = $data['no_kamar'] ?? '-';

// ===== TELEGRAM CONFIG =====
$token = "8509486480:AAHdzT64DGlKiJ1zzoVo__cGhVxCwOIYLHU";
$chat_id = "509064648";

// ===== PESAN =====
$pesan = "⚠️ *ALERT JATUH!*\n";
$pesan .= "👤 Nama: $nama\n";
$pesan .= "🛏️ Kamar: $kamar\n";
$pesan .= "📌 Aktivitas: $aktivitas\n";
$pesan .= "⏰ Waktu: " . date("Y-m-d H:i:s");

// ===== KIRIM TELEGRAM =====
$url = "https://api.telegram.org/bot$token/sendMessage";

file_get_contents($url . "?" . http_build_query([
    'chat_id' => $chat_id,
    'text' => $pesan,
    'parse_mode' => 'Markdown'
]));

echo "OK";
?>