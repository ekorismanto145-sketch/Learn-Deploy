<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "laundry_EcoSmart";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
session_start();

// Menjamin tabel review tersedia saat database.sql belum sempat diimpor.
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS rating_laundry (id_rating INT AUTO_INCREMENT PRIMARY KEY, id_karyawan INT NULL, nama_reviewer VARCHAR(100) NOT NULL, rating TINYINT UNSIGNED NOT NULL, komentar VARCHAR(500) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CONSTRAINT chk_rating_value CHECK (rating BETWEEN 1 AND 5))");

foreach (['pemilik', 'karyawan'] as $profile_table) {
    $profile_columns = mysqli_query($koneksi, "SHOW COLUMNS FROM {$profile_table} LIKE 'foto_profil'");
    if ($profile_columns && mysqli_num_rows($profile_columns) === 0) {
        mysqli_query($koneksi, "ALTER TABLE {$profile_table} ADD foto_profil VARCHAR(255) NULL AFTER nama");
    }
}
$customer_link = mysqli_query($koneksi, "SHOW COLUMNS FROM karyawan LIKE 'id_pelanggan'");
if ($customer_link && mysqli_num_rows($customer_link) === 0) {
    mysqli_query($koneksi, "ALTER TABLE karyawan ADD id_pelanggan INT NULL AFTER id_karyawan");
}
mysqli_query($koneksi, "UPDATE karyawan k JOIN pelanggan p ON p.nama = k.nama AND (p.no_hp = k.no_hp OR (p.no_hp IS NULL AND k.no_hp IS NULL)) SET k.id_pelanggan = p.id_pelanggan WHERE k.role = 'User' AND k.id_pelanggan IS NULL");
?>