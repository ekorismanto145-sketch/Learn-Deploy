<?php
require_once 'app_layout.php';
if (!is_admin()) { http_response_code(403); exit('Akses hanya untuk Admin.'); }
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$valid_date = static fn(string $value): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
if (!$valid_date($from)) $from = date('Y-m-01');
if (!$valid_date($to)) $to = date('Y-m-d');
$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS total_transaksi, COALESCE(SUM(total_harga), 0) AS omset, COALESCE(SUM(CASE WHEN status_pembayaran = 'Lunas' THEN total_harga ELSE 0 END), 0) AS lunas FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, 'ss', $from, $to);
mysqli_stmt_execute($stmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$daily_stmt = mysqli_prepare($koneksi, "SELECT DATE(tanggal_transaksi) AS hari, COUNT(*) AS jumlah, COALESCE(SUM(total_harga), 0) AS total FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN ? AND ? GROUP BY DATE(tanggal_transaksi) ORDER BY hari ASC");
mysqli_stmt_bind_param($daily_stmt, 'ss', $from, $to);
mysqli_stmt_execute($daily_stmt);
$daily = mysqli_stmt_get_result($daily_stmt);
$rows = [];
while ($item = mysqli_fetch_assoc($daily)) $rows[] = $item;
$max = 1;
foreach ($rows as $item) $max = max($max, (float) $item['total']);
app_start('Laporan Keuangan', 'report');
?>
<style>@media print{.no-print,#app-sidebar,#sidebar-overlay,header{display:none!important}main{max-width:none!important;padding:0!important}.surface{box-shadow:none!important;border:1px solid #ddd!important}}</style>
<div class="no-print flex flex-wrap items-end justify-between gap-3"><div><p class="text-sm font-bold uppercase tracking-widest text-cyan-700">Admin</p><h1 class="mt-2 text-3xl font-black">Laporan keuangan</h1></div><button type="button" onclick="window.print()" class="rounded-lg bg-slate-900 px-4 py-3 text-sm font-black text-white">Cetak / Simpan PDF</button></div><div class="mt-6 rounded-2xl bg-white p-5 shadow-sm no-print"><form method="GET" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]"><div><label class="mb-1 block text-xs font-bold uppercase text-slate-500">Dari tanggal</label><input type="date" name="from" value="<?= e($from); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2"></div><div><label class="mb-1 block text-xs font-bold uppercase text-slate-500">Sampai tanggal</label><input type="date" name="to" value="<?= e($to); ?>" class="w-full rounded-lg border border-slate-200 px-3 py-2"></div><button class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-bold text-white">Terapkan Filter</button></form></div><section class="mt-6 grid gap-4 sm:grid-cols-3"><article class="surface rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="muted text-sm">Total transaksi</p><strong class="mt-2 block text-3xl font-black"><?= number_format($summary['total_transaksi']); ?></strong></article><article class="surface rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="muted text-sm">Total omset</p><strong class="mt-2 block text-3xl font-black text-cyan-700">Rp <?= number_format($summary['omset'], 0, ',', '.'); ?></strong></article><article class="surface rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="muted text-sm">Pembayaran lunas</p><strong class="mt-2 block text-3xl font-black text-emerald-600">Rp <?= number_format($summary['lunas'], 0, ',', '.'); ?></strong></article></section><section class="surface mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-lg font-black">Rincian <?= date('d/m/Y', strtotime($from)); ?> - <?= date('d/m/Y', strtotime($to)); ?></h2><div class="mt-8 flex h-64 items-end gap-3 border-b border-l border-slate-200 px-3 pt-4"><?php foreach ($rows as $item): $height = max(6, round(((float) $item['total'] / $max) * 100)); ?><div class="group flex h-full flex-1 flex-col items-center justify-end gap-2"><span class="invisible text-xs font-bold group-hover:visible">Rp <?= number_format($item['total'], 0, ',', '.'); ?></span><div class="w-full max-w-12 rounded-t-lg bg-cyan-500" style="height:<?= $height; ?>%"></div><span class="muted text-xs"><?= date('d/m', strtotime($item['hari'])); ?></span></div><?php endforeach; ?></div><div class="mt-6 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b border-slate-200"><th class="px-3 py-3">Tanggal</th><th class="px-3 py-3">Jumlah transaksi</th><th class="px-3 py-3">Omset</th></tr></thead><tbody><?php foreach ($rows as $item): ?><tr class="border-b border-slate-100"><td class="px-3 py-3"><?= date('d/m/Y', strtotime($item['hari'])); ?></td><td class="px-3 py-3"><?= (int) $item['jumlah']; ?></td><td class="px-3 py-3 font-bold">Rp <?= number_format($item['total'], 0, ',', '.'); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php app_end(); ?>
