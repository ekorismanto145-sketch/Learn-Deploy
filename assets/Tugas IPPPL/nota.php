<?php
require_once 'koneksi.php';
if (!isset($_GET['id'])) { die("ID Transaksi tidak ditemukan."); }

$id_transaksi = $_GET['id'];
$query = mysqli_query($koneksi, "
    SELECT t.*, p.nama AS nama_pelanggan, p.no_hp, k.nama AS nama_kasir 
    FROM transaksi t
    JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
    JOIN karyawan k ON t.id_karyawan = k.id_karyawan
    WHERE t.id_transaksi = '$id_transaksi'
");
$data = mysqli_fetch_assoc($query);

$detail = mysqli_query($koneksi, "
    SELECT d.*, l.nama_layanan 
    FROM detail_transaksi d
    JOIN layanan_laundry l ON d.id_layanan = l.id_layanan
    WHERE d.id_transaksi = '$id_transaksi'
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota - <?= $data['kode_transaksi']; ?></title>
    <style>
        body { font-family: monospace; width: 80mm; padding: 10px; margin: auto; }
        .text-center { text-align: center; }
        .line { border-bottom: 1px dashed #000; margin: 8px 0; }
        .flex { display: flex; justify-content: space-between; }
        .print-button { display: block; width: 100%; margin: 0 auto 12px; padding: 8px; border: 0; border-radius: 5px; background: #0f172a; color: white; cursor: pointer; font-family: sans-serif; }
        @media print { .print-button { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <button type="button" class="print-button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
    <div class="text-center">
        <h3 style="margin:0;">LAUNDRY RAVF</h3>
        <p style="font-size: 11px; margin: 2px 0;">Jl. Raya Laundry No. 12, Surabaya</p>
        <p style="font-size: 11px; margin: 2px 0;">Telp: 0812-3456-7890</p>
    </div>
    
    <div class="line"></div>
    
    <div style="font-size: 11px;">
        <div>Kode : <?= $data['kode_transaksi']; ?></div>
        <div>Tgl  : <?= date('d/m/Y H:i', strtotime($data['tanggal_transaksi'])); ?></div>
        <div>Kasir: <?= $data['nama_kasir']; ?></div>
        <div>Plg  : <?= $data['nama_pelanggan']; ?></div>
    </div>
    
    <div class="line"></div>
    
    <table style="width: 100%; font-size: 11px;">
        <?php while($item = mysqli_fetch_assoc($detail)): ?>
        <tr>
            <td colspan="2"><b><?= $item['nama_layanan']; ?></b></td>
        </tr>
        <tr>
            <td><?= $item['jumlah']; ?> Kg</td>
            <td style="text-align: right;">Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <div class="line"></div>
    
    <div style="font-size: 11px;" class="flex">
        <span><b>TOTAL:</b></span>
        <span><b>Rp <?= number_format($data['total_harga'], 0, ',', '.'); ?></b></span>
    </div>
    <div style="font-size: 11px;" class="flex">
        <span>Status Bayar:</span>
        <span><?= $data['status_pembayaran']; ?> (<?= $data['metode_pembayaran']; ?>)</span>
    </div>
    <div style="font-size: 11px;" class="flex">
        <span>Est. Selesai:</span>
        <span><?= date('d/m/Y', strtotime($data['tanggal_selesai'])); ?></span>
    </div>

    <div class="line"></div>
    <div class="text-center" style="font-size: 10px;">
        <p>Terima kasih telah mempercayakan cucian Anda di LAUNDRY RAVF!</p>
    </div>
</body>
</html>