<?php
// ...existing code...
session_start();

// Cek apakah sudah login dan role = admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['role'] != "admin") {
    header("location: ../index.php?error=unauthorized");
    exit;
}

include __DIR__ . '/../koneksi.php';

if (!isset($conn)) {
    die("❌ Koneksi database tidak ditemukan!");
}

/*
  Server-side handlers for create / edit / delete.
  - Create -> POST with action=create
  - Edit   -> POST with action=edit
  - Delete -> POST with action=delete (kept compatibility with existing delete_nis)
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CREATE
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $nis = trim($_POST['nis'] ?? '');
        $nama = trim($_POST['nama_siswa'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $jurusan = trim($_POST['jurusan'] ?? '');

        if ($nis === '' || $nama === '') {
            header("Location: dashboard.php?msg=error&reason=missing");
            exit;
        }

        // Cek duplikat nis
        $chk = $conn->prepare("SELECT 1 FROM siswa WHERE nis = ? LIMIT 1");
        $chk->bind_param("s", $nis);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            header("Location: dashboard.php?msg=error&reason=exists");
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO siswa (nis, nama_siswa, kelas, jurusan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nis, $nama, $kelas, $jurusan);
        $ok = $stmt->execute();
        header("Location: dashboard.php?msg=" . ($ok ? "created" : "error"));
        exit;
    }

    // EDIT
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $original_nis = trim($_POST['original_nis'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $nama = trim($_POST['nama_siswa'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $jurusan = trim($_POST['jurusan'] ?? '');

        if ($original_nis === '' || $nis === '' || $nama === '') {
            header("Location: dashboard.php?msg=error&reason=missing");
            exit;
        }

        // Jika nis diubah, pastikan tidak menabrak nis lain
        if ($nis !== $original_nis) {
            $chk = $conn->prepare("SELECT 1 FROM siswa WHERE nis = ? LIMIT 1");
            $chk->bind_param("s", $nis);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                header("Location: dashboard.php?msg=error&reason=exists");
                exit;
            }
        }

        $stmt = $conn->prepare("UPDATE siswa SET nis = ?, nama_siswa = ?, kelas = ?, jurusan = ? WHERE nis = ?");
        $stmt->bind_param("sssss", $nis, $nama, $kelas, $jurusan, $original_nis);
        $ok = $stmt->execute();
        header("Location: dashboard.php?msg=" . ($ok ? "updated" : "error"));
        exit;
    }

    // DELETE (support both old delete_nis and new action=delete)
    if ((isset($_POST['delete_nis']) && $_POST['delete_nis'] !== '') || (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['nis']))) {
        $nisToDelete = $_POST['delete_nis'] ?? $_POST['nis'];
        $stmt = $conn->prepare("DELETE FROM siswa WHERE nis = ?");
        $stmt->bind_param("s", $nisToDelete);
        $stmt->execute();
        header("Location: dashboard.php?msg=deleted");
        exit;
    }
}

// ...existing code...
// Baca data
$siswa = [];
$totalSiswa = 0;
$q = $_GET['q'] ?? '';

if (!empty($q)) {
    $stmt = $conn->prepare("SELECT * FROM siswa WHERE nis LIKE ? OR nama_siswa LIKE ? ORDER BY nis ASC");
    $like = "%$q%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM siswa ORDER BY nis ASC");
}

while ($row = $result->fetch_assoc()) {
    $siswa[] = $row;
}

$totalSiswa = (int) ($conn->query("SELECT COUNT(*) AS c FROM siswa")->fetch_assoc()['c'] ?? 0);

// optional: coba ambil total guru jika tabel ada
$totalGuru = 0;
$check = $conn->query("SHOW TABLES LIKE 'guru'");
if ($check && $check->num_rows > 0) {
    $totalGuru = (int) ($conn->query("SELECT COUNT(*) AS c FROM guru")->fetch_assoc()['c'] ?? 0);
}

$todayActivity = 0; // placeholder, sesuaikan dengan sumber data aktivitas bila ada

function h($s){ return htmlspecialchars($s ?: '', ENT_QUOTES, 'UTF-8'); }

// helper untuk menu aktif
$uri = $_SERVER['REQUEST_URI'];
$script = basename($_SERVER['SCRIPT_NAME']);
$baseClasses = 'w-full inline-flex items-center justify-center md:justify-start gap-3 px-4 py-3 rounded-xl font-semibold transition transform hover:scale-105 shadow-sm';
$activeClasses = 'bg-gradient-to-r from-cyan-500 to-violet-600 text-black';
$inactiveClasses = 'bg-slate-800/30 text-slate-100';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Data Siswa | SMK TI Bali Global Denpasar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <style>
        :root{
            --accent:#06b6d4;
            --accent-2:#7c3aed;
            --panel:#0b1220;
        }
        .glass {
            background: linear-gradient(135deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,0.04);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-950 to-black text-slate-100 font-sans">

<div class="min-h-screen flex">

    <!-- SIDEBAR -->
    <aside class="w-20 md:w-64 bg-gradient-to-b from-[#071227] to-[#07172b] border-r border-slate-800 p-4 flex flex-col">
        <div class="flex items-center gap-3 mb-6">
            <div class="hidden md:block">
                <h1 class="text-lg font-semibold tracking-wide text-cyan-300">SMK TI Bali Global</h1>
                <p class="text-xs text-slate-400">Admin Panel</p>
            </div>
        </div>

        <nav class="flex-1">
            <ul class="space-y-3">
                <li>
                    <a href="../dashboard.php" class="<?php echo $baseClasses . ' ' . ($script === '/dashboard.php' ? $activeClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Dashboard</span>
                        <span class="md:hidden">🏠</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/siswa') !== false ? $activeClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Data Siswa</span>
                        <span class="md:hidden">👨‍🎓</span>
                    </a>
                </li>
                <li>
                    <a href="../guruCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/guru') !== false ? $activeClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Data Guru</span>
                        <span class="md:hidden">👩‍🏫</span>
                    </a>
                </li>
                <li>
                    <a href="../jurusanCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/jurusan') !== false ? $activeClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Jurusan</span>
                        <span class="md:hidden">📚</span>
                    </a>
                </li>
                <li>
                    <a href="../mapelCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/mapel') !== false ? $activeClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Mata Pelajaran</span>
                        <span class="md:hidden">📖</span>
                    </a>
                </li>
                <li>
                    <a href="../ekstraCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/ekstra') !== false ? $activeClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Ekstrakurikuler</span>
                        <span class="md:hidden">🎯</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="mt-4">
            <a href="../config/logoutSistem.php" class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-violet-600 text-black font-semibold hover:scale-105 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1"/></svg>
                <span class="hidden md:inline">Logout</span>
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 p-6 md:p-10">
        <!-- Topbar -->
        <header class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-cyan-300">Data Siswa</h2>
                <p class="text-sm text-slate-400">Selamat datang, <span class="font-semibold text-white"><?php echo h($_SESSION['username'] ?? ''); ?></span></p>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-3 px-4 py-2 rounded-lg bg-slate-800/50 border border-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-cyan-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a1 1 0 001 1h3m10-12v10a1 1 0 01-1 1h-3M7 9h10"/></svg>
                    <div class="text-right">
                        <div class="text-xs text-slate-400">Role</div>
                        <div class="text-sm font-semibold text-white"><?php echo h($_SESSION['role'] ?? ''); ?></div>
                    </div>
                </div>
                <a href="../config/logoutSistem.php" class="md:hidden inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-violet-600 text-black font-semibold">
                    Logout
                </a>
            </div>
        </header>

        <!-- Controls: Tambah + Pencarian -->
        <div class="flex flex-col sm:flex-row justify-between mb-6 gap-4">
            <button id="openCreate" class="bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2 rounded-lg font-semibold text-black hover:opacity-90 transition">
                + Tambah Siswa
            </button>

            <form method="get" class="flex gap-2">
                <input type="search" name="q" placeholder="Cari..."
                    value="<?php echo h($q); ?>"
                    class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none text-white">
                <button class="bg-slate-700 px-3 py-2 rounded-lg hover:bg-slate-600 transition">Cari</button>
            </form>
        </div>

        <p class="text-sm text-slate-400 mb-4">Total Siswa: <span class="text-white font-semibold"><?php echo $totalSiswa; ?></span></p>

        <!-- Notifikasi popup (auto-dismiss) -->
        <?php if (!empty($_GET['msg'])): 
            $m = $_GET['msg'];
            $reason = $_GET['reason'] ?? '';
            $bgClass = 'bg-yellow-600';
            $text = 'Terjadi kesalahan.';

            if ($m === 'deleted') {
            $bgClass = 'bg-red-600';
            $text = 'Data siswa berhasil dihapus.';
            } elseif ($m === 'created') {
            $bgClass = 'bg-green-600';
            $text = 'Data siswa berhasil ditambahkan.';
            } elseif ($m === 'updated') {
            $bgClass = 'bg-orange-600';
            $text = 'Data siswa berhasil diupdate.';
            } else {
            if ($reason === 'exists') $text = 'NIS sudah terdaftar.';
            elseif ($reason === 'missing') $text = 'Isi NIS dan nama minimal.';
            else $text = 'Terjadi kesalahan.';
            }
        ?>
            <div id="flashNotif" role="status" aria-live="polite"
                class="fixed top-6 right-6 z-50 max-w-sm w-full rounded-lg shadow-lg text-white <?php echo $bgClass; ?> transform translate-y-2 opacity-0 transition-all duration-300">
            <div class="px-4 py-3 flex items-start gap-3">
                <div class="flex-1 text-sm"><?php echo h($text); ?></div>
                <button id="flashClose" class="ml-2 text-white/80 hover:text-white text-lg leading-none" aria-label="Tutup">&times;</button>
            </div>
            </div>

            <script>
            (function(){
                var ANIM_MS = 300;
                var SHOW_MS = 4000;
                var n = document.getElementById('flashNotif');
                if (!n) return;

                // show (next frame to allow transition)
                requestAnimationFrame(function(){
                n.classList.remove('translate-y-2','opacity-0');
                n.classList.add('translate-y-0','opacity-100');
                });

                var hideTimeout = setTimeout(hide, SHOW_MS);

                document.getElementById('flashClose').addEventListener('click', function(){
                clearTimeout(hideTimeout);
                hide();
                });

                function hide(){
                if (!n) return;
                n.classList.add('translate-y-2','opacity-0');
                n.classList.remove('translate-y-0','opacity-100');
                setTimeout(function(){
                    if (n && n.parentNode) n.parentNode.removeChild(n);
                }, ANIM_MS + 50);
                }

                // optional: dismiss on Escape
                document.addEventListener('keydown', function(e){
                if (e.key === 'Escape') {
                    clearTimeout(hideTimeout);
                    hide();
                }
                });
            })();
            </script>
        <?php endif; ?>

        <!-- Tabel dalam card -->
        <div class="glass p-4 rounded-2xl">
            <div class="overflow-x-auto">
                <table class="min-w-full border border-slate-700 rounded-lg">
                    <thead class="bg-slate-800 text-slate-300">
                        <tr>
                            <!-- <th class="px-4 py-2 text-left">#</th> -->
                            <th class="px-4 py-2 text-left">NIS</th>
                            <th class="px-4 py-2 text-left">Nama Siswa</th>
                            <th class="px-4 py-2 text-left">Kelas</th>
                            <th class="px-4 py-2 text-left">Jurusan</th>
                            <th class="px-4 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($siswa)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-400">Belum ada data siswa.</td>
                            </tr>
                        <?php else: foreach ($siswa as $i => $row): ?>
                            <tr class="border-t border-slate-700 hover:bg-slate-800 transition">
                                <!-- <td class="px-4 py-2"><?php echo $i + 1; ?></td> -->
                                <td class="px-4 py-2"><?php echo h($row['nis']); ?></td>
                                <td class="px-4 py-2"><?php echo h($row['nama_siswa']); ?></td>
                                <td class="px-4 py-2"><?php echo h($row['kelas']); ?></td>
                                <td class="px-4 py-2"><?php echo h($row['jurusan']); ?></td>
                                <td class="px-4 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            class="p-2 rounded bg-yellow-400 text-black hover:opacity-90 inline-flex items-center openEditBtn"
                                            title="Edit"
                                            data-nis="<?php echo h($row['nis']); ?>"
                                            data-nama="<?php echo h($row['nama_siswa']); ?>"
                                            data-kelas="<?php echo h($row['kelas']); ?>"
                                            data-jurusan="<?php echo h($row['jurusan']); ?>">
                                            <i class='bxr bx-pencil'></i>
                                            <span class="sr-only">Edit</span>
                                        </button>

                                        <button
                                            class="p-2 rounded bg-red-600 text-white hover:opacity-90 inline-flex items-center openDeleteBtn"
                                            title="Hapus"
                                            data-nis="<?php echo h($row['nis']); ?>">
                                            <i class='bxr bx-trash'></i>
                                            <span class="sr-only">Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-12 text-center text-slate-500">
            © <?php echo date('Y'); ?> SMK TI Bali Global Denpasar — Admin Panel
        </footer>
    </main>
</div>

<!-- MODALS -->
<!-- Create Modal -->
<div id="modalCreate" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md bg-slate-900 rounded-xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Tambah Siswa</h3>
            <button class="closeModal text-slate-400 hover:text-white" data-target="modalCreate">✕</button>
        </div>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="text-sm">NIS</label>
                <input name="nis" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm">Nama Siswa</label>
                <input name="nama_siswa" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm">Kelas</label>
                <input name="kelas" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm">Jurusan</label>
                <input name="jurusan" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div class="flex justify-end gap-2 mt-3">
                <button type="button" class="px-4 py-2 rounded bg-slate-700 closeModal" data-target="modalCreate">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-cyan-500 text-black font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="modalEdit" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md bg-slate-900 rounded-xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Edit Siswa</h3>
            <button class="closeModal text-slate-400 hover:text-white" data-target="modalEdit">✕</button>
        </div>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="original_nis" id="edit_original_nis">
            <div>
                <label class="text-sm">NIS</label>
                <input id="edit_nis" name="nis" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm">Nama Siswa</label>
                <input id="edit_nama" name="nama_siswa" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm">Kelas</label>
                <input id="edit_kelas" name="kelas" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm">Jurusan</label>
                <input id="edit_jurusan" name="jurusan" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div class="flex justify-end gap-2 mt-3">
                <button type="button" class="px-4 py-2 rounded bg-slate-700 closeModal" data-target="modalEdit">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-yellow-400 text-black font-semibold">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="modalDelete" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-sm bg-slate-900 rounded-xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Hapus Siswa</h3>
            <button class="closeModal text-slate-400 hover:text-white" data-target="modalDelete">✕</button>
        </div>
        <p class="text-sm text-slate-300 mb-4">Yakin ingin menghapus data siswa dengan NIS <span id="delete_nis_text" class="font-semibold"></span> ?</p>
        <form method="post" id="deleteForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="nis" id="delete_nis_input">
            <div class="flex justify-end gap-2">
                <button type="button" class="px-4 py-2 rounded bg-slate-700 closeModal" data-target="modalDelete">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white font-semibold">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Utility to open/close modal
    function openModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.remove('hidden');
            m.classList.add('flex');
        }
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.remove('flex');
            m.classList.add('hidden');
        }
    }

    // Attach close buttons
    document.querySelectorAll('.closeModal').forEach(btn=>{
        btn.addEventListener('click', e=>{
            const target = btn.getAttribute('data-target');
            if (target) closeModal(target);
        });
    });

    // Open create
    document.getElementById('openCreate').addEventListener('click', ()=>{
        openModal('modalCreate');
    });

    // Edit buttons
    document.querySelectorAll('.openEditBtn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const nis = btn.getAttribute('data-nis') || '';
            const nama = btn.getAttribute('data-nama') || '';
            const kelas = btn.getAttribute('data-kelas') || '';
            const jurusan = btn.getAttribute('data-jurusan') || '';

            document.getElementById('edit_original_nis').value = nis;
            document.getElementById('edit_nis').value = nis;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kelas').value = kelas;
            document.getElementById('edit_jurusan').value = jurusan;

            openModal('modalEdit');
        });
    });

    // Delete buttons
    document.querySelectorAll('.openDeleteBtn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
            const nis = btn.getAttribute('data-nis') || '';
            document.getElementById('delete_nis_text').textContent = nis;
            document.getElementById('delete_nis_input').value = nis;
            openModal('modalDelete');
        });
    });

    // Close modals on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="modal"]').forEach(m=>{
                if (!m.classList.contains('hidden')) closeModal(m.id);
            });
        }
    });

    // Click outside modal content to close
    document.querySelectorAll('[id^="modal"]').forEach(modal=>{
        modal.addEventListener('click', (e)=>{
            if (e.target === modal) closeModal(modal.id);
        });
    });
</script>

</body>
</html>
// ...existing code...