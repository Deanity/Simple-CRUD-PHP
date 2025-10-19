<?php
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

function h($s){ return htmlspecialchars($s ?: '', ENT_QUOTES, 'UTF-8'); }

// HANDLE POST: create / edit / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $kode = trim((string)($_POST['kode_jurusan'] ?? ''));
        $nama = trim((string)($_POST['nama_jurusan'] ?? ''));
        $ket  = trim((string)($_POST['keterangan'] ?? ''));

        if ($kode === '' || $nama === '') {
            header("Location: dashboard.php?msg=error&err=missing");
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO jurusan (kode_jurusan, nama_jurusan, keterangan) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $kode, $nama, $ket);
        if ($stmt->execute()) {
            header("Location: dashboard.php?msg=created");
            exit;
        } else {
            if ($conn->errno === 1062) {
                header("Location: dashboard.php?msg=error&err=duplicate");
                exit;
            }
            header("Location: dashboard.php?msg=error&err=insert");
            exit;
        }
    }

    if ($action === 'edit') {
        $orig = trim((string)($_POST['orig_kode'] ?? ''));
        $kode = trim((string)($_POST['kode_jurusan'] ?? ''));
        $nama = trim((string)($_POST['nama_jurusan'] ?? ''));
        $ket  = trim((string)($_POST['keterangan'] ?? ''));

        if ($orig === '' || $kode === '' || $nama === '') {
            header("Location: dashboard.php?msg=error&err=missing");
            exit;
        }

        $stmt = $conn->prepare("UPDATE jurusan SET kode_jurusan = ?, nama_jurusan = ?, keterangan = ? WHERE kode_jurusan = ?");
        $stmt->bind_param("ssss", $kode, $nama, $ket, $orig);
        if ($stmt->execute()) {
            // jika tidak ada row affected tetap treat as updated (no change)
            header("Location: dashboard.php?msg=updated");
            exit;
        } else {
            if ($conn->errno === 1062) {
                header("Location: dashboard.php?msg=error&err=duplicate");
                exit;
            }
            header("Location: dashboard.php?msg=error&err=update");
            exit;
        }
    }

    if ($action === 'delete') {
        $kode = trim((string)($_POST['delete_kode'] ?? ''));
        if ($kode === '') {
            header("Location: dashboard.php?msg=error&err=missing");
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM jurusan WHERE kode_jurusan = ?");
        $stmt->bind_param("s", $kode);
        if ($stmt->execute()) {
            header("Location: dashboard.php?msg=deleted");
            exit;
        } else {
            header("Location: dashboard.php?msg=error&err=delete");
            exit;
        }
    }
}

// Baca data
$jurusan = [];
$totalJurusan = 0;
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = $conn->prepare("SELECT * FROM jurusan WHERE CONCAT(IFNULL(kode_jurusan,''), ' ', IFNULL(nama_jurusan,''), ' ', IFNULL(keterangan,'')) LIKE ? ORDER BY kode_jurusan ASC");
    $like = "%$q%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM jurusan ORDER BY kode_jurusan ASC");
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $jurusan[] = $row;
    }
}

$totalJurusan = (int) ($conn->query("SELECT COUNT(*) AS c FROM jurusan")->fetch_assoc()['c'] ?? 0);

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
    <title>Jurusan | SMK TI Bali Global Denpasar</title>
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
        .modal-backdrop { background: rgba(0,0,0,0.6); }
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
                    <a href="../siswaCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/siswa') !== false ? $activeClasses : $inactiveClasses); ?>">
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
                    <a href="dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/jurusan') !== false ? $activeClasses : $inactiveClasses); ?>">
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
                <h2 class="text-3xl font-bold text-cyan-300">Jurusan</h2>
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
            <button onclick="openCreateModal()" class="bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2 rounded-lg font-semibold text-black hover:opacity-90 transition">
                + Tambah Jurusan
            </button>

            <form method="get" class="flex gap-2">
                <input type="search" name="q" placeholder="Cari..." 
                    value="<?php echo h($q); ?>"
                    class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none text-white">
                <button class="bg-slate-700 px-3 py-2 rounded-lg hover:bg-slate-600 transition">Cari</button>
            </form>
        </div>

        <p class="text-sm text-slate-400 mb-4">Total Jurusan: <span class="text-white font-semibold"><?php echo $totalJurusan; ?></span></p>

        <!-- Notifikasi popup (auto-dismiss) -->
        <?php if (!empty($_GET['msg'])):
            $m = $_GET['msg'];
            $reason = $_GET['reason'] ?? '';
            $bgClass = 'bg-yellow-600';
            $text = 'Terjadi kesalahan.';

            if ($m === 'deleted') {
            $bgClass = 'bg-red-600';
            $text = 'Data guru berhasil dihapus.';
            } elseif ($m === 'created') {
            $bgClass = 'bg-green-600';
            $text = 'Data guru berhasil ditambahkan.';
            } elseif ($m === 'updated') {
            $bgClass = 'bg-orange-600';
            $text = 'Data guru berhasil diupdate.';
            } else {
            if ($reason === 'exists') $text = 'NIP/NIK sudah terdaftar.';
            elseif ($reason === 'missing') $text = 'Isi NIP dan nama minimal.';
            else $text = 'Terjadi kesalahan.';
            }
        ?>
            <div id="toastMsg" class="fixed right-6 top-6 z-50 px-4 py-2 rounded-md text-white <?php echo $bgClass; ?> shadow-lg">
            <?php echo h($text); ?>
            </div>
            <script>
            setTimeout(function(){
                var el = document.getElementById('toastMsg');
                if(!el) return;
                el.style.transition = 'opacity 0.35s ease';
                el.style.opacity = '0';
                setTimeout(function(){ el.remove(); }, 400);
            }, 3500);
            </script>
        <?php endif; ?>

        <!-- Tabel dalam card -->
        <div class="glass p-4 rounded-2xl">
            <div class="overflow-x-auto">
                <table class="min-w-full border border-slate-700 rounded-lg">
                    <thead class="bg-slate-800 text-slate-300">
                        <tr>
                            <th class="px-4 py-2 text-left">Kode Jurusan</th>
                            <th class="px-4 py-2 text-left">Nama Jurusan</th>
                            <th class="px-4 py-2 text-left">Keterangan</th>
                            <th class="px-4 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jurusan)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400">Belum ada data jurusan.</td>
                            </tr>
                        <?php else: foreach ($jurusan as $i => $row): ?>
                            <tr class="border-t border-slate-700 hover:bg-slate-800 transition">
                                <td class="px-4 py-2"><?php echo h($row['kode_jurusan'] ?? ''); ?></td>
                                <td class="px-4 py-2"><?php echo h($row['nama_jurusan'] ?? ''); ?></td>
                                <td class="px-4 py-2"><?php echo h($row['keterangan'] ?? '-'); ?></td>
                                <td class="px-4 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button
                                           class="p-2 rounded bg-yellow-400 text-black hover:opacity-90 inline-flex items-center"
                                           title="Edit"
                                           onclick="openEditModal('<?php echo h($row['kode_jurusan']); ?>', '<?php echo h(addslashes($row['nama_jurusan'])); ?>', '<?php echo h(addslashes($row['keterangan'])); ?>')">
                                            <i class='bxr bx-pencil'></i>
                                            <span class="sr-only">Edit</span>
                                        </button>

                                        <button
                                            class="p-2 rounded bg-red-600 text-white hover:opacity-90 inline-flex items-center"
                                            title="Hapus"
                                            onclick="openDeleteModal('<?php echo h($row['kode_jurusan']); ?>')">
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

<!-- MODAL: CREATE -->
<div id="modal-create" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="absolute inset-0 modal-backdrop"></div>
    <div class="relative bg-slate-900 rounded-xl p-6 w-full max-w-lg glass">
        <h3 class="text-xl font-semibold mb-4">Tambah Jurusan</h3>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="text-sm text-slate-300">Kode Jurusan</label>
                <input name="kode_jurusan" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700">
            </div>
            <div>
                <label class="text-sm text-slate-300">Nama Jurusan</label>
                <input name="nama_jurusan" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700">
            </div>
            <div>
                <label class="text-sm text-slate-300">Keterangan</label>
                <textarea name="keterangan" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 rounded bg-slate-700">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-green-600 text-black font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT -->
<div id="modal-edit" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="absolute inset-0 modal-backdrop"></div>
    <div class="relative bg-slate-900 rounded-xl p-6 w-full max-w-lg glass">
        <h3 class="text-xl font-semibold mb-4">Edit Jurusan</h3>
        <form method="post" class="space-y-3">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="orig_kode" id="edit-orig-kode">
            <div>
                <label class="text-sm text-slate-300">Kode Jurusan</label>
                <input name="kode_jurusan" id="edit-kode" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700">
            </div>
            <div>
                <label class="text-sm text-slate-300">Nama Jurusan</label>
                <input name="nama_jurusan" id="edit-nama" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700">
            </div>
            <div>
                <label class="text-sm text-slate-300">Keterangan</label>
                <textarea name="keterangan" id="edit-ket" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded bg-slate-700">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-yellow-400 text-black font-semibold">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: DELETE -->
<div id="modal-delete" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="absolute inset-0 modal-backdrop"></div>
    <div class="relative bg-slate-900 rounded-xl p-6 w-full max-w-md glass">
        <h3 class="text-xl font-semibold mb-4 text-red-400">Hapus Jurusan</h3>
        <p class="mb-4">Yakin ingin menghapus jurusan dengan kode: <span id="delete-kode-text" class="font-semibold"></span> ?</p>
        <form method="post" class="flex justify-end gap-2">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="delete_kode" id="delete-kode-input">
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded bg-slate-700">Batal</button>
            <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white font-semibold">Hapus</button>
        </form>
    </div>
</div>

<script>
    // Modal controls
    function openCreateModal(){
        document.getElementById('modal-create').classList.remove('hidden');
        document.getElementById('modal-create').classList.add('flex');
    }
    function closeCreateModal(){
        document.getElementById('modal-create').classList.add('hidden');
        document.getElementById('modal-create').classList.remove('flex');
    }

    function openEditModal(kode, nama, ket){
        document.getElementById('edit-orig-kode').value = kode;
        document.getElementById('edit-kode').value = kode;
        document.getElementById('edit-nama').value = nama;
        document.getElementById('edit-ket').value = ket;
        document.getElementById('modal-edit').classList.remove('hidden');
        document.getElementById('modal-edit').classList.add('flex');
    }
    function closeEditModal(){
        document.getElementById('modal-edit').classList.add('hidden');
        document.getElementById('modal-edit').classList.remove('flex');
    }

    function openDeleteModal(kode){
        document.getElementById('delete-kode-text').textContent = kode;
        document.getElementById('delete-kode-input').value = kode;
        document.getElementById('modal-delete').classList.remove('hidden');
        document.getElementById('modal-delete').classList.add('flex');
    }
    function closeDeleteModal(){
        document.getElementById('modal-delete').classList.add('hidden');
        document.getElementById('modal-delete').classList.remove('flex');
    }

    // close modal on backdrop click
    document.querySelectorAll('[id^="modal-"]').forEach(modal=>{
        modal.addEventListener('click', function(e){
            if (e.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
    });
</script>

</body>
</html>