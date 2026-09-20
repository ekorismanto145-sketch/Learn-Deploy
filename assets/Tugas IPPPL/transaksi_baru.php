<?php
require_once 'app_layout.php';
if (!can_create_transactions()) { http_response_code(403); exit('Akses tidak diizinkan.'); }
$error = '';
$today = date('Y-m-d');
$is_customer = current_role() === 'User';
$customer_id = 0;
if ($is_customer) {
    $customer_stmt = mysqli_prepare($koneksi, 'SELECT id_pelanggan FROM karyawan WHERE id_karyawan = ? LIMIT 1');
    $account_id = (int) $_SESSION['user_id'];
    mysqli_stmt_bind_param($customer_stmt, 'i', $account_id);
    mysqli_stmt_execute($customer_stmt);
    $customer_id = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($customer_stmt))['id_pelanggan'] ?? 0);
    if ($customer_id === 0) $error = 'Data pelanggan belum terhubung ke akun ini. Hubungi kasir.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $kode = 'RAVF-' . date('YmdHis');
    $pelanggan = $is_customer ? $customer_id : (int) ($_POST['id_pelanggan'] ?? 0);
    $petugas = current_role() === 'Karyawan' ? (int) $_SESSION['user_id'] : 0;
    if ($petugas === 0) {
        $q = mysqli_query($koneksi, "SELECT id_karyawan FROM karyawan WHERE status = 'Aktif' ORDER BY id_karyawan LIMIT 1");
        $petugas = (int) (mysqli_fetch_assoc($q)['id_karyawan'] ?? 0);
    }
    $layanan = (int) ($_POST['id_layanan'] ?? 0);
    $berat = (float) ($_POST['berat'] ?? 0);
    $metode = $_POST['metode_pembayaran'] ?? 'Tunai';
    $bayar = $_POST['status_pembayaran'] ?? 'Belum Lunas';
    $selesai = $_POST['tanggal_selesai'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    if ($selesai < $today) $error = 'Tanggal selesai tidak boleh sebelum hari ini.';
    if ($pelanggan <= 0) $error = 'Data pelanggan tidak valid.';
    $q = mysqli_query($koneksi, 'SELECT harga_per_kg FROM layanan_laundry WHERE id_layanan = ' . $layanan);
    $harga = (float) (mysqli_fetch_assoc($q)['harga_per_kg'] ?? 0);
    $total = $berat * $harga;
    $stmt = mysqli_prepare($koneksi, 'INSERT INTO transaksi (kode_transaksi, id_pelanggan, id_karyawan, berat_cucian, total_harga, metode_pembayaran, status_pembayaran, tanggal_selesai, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($stmt, 'siiddssss', $kode, $pelanggan, $petugas, $berat, $total, $metode, $bayar, $selesai, $catatan);
    if ($error === '' && mysqli_stmt_execute($stmt)) {
        $transaction_id = mysqli_insert_id($koneksi);
        mysqli_query($koneksi, "INSERT INTO detail_transaksi (id_transaksi, id_layanan, jumlah, subtotal) VALUES ($transaction_id, $layanan, $berat, $total)");
        mysqli_query($koneksi, 'UPDATE pelanggan SET jumlah_bonus = jumlah_bonus + 1 WHERE id_pelanggan = ' . $pelanggan);
        header('Location: nota.php?id=' . $transaction_id);
        exit;
    }
}
$pelanggan_list = mysqli_query($koneksi, 'SELECT * FROM pelanggan ORDER BY nama ASC');
$layanan_list = mysqli_query($koneksi, 'SELECT * FROM layanan_laundry ORDER BY nama_layanan ASC');
app_start('Transaksi Baru', 'new');
?>
<section class="surface mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><h1 class="border-b border-slate-200 pb-4 text-xl font-black text-blue-700">Transaksi Cucian Baru</h1><?php if ($error): ?><div class="mt-4 rounded-lg bg-red-50 p-3 text-sm font-bold text-red-700"><?= e($error); ?></div><?php endif; ?><form method="POST" class="mt-5 space-y-4"><?php if (!$is_customer): ?><div><label class="mb-1 block text-sm font-bold">Pilih Pelanggan</label><select name="id_pelanggan" required class="w-full rounded-lg border border-slate-200 p-3 text-sm"><?php while ($p = mysqli_fetch_assoc($pelanggan_list)): ?><option value="<?= $p['id_pelanggan']; ?>"><?= e($p['nama']); ?> (<?= e($p['no_hp']); ?>)</option><?php endwhile; ?></select></div><?php endif; ?><div><label class="mb-1 block text-sm font-bold">Pilih Layanan</label><select name="id_layanan" required class="w-full rounded-lg border border-slate-200 p-3 text-sm"><?php while ($l = mysqli_fetch_assoc($layanan_list)): ?><option value="<?= $l['id_layanan']; ?>"><?= e($l['nama_layanan']); ?> - Rp <?= number_format($l['harga_per_kg'], 0, ',', '.'); ?>/Kg</option><?php endwhile; ?></select></div><div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-bold">Hasil Timbangan (Kg)</label><input type="number" step="0.01" min="0.01" name="berat" required class="w-full rounded-lg border border-slate-200 p-3 text-sm" placeholder="Contoh: 3.5"></div><div><label class="mb-1 block text-sm font-bold">Estimasi Selesai</label><input type="date" name="tanggal_selesai" min="<?= $today; ?>" required class="w-full rounded-lg border border-slate-200 p-3 text-sm"></div></div><div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-bold">Metode Pembayaran</label><select name="metode_pembayaran" class="w-full rounded-lg border border-slate-200 p-3 text-sm"><option>Tunai</option><option>QRIS</option><option>Transfer</option><option>Debit</option></select></div><div><label class="mb-1 block text-sm font-bold">Status Pembayaran</label><select name="status_pembayaran" class="w-full rounded-lg border border-slate-200 p-3 text-sm"><option>Belum Lunas</option><option>Lunas</option></select></div></div><div><label class="mb-1 block text-sm font-bold">Catatan Khusus</label><textarea name="catatan" rows="3" class="w-full rounded-lg border border-slate-200 p-3 text-sm" placeholder="Catatan pengerjaan"></textarea></div><button class="w-full rounded-lg bg-blue-600 py-3 font-black text-white hover:bg-blue-700">Simpan & Cetak Nota</button></form></section>
<?php app_end(); ?>
