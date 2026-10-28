<?php
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');
$user = current_user();
$namaUser = $user ? $user['nama'] : 'Kapten';
$koinUser = $user ? (int) $user['star_coins'] : 0;
$levelUser = $user ? (int) $user['level'] : 1;
?>
<header class="sticky top-0 z-40 bg-cosmic-card border-b-4 border-cosmic-accent shadow-md">
    <nav class="max-w-6xl mx-auto px-4 py-2.5 flex items-center justify-between gap-3">

        <!-- KIRI: spacer untuk tombol hamburger sidebar -->
        <div class="ml-14"></div>

        <!-- KANAN: Koin + Profil -->
        <div class="flex items-center gap-2">
            <div id="star-coin-badge" class="flex items-center gap-1 bg-cosmic-accent border-2 border-amber-300 rounded-full pl-1.5 pr-2.5 py-1 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 shrink-0" aria-hidden="true">
                    <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" fill="#FCD34D" stroke="#B45309" stroke-width="1.2" stroke-linejoin="round"/>
                </svg>
                <span id="star-coin-count" class="font-extrabold text-base leading-none"><?= $koinUser ?></span>
            </div>

            <div class="relative">
                <button type="button" id="btn-profil" aria-haspopup="true" aria-expanded="false"
                        class="flex items-center gap-2 bg-white hover:bg-violet-50 border-2 border-violet-200 rounded-full pl-1 pr-3 py-1 shadow-sm">
                    <span class="w-7 h-7 rounded-full bg-gradient-to-br from-violet-400 to-indigo-500 flex items-center justify-center text-white font-black text-xs">
                        <?= strtoupper(substr($namaUser, 0, 1)) ?>
                    </span>
                    <span class="hidden sm:inline text-sm font-extrabold max-w-[80px] truncate"><?= htmlspecialchars($namaUser) ?></span>
                </button>
                <div id="profil-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-white border-2 border-violet-200 rounded-2xl shadow-xl p-3 z-50" style="display:none;">
                    <div class="pb-2 border-b-2 border-violet-100">
                        <p class="text-xs font-extrabold uppercase text-violet-600">Kapten</p>
                        <p class="text-lg font-black truncate"><?= htmlspecialchars($namaUser) ?></p>
                        <p class="text-xs font-bold text-amber-600 mt-0.5">🏆 Level <?= $levelUser ?></p>
                    </div>
                    <a href="logout.php" class="block mt-2 px-3 py-2 rounded-xl hover:bg-rose-50 text-sm font-extrabold text-rose-600">🚪 Keluar</a>
                </div>
            </div>
        </div>
    </nav>
</header>
<script>
document.addEventListener('click', function (e) {
    var btn = document.getElementById('btn-profil');
    var dd  = document.getElementById('profil-dropdown');
    if (!btn || !dd) return;
    if (btn.contains(e.target)) {
        var buka = dd.classList.contains('hidden');
        dd.classList.toggle('hidden');
        dd.style.display = buka ? '' : 'none';
    } else if (!dd.contains(e.target)) {
        dd.classList.add('hidden');
        dd.style.display = 'none';
    }
});
</script>