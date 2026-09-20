<?php
require_once 'app_layout.php';
if (!is_admin()) { http_response_code(403); exit('Akses hanya untuk Admin.'); }
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id_karyawan'] ?? 0);
    if ($action === 'status' && $id > 0) {
        $status = $_POST['status'] === 'Aktif' ? 'Aktif' : 'Nonaktif';
        $stmt = mysqli_prepare($koneksi, 'UPDATE karyawan SET status = ? WHERE id_karyawan = ?');
        mysqli_stmt_bind_param($stmt, 'si', $status, $id);
        mysqli_stmt_execute($stmt);
        $message = 'Status karyawan diperbarui.';
    } elseif ($action === 'delete' && $id > 0) {
        $stmt = mysqli_prepare($koneksi, "UPDATE karyawan SET status = 'Nonaktif' WHERE id_karyawan = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $message = 'Karyawan dinonaktifkan agar riwayat transaksi tetap aman.';
    }
}
$employees = mysqli_query($koneksi, 'SELECT id_karyawan, nama, no_hp, jabatan, username, status, role, created_at FROM karyawan ORDER BY nama ASC');
app_start('Kelola Karyawan', 'employees');
?>
<div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-sm font-bold uppercase tracking-widest text-cyan-700">Admin</p><h1 class="mt-2 text-3xl font-black">Kelola karyawan</h1><p class="muted mt-2 text-sm">Lihat dan atur status akun kasir/karyawan.</p></div><a href="register.php" class="rounded-lg bg-cyan-500 px-4 py-3 text-sm font-black text-slate-950">+ Tambah Karyawan</a></div>
<?php if ($message): ?><div class="mt-5 rounded-lg bg-emerald-100 p-3 text-sm font-bold text-emerald-700"><?= e($message); ?></div><?php endif; ?><div class="mt-6 overflow-x-auto rounded-2xl bg-white shadow-sm"><table class="min-w-[850px] w-full text-left text-sm"><thead class="bg-slate-900 text-xs uppercase tracking-wider text-white"><tr><th class="px-5 py-4">Nama</th><th class="px-5 py-4">Username</th><th class="px-5 py-4">Jabatan</th><th class="px-5 py-4">Role</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100"><?php while ($employee = mysqli_fetch_assoc($employees)): ?><tr><td class="px-5 py-4 font-bold"><?= e($employee['nama']); ?><br><span class="text-xs text-slate-400"><?= e($employee['no_hp']); ?></span></td><td class="px-5 py-4"><?= e($employee['username']); ?></td><td class="px-5 py-4"><?= e($employee['jabatan']); ?></td><td class="px-5 py-4"><?= e($employee['role']); ?></td><td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold <?= $employee['status'] === 'Aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'; ?>"><?= e($employee['status']); ?></span></td><td class="px-5 py-4"><form method="POST" class="flex flex-wrap gap-2"><input type="hidden" name="action" value="status"><input type="hidden" name="id_karyawan" value="<?= (int) $employee['id_karyawan']; ?>"><select name="status" class="rounded-md border border-slate-200 px-2 py-1 text-xs"><option <?= $employee['status'] === 'Aktif' ? 'selected' : ''; ?>>Aktif</option><option <?= $employee['status'] === 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option></select><button class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-bold text-white">Simpan</button></form></td></tr><?php endwhile; ?></tbody></table></div>
<?php app_end(); ?>
