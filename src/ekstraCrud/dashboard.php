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

function respond_json($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// API: handle AJAX create / edit / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $nama = trim($_POST['nama_ekstra'] ?? '');
        $jadwal = trim($_POST['jadwal'] ?? '');
        $guru = trim($_POST['guru_ekstra'] ?? '');

        if ($nama === '') {
            respond_json(['success' => false, 'message' => 'Nama ekstra wajib diisi.']);
        }

        $stmt = $conn->prepare("INSERT INTO ekstra (nama_ekstra, jadwal, guru_ekstra) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $jadwal, $guru);
        if (!$stmt->execute()) {
            respond_json(['success' => false, 'message' => 'Gagal menyimpan data.']);
        }
        $id = $conn->insert_id;
        $res = $conn->prepare("SELECT * FROM ekstra WHERE id = ?");
        $res->bind_param("i", $id);
        $res->execute();
        $row = $res->get_result()->fetch_assoc();
        respond_json(['success' => true, 'message' => 'Berhasil dibuat.', 'row' => $row]);
    }

    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($_POST['nama_ekstra'] ?? '');
        $jadwal = trim($_POST['jadwal'] ?? '');
        $guru = trim($_POST['guru_ekstra'] ?? '');

        if ($id <= 0 || $nama === '') {
            respond_json(['success' => false, 'message' => 'Data tidak valid.']);
        }

        $stmt = $conn->prepare("UPDATE ekstra SET nama_ekstra = ?, jadwal = ?, guru_ekstra = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nama, $jadwal, $guru, $id);
        if (!$stmt->execute()) {
            respond_json(['success' => false, 'message' => 'Gagal memperbarui data.']);
        }  
        $res = $conn->prepare("SELECT * FROM ekstra WHERE id = ?");
        $res->bind_param("i", $id);
        $res->execute();
        $row = $res->get_result()->fetch_assoc();
        respond_json(['success' => true, 'message' => 'Berhasil diupdate.', 'row' => $row]);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            respond_json(['success' => false, 'message' => 'ID tidak valid.']);
        }
        $stmt = $conn->prepare("DELETE FROM ekstra WHERE id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            respond_json(['success' => false, 'message' => 'Gagal menghapus data.']);
        }
        respond_json(['success' => true, 'message' => 'Berhasil dihapus.', 'id' => $id]);
    }

    respond_json(['success' => false, 'message' => 'Aksi tidak dikenal.']);
}

// Baca data untuk tampilan awal
$ekstra = [];
$totalEkstra = 0;
$q = trim($_GET['q'] ?? '');

if ($q !== '') {
    $stmt = $conn->prepare("SELECT * FROM ekstra WHERE CONCAT(IFNULL(id,''), ' ', IFNULL(nama_ekstra,''), ' ', IFNULL(jadwal,''), ' ', IFNULL(guru_ekstra,'')) LIKE ? ORDER BY id ASC");
    $like = "%$q%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM ekstra ORDER BY id ASC");
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ekstra[] = $row;
    }
}

$totalEkstra = (int) ($conn->query("SELECT COUNT(*) AS c FROM ekstra")->fetch_assoc()['c'] ?? 0);

function h($s){ return htmlspecialchars($s ?: '', ENT_QUOTES, 'UTF-8'); }

// helper untuk menu aktif
$uri = $_SERVER['REQUEST_URI'];
$scriptName = basename($_SERVER['SCRIPT_NAME']);
$baseClasses = 'w-full inline-flex items-center justify-center md:justify-start gap-3 px-4 py-3 rounded-xl font-semibold transition transform hover:scale-105 shadow-sm';
$activeClasses = 'bg-gradient-to-r from-cyan-500 to-violet-600 text-black';
$inactiveClasses = 'bg-slate-800/30 text-slate-100';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Ekstrakurikuler | SMK TI Bali Global Denpasar</title>
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
        .modal-backdrop { background: rgba(2,6,23,0.6); }
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
                    <a href="../dashboard.php" class="<?php echo $baseClasses . ' ' . ($scriptName === '/dashboard.php' ? $activeClasses : $inactiveClasses); ?>">
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
                    <a href="../mapelCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/mapel') !== false ? $activeClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Mata Pelajaran</span>
                        <span class="md:hidden">📖</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/ekstra') !== false ? $activeClasses : $inactiveClasses); ?>">
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
                <h2 class="text-3xl font-bold text-cyan-300">Ekstrakurikuler</h2>
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
            <button id="btnCreate" class="bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2 rounded-lg font-semibold text-black hover:opacity-90 transition">
                + Tambah Ekstrakurikuler
            </button>

            <form method="get" class="flex gap-2">
                <input type="search" name="q" placeholder="Cari..." 
                    value="<?php echo h($q); ?>"
                    class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none text-white">
                <button class="bg-slate-700 px-3 py-2 rounded-lg hover:bg-slate-600 transition">Cari</button>
            </form>
        </div>

        <p class="text-sm text-slate-400 mb-4">Total Ekstrakurikuler: <span id="totalEkstra" class="text-white font-semibold"><?php echo $totalEkstra; ?></span></p>

        <!-- Pesan singkat -->
        <div id="toast" class="fixed right-6 top-6 hidden px-4 py-2 rounded shadow-lg text-black"></div>

        <!-- Tabel dalam card -->
        <div class="glass p-4 rounded-2xl">
            <div class="overflow-x-auto">
                <table id="tableEkstra" class="min-w-full border border-slate-700 rounded-lg">
                    <thead class="bg-slate-800 text-slate-300">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Nama Ekstra</th>
                            <th class="px-4 py-2 text-left">Jadwal</th>
                            <th class="px-4 py-2 text-left">Guru Ekstra</th>
                            <th class="px-4 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyEkstra">
                        <?php if (empty($ekstra)): ?>
                            <tr id="noDataRow">
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400">Belum ada data ekstrakurikuler.</td>
                            </tr>
                        <?php else: foreach ($ekstra as $i => $row): ?>
                            <tr data-id="<?php echo h($row['id'] ?? ''); ?>" class="border-t border-slate-700 hover:bg-slate-800 transition">
                                <td class="px-4 py-2 col-id"><?php echo h($row['id'] ?? ''); ?></td>
                                <td class="px-4 py-2 col-nama"><?php echo h($row['nama_ekstra'] ?? ''); ?></td>
                                <td class="px-4 py-2 col-jadwal"><?php echo h($row['jadwal'] ?? '-'); ?></td>
                                <td class="px-4 py-2 col-guru"><?php echo h($row['guru_ekstra'] ?? '-'); ?></td>
                                <td class="px-4 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button data-action="edit" data-id="<?php echo h($row['id']); ?>" class="p-2 rounded bg-yellow-400 text-black hover:opacity-90 inline-flex items-center" title="Edit">
                                            <i class='bxr bx-pencil'></i>
                                        </button>

                                        <button data-action="delete" data-id="<?php echo h($row['id']); ?>" class="p-2 rounded bg-red-600 text-white hover:opacity-90 inline-flex items-center" title="Hapus">
                                            <i class='bxr bx-trash'></i>
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

<!-- MODAL CREATE / EDIT -->
<div id="modalCreate" class="fixed inset-0 hidden items-center justify-center modal-backdrop z-50">
    <div class="bg-slate-900 rounded-lg p-6 w-full max-w-lg glass">
        <h3 class="text-xl font-semibold mb-4">Tambah Ekstrakurikuler</h3>
        <form id="formCreate" class="space-y-3">
            <div>
                <label class="text-sm text-slate-300">Nama Ekstra</label>
                <input name="nama_ekstra" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm text-slate-300">Jadwal</label>
                <input name="jadwal" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm text-slate-300">Guru Ekstra</label>
                <input name="guru_ekstra" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('modalCreate')" class="px-4 py-2 rounded bg-slate-700">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-cyan-500 text-black font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEdit" class="fixed inset-0 hidden items-center justify-center modal-backdrop z-50">
    <div class="bg-slate-900 rounded-lg p-6 w-full max-w-lg glass">
        <h3 class="text-xl font-semibold mb-4">Edit Ekstrakurikuler</h3>
        <form id="formEdit" class="space-y-3">
            <input type="hidden" name="id">
            <div>
                <label class="text-sm text-slate-300">Nama Ekstra</label>
                <input name="nama_ekstra" required class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm text-slate-300">Jadwal</label>
                <input name="jadwal" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div>
                <label class="text-sm text-slate-300">Guru Ekstra</label>
                <input name="guru_ekstra" class="w-full mt-1 px-3 py-2 rounded bg-slate-800 border border-slate-700 text-white">
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModal('modalEdit')" class="px-4 py-2 rounded bg-slate-700">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-yellow-400 font-semibold">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="modalDelete" class="fixed inset-0 hidden items-center justify-center modal-backdrop z-50">
    <div class="bg-slate-900 rounded-lg p-6 w-full max-w-md glass">
        <h3 class="text-xl font-semibold mb-4">Hapus Ekstrakurikuler</h3>
        <p id="deleteText" class="text-slate-300 mb-4">Yakin ingin menghapus data ini?</p>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeModal('modalDelete')" class="px-4 py-2 rounded bg-slate-700">Batal</button>
            <button id="confirmDeleteBtn" class="px-4 py-2 rounded bg-red-600 text-white font-semibold">Hapus</button>
        </div>
    </div>
</div>

<script>
const apiUrl = location.pathname; // same page

function showToast(msg, success = true) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = success ? 'rgba(34,197,94,0.95)' : 'rgba(239,68,68,0.95)';
    t.classList.remove('hidden');
    setTimeout(()=> t.classList.add('hidden'), 3000);
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('flex');
    document.getElementById(id).classList.add('hidden');
}

// Render row DOM safely
function renderRow(row) {
    const tr = document.createElement('tr');
    tr.setAttribute('data-id', row.id);
    tr.className = 'border-t border-slate-700 hover:bg-slate-800 transition';
    // ID
    let tdId = document.createElement('td'); tdId.className='px-4 py-2 col-id'; tdId.textContent = row.id;
    let tdNama = document.createElement('td'); tdNama.className='px-4 py-2 col-nama'; tdNama.textContent = row.nama_ekstra ?? '';
    let tdJadwal = document.createElement('td'); tdJadwal.className='px-4 py-2 col-jadwal'; tdJadwal.textContent = row.jadwal ?? '-';
    let tdGuru = document.createElement('td'); tdGuru.className='px-4 py-2 col-guru'; tdGuru.textContent = row.guru_ekstra ?? '-';
    let tdAksi = document.createElement('td'); tdAksi.className='px-4 py-2 text-center';
    tdAksi.innerHTML = `<div class="flex justify-center gap-2">
        <button data-action="edit" data-id="${row.id}" class="p-2 rounded bg-yellow-400 text-black hover:opacity-90 inline-flex items-center" title="Edit">
            <i class='bxr bx-pencil'></i>
        </button>
        <button data-action="delete" data-id="${row.id}" class="p-2 rounded bg-red-600 text-white hover:opacity-90 inline-flex items-center" title="Hapus">
            <i class='bxr bx-trash'></i>
        </button>
    </div>`;
    tr.appendChild(tdId);
    tr.appendChild(tdNama);
    tr.appendChild(tdJadwal);
    tr.appendChild(tdGuru);
    tr.appendChild(tdAksi);
    return tr;
}

// Update total count helper
function setTotal(n) {
    const el = document.getElementById('totalEkstra');
    el.textContent = n;
}

// Button handlers
document.getElementById('btnCreate').addEventListener('click', ()=> {
    document.getElementById('formCreate').reset();
    openModal('modalCreate');
});

document.getElementById('formCreate').addEventListener('submit', async function(e){
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action','create');
    const res = await fetch(apiUrl, { method:'POST', body: fd });
    const j = await res.json();
    if (j.success) {
        // remove "no data" row if present
        const noRow = document.getElementById('noDataRow');
        if (noRow) noRow.remove();
        const tbody = document.getElementById('tbodyEkstra');
        tbody.appendChild(renderRow(j.row));
        // increment total
        const total = parseInt(document.getElementById('totalEkstra').textContent || '0', 10) + 1;
        setTotal(total);
        showToast(j.message, true);
        closeModal('modalCreate');
    } else {
        showToast(j.message, false);
    }
});

// Delegasi klik untuk edit / delete buttons
document.getElementById('tbodyEkstra').addEventListener('click', function(e){
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const action = btn.getAttribute('data-action');
    const id = btn.getAttribute('data-id');
    if (action === 'edit') {
        // ambil data dari row
        const tr = document.querySelector(`tr[data-id="${id}"]`);
        const nama = tr.querySelector('.col-nama').textContent;
        const jadwal = tr.querySelector('.col-jadwal').textContent;
        const guru = tr.querySelector('.col-guru').textContent;
        const form = document.getElementById('formEdit');
        form.id.value = id;
        form.nama_ekstra.value = nama === '-' ? '' : nama;
        form.jadwal.value = jadwal === '-' ? '' : jadwal;
        form.guru_ekstra.value = guru === '-' ? '' : guru;
        openModal('modalEdit');
    } else if (action === 'delete') {
        const tr = document.querySelector(`tr[data-id="${id}"]`);
        const nama = tr.querySelector('.col-nama').textContent;
        document.getElementById('deleteText').textContent = `Yakin ingin menghapus "${nama}" (ID ${id})?`;
        document.getElementById('confirmDeleteBtn').setAttribute('data-id', id);
        openModal('modalDelete');
    }
});

document.getElementById('formEdit').addEventListener('submit', async function(e){
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action','edit');
    const res = await fetch(apiUrl, { method:'POST', body: fd });
    const j = await res.json();
    if (j.success) {
        // update row DOM
        const id = j.row.id;
        const trOld = document.querySelector(`tr[data-id="${id}"]`);
        if (trOld) {
            const newTr = renderRow(j.row);
            trOld.replaceWith(newTr);
        }
        showToast(j.message, true);
        closeModal('modalEdit');
    } else {
        showToast(j.message, false);
    }
});

document.getElementById('confirmDeleteBtn').addEventListener('click', async function(){
    const id = this.getAttribute('data-id');
    if (!id) return;
    const fd = new FormData();
    fd.append('action','delete');
    fd.append('id', id);
    const res = await fetch(apiUrl, { method:'POST', body: fd });
    const j = await res.json();
    if (j.success) {
        const tr = document.querySelector(`tr[data-id="${id}"]`);
        if (tr) tr.remove();
        // update total
        const total = Math.max(0, parseInt(document.getElementById('totalEkstra').textContent || '0',10) - 1);
        setTotal(total);
        // if table empty, insert no-data row
        const tbody = document.getElementById('tbodyEkstra');
        if (!tbody.querySelector('tr')) {
            const r = document.createElement('tr');
            r.id = 'noDataRow';
            r.innerHTML = '<td colspan="5" class="px-4 py-6 text-center text-slate-400">Belum ada data ekstrakurikuler.</td>';
            tbody.appendChild(r);
        }
        showToast(j.message, true);
        closeModal('modalDelete');
    } else {
        showToast(j.message, false);
    }
});

// Close modals on ESC
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') {
        ['modalCreate','modalEdit','modalDelete'].forEach(id => {
            if (!document.getElementById(id).classList.contains('hidden')) closeModal(id);
        });
    }
});
</script>
</body>
</html>