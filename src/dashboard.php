<?php
session_start();

// Cek apakah sudah login dan role = admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login" || $_SESSION['role'] != "admin") {
    header("location: index.php?error=unauthorized");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Dashboard | SMK TI Bali Global Denpasar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Quick theme tweaks */
        :root{
            --accent:#06b6d4; /* cyan-500 */
            --accent-2:#7c3aed; /* violet-600 */
            --panel:#0b1220;
        }
        /* make cards slightly glassy */
        .glass {
            background: linear-gradient(135deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,0.04);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-950 to-black text-slate-100 font-sans">

    <!-- Layout: Sidebar + Main -->
    <div class="min-h-screen flex">

        <!-- SIDEBAR -->
        <aside class="w-20 md:w-64 bg-gradient-to-b from-[#071227] to-[#07172b] border-r border-slate-800 p-4 flex flex-col">
            <div class="flex items-center gap-3 mb-6">
                <div class="hidden md:block">
                <h1 class="text-lg font-semibold tracking-wide text-cyan-300">SMK TI Bali Global</h1>
                <p class="text-xs text-slate-400">Admin Panel</p>
                </div>
            </div>

            <?php
            // helper untuk menandai menu aktif berdasarkan URI atau nama script
            $uri = $_SERVER['REQUEST_URI'];
            $script = basename($_SERVER['SCRIPT_NAME']);
            $baseClasses = 'w-full inline-flex items-center justify-center md:justify-start gap-3 px-4 py-3 rounded-xl font-semibold transition transform hover:scale-105 shadow-sm';
            $activeClasses = 'bg-gradient-to-r from-cyan-500 to-violet-600 text-black';
            $inactiveClasses = 'bg-slate-800/30 text-slate-100';
            ?>
            <nav class="flex-1">
                <ul class="space-y-3">
                    <li>
                        <a href="dashboard.php" class="<?php echo $baseClasses . ' ' . ($script === 'dashboard.php' ? $activeClasses : $inactiveClasses); ?>">
                            <span class="hidden md:inline">Dashboard</span>
                            <span class="md:hidden">🏠</span>
                        </a>
                    </li>
                    <li>
                        <a href="siswaCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/siswa') !== false ? $activeClasses : $inactiveClasses); ?>">
                            <span class="hidden md:inline">Data Siswa</span>
                            <span class="md:hidden">👨‍🎓</span>
                        </a>
                    </li>
                    <li>
                        <a href="guruCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/guru') !== false ? $activeClasses : $inactiveClasses); ?>">
                            <span class="hidden md:inline">Data Guru</span>
                            <span class="md:hidden">👩‍🏫</span>
                        </a>
                    </li>
                    <li>
                        <a href="jurusanCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/jurusan') !== false ? $activeClasses : $inactiveClasses); ?>">
                            <span class="hidden md:inline">Jurusan</span>
                            <span class="md:hidden">📚</span>
                        </a>
                    </li>
                    <li>
                        <a href="mapelCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/mapel') !== false ? $activeClasses : $inactiveClasses); ?>">
                            <span class="hidden md:inline">Mata Pelajaran</span>
                            <span class="md:hidden">📖</span>
                        </a>
                    </li>
                    <li>
                        <a href="ekstraCrud/dashboard.php" class="<?php echo $baseClasses . ' ' . (strpos($uri, '/ekstra') !== false ? $activeClasses : $inactiveClasses); ?>">
                            <span class="hidden md:inline">Ekstrakurikuler</span>
                            <span class="md:hidden">🎯</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="mt-4">
                <a href="config/logoutSistem.php" class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-violet-600 text-black font-semibold hover:scale-105 transition">
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
                <h2 class="text-3xl font-bold text-cyan-300">Admin Dashboard</h2>
                <p class="text-sm text-slate-400">Selamat datang, <span class="font-semibold text-white"><?php echo htmlspecialchars($_SESSION['username']); ?></span></p>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-3 px-4 py-2 rounded-lg bg-slate-800/50 border border-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-cyan-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a1 1 0 001 1h3m10-12v10a1 1 0 01-1 1h-3M7 9h10"/></svg>
                    <div class="text-right">
                    <div class="text-xs text-slate-400">Role</div>
                    <div class="text-sm font-semibold text-white"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
                </div>
            </div>
                <a href="config/logoutSistem.php" class="md:hidden inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gradient-to-r from-cyan-500 to-violet-600 text-black font-semibold">
                    Logout
                </a>
            </div>
        </header>

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="siswaCrud/dashboard.php" class="glass p-8 rounded-2xl hover:scale-[1.02] transition transform">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-white">Kelola Siswa</h4>
                        <p class="text-sm text-slate-400">Tambah / edit / hapus data siswa</p>
                    </div>
                    <div class="text-4xl">👨‍🎓</div>
                </div>
                <div class="text-xs text-slate-400">Akses cepat ke modul siswa</div>
            </a>

            <a href="guruCrud/dashboard.php" class="glass p-8 rounded-2xl hover:scale-[1.02] transition transform">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-white">Kelola Guru</h4>
                        <p class="text-sm text-slate-400">Manajemen data guru</p>
                    </div>
                    <div class="text-4xl">👩‍🏫</div>
                </div>
                <div class="text-xs text-slate-400">Atur jadwal dan profil guru</div>
            </a>

            <a href="jurusanCrud/dashboard.php" class="glass p-8 rounded-2xl hover:scale-[1.02] transition transform">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-white">Jurusan</h4>
                        <p class="text-sm text-slate-400">Kelola jurusan & kompetensi</p>
                    </div>
                    <div class="text-4xl">📚</div>
                </div>
                <div class="text-xs text-slate-400">Tambah atau ubah jurusan</div>
            </a>

            <a href="mapelCrud/dashboard.php" class="glass p-8 rounded-2xl hover:scale-[1.02] transition transform">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-white">Mata Pelajaran</h4>
                        <p class="text-sm text-slate-400">Atur kurikulum</p>
                    </div>
                    <div class="text-4xl">📖</div>
                </div>
                <div class="text-xs text-slate-400">Kelola mapel & pengajar</div>
            </a>

            <a href="ekstraCrud/dashboard.php" class="glass p-8 rounded-2xl hover:scale-[1.02] transition transform">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-lg font-semibold text-white">Ekstrakurikuler</h4>
                        <p class="text-sm text-slate-400">Manajemen kegiatan ekstra</p>
                    </div>
                    <div class="text-4xl">🎯</div>
                </div>
                <div class="text-xs text-slate-400">Kelola klub & kegiatan</div>
            </a>
        </section>

        <!-- Footer -->
        <footer class="mt-12 text-center text-slate-500">
            © 2025 SMK TI Bali Global Denpasar — Admin Panel
        </footer>
        </main>
    </div>

</body>
</html>
