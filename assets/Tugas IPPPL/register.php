<?php
require_once 'auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$old = ['nama' => '', 'no_hp' => '', 'jabatan' => 'Kasir', 'username' => '', 'role' => 'User'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $value) {
        $old[$key] = trim($_POST[$key] ?? $value);
    }

    if ($old['role'] === 'User') {
        $old['jabatan'] = 'Pelanggan';
    }

    $password = $_POST['password'] ?? '';
    if ($old['nama'] === '' || $old['username'] === '' || $password === '') {
        $error = 'Nama, username, dan password wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif (!in_array($old['role'], ['Karyawan', 'User'], true)) {
        $error = 'Jenis akun tidak valid.';
    } else {
        $stmt = mysqli_prepare($koneksi, 'SELECT username FROM pemilik WHERE username = ? UNION SELECT username FROM karyawan WHERE username = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'ss', $old['username'], $old['username']);
        mysqli_stmt_execute($stmt);
        $exists = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($exists) > 0) {
            $error = 'Username sudah digunakan, silakan pilih username lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi, "INSERT INTO karyawan (nama, no_hp, jabatan, username, password, status, role) VALUES (?, ?, ?, ?, ?, 'Aktif', ?)");
            mysqli_stmt_bind_param($stmt, 'ssssss', $old['nama'], $old['no_hp'], $old['jabatan'], $old['username'], $hash, $old['role']);
            if (mysqli_stmt_execute($stmt)) {
                $employee_id = mysqli_insert_id($koneksi);
                if ($old['role'] === 'User') {
                    $customer_stmt = mysqli_prepare($koneksi, 'INSERT INTO pelanggan (nama, no_hp) VALUES (?, ?)');
                    mysqli_stmt_bind_param($customer_stmt, 'ss', $old['nama'], $old['no_hp']);
                    if (mysqli_stmt_execute($customer_stmt)) {
                        $customer_id = mysqli_insert_id($koneksi);
                        $link_stmt = mysqli_prepare($koneksi, 'UPDATE karyawan SET id_pelanggan = ? WHERE id_karyawan = ?');
                        mysqli_stmt_bind_param($link_stmt, 'ii', $customer_id, $employee_id);
                        mysqli_stmt_execute($link_stmt);
                    }
                }
                $_SESSION['login_flash'] = ['success' => 'Registrasi berhasil. Silakan masuk sebagai ' . $old['role'] . '.'];
                header('Location: login.php');
                exit;
            }
            $error = 'Registrasi gagal. Periksa koneksi database dan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - RAVF Laundry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #edf6ff; background-image: linear-gradient(rgba(30, 64, 175, .08) 1px, transparent 1px), linear-gradient(90deg, rgba(30, 64, 175, .08) 1px, transparent 1px); background-size: 34px 34px; }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center px-4 py-8">
    <main class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl sm:p-8">
        <a href="login.php" class="text-sm font-bold text-cyan-700 hover:underline">&larr; Kembali ke beranda</a>
        <div class="mt-6"><p class="text-xs font-black uppercase tracking-[.2em] text-cyan-600">RAVF Laundry</p><h1 class="mt-2 text-3xl font-black text-slate-900">Buat akun akses</h1><p class="mt-2 text-sm text-slate-500">Pilih jenis akses sesuai tugas Anda di sistem.</p></div>
        <?php if ($error): ?><div class="mt-5 rounded-lg border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-700"><?= e($error); ?></div><?php endif; ?>
        <form method="POST" class="mt-6 space-y-4" autocomplete="off">
            <div><label class="mb-1 block text-sm font-bold text-slate-700">Nama lengkap</label><input name="nama" value="<?= e($old['nama']); ?>" required class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm outline-none focus:border-cyan-500"></div>
            <div><label class="mb-1 block text-sm font-bold text-slate-700">Daftar sebagai</label><select id="role" name="role" class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm outline-none focus:border-cyan-500"><option value="User" <?= $old['role'] === 'User' ? 'selected' : ''; ?>>Pelanggan</option><option value="Karyawan" <?= $old['role'] === 'Karyawan' ? 'selected' : ''; ?>>Karyawan</option></select></div>
            <div id="jabatan-field" class="<?= $old['role'] === 'User' ? 'hidden' : ''; ?>"><label class="mb-1 block text-sm font-bold text-slate-700">Karyawan sebagai</label><select name="jabatan" class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm outline-none focus:border-cyan-500"><option value="Kasir" <?= $old['jabatan'] === 'Kasir' ? 'selected' : ''; ?>>Kasir</option><option value="Kurir" <?= $old['jabatan'] === 'Kurir' ? 'selected' : ''; ?>>Kurir</option><option value="Petugas Timbang" <?= $old['jabatan'] === 'Petugas Timbang' ? 'selected' : ''; ?>>Petugas Timbang</option></select></div>
            <div><label class="mb-1 block text-sm font-bold text-slate-700">Username</label><input name="username" value="<?= e($old['username']); ?>" required placeholder="Masukkan username" autocomplete="username" class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-500"></div>
            <div><label class="mb-1 block text-sm font-bold text-slate-700">Password</label><input type="password" name="password" minlength="6" required placeholder="Masukkan password" autocomplete="new-password" class="w-full rounded-lg border border-slate-200 px-3 py-3 text-sm outline-none placeholder:text-slate-400 focus:border-cyan-500"></div>
            <button class="w-full rounded-lg bg-cyan-500 py-3 font-black text-slate-950 transition hover:bg-cyan-400">Daftar Akun</button>
        </form>
    </main>
    <script>
        const roleField = document.getElementById('role');
        const jabatanField = document.getElementById('jabatan-field');
        function updateJabatanField() {
            const isEmployee = roleField.value === 'Karyawan';
            jabatanField.classList.toggle('hidden', !isEmployee);
        }
        roleField.addEventListener('change', updateJabatanField);
        updateJabatanField();
    </script>
</body>
</html>
