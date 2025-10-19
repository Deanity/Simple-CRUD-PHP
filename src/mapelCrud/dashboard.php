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

// Helper
function h($s){ return htmlspecialchars($s ?: '', ENT_QUOTES, 'UTF-8'); }
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// --- API: handle create / edit / delete (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $nama = trim($_POST['nama_mapel'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $guru = trim($_POST['guru_pengajar'] ?? '');
        if ($nama === '') {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'msg'=>'Nama mata pelajaran wajib']); exit; }
            header('Location: dashboard.php?msg=error');
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO mapel (nama_mapel, kelas, guru_pengajar) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $kelas, $guru);
        $ok = $stmt->execute();
        $newId = $stmt->insert_id;
        if ($isAjax) {
            header('Content-Type: application/json');
            if ($ok) {
                echo json_encode(['success'=>true,'msg'=>'created','row'=>['id'=>$newId,'nama_mapel'=>$nama,'kelas'=>$kelas,'guru_pengajar'=>$guru]]);
            } else {
                echo json_encode(['success'=>false,'msg'=>'DB error']);
            }
            exit;
        } else {
            header("Location: dashboard.php?msg=" . ($ok ? "created" : "error"));
            exit;
        }
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_mapel'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $guru = trim($_POST['guru_pengajar'] ?? '');
        if ($id <= 0 || $nama === '') {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'msg'=>'Invalid input']); exit; }
            header('Location: dashboard.php?msg=error');
            exit;
        }
        $stmt = $conn->prepare("UPDATE mapel SET nama_mapel = ?, kelas = ?, guru_pengajar = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nama, $kelas, $guru, $id);
        $ok = $stmt->execute();
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>$ok,'msg'=>$ok?'updated':'error','row'=>['id'=>$id,'nama_mapel'=>$nama,'kelas'=>$kelas,'guru_pengajar'=>$guru]]);
            exit;
        } else {
            header("Location: dashboard.php?msg=" . ($ok ? "updated" : "error"));
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['delete_id'] ?? 0);
        if ($id <= 0) {
            if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'msg'=>'Invalid id']); exit; }
            header('Location: dashboard.php?msg=error');
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM mapel WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success'=>$ok,'msg'=>$ok?'deleted':'error','id'=>$id]);
            exit;
        } else {
            header("Location: dashboard.php?msg=" . ($ok ? "deleted" : "error"));
            exit;
        }
    }
}

// --- Read data for initial render ---
$mapel = [];
$totalMapel = 0;
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = $conn->prepare("SELECT * FROM mapel WHERE CONCAT(id, ' ', IFNULL(nama_mapel,''), ' ', IFNULL(kelas,''), ' ', IFNULL(guru_pengajar,'')) LIKE ? ORDER BY id ASC");
    $like = "%$q%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM mapel ORDER BY id ASC");
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $mapel[] = $row;
    }
}

$totalMapel = (int) ($conn->query("SELECT COUNT(*) AS c FROM mapel")->fetch_assoc()['c'] ?? 0);

// optional: ambil total guru jika tabel ada
$totalGuru = 0;
$check = $conn->query("SHOW TABLES LIKE 'guru'");
if ($check && $check->num_rows > 0) {
    $totalGuru = (int) ($conn->query("SELECT COUNT(*) AS c FROM guru")->fetch_assoc()['c'] ?? 0);
}

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
    <title>Mata Pelajaran | SMK TI Bali Global Denpasar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <style>
        :root{ --accent:#06b6d4; --accent-2:#7c3aed; --panel:#0b1220; }
        .glass { background: linear-gradient(135deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.04); }
        /* simple modal centering for tailwind */
        .modal-backdrop { background: rgba(2,6,23,0.7); }
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
                    <a href="../jurusanCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/jurusan') !== false ? $activeClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Jurusan</span>
                        <span class="md:hidden">📚</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/mapel') !== false ? $activeClasses : $inactiveClasses); ?>">
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
                <h2 class="text-3xl font-bold text-cyan-300">Mata Pelajaran</h2>
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
            <button id="btnOpenCreate" class="bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2 rounded-lg font-semibold text-black hover:opacity-90 transition">
                + Tambah Mata Pelajaran
            </button>

            <form method="get" class="flex gap-2">
                <input type="search" name="q" placeholder="Cari..." 
                    value="<?php echo h($q); ?>"
                    class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none text-white">
                <button class="bg-slate-700 px-3 py-2 rounded-lg hover:bg-slate-600 transition">Cari</button>
            </form>
        </div>

        <p class="text-sm text-slate-400 mb-4">Total Mata Pelajaran: <span class="text-white font-semibold" id="totalMapel"><?php echo $totalMapel; ?></span></p>

        <!-- Notifikasi popup (auto-dismiss) -->
        <?php if (!empty($_GET['msg'])):
            $m = $_GET['msg'];
            $reason = $_GET['reason'] ?? '';
            $bgClass = 'bg-yellow-600';
            $text = 'Terjadi kesalahan.';

            if ($m === 'deleted') {
                $bgClass = 'bg-red-600';
                $text = 'Data mata pelajaran berhasil dihapus.';
            } elseif ($m === 'created') {
                $bgClass = 'bg-green-600';
                $text = 'Data mata pelajaran berhasil ditambahkan.';
            } elseif ($m === 'updated') {
                $bgClass = 'bg-orange-600';
                $text = 'Data mata pelajaran berhasil diperbarui.';
            } else {
                if ($reason === 'exists') $text = 'Data sudah ada.';
                elseif ($reason === 'missing') $text = 'Isi semua kolom wajib.';
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
                <table class="min-w-full border border-slate-700 rounded-lg" id="tblMapel">
                    <thead class="bg-slate-800 text-slate-300">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Nama Mata Pelajaran</th>
                            <th class="px-4 py-2 text-left">Kelas</th>
                            <th class="px-4 py-2 text-left">Guru Pengajar</th>
                            <th class="px-4 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mapel)): ?>
                            <tr id="noDataRow">
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400">Belum ada data mata pelajaran.</td>
                            </tr>
                        <?php else: foreach ($mapel as $i => $row): ?>
                            <tr id="row-<?php echo h($row['id']); ?>" class="border-t border-slate-700 hover:bg-slate-800 transition"
                                data-id="<?php echo h($row['id']); ?>"
                                data-nama="<?php echo h($row['nama_mapel']); ?>"
                                data-kelas="<?php echo h($row['kelas']); ?>"
                                data-guru="<?php echo h($row['guru_pengajar']); ?>">
                                <td class="px-4 py-2"><?php echo h($row['id'] ?? ''); ?></td>
                                <td class="px-4 py-2"><?php echo h($row['nama_mapel'] ?? ''); ?></td>
                                <td class="px-4 py-2"><?php echo h($row['kelas'] ?? '-'); ?></td>
                                <td class="px-4 py-2"><?php echo h($row['guru_pengajar'] ?? '-'); ?></td>
                                <td class="px-4 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button data-action="edit" class="p-2 rounded bg-yellow-400 text-black hover:opacity-90 inline-flex items-center open-edit" title="Edit">
                                            <i class='bxr bx-pencil'></i><span class="sr-only">Edit</span>
                                        </button>

                                        <button data-action="delete" class="p-2 rounded bg-red-600 text-white hover:opacity-90 inline-flex items-center open-delete" title="Hapus">
                                            <i class='bxr bx-trash'></i><span class="sr-only">Hapus</span>
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

<!-- Modal: Create/Edit -->
<div id="modalForm" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="modal-backdrop absolute inset-0"></div>
    <div class="relative bg-slate-900 w-full max-w-xl rounded-xl p-6 glass z-10">
        <h3 id="modalTitle" class="text-xl font-semibold mb-4">Tambah Mata Pelajaran</h3>
        <form id="formModal" method="POST" action="dashboard.php">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="formId" value="">
            <div class="space-y-3">
                <label class="block">
                    <div class="text-sm text-slate-300">Nama Mata Pelajaran</div>
                    <input type="text" name="nama_mapel" id="formNama"
                        class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white" required>
                </label>
                <label class="block">
                    <div class="text-sm text-slate-300">Kelas</div>
                    <input type="text" name="kelas" id="formKelas"
                        class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white">
                </label>
                <label class="block">
                    <div class="text-sm text-slate-300">Guru Pengajar</div>
                    <input type="text" name="guru_pengajar" id="formGuru"
                        class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white">
                </label>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <button type="button" id="btnCloseForm" class="px-4 py-2 rounded bg-slate-700">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-cyan-500 text-black font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete -->
<div id="modalDelete" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="modal-backdrop absolute inset-0"></div>
    <div class="relative bg-slate-900 w-full max-w-md rounded-xl p-6 glass z-10">
        <h3 class="text-xl font-semibold mb-3">Hapus Mata Pelajaran</h3>
        <p class="text-sm text-slate-300 mb-4">Yakin ingin menghapus data ini?</p>
        <form id="formDelete" method="POST" action="dashboard.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="delete_id" id="deleteId" value="">
            <div class="flex justify-end gap-2">
                <button type="button" id="btnCloseDelete" class="px-4 py-2 rounded bg-slate-700">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white font-semibold">Hapus</button>
            </div>
        </form>
    </div>
</div>


<script>
// Utility helpers
const $ = sel => document.querySelector(sel);
const $$ = sel => Array.from(document.querySelectorAll(sel));

// Modal controls
const modalForm = $('#modalForm');
const modalDelete = $('#modalDelete');
const btnOpenCreate = $('#btnOpenCreate');
const btnCloseForm = $('#btnCloseForm');
const btnCloseDelete = $('#btnCloseDelete');

btnOpenCreate.addEventListener('click', () => {
    $('#modalTitle').textContent = 'Tambah Mata Pelajaran';
    $('#formAction').value = 'create';
    $('#formId').value = '';
    $('#formNama').value = '';
    $('#formKelas').value = '';
    $('#formGuru').value = '';
    modalForm.classList.remove('hidden');
    modalForm.classList.add('flex');
});

btnCloseForm.addEventListener('click', () => {
    modalForm.classList.add('hidden');
    modalForm.classList.remove('flex');
});

btnCloseDelete.addEventListener('click', () => {
    modalDelete.classList.add('hidden');
    modalDelete.classList.remove('flex');
});

// Open edit or delete from table buttons
document.addEventListener('click', (ev) => {
    const el = ev.target.closest('[data-action]');
    if (!el) return;
    const action = el.getAttribute('data-action');
    const tr = el.closest('tr');
    if (!tr) return;
    const id = tr.dataset.id;
    const nama = tr.dataset.nama || '';
    const kelas = tr.dataset.kelas || '';
    const guru = tr.dataset.guru || '';

    if (action === 'edit') {
        $('#modalTitle').textContent = 'Edit Mata Pelajaran';
        $('#formAction').value = 'edit';
        $('#formId').value = id;
        $('#formNama').value = nama;
        $('#formKelas').value = kelas;
        $('#formGuru').value = guru;
        modalForm.classList.remove('hidden');
        modalForm.classList.add('flex');
    }

    if (action === 'delete') {
        $('#deleteId').value = id;
        modalDelete.classList.remove('hidden');
        modalDelete.classList.add('flex');
    }
});

// small helper to escape HTML in JS
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

</body>
</html>