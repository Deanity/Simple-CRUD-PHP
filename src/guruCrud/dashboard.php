<?php
session_start();

// hanya izinkan guru atau admin mengakses dashboard guru
if (!isset($_SESSION['status']) || $_SESSION['status'] !== "login" || !in_array($_SESSION['role'] ?? '', ['guru', 'admin'])) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}

include __DIR__ . '/../koneksi.php';

if (!isset($conn)) {
    die("❌ Koneksi database tidak ditemukan!");
}

function h($s){ return htmlspecialchars($s ?: '', ENT_QUOTES, 'UTF-8'); }

// baca kolom tabel guru
$columns = [];
$colRes = $conn->query("DESCRIBE guru");
if ($colRes) {
    while ($c = $colRes->fetch_assoc()) {
        $columns[] = $c['Field'];
    }
}

// tentukan primary key candidate
$pkCandidates = ['id', 'id_guru', 'nip', 'nik', 'guru_id'];
$pkCol = null;
foreach ($pkCandidates as $c) {
    if (in_array($c, $columns)) { $pkCol = $c; break; }
}
if (!$pkCol && count($columns) > 0) { $pkCol = $columns[0]; }

// ======= HANDLE POSTS: create / update / delete (single page) =======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete' && isset($_POST['delete_key']) && $pkCol) {
        $deleteKey = $_POST['delete_key'];
        $stmt = $conn->prepare("DELETE FROM guru WHERE `$pkCol` = ?");
        $stmt->bind_param("s", $deleteKey);
        $stmt->execute();
        header("Location: dashboard.php?msg=deleted");
        exit;
    }

    if ($action === 'create') {
        // ambil nilai-nilai yang dikirim untuk kolom yang ada
        $cols = [];
        $placeholders = [];
        $values = [];
        foreach ($columns as $c) {
            // skip password jika kosong, biarkan jika diisi
            if (!array_key_exists($c, $_POST)) continue;
            $v = $_POST[$c];
            // jika field PK kosong dan PK tampaknya auto-increment, biarkan kosong (tidak disertakan)
            if ($c === $pkCol && $v === '') continue;
            // jangan sisipkan kolom yang sengaja dikosongkan
            $cols[] = "`$c`";
            $placeholders[] = '?';
            $values[] = $v;
        }

        if (!empty($cols)) {
            $sql = "INSERT INTO guru (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $types = str_repeat('s', count($values));
                $bind = [];
                $bind[] = $types;
                for ($i = 0; $i < count($values); $i++) $bind[] = &$values[$i];
                call_user_func_array([$stmt, 'bind_param'], $bind);
                $stmt->execute();
                header("Location: dashboard.php?msg=created");
                exit;
            } else {
                header("Location: dashboard.php?msg=error");
                exit;
            }
        } else {
            header("Location: dashboard.php?msg=noinput");
            exit;
        }
    }

    if ($action === 'update' && $pkCol && isset($_POST['pk_value'])) {
        $pkValue = $_POST['pk_value'];
        $updates = [];
        $values = [];
        foreach ($columns as $c) {
            if ($c === $pkCol) continue;
            if (!array_key_exists($c, $_POST)) continue;
            $v = $_POST[$c];
            // jika password kosong, jangan update kolom password
            if (in_array(strtolower($c), ['password','passwd','pwd']) && $v === '') continue;
            $updates[] = "`$c` = ?";
            $values[] = $v;
        }
        if (!empty($updates)) {
            $sql = "UPDATE guru SET " . implode(", ", $updates) . " WHERE `$pkCol` = ?";
            $values[] = $pkValue;
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $types = str_repeat('s', count($values));
                $bind = [];
                $bind[] = $types;
                for ($i = 0; $i < count($values); $i++) $bind[] = &$values[$i];
                call_user_func_array([$stmt, 'bind_param'], $bind);
                $stmt->execute();
                header("Location: dashboard.php?msg=updated");
                exit;
            } else {
                header("Location: dashboard.php?msg=error");
                exit;
            }
        } else {
            header("Location: dashboard.php?msg=noupdates");
            exit;
        }
    }
}

// Baca data (dengan pencarian)
$q = trim($_GET['q'] ?? '');
$guru = [];

if ($q !== '' && !empty($columns)) {
    $searchCols = array_values(array_filter($columns, function($c){
        return in_array(strtolower($c), ['nama_guru','nama','nip','nik','email','mata_pelajaran','mapel','alamat']);
    }));
    if (empty($searchCols)) {
        $searchCols = array_slice($columns, 0, min(2, count($columns)));
    }
    $whereParts = [];
    foreach ($searchCols as $sc) {
        $whereParts[] = "`$sc` LIKE ?";
    }
    $sql = "SELECT * FROM guru WHERE " . implode(" OR ", $whereParts) . " ORDER BY `$pkCol` ASC";
    $stmt = $conn->prepare($sql);
    $like = "%$q%";
    $types = str_repeat('s', count($searchCols));
    $params = array_fill(0, count($searchCols), $like);
    $bind_names = [];
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query("SELECT * FROM guru ORDER BY " . ($pkCol ? "`$pkCol`" : "1") . " ASC");
}

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $guru[] = $r;
    }
}

// count
$totalGuru = (int) ($conn->query("SELECT COUNT(*) AS c FROM guru")->fetch_assoc()['c'] ?? 0);

// UI helpers
$uri = $_SERVER['REQUEST_URI'];
$script = basename($_SERVER['SCRIPT_NAME']);
$baseClasses = 'w-full inline-flex items-center justify-center md:justify-start gap-3 px-4 py-3 rounded-xl font-semibold transition transform hover:scale-105 shadow-sm';
$activeClasses = 'bg-gradient-to-r from-cyan-500 to-violet-600 text-black';
$inactiveClasses = 'bg-slate-800/30 text-slate-100';

// visible cols (exclude password in table)
$visibleCols = array_filter($columns, function($c){
    return !in_array(strtolower($c), ['password','passwd','pwd']);
});
if (empty($visibleCols) && !empty($guru)) {
    $visibleCols = array_keys($guru[0]);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Data Guru | SMK TI Bali Global Denpasar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <style>
        :root{ --accent:#06b6d4; --accent-2:#7c3aed; --panel:#0b1220; }
        .glass { background: linear-gradient(135deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); backdrop-filter: blur(6px); border: 1px solid rgba(255,255,255,0.04); }
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
                <p class="text-xs text-slate-400">Guru Panel</p>
            </div>
        </div>

        <nav class="flex-1">
            <ul class="space-y-3">
                <li>
                    <a href="../dashboard.php" class="<?php echo $baseClasses . ' ' . ($script === 'dashboard.php' ? $inactiveClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Dashboard</span>
                        <span class="md:hidden">🏠</span>
                    </a>
                </li>
                <li>
                    <a href="../siswaCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/siswa') !== false ? $inactiveClasses : $inactiveClasses); ?>">
                        <span class="hidden md:inline">Data Siswa</span>
                        <span class="md:hidden">👨‍🎓</span>
                    </a>
                </li>
                <li>
                    <a href="dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/guruCrud') !== false ? $activeClasses : $inactiveClasses); ?>">
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
                <h2 class="text-3xl font-bold text-cyan-300">Data Guru</h2>
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
            <button onclick="openCreate()" class="bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-2 rounded-lg font-semibold text-black hover:opacity-90 transition">
                + Tambah Guru
            </button>

            <form method="get" class="flex gap-2">
                <input type="search" name="q" placeholder="Cari..." 
                    value="<?php echo h($q); ?>"
                    class="px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 focus:outline-none text-white">
                <button class="bg-slate-700 px-3 py-2 rounded-lg hover:bg-slate-600 transition">Cari</button>
            </form>
        </div>

        <p class="text-sm text-slate-400 mb-4">Total Guru: <span class="text-white font-semibold"><?php echo $totalGuru; ?></span></p>

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
                            <?php foreach ($visibleCols as $vc): ?>
                                <th class="px-4 py-2 text-left"><?php echo h(ucwords(str_replace('_',' ',$vc))); ?></th>
                            <?php endforeach; ?>
                            <th class="px-4 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($guru)): ?>
                            <tr>
                                <td colspan="<?php echo max(2, count($visibleCols) + 1); ?>" class="px-4 py-6 text-center text-slate-400">Belum ada data guru.</td>
                            </tr>
                        <?php else: foreach ($guru as $i => $row): ?>
                            <tr class="border-t border-slate-700 hover:bg-slate-800 transition">
                                <?php foreach ($visibleCols as $vc): ?>
                                    <td class="px-4 py-2"><?php echo h($row[$vc] ?? ''); ?></td>
                                <?php endforeach; ?>
                                <td class="px-4 py-2 text-center">
                                    <div class="flex justify-center gap-2">
                                        <?php if ($pkCol): ?>
                                        <button onclick='openEdit(<?php echo json_encode($row, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT); ?>)' 
                                            class="p-2 rounded bg-yellow-400 text-black hover:opacity-90 inline-flex items-center" title="Edit">
                                            <i class='bxr bx-pencil'></i>
                                            <span class="sr-only">Edit</span>
                                        </button>

                                        <button onclick="openDelete('<?php echo h($row[$pkCol]); ?>')" 
                                            class="p-2 rounded bg-red-600 text-white hover:opacity-90 inline-flex items-center" title="Hapus">
                                            <i class='bxr bx-trash'></i>
                                            <span class="sr-only">Hapus</span>
                                        </button>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-xs">No PK</span>
                                        <?php endif; ?>
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

<!-- ===== MODALS ===== -->
<!-- Create / Edit Modal -->
<div id="modalForm" class="hidden fixed inset-0 z-50 items-center justify-center modal-backdrop">
    <div class="bg-slate-900 rounded-xl max-w-2xl w-full p-6 glass">
        <div class="flex items-center justify-between mb-4">
            <h3 id="modalTitle" class="text-lg font-semibold text-white">Form</h3>
            <button onclick="closeModal()" class="text-slate-300 hover:text-white">✕</button>
        </div>

        <form id="mainForm" method="post" class="space-y-4">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="pk_value" id="pkValue" value="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($columns as $col): 
                    $lower = strtolower($col);
                    // show field in form (including pk so user may set if needed)
                    $isPassword = in_array($lower, ['password','passwd','pwd']);
                    $inputType = $isPassword ? 'password' : 'text';
                    // prefer textarea for alamat or description
                    $isTextarea = strpos($lower, 'alamat') !== false || strpos($lower, 'desc') !== false;
                ?>
                    <div>
                        <label class="block text-sm text-slate-300 mb-1"><?php echo h(ucwords(str_replace('_',' ',$col))); ?></label>
                        <?php if ($isTextarea): ?>
                            <textarea id="field-<?php echo h($col); ?>" name="<?php echo h($col); ?>" class="w-full p-2 rounded bg-slate-800 border border-slate-700 text-white"></textarea>
                        <?php else: ?>
                            <input id="field-<?php echo h($col); ?>" name="<?php echo h($col); ?>" type="<?php echo $inputType; ?>" class="w-full p-2 rounded bg-slate-800 border border-slate-700 text-white">
                        <?php endif; ?>
                        <?php if ($isPassword): ?>
                            <p class="text-xs text-slate-400 mt-1">Kosongkan untuk tidak mengubah password saat edit.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded bg-slate-700 hover:bg-slate-600">Batal</button>
                <button type="submit" class="px-4 py-2 rounded bg-gradient-to-r from-cyan-500 to-violet-600 text-black font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="modalDelete" class="hidden fixed inset-0 z-50 items-center justify-center modal-backdrop">
    <div class="bg-slate-900 rounded-xl max-w-md w-full p-6 glass">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-white">Konfirmasi Hapus</h3>
            <p class="text-sm text-slate-400">Yakin ingin menghapus data ini? Tindakan tidak dapat dibatalkan.</p>
        </div>
        <form method="post" class="flex justify-end gap-2">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="delete_key" id="deleteKey" value="">
            <button type="button" onclick="closeDelete()" class="px-4 py-2 rounded bg-slate-700 hover:bg-slate-600">Batal</button>
            <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white">Hapus</button>
        </form>
    </div>
</div>

<script>
    // open create modal
    function openCreate(){
        document.getElementById('modalTitle').textContent = 'Tambah Guru';
        document.getElementById('formAction').value = 'create';
        document.getElementById('pkValue').value = '';
        // clear fields
        <?php foreach ($columns as $c): ?>
            var el = document.getElementById('field-<?php echo h($c); ?>');
            if(el) el.value = '';
        <?php endforeach; ?>
        var m = document.getElementById('modalForm');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    // open edit with row object
    function openEdit(row){
        document.getElementById('modalTitle').textContent = 'Edit Guru';
        document.getElementById('formAction').value = 'update';
        <?php if ($pkCol): ?>
            document.getElementById('pkValue').value = row['<?php echo h($pkCol); ?>'] ?? '';
        <?php endif; ?>
        <?php foreach ($columns as $c): ?>
            var el = document.getElementById('field-<?php echo h($c); ?>');
            if(el) el.value = row['<?php echo h($c); ?>'] ?? '';
        <?php endforeach; ?>
        var m = document.getElementById('modalForm');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function closeModal(){
        var m = document.getElementById('modalForm');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    // delete
    function openDelete(pk){
        document.getElementById('deleteKey').value = pk;
        var md = document.getElementById('modalDelete');
        md.classList.remove('hidden');
        md.classList.add('flex');
    }
    function closeDelete(){
        var md = document.getElementById('modalDelete');
        md.classList.add('hidden');
        md.classList.remove('flex');
    }

    // optional: close modals with ESC
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape'){
            closeModal();
            closeDelete();
        }
    });
</script>

</body>
</html>