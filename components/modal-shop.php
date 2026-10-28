<?php
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');
if (!isset($items)) { $items = []; }
$arazkaSvgShop = file_get_contents(__DIR__ . '/arazka-svg.php');
?>
<div id="toko-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-cosmic-text/70" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal-panel relative w-full max-w-2xl max-h-[92vh] overflow-y-auto bg-cosmic-card border-4 border-cosmic-accent rounded-3xl shadow-2xl p-5 sm:p-6">
        <button type="button" id="toko-tutup" class="absolute top-3 right-3 w-9 h-9 flex items-center justify-center rounded-full bg-white/80 hover:bg-white">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        <div class="flex items-center gap-3 pr-10">
            <div class="w-14 h-14 shrink-0 flex items-center justify-center bg-cosmic-accent rounded-full border-2 border-amber-300 text-3xl">🛍️</div>
            <div>
                <p class="text-xs font-extrabold uppercase text-violet-600">Toko</p>
                <h2 class="text-xl font-black">Toko Bintang</h2>
            </div>
        </div>

        <div class="mt-3 inline-flex items-center gap-1.5 bg-cosmic-accent border-2 border-amber-300 rounded-full pl-2 pr-3 py-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5" aria-hidden="true">
                <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" fill="#FCD34D" stroke="#B45309" stroke-width="1.2"/>
            </svg>
            <span class="text-sm font-extrabold">Saldo: <span id="toko-saldo">0</span> Koin</span>
        </div>

        <div id="toko-grid" class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3"></div>

        <div id="toko-feedback" class="mt-5 flex items-start gap-3">
            <div id="arazka-toko" class="arazka-wrap relative w-14 h-14 shrink-0">
                <?= $arazkaSvgShop ?>
                <div id="toko-percik" class="absolute inset-0 pointer-events-none"></div>
            </div>
            <div id="toko-bubble" class="relative flex-1 rounded-2xl border-2 p-3 bg-violet-50 border-violet-200">
                <p class="text-xs font-extrabold text-violet-600">Arazka</p>
                <p id="toko-fb-judul" class="text-base font-black"></p>
                <p id="toko-fb-teks" class="mt-1 text-sm font-bold"></p>
            </div>
        </div>
    </div>
</div>

<script>
    window.BRIGHT_SPACE_ITEMS = <?= json_encode($items ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>