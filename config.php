<?php
$conn = new mysqli("localhost", "root", "", "gelang_monitoring");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>