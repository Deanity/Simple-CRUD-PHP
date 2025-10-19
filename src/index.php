<?php
session_start();

if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
  header("Location: dashboard.php");
  exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | SMK TI Bali Global Denpasar</title>
  <link href='https://cdn.boxicons.com/fonts/brands/boxicons-brands.min.css' rel='stylesheet'>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Optional Google Fonts for a techy look -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Rajdhani:wght@500;700&display=swap" rel="stylesheet">

  <style>
    body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }
    .font-tech { font-family: 'Rajdhani', 'Inter', sans-serif; }
    /* subtle animated tech-grid background */
    .tech-grid {
      background-image:
        radial-gradient(circle at 10% 10%, rgba(59,130,246,0.06) 0, transparent 20%),
        linear-gradient(90deg, rgba(99,102,241,0.03) 1px, transparent 1px),
        linear-gradient(180deg, rgba(56,189,248,0.02) 1px, transparent 1px);
      background-size: 100% 100%, 40px 40px, 40px 40px;
      animation: gridMove 12s linear infinite;
    }
    @keyframes gridMove {
      0% { background-position: 0 0, 0 0, 0 0; }
      50% { background-position: 0 0, 20px 20px, -20px -20px; }
      100% { background-position: 0 0, 0 0, 0 0; }
    }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-900 text-slate-100 tech-grid">

  <div class="container mx-auto px-6 max-w-4xl">
    <div class="grid grid-row-1 md:grid-row-2 gap-8 items-center">
      <!-- Right: Login card -->
      <div class="relative mx-auto w-full max-w-md">
        <div class="absolute -inset-1 blur-lg opacity-30 rounded-2xl bg-gradient-to-r from-cyan-500 via-blue-600 to-indigo-600"></div>
        <div class="relative bg-slate-900/70 border border-slate-700 rounded-2xl p-8 shadow-2xl">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h1 class="text-2xl font-tech font-bold">Admin Login</h1>
              <p class="text-sm text-slate-400">Masuk untuk mengelola sistem sekolah</p>
            </div>
          </div>

          <form action="config/loginSistem.php" method="POST" class="space-y-5">
            <label class="block">
              <span class="text-xs text-slate-300">Username</span>
              <div class="mt-2 relative">
                <input type="text" id="username" name="username" required
                  class="w-full pr-10 bg-slate-800/60 placeholder-slate-400 text-white rounded-lg px-4 py-3 border border-slate-700 focus:outline-none focus:ring-2 focus:ring-cyan-400"
                  placeholder="Masukkan username admin" ><i class='bxr  bx-lock'  ></i> 
              </div>
            </label>

            <label class="block">
              <span class="text-xs text-slate-300">Password</span>
              <div class="mt-2 relative">
                <input 
                  type="password" 
                  id="password" 
                  name="password" 
                  required
                  class="w-full bg-slate-800/60 placeholder-slate-400 text-white rounded-lg px-4 py-3 pr-12 border border-slate-700 focus:outline-none focus:ring-2 focus:ring-cyan-400 transition-all duration-200"
                  placeholder="Masukkan password"
                >
                <button 
                  type="button" 
                  id="togglePassword"
                  class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-cyan-400 transition-colors duration-200"
                >
                  <i class='bx bx-hide text-xl' id="eyeIcon"></i>
                </button>
              </div>
            </label>

            <button type="submit"
              class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-cyan-400 to-blue-600 hover:from-cyan-500 hover:to-blue-700 transition-all duration-200 text-slate-900 font-semibold py-3 rounded-lg shadow-lg">
              <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M12 5l7 7-7 7"/>
              </svg>
              Masuk
            </button>

            <?php if (isset($_GET['error'])): ?>
              <p class="text-center text-red-300 mt-2 text-sm">
                <?php
                if ($_GET['error'] == 'gagal') {
                  echo "Login gagal! Username atau password salah, atau Anda bukan admin.";
                }
                ?>
              </p>
            <?php endif; ?>
          </form>
        </div>
      </div>

    </div>
  </div>
</body>
</html>
