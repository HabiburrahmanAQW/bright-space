<?php
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');

$user = current_user();
$namaUser  = $user['nama']        ?? 'Kapten';
$koinUser  = (int) ($user['star_coins'] ?? 0);
$levelUser = (int) ($user['level']      ?? 1);

$currentPage = basename($_SERVER['PHP_SELF']);

// Mapping menu — biar gampang nambah nanti
$menuUtama = [
    ['href' => 'dashboard.php',    'icon' => '🏠', 'label' => 'Dashboard'],
    ['href' => 'tambah-misi.php',         'icon' => '🗺️', 'label' => 'Peta Misi / Planet'],
    // ['href' => 'shop.php',         'icon' => '🛍️', 'label' => 'Toko Bintang'],
    // ['href' => 'leaderboard.php',  'icon' => '🏆', 'label' => 'Papan Skor'],
    // ['href' => 'arazka.php',       'icon' => '🤖', 'label' => 'Tanya Arazka'],
    // ['href' => 'profile.php',      'icon' => '👤', 'label' => 'Profil Tutor'],
];
?>
<!-- ============ HAMBURGER (mobile) ============ -->
<button type="button" id="btn-sidebar-open" aria-label="Buka menu"
        class="fixed top-3 left-3 z-[70] w-11 h-11 flex items-center justify-center bg-white border-2 border-violet-300 rounded-2xl shadow-lg hover:scale-105 transition-transform lg:hidden">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6 text-cosmic-text" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <path d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

<!-- ============ BACKDROP (mobile) ============ -->
<div id="sidebar-backdrop"
     class="hidden fixed inset-0 bg-cosmic-text/50 z-[75] lg:hidden"
     style="display:none;"></div>

<!-- ============ SIDEBAR ============ -->
<aside id="sidebar"
       aria-label="Menu navigasi utama"
       class="fixed top-0 left-0 h-full w-72 max-w-[85vw] bg-cosmic-card border-r-4 border-cosmic-accent shadow-2xl z-[80] flex flex-col
              transition-transform duration-300 ease-out
              -translate-x-full lg:translate-x-0">

    <!-- Header sidebar -->
    <div class="flex items-center justify-between p-4 border-b-2 border-violet-200 shrink-0">
        <a href="dashboard.php" class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-cosmic-accent shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-6 h-6" aria-hidden="true">
                    <path d="M9.5 17.5c-.5 1.6-.3 3.2.5 4.5 1-.6 1.6-1.2 2-2 .4.8 1 1.4 2 2 .8-1.3 1-2.9.5-4.5z" fill="#FB923C"/>
                    <path d="M8 11.5 4.5 15l.5 3 3.5-2z" fill="#A78BFA"/>
                    <path d="M16 11.5 19.5 15l-.5 3-3.5-2z" fill="#A78BFA"/>
                    <path d="M12 1.5c3 2 4.8 5.6 4.8 9.6 0 2.2-.4 4.2-1.1 6.4H8.3c-.7-2.2-1.1-4.2-1.1-6.4 0-4 1.8-7.6 4.8-9.6z" fill="#F8FAFC" stroke="#1E1B4B" stroke-width="1.2" stroke-linejoin="round"/>
                    <circle cx="12" cy="10" r="2.2" fill="#7DD3FC" stroke="#1E1B4B" stroke-width="1.2"/>
                </svg>
            </span>
            <span class="text-base font-black">BRIGHT <span class="text-violet-600">SPACE</span></span>
        </a>
        <button type="button" id="btn-sidebar-close" aria-label="Tutup menu"
                class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-white/60 lg:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M6 6l12 12M18 6L6 18"/>
            </svg>
        </button>
    </div>

    <!-- Profil singkat -->
    <div class="p-4 bg-gradient-to-br from-violet-100 to-indigo-100 border-b-2 border-violet-200 shrink-0">
        <div class="flex items-center gap-3">
            <span class="w-12 h-12 rounded-full bg-gradient-to-br from-violet-400 to-indigo-500 flex items-center justify-center text-white font-black text-lg shadow">
                <?= strtoupper(substr($namaUser, 0, 1)) ?>
            </span>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-extrabold uppercase text-violet-600">Tutor</p>
                <p class="text-base font-black truncate"><?= htmlspecialchars($namaUser) ?></p>
                <!-- <p class="text-xs font-bold text-amber-600">🏆 Level <?= $levelUser ?></p> -->
            </div>
        </div>
        <!-- <div class="mt-3 flex items-center gap-2 bg-cosmic-accent border-2 border-amber-300 rounded-full px-3 py-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 shrink-0" aria-hidden="true">
                <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"
                         fill="#FCD34D" stroke="#B45309" stroke-width="1.2"/>
            </svg>
            <span class="font-extrabold text-base" id="sidebar-koin"><?= $koinUser ?></span>
            <span class="text-xs font-bold opacity-70">Koin</span>
        </div> -->
    </div>

    <!-- Menu -->
    <nav class="flex-1 overflow-y-auto p-3">
        <p class="text-xs font-extrabold uppercase tracking-wider text-violet-500 px-3 py-2">Menu Utama</p>

        <?php foreach ($menuUtama as $item):
            $active = ($currentPage === basename($item['href']));
            $kelas = $active
                ? 'bg-violet-500 text-white shadow-md'
                : 'text-cosmic-text hover:bg-violet-100';
        ?>
            <a href="<?= htmlspecialchars($item['href']) ?>"
               class="menu-item flex items-center gap-3 px-3 py-2.5 rounded-2xl font-extrabold text-sm transition-colors mb-1 <?= $kelas ?>">
                <span class="text-lg"><?= $item['icon'] ?></span>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Tombol Logout -->
    <div class="p-3 border-t-2 border-violet-200 shrink-0">
        <a href="logout.php"
           class="flex items-center justify-center gap-2 bg-rose-500 hover:bg-rose-600 text-white font-extrabold text-sm py-3 rounded-2xl shadow transition-transform hover:scale-105">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>Log Out</span>
        </a>
    </div>
</aside>

<script>
(function () {
    var sidebar   = document.getElementById('sidebar');
    var backdrop  = document.getElementById('sidebar-backdrop');
    var btnOpen   = document.getElementById('btn-sidebar-open');
    var btnClose  = document.getElementById('btn-sidebar-close');
    if (!sidebar || !backdrop) return;

    function bukaSidebar() {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
        backdrop.style.display = '';
        document.body.classList.add('overflow-hidden');
    }
    function tutupSidebar() {
        // Hanya tutup di mobile (lg = 1024px)
        if (window.innerWidth < 1024) {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
            backdrop.style.display = 'none';
            document.body.classList.remove('overflow-hidden');
        }
    }

    btnOpen  && btnOpen.addEventListener('click', bukaSidebar);
    btnClose && btnClose.addEventListener('click', tutupSidebar);
    backdrop && backdrop.addEventListener('click', tutupSidebar);

    // Kalau resize ke desktop, otomatis sembunyikan backdrop & buka sidebar
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.add('hidden');
            backdrop.style.display = 'none';
            document.body.classList.remove('overflow-hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
        }
    });

    // Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') tutupSidebar();
    });

    // Tandai menu aktif berdasarkan URL (fallback kalau PHP salah deteksi)
    var path = window.location.pathname.split('/').pop() || 'dashboard.php';
    document.querySelectorAll('.menu-item').forEach(function (a) {
        var href = a.getAttribute('href');
        if (href === path) {
            a.classList.add('bg-violet-500', 'text-white', 'shadow-md');
            a.classList.remove('text-cosmic-text', 'hover:bg-violet-100');
        }
    });
})();
</script>