CREATE DATABASE IF NOT EXISTS laundry_EcoSmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laundry_EcoSmart;

CREATE TABLE IF NOT EXISTS pemilik (
    id_pemilik INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    foto_profil VARCHAR(255) NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'Pemilik',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS karyawan (
    id_karyawan INT AUTO_INCREMENT PRIMARY KEY,
    id_pelanggan INT NULL,
    nama VARCHAR(100) NOT NULL,
    foto_profil VARCHAR(255) NULL,
    no_hp VARCHAR(20),
    jabatan VARCHAR(50) DEFAULT 'Kasir',
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
    role VARCHAR(20) NOT NULL DEFAULT 'Karyawan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pelanggan (
    id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20),
    alamat VARCHAR(255),
    jumlah_bonus INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS layanan_laundry (
    id_layanan INT AUTO_INCREMENT PRIMARY KEY,
    nama_layanan VARCHAR(100) NOT NULL,
    kategori VARCHAR(50),
    satuan VARCHAR(20) DEFAULT 'Kg',
    harga_per_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transaksi (
    id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(50) NOT NULL UNIQUE,
    id_pelanggan INT NOT NULL,
    id_karyawan INT NOT NULL,
    tanggal_transaksi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    berat_cucian DECIMAL(5,2) NOT NULL,
    total_harga DECIMAL(10,2) NOT NULL,
    metode_pembayaran ENUM('Tunai','QRIS','Transfer','Debit') NOT NULL DEFAULT 'Tunai',
    status_pembayaran ENUM('Belum Lunas','Lunas') NOT NULL DEFAULT 'Belum Lunas',
    status_cucian ENUM('Menunggu','Dalam Proses','Selesai','Sudah Diambil') NOT NULL DEFAULT 'Menunggu',
    tanggal_selesai DATE NOT NULL,
    catatan TEXT,
    CONSTRAINT fk_transaksi_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan),
    CONSTRAINT fk_transaksi_karyawan FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan)
);

CREATE TABLE IF NOT EXISTS detail_transaksi (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_layanan INT NOT NULL,
    jumlah DECIMAL(5,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detail_transaksi FOREIGN KEY (id_transaksi) REFERENCES transaksi(id_transaksi) ON DELETE CASCADE,
    CONSTRAINT fk_detail_layanan FOREIGN KEY (id_layanan) REFERENCES layanan_laundry(id_layanan)
);

CREATE TABLE IF NOT EXISTS rating_laundry (
    id_rating INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NULL,
    nama_reviewer VARCHAR(100) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    komentar VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_rating_value CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_rating_user FOREIGN KEY (id_karyawan) REFERENCES karyawan(id_karyawan) ON DELETE SET NULL
);
