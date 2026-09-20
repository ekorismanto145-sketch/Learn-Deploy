<?php
require_once 'koneksi.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error   = '';
$success = '';
$reviewers = [];
$rating_average = 0;

// Flash message hanya ditampilkan sekali setelah redirect.
if (isset($_SESSION['login_flash'])) {
    $flash = $_SESSION['login_flash'];
    unset($_SESSION['login_flash']);
    $error = $flash['error'] ?? '';
    $success = $flash['success'] ?? '';
}

$review_query = mysqli_query($koneksi, 'SELECT nama_reviewer, rating, komentar, created_at FROM rating_laundry ORDER BY created_at DESC LIMIT 4');
if ($review_query) {
    while ($review = mysqli_fetch_assoc($review_query)) {
        $reviewers[] = $review;
    }
}
$rating_summary = mysqli_fetch_assoc(mysqli_query($koneksi, 'SELECT COALESCE(AVG(rating), 0) AS average_rating FROM rating_laundry'));
$rating_average = round((float) ($rating_summary['average_rating'] ?? 0), 1);
$home_stats = ['menunggu' => 0, 'proses' => 0, 'selesai' => 0, 'diambil' => 0, 'total' => 0];
$home_stats_query = mysqli_query($koneksi, "SELECT status_cucian, COUNT(*) AS total FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE() GROUP BY status_cucian");
if ($home_stats_query) {
    while ($home_row = mysqli_fetch_assoc($home_stats_query)) {
        $home_count = (int) $home_row['total'];
        $home_stats['total'] += $home_count;
        if ($home_row['status_cucian'] === 'Menunggu') $home_stats['menunggu'] = $home_count;
        if ($home_row['status_cucian'] === 'Dalam Proses') $home_stats['proses'] = $home_count;
        if ($home_row['status_cucian'] === 'Selesai') $home_stats['selesai'] = $home_count;
        if ($home_row['status_cucian'] === 'Sudah Diambil') $home_stats['diambil'] = $home_count;
    }
}
$home_completed = $home_stats['selesai'] + $home_stats['diambil'];
$home_percent = $home_stats['total'] > 0 ? (int) round(($home_completed / $home_stats['total']) * 100) : 0;

// Handling Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ================= PROSES LOGIN =================
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Cek ke tabel Pemilik
        $stmt_pemilik = mysqli_prepare($koneksi, "SELECT * FROM pemilik WHERE username = ? LIMIT 1");
        $d_pemilik = false;
        if ($stmt_pemilik) {
            mysqli_stmt_bind_param($stmt_pemilik, 's', $username);
            mysqli_stmt_execute($stmt_pemilik);
            $d_pemilik = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pemilik));
            mysqli_stmt_close($stmt_pemilik);
        }

        if ($d_pemilik && verify_login_password($password, $d_pemilik['password'])) {
            $_SESSION['user_id'] = $d_pemilik['id_pemilik'];
            $_SESSION['nama']    = $d_pemilik['nama'];
            $_SESSION['role']    = 'Admin';
            header("Location: dashboard.php");
            exit;
        }

        // Cek ke tabel Karyawan
        $stmt_karyawan = mysqli_prepare($koneksi, "SELECT * FROM karyawan WHERE username = ? AND status = 'Aktif' LIMIT 1");
        $d_karyawan = false;
        if ($stmt_karyawan) {
            mysqli_stmt_bind_param($stmt_karyawan, 's', $username);
            mysqli_stmt_execute($stmt_karyawan);
            $d_karyawan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_karyawan));
            mysqli_stmt_close($stmt_karyawan);
        }

    if ($d_karyawan && verify_login_password($password, $d_karyawan['password'])) {
            $_SESSION['user_id'] = $d_karyawan['id_karyawan'];
            $_SESSION['nama']    = $d_karyawan['nama'];
            $_SESSION['role']    = $d_karyawan['role'] ?: 'Karyawan';
            header('Location: ' . ($d_karyawan['role'] === 'User' ? 'customer_dashboard.php' : 'dashboard.php'));
            exit;
        }

        $_SESSION['login_flash'] = ['error' => 'Username atau Password salah!'];
        header('Location: login.php#beranda');
        exit;
    }

}

function verify_login_password(string $password, string $stored_password): bool
{
    if (password_verify($password, $stored_password)) {
        return true;
    }

    // Mendukung akun lama saat password belum dimigrasikan ke hash.
    return !password_get_info($stored_password)['algo'] && hash_equals($stored_password, $password);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAVF Laundry - Kelola Laundry Lebih Mudah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Trebuchet MS', sans-serif; }
        .hero-grid { background-image: linear-gradient(rgba(30, 64, 175, .08) 1px, transparent 1px), linear-gradient(90deg, rgba(30, 64, 175, .08) 1px, transparent 1px); background-size: 34px 34px; }
        .modal-backdrop { background: rgba(15, 23, 42, .72); backdrop-filter: blur(5px); }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">
    <header class="absolute inset-x-0 top-0 z-20 border-b border-indigo-800/60 bg-indigo-950/95 shadow-lg shadow-indigo-950/20">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-5 py-5 lg:px-8" aria-label="Navigasi utama">
            <a href="#beranda" class="flex items-center gap-3 text-white">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-400 text-xl text-slate-950 shadow-lg shadow-cyan-950/20"><i class="fa-solid fa-shirt"></i></span>
                <span class="text-xl font-black tracking-[.18em]">RAVF</span>
            </a>
            <div class="hidden items-center gap-8 text-sm font-semibold text-slate-200 md:flex">
                <a href="#beranda" class="transition hover:text-cyan-300">Beranda</a>
                <a href="#layanan" class="transition hover:text-cyan-300">Layanan</a>
                <a href="#rating" class="transition hover:text-cyan-300">Rating</a>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="openAuth('login')" class="rounded-lg px-3 py-2 text-sm font-bold text-white transition hover:bg-white/10"><i class="fa-solid fa-right-to-bracket mr-1.5"></i>Masuk</button>
                <a href="register.php" class="rounded-lg bg-cyan-400 px-4 py-2 text-sm font-bold text-slate-950 shadow-lg shadow-cyan-950/20 transition hover:bg-cyan-300"><i class="fa-solid fa-user-plus mr-1.5"></i>Daftar</a>
            </div>
        </nav>
    </header>

    <main>
        <section id="beranda" class="hero-grid overflow-hidden bg-[#edf6ff] px-5 pb-20 pt-32 text-slate-950 lg:px-8 lg:pb-28 lg:pt-40">
            <div class="mx-auto grid max-w-6xl items-center gap-12 lg:grid-cols-[1.05fr_.95fr]">
                <div>
                    <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-cyan-300/30 bg-cyan-300/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[.16em] text-cyan-300"><span class="h-2 w-2 rounded-full bg-cyan-300"></span>Operasional laundry, lebih terarah</p>
                    <h1 class="max-w-2xl text-4xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">Laundry rapi, pelanggan kembali.</h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-600">RAVF membantu tim Anda menerima cucian, memantau proses, mencetak nota, dan membaca laporan dalam satu dashboard sederhana.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <button type="button" onclick="openAuth('login')" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:bg-blue-900"><i class="fa-solid fa-arrow-right-to-bracket mr-2"></i>Mulai kelola laundry</button>
                        <a href="#layanan" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold text-slate-800 transition hover:border-blue-600 hover:text-blue-700">Lihat fitur</a>
                    </div>
                    <div class="mt-10 flex flex-wrap gap-7 border-t border-blue-200 pt-6 text-sm text-slate-600">
                        <span><strong class="block text-2xl text-slate-950">24/7</strong>Data tersimpan rapi</span>
                        <span><strong class="block text-2xl text-slate-950"><?= number_format($rating_average, 1); ?>/5</strong>Kepuasan pengguna</span>
                        <span><strong class="block text-2xl text-slate-950">1</strong>Dashboard terpadu</span>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute -inset-8 rounded-full bg-cyan-400/10 blur-3xl"></div>
                    <div class="relative rounded-2xl border border-white/10 bg-white p-5 text-slate-800 shadow-2xl shadow-cyan-950/30 sm:p-7">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-5"><div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ringkasan hari ini</p><h2 class="mt-1 text-xl font-black">Dashboard RAVF</h2></div><span class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-600"><i class="fa-solid fa-arrow-trend-up mr-1"></i><?= $home_stats['menunggu'] + $home_stats['proses'] > 0 ? 'Aktif' : 'Tidak ada pesanan'; ?></span></div>
                        <div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-xl bg-slate-50 p-4"><i class="fa-solid fa-basket-shopping text-cyan-600"></i><p class="mt-3 text-2xl font-black"><?= $home_stats['menunggu']; ?></p><p class="text-xs text-slate-500">Pesanan masuk</p></div><div class="rounded-xl bg-amber-50 p-4"><i class="fa-solid fa-clock text-amber-600"></i><p class="mt-3 text-2xl font-black"><?= $home_stats['proses']; ?></p><p class="text-xs text-slate-500">Dalam proses</p></div></div>
                        <div class="mt-4 rounded-xl border border-slate-100 p-4"><div class="flex items-center justify-between text-sm"><span class="font-bold">Target selesai hari ini</span><span class="font-black text-cyan-600"><?= $home_percent; ?>%</span></div><div class="mt-3 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-cyan-400" style="width: <?= $home_percent; ?>%"></div></div><p class="mt-3 text-xs text-slate-500"><i class="fa-solid fa-circle-check mr-1 text-emerald-500"></i>Data sesuai transaksi hari ini</p></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="layanan" class="bg-white px-5 py-20 lg:px-8"><div class="mx-auto max-w-6xl"><div class="max-w-xl"><p class="text-xs font-black uppercase tracking-[.2em] text-cyan-600">Satu ruang kerja</p><h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900">Semua yang dibutuhkan untuk laundry yang bertumbuh.</h2></div><div class="mt-10 grid gap-4 md:grid-cols-3"><article class="rounded-2xl border border-slate-200 bg-slate-50 p-6"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700"><i class="fa-solid fa-receipt"></i></span><h3 class="mt-5 font-black">Transaksi cepat</h3><p class="mt-2 text-sm leading-6 text-slate-500">Catat pelanggan, layanan, berat cucian, dan pembayaran tanpa langkah berbelit.</p></article><article class="rounded-2xl border border-slate-200 bg-slate-50 p-6"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><i class="fa-solid fa-list-check"></i></span><h3 class="mt-5 font-black">Status terpantau</h3><p class="mt-2 text-sm leading-6 text-slate-500">Pantau cucian dari menunggu hingga selesai agar tidak ada pesanan terlewat.</p></article><article class="rounded-2xl border border-slate-200 bg-slate-50 p-6"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"><i class="fa-solid fa-chart-line"></i></span><h3 class="mt-5 font-black">Laporan jelas</h3><p class="mt-2 text-sm leading-6 text-slate-500">Baca omset dan aktivitas operasional untuk mengambil keputusan dengan percaya diri.</p></article></div></div></section>

        <section id="rating" class="bg-cyan-50 px-5 py-20 lg:px-8"><div class="mx-auto grid max-w-6xl items-center gap-10 lg:grid-cols-[.8fr_1.2fr]"><div><p class="text-xs font-black uppercase tracking-[.2em] text-cyan-700">Dipakai setiap hari</p><h2 class="mt-3 text-3xl font-black text-slate-900">Cerita baik dari tim laundry.</h2><div class="mt-5 flex items-center gap-3"><span class="text-4xl font-black text-slate-900">4.9</span><div><div class="text-amber-500" aria-label="Rating 5 dari 5"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div><p class="mt-1 text-xs text-slate-500">Rating rata-rata pengguna</p></div></div></div><div class="grid gap-4 sm:grid-cols-2"><blockquote class="rounded-2xl bg-white p-6 shadow-sm"><div class="text-amber-500 text-sm"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div><p class="mt-4 text-sm leading-6 text-slate-600">“Pencatatan jadi lebih cepat dan status cucian mudah dicek oleh semua tim.”</p><footer class="mt-4 text-xs font-bold text-slate-900">Rina, Pemilik Laundry</footer></blockquote><blockquote class="rounded-2xl bg-white p-6 shadow-sm"><div class="text-amber-500 text-sm"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div><p class="mt-4 text-sm leading-6 text-slate-600">“Dashboard-nya membantu saya melihat pekerjaan hari ini tanpa buka banyak catatan.”</p><footer class="mt-4 text-xs font-bold text-slate-900">Dimas, Supervisor</footer></blockquote></div></div></section>
        <section class="bg-slate-50 px-5 py-16 lg:px-8"><div class="mx-auto max-w-6xl"><div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-[.2em] text-cyan-700">Review terbaru</p><h2 class="mt-2 text-3xl font-black text-slate-900">Apa kata pelanggan kami?</h2></div><a href="register.php" class="rounded-lg border border-cyan-600 px-4 py-2 text-sm font-bold text-cyan-700 hover:bg-cyan-50">Beri penilaian</a></div><div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><?php if (!$reviewers): ?><p class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">Belum ada review. Jadilah pelanggan pertama yang memberikan penilaian.</p><?php else: ?><?php foreach ($reviewers as $review): ?><article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="text-amber-500"><?php for ($star = 1; $star <= 5; $star++): ?><i class="fa-solid fa-star<?= $star <= (int) $review['rating'] ? '' : ' text-slate-200'; ?>"></i><?php endfor; ?></div><p class="mt-3 text-sm leading-6 text-slate-600">&ldquo;<?= htmlspecialchars($review['komentar'], ENT_QUOTES, 'UTF-8'); ?>&rdquo;</p><p class="mt-4 text-xs font-black text-slate-900"><?= htmlspecialchars($review['nama_reviewer'], ENT_QUOTES, 'UTF-8'); ?></p><p class="mt-1 text-[11px] text-slate-400"><?= date('d/m/Y', strtotime($review['created_at'])); ?></p></article><?php endforeach; ?><?php endif; ?></div></div></section>
        <section class="bg-indigo-950 px-5 py-10 text-center text-white lg:px-8"><p class="text-xs font-black uppercase tracking-[.2em] text-cyan-300">Rating aktual</p><p class="mt-2 text-4xl font-black"><?= number_format($rating_average, 1); ?><span class="text-lg text-slate-300"> / 5</span></p><p class="mt-2 text-sm text-slate-300">Rata-rata dari review pelanggan yang tersimpan.</p></section>
    </main>

    <footer class="bg-slate-950 px-5 py-7 text-center text-xs text-slate-400">&copy; <?= date('Y'); ?> SISTEM LAUNDRY RAVF. Kelola lebih rapi, layani lebih baik.</footer>

    <div id="auth-modal" class="modal-backdrop fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto p-4" role="dialog" aria-modal="true" aria-labelledby="auth-title">
        <div class="relative my-6 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
            <button type="button" onclick="closeAuth()" class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
            <div class="mb-6 pr-8"><p class="text-xs font-black uppercase tracking-[.18em] text-cyan-600">RAVF Laundry</p><h2 id="auth-title" class="mt-2 text-2xl font-black text-slate-900">Selamat datang kembali</h2><p id="auth-subtitle" class="mt-2 text-sm text-slate-500">Masuk untuk mengelola operasional laundry.</p></div>
            <?php if ($error): ?><div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-3 text-xs text-red-700"><i class="fa-solid fa-triangle-exclamation mr-1"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="mb-4 rounded-lg border-l-4 border-emerald-500 bg-emerald-50 p-3 text-xs text-emerald-700"><i class="fa-solid fa-circle-check mr-1"></i><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form id="form-login" method="POST" action="" class="space-y-4" autocomplete="off"><input type="hidden" name="action" value="login"><div><label for="login-username" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-600">Username</label><input id="login-username" type="text" name="username" required autocomplete="off" placeholder="Masukkan username" aria-label="Masukkan username" data-lpignore="true" class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"></div><div><label for="login-password" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-600">Password</label><input id="login-password" type="password" name="password" required autocomplete="new-password" placeholder="Masukkan password" aria-label="Masukkan password" data-lpignore="true" class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"></div><button type="submit" class="w-full rounded-lg bg-slate-900 py-3 text-sm font-black text-white transition hover:bg-cyan-700">Masuk ke Sistem</button></form>
            <p class="mt-5 text-center text-xs text-slate-500">Belum punya akun? <a href="register.php" class="font-bold text-cyan-700 hover:underline">Daftar sekarang</a></p>
        </div>
    </div>

    <script>
        const modal = document.getElementById('auth-modal');
        let authMode = 'login';
        function openAuth(type) { authMode = 'login'; updateAuth(); document.getElementById('login-username').value = ''; document.getElementById('login-password').value = ''; window.scrollTo({ top: 0, left: 0, behavior: 'auto' }); document.documentElement.scrollTop = 0; document.body.scrollTop = 0; modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.classList.add('overflow-hidden'); setTimeout(() => { window.scrollTo(0, 0); document.documentElement.scrollTop = 0; document.body.scrollTop = 0; }, 0); document.getElementById('login-username').focus({ preventScroll: true }); }
        function closeAuth() { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); }
        function updateAuth() {
            document.getElementById('form-login').classList.remove('hidden');
            document.getElementById('auth-title').textContent = 'Selamat datang kembali';
            document.getElementById('auth-subtitle').textContent = 'Masuk untuk mengelola operasional laundry.';
        }
        modal.addEventListener('click', (event) => { if (event.target === modal) closeAuth(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeAuth(); });
        window.addEventListener('load', () => {
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;
            history.replaceState(null, document.title, window.location.pathname);
            <?php if (!$error && !$success): ?>closeAuth();<?php endif; ?>
        });
        <?php if ($error): ?>openAuth('login');<?php elseif ($success): ?>openAuth('login');<?php endif; ?>
    </script>
</body>
</html>