<?php
require_once 'auth.php';

function app_start(string $title, string $active = ''): void
{
    global $koneksi;
    require_login();
    $role = current_role();
    $is_staff_user = is_staff();
    $home = $role === 'User' ? 'customer_dashboard.php' : 'dashboard.php';
    $display_name = $_SESSION['nama'] ?? 'Pengguna';
    $profile_table = is_admin() ? 'pemilik' : 'karyawan';
    $profile_id = is_admin() ? 'id_pemilik' : 'id_karyawan';
    $profile_stmt = mysqli_prepare($koneksi, "SELECT foto_profil FROM {$profile_table} WHERE {$profile_id} = ? LIMIT 1");
    $profile_user_id = (int) ($_SESSION['user_id'] ?? 0);
    mysqli_stmt_bind_param($profile_stmt, 'i', $profile_user_id);
    mysqli_stmt_execute($profile_stmt);
    $profile_data = mysqli_fetch_assoc(mysqli_stmt_get_result($profile_stmt)) ?: [];
    $profile_photo = $profile_data['foto_profil'] ?? '';
    $show_back = !in_array($active, ['dashboard', 'customer'], true);
    ?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title); ?> - RAVF</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [data-theme="dark"] body{background:#0f172a;color:#e2e8f0}
        [data-theme="dark"] .surface{background:#1e293b;color:#e2e8f0;border-color:#334155}
        [data-theme="dark"] .muted{color:#94a3b8}
        [data-theme="dark"] input,[data-theme="dark"] select,[data-theme="dark"] textarea{background:#0f172a!important;color:#e2e8f0!important;border-color:#475569!important}
        [data-theme="dark"] input::placeholder,[data-theme="dark"] textarea::placeholder{color:#94a3b8}
        [data-theme="dark"] .bg-white{background:#1e293b!important;color:#e2e8f0}
        [data-theme="dark"] .bg-slate-50{background:#243247!important}
        [data-theme="dark"] .bg-slate-100{background:#0f172a!important}
        [data-theme="dark"] .bg-emerald-100{background:#064e3b!important;color:#a7f3d0!important}
        [data-theme="dark"] .bg-emerald-50{background:#123b32!important}
        [data-theme="dark"] .bg-amber-50{background:#422006!important}
        [data-theme="dark"] .bg-cyan-50{background:#083344!important}
        [data-theme="dark"] .bg-red-50{background:#450a0a!important;color:#fecaca!important}
        [data-theme="dark"] .bg-indigo-100{background:#312e81!important;color:#c7d2fe!important}
        [data-theme="dark"] .text-slate-900,[data-theme="dark"] .text-slate-800,[data-theme="dark"] .text-slate-700{color:#e2e8f0!important}
        [data-theme="dark"] .text-slate-600,[data-theme="dark"] .text-slate-500{color:#cbd5e1!important}
        [data-theme="dark"] .border-slate-100,[data-theme="dark"] .border-slate-200{border-color:#334155!important}
        .sidebar{transform:translateX(-105%);transition:transform .25s ease}
        .sidebar.open{transform:translateX(0)}
        .sidebar-overlay{opacity:0;pointer-events:none;transition:opacity .25s ease}
        .sidebar-overlay.open{opacity:1;pointer-events:auto}
    </style>
</head>
<body class="min-h-screen bg-slate-100 transition-colors">
<header class="sticky top-0 z-30 bg-indigo-950 text-white shadow-lg"><div class="flex min-h-16 items-center justify-between gap-3 px-4 lg:px-8"><div class="flex items-center gap-3"><button id="sidebar-toggle" type="button" class="rounded-lg border border-indigo-700 px-3 py-2 text-lg hover:bg-indigo-800" aria-label="Buka menu">☰</button><a href="<?= $home; ?>" class="text-xl font-black tracking-widest text-cyan-300">RAVF</a></div><div class="flex items-center gap-3 text-sm"><?php if ($profile_photo): ?><img src="<?= e($profile_photo); ?>" alt="Foto profil" class="h-9 w-9 rounded-full object-cover ring-2 ring-cyan-300"><?php else: ?><span class="flex h-9 w-9 items-center justify-center rounded-full bg-cyan-400 font-black text-slate-950"><?= e(strtoupper(substr($display_name, 0, 1))); ?></span><?php endif; ?><span class="hidden text-indigo-200 sm:inline"><?= e($display_name); ?> · <?= e($role); ?></span><button id="language-toggle" type="button" class="rounded-lg border border-indigo-700 px-3 py-2 hover:bg-indigo-800">EN</button><button id="theme-toggle" type="button" class="rounded-lg border border-indigo-700 px-3 py-2" aria-label="Ganti mode warna">☾</button></div></div></header>
<div id="sidebar-overlay" class="sidebar-overlay fixed inset-0 z-40 bg-slate-950/50"></div>
<aside id="app-sidebar" class="sidebar fixed inset-y-0 left-0 z-50 w-72 bg-indigo-950 p-5 text-white shadow-2xl"><div class="flex items-center justify-between"><div class="flex items-center gap-3"><?php if ($profile_photo): ?><img src="<?= e($profile_photo); ?>" alt="Foto profil" class="h-10 w-10 rounded-full object-cover"><?php endif; ?><div><p class="text-xs font-black uppercase tracking-[.2em] text-cyan-300">Menu RAVF</p><p class="mt-1 text-sm text-indigo-200"><?= e($display_name); ?></p></div></div><button id="sidebar-close" type="button" class="rounded-lg px-3 py-2 text-xl text-indigo-200 hover:bg-indigo-800" aria-label="Tutup menu">×</button></div><div class="mt-6 rounded-xl bg-indigo-900 p-4"><p class="text-xs text-indigo-300">Login sebagai</p><p class="mt-1 font-black text-white"><?= e($role); ?></p><a href="profile.php" class="mt-3 inline-flex text-sm font-bold text-cyan-300 hover:text-cyan-200">Kelola profil &rarr;</a></div><nav class="mt-6 space-y-2 text-sm font-semibold"><?php if ($is_staff_user): ?><a class="block rounded-lg px-3 py-3 <?= $active === 'dashboard' ? 'bg-cyan-500 text-slate-950' : 'hover:bg-indigo-800'; ?>" href="dashboard.php">Dashboard Operasional</a><?php endif; ?><?php if (can_create_transactions()): ?><a class="block rounded-lg px-3 py-3 <?= $active === 'new' ? 'bg-cyan-500 text-slate-950' : 'hover:bg-indigo-800'; ?>" href="transaksi_baru.php">Transaksi Baru</a><?php endif; ?><a class="block rounded-lg px-3 py-3 <?= $active === 'history' ? 'bg-cyan-500 text-slate-950' : 'hover:bg-indigo-800'; ?>" href="history_transaksi.php">History Transaksi</a><?php if (is_admin()): ?><a class="block rounded-lg px-3 py-3 <?= $active === 'report' ? 'bg-cyan-500 text-slate-950' : 'hover:bg-indigo-800'; ?>" href="laporan.php">Laporan Keuangan</a><a class="block rounded-lg px-3 py-3 <?= $active === 'employees' ? 'bg-cyan-500 text-slate-950' : 'hover:bg-indigo-800'; ?>" href="kelola_karyawan.php">Kelola Karyawan</a><?php endif; ?><a class="mt-6 block rounded-lg bg-rose-600 px-3 py-3 hover:bg-rose-500" href="logout.php">Keluar</a></nav></aside>
<main class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
    <?php if ($show_back): ?><div class="mb-5"><a href="javascript:history.back()" class="inline-flex items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-bold text-indigo-700 shadow-sm transition hover:border-cyan-400 hover:bg-cyan-50 hover:text-cyan-700">Kembali</a></div><?php endif; ?>
<?php
}

function app_end(): void
{
    ?>
</main>
<script>
const root=document.documentElement, sidebar=document.getElementById('app-sidebar'), overlay=document.getElementById('sidebar-overlay');
function closeSidebar(){sidebar.classList.remove('open');overlay.classList.remove('open');document.body.classList.remove('overflow-hidden')}
document.getElementById('sidebar-toggle').addEventListener('click',()=>{sidebar.classList.add('open');overlay.classList.add('open');document.body.classList.add('overflow-hidden')});document.getElementById('sidebar-close').addEventListener('click',closeSidebar);overlay.addEventListener('click',closeSidebar);document.addEventListener('keydown',event=>{if(event.key==='Escape')closeSidebar()});
document.getElementById('theme-toggle').addEventListener('click',()=>{const dark=root.dataset.theme!=='dark';root.dataset.theme=dark?'dark':'light';localStorage.setItem('ravf-theme',root.dataset.theme)});if(localStorage.getItem('ravf-theme')==='dark')root.dataset.theme='dark';
const translations={
    'Menu RAVF':'RAVF Menu','Login sebagai':'Logged in as','Kelola profil →':'Manage profile →','Dashboard Operasional':'Operations Dashboard','Transaksi Baru':'New Transaction','History Transaksi':'Transaction History','Laporan Keuangan':'Financial Report','Keluar':'Logout','Kembali':'Back','Dashboard operasional':'Operational dashboard','Menunggu':'Waiting','Dalam Proses':'In Progress','Selesai':'Completed','Omset Hari Ini':'Today\'s Revenue','Target penyelesaian hari ini':'Today\'s completion target','Selesai dan sudah diambil dibanding seluruh pesanan hari ini.':'Completed and picked up compared with all orders today.','+ Transaksi baru':'+ New transaction','Lihat history':'View history','Laporan keuangan':'Financial report','Catat pesanan pelanggan.':'Record a customer order.','Ubah status atau hapus transaksi.':'Update or delete transactions.','Pantau omset dan grafik pemasukan.':'Monitor revenue and income charts.','Pengaturan akun':'Account settings','Ganti foto, nama tampilan, dan username akun Anda.':'Change your photo, display name, and username.','Nama':'Name','Username':'Username','Foto profil':'Profile photo','Simpan perubahan':'Save changes','Transaksi Baru & Timbang Cucian':'New Transaction & Weighing','Pilih Pelanggan':'Select Customer','Pilih Layanan':'Select Service','Hasil Timbangan (Kg)':'Weight (Kg)','Metode Pembayaran':'Payment Method','Status Pembayaran':'Payment Status','Estimasi Selesai':'Estimated Completion','Catatan Khusus':'Special Notes','Simpan & Cetak Nota':'Save & Print Receipt','History transaksi':'Transaction history','Cari transaksi berdasarkan tanggal transaksi.':'Search transactions by date.','Dari tanggal':'From date','Sampai tanggal':'To date','Filter':'Filter','Reset':'Reset','Kode':'Code','Pelanggan':'Customer','Tanggal':'Date','Total':'Total','Pembayaran':'Payment','Status':'Status','Aksi':'Actions','Nota':'Receipt','Simpan':'Save','Hapus':'Delete','Login sebagai Admin':'Logged in as Admin','Laporan keuangan':'Financial report','Total transaksi':'Total transactions','Total omset':'Total revenue','Pembayaran lunas':'Paid amount','Omset 7 hari terakhir':'Revenue in the last 7 days','Beri penilaian':'Leave a review','Reviewer pelanggan':'Customer reviews','Kirim Review':'Submit Review','Rating':'Rating','Komentar':'Comment','Pilih bintang':'Choose stars','Halo':'Hello','Data belum tersedia':'Data unavailable','Memuat data...':'Loading data...'
};
function applyLanguage(english){window.ravfEnglish=english;document.querySelectorAll('[data-id]').forEach(element=>{const value=english?element.dataset.en:element.dataset.id;if(value)element.textContent=value});const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);const nodes=[];while(walker.nextNode())nodes.push(walker.currentNode);nodes.forEach(node=>{const original=node.nodeValue.trim();if(!original)return;const translated=english?(translations[original]||original):Object.keys(translations).find(key=>translations[key]===original)||original;if(translated!==original)node.nodeValue=node.nodeValue.replace(original,translated)});document.getElementById('language-toggle').textContent=english?'ID':'EN';localStorage.setItem('ravf-language',english?'en':'id');document.dispatchEvent(new CustomEvent('ravf-language-changed',{detail:{english}}))}
document.getElementById('language-toggle').addEventListener('click',event=>applyLanguage(event.currentTarget.textContent==='EN'));if(localStorage.getItem('ravf-language')==='en')applyLanguage(true);
</script></body></html>
<?php
}
