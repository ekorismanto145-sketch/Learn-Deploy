<?php
require_once 'app_layout.php';
if (current_role() !== 'User') { header('Location: dashboard.php'); exit; }
$user_id = (int) $_SESSION['user_id'];
$message = '';
$stats = ['menunggu' => 0, 'proses' => 0, 'selesai' => 0, 'diambil' => 0, 'total' => 0, 'omset' => 0];
$stats_query = mysqli_query($koneksi, "SELECT status_cucian, COUNT(*) AS total, COALESCE(SUM(total_harga), 0) AS omset FROM transaksi WHERE id_karyawan = {$user_id} GROUP BY status_cucian");
while ($row = mysqli_fetch_assoc($stats_query)) {
    $count = (int) $row['total'];
    $stats['total'] += $count;
    $stats['omset'] += (float) $row['omset'];
    if ($row['status_cucian'] === 'Menunggu') $stats['menunggu'] = $count;
    if ($row['status_cucian'] === 'Dalam Proses') $stats['proses'] = $count;
    if ($row['status_cucian'] === 'Selesai') $stats['selesai'] = $count;
    if ($row['status_cucian'] === 'Sudah Diambil') $stats['diambil'] = $count;
}
$completed = $stats['selesai'] + $stats['diambil'];
$percent = $stats['total'] > 0 ? (int) round(($completed / $stats['total']) * 100) : 0;
$reviews = mysqli_query($koneksi, 'SELECT nama_reviewer, rating, komentar, created_at FROM rating_laundry ORDER BY created_at DESC LIMIT 8');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $completed > 0) {
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['komentar'] ?? '');
    if ($rating < 1 || $rating > 5 || $comment === '') {
        $message = 'Pilih rating 1-5 dan isi komentar.';
    } else {
        $stmt = mysqli_prepare($koneksi, 'INSERT INTO rating_laundry (id_karyawan, nama_reviewer, rating, komentar) VALUES (?, ?, ?, ?)');
        $reviewer_name = $_SESSION['nama'] ?? 'Pelanggan';
        mysqli_stmt_bind_param($stmt, 'isis', $user_id, $reviewer_name, $rating, $comment);
        $message = mysqli_stmt_execute($stmt) ? 'Terima kasih, review Anda sudah tersimpan.' : 'Review gagal disimpan.';
    }
}
app_start('Dashboard Pelanggan', 'customer');
?>
<div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="muted text-sm">Dashboard pelanggan</p><h1 class="mt-1 text-3xl font-black">Halo, <?= e($_SESSION['nama'] ?? 'Pelanggan'); ?></h1><p class="muted mt-2 text-sm">Login sebagai <strong>Pelanggan</strong></p></div><div class="rounded-full bg-emerald-100 px-4 py-2 text-sm font-bold text-emerald-700">● Aktif</div></div>
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><article class="surface rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="muted">Menunggu</p><p class="mt-2 text-3xl font-black text-amber-600"><?= $stats['menunggu']; ?></p></article><article class="surface rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="muted">Dalam Proses</p><p class="mt-2 text-3xl font-black text-blue-600"><?= $stats['proses']; ?></p></article><article class="surface rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="muted">Selesai</p><p class="mt-2 text-3xl font-black text-emerald-600"><?= $stats['selesai']; ?></p></article><article class="surface rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="muted">Total Cucian</p><p class="mt-2 text-3xl font-black text-indigo-600"><?= $stats['total']; ?></p></article></section>
<section class="surface mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-black">Progres cucian Anda</h2><p class="muted mt-1 text-sm">Cucian selesai atau sudah diambil dari seluruh transaksi Anda.</p></div><strong class="text-3xl font-black text-cyan-600"><?= $percent; ?>%</strong></div><div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full bg-cyan-500" style="width:<?= $percent; ?>%"></div></div></section>
<section class="mt-6 grid gap-4 md:grid-cols-2"><a href="transaksi_baru.php" class="surface rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><strong class="text-cyan-700">+ Transaksi baru</strong><p class="muted mt-2 text-sm">Buat pesanan laundry baru.</p></a><a href="history_transaksi.php" class="surface rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><strong class="text-indigo-700">Lihat history</strong><p class="muted mt-2 text-sm">Lihat status semua cucian Anda.</p></a></section>
<?php if ($completed > 0): ?><section class="mt-8 grid gap-6 lg:grid-cols-[.8fr_1.2fr]"><div class="surface rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Beri penilaian</h2><p class="muted mt-2 text-sm">Fitur ini tersedia karena cucian Anda sudah selesai.</p><?php if ($message): ?><div class="mt-4 rounded-lg bg-emerald-100 p-3 text-sm font-bold text-emerald-700"><?= e($message); ?></div><?php endif; ?><form method="POST" class="mt-5 space-y-4"><select name="rating" required class="w-full rounded-lg border border-slate-200 px-3 py-3"><option value="">Pilih bintang</option><option value="5">5 - Sangat puas</option><option value="4">4 - Puas</option><option value="3">3 - Cukup</option><option value="2">2 - Kurang</option><option value="1">1 - Tidak puas</option></select><textarea name="komentar" required maxlength="500" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-3" placeholder="Ceritakan pengalaman Anda"></textarea><button class="w-full rounded-lg bg-emerald-600 py-3 font-black text-white hover:bg-emerald-700">Kirim Review</button></form></div><div class="surface rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-xl font-black">Reviewer pelanggan</h2><div class="mt-5 space-y-4"><?php while ($review = mysqli_fetch_assoc($reviews)): ?><article class="border-b border-slate-100 pb-4 last:border-0"><div class="flex justify-between gap-2"><strong><?= e($review['nama_reviewer']); ?></strong><span class="text-amber-500"><?= str_repeat('★', (int) $review['rating']); ?></span></div><p class="muted mt-2 text-sm">“<?= e($review['komentar']); ?>”</p></article><?php endwhile; ?></div></div></section><?php endif; ?>
<?php app_end(); ?>
