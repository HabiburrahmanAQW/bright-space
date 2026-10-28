<?php
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');
?>
<aside id="arazka-widget" class="fixed bottom-4 right-4 z-40 flex items-end gap-2">
    <div id="arazka-bubble" class="hidden relative max-w-[14rem] sm:max-w-xs bg-white border-2 border-violet-300 rounded-2xl px-3 py-2 shadow-lg" style="display:none;">
        <p class="text-xs font-extrabold text-violet-600">Arazka</p>
        <p id="arazka-teks" class="text-sm font-bold leading-snug"></p>
        <span class="absolute -right-1.5 bottom-5 w-3 h-3 rotate-45 bg-white border-t-2 border-r-2 border-violet-300"></span>
    </div>
    <button type="button" id="arazka-btn" aria-expanded="false"
            class="arazka-wrap w-16 h-16 sm:w-20 sm:h-20 shrink-0 p-1 rounded-full bg-cosmic-card border-4 border-cosmic-accent shadow-lg hover:scale-110 active:scale-95 transition-transform">
        <?= $arazkaSvg ?>
    </button>
</aside>

<div id="kuis-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-cosmic-text/70" style="display:none;" role="dialog" aria-modal="true">
    <div class="modal-panel relative w-full max-w-lg max-h-[92vh] overflow-y-auto bg-cosmic-card border-4 border-cosmic-accent rounded-3xl shadow-2xl p-5 sm:p-6">
        <button type="button" id="kuis-tutup" class="absolute top-3 right-3 w-9 h-9 flex items-center justify-center rounded-full bg-white/80 hover:bg-white">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        <div id="kuis-tahap-soal">
            <div class="flex items-center gap-3 pr-10">
                <div id="kuis-ikon" class="w-14 h-14 shrink-0"></div>
                <div class="flex-1">
                    <p class="text-xs font-extrabold uppercase tracking-wide text-violet-600">Misi Berlangsung</p>
                    <h2 id="kuis-judul" class="text-lg font-black leading-tight"></h2>
                </div>
                <div id="kuis-timer-box" class="hidden shrink-0 bg-rose-100 border-2 border-rose-300 rounded-2xl px-3 py-1 text-center">
                    <p class="text-[10px] font-extrabold uppercase text-rose-700">Waktu</p>
                    <p id="kuis-timer" class="text-lg font-black text-rose-700 leading-none">05:00</p>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between text-xs font-extrabold mb-1">
                    <span id="kuis-progress-teks">Soal 1 dari 10</span>
                    <span id="kuis-progress-benar">✅ 0</span>
                </div>
                <div class="w-full bg-white/60 rounded-full h-3 overflow-hidden border-2 border-violet-200">
                    <div id="kuis-progress-bar" class="bg-violet-500 h-full rounded-full transition-all duration-300" style="width:0%;"></div>
                </div>
            </div>

            <p id="kuis-pertanyaan" class="mt-4 text-lg font-extrabold leading-snug"></p>
            <div id="kuis-pilihan" class="mt-4 grid gap-3"></div>

            <div id="kuis-feedback" class="mt-4 flex items-start gap-3">
                <div id="arazka-modal" class="arazka-wrap relative w-14 h-14 shrink-0">
                    <?= $arazkaSvg ?>
                    <div id="kuis-percik" class="absolute inset-0 pointer-events-none"></div>
                </div>
                <div id="kuis-bubble" class="relative flex-1 rounded-2xl border-2 p-3 bg-violet-50 border-violet-200">
                    <p class="text-xs font-extrabold text-violet-600">Arazka</p>
                    <p id="kuis-fb-judul" class="text-base font-black"></p>
                    <p id="kuis-fb-teks" class="mt-1 text-sm font-bold"></p>
                </div>
            </div>

            <button type="button" id="kuis-lanjut" class="hidden w-full mt-4 bg-violet-500 text-white font-extrabold text-base py-3 rounded-full shadow hover:bg-violet-600">
                Soal Berikutnya →
            </button>
        </div>

        <div id="kuis-tahap-hasil" class="hidden text-center">
            <div class="text-5xl mb-2">🎉</div>
            <h2 class="text-2xl font-black">Misi Selesai!</h2>

            <div class="mt-4 mx-auto max-w-xs bg-white border-4 border-cosmic-accent rounded-3xl p-5 shadow-lg">
                <p class="text-xs font-extrabold uppercase text-violet-600 mb-1">Skor Kamu</p>
                <p class="text-5xl font-black leading-none">
                    <span id="hasil-benar">0</span><span class="text-2xl opacity-60">/</span><span id="hasil-total">10</span>
                </p>
                <p id="hasil-akurasi" class="mt-2 text-lg font-extrabold text-violet-700">Akurasi: 0%</p>
                <div class="mt-3 w-full bg-violet-100 rounded-full h-3 overflow-hidden">
                    <div id="hasil-bar" class="bg-emerald-500 h-full rounded-full transition-all duration-700" style="width:0%;"></div>
                </div>
            </div>

            <div id="hasil-reward-box" class="mt-4 inline-flex items-center gap-2 bg-cosmic-accent border-4 border-amber-300 rounded-full pl-2 pr-4 py-2 shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-7 h-7 shrink-0" aria-hidden="true">
                    <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" fill="#FCD34D" stroke="#B45309" stroke-width="1.2" stroke-linejoin="round"/>
                </svg>
                <span class="text-lg font-black">+<span id="hasil-reward">0</span> Koin Bintang</span>
            </div>

            <p id="hasil-bonus-teks" class="mt-2 text-sm font-extrabold text-emerald-700 hidden">🌟 Bonus Sempurna! +20%</p>

            <div class="mt-5 flex items-start gap-3 text-left">
                <div id="arazka-hasil" class="arazka-wrap relative w-14 h-14 shrink-0">
                    <?= $arazkaSvg ?>
                    <div id="hasil-percik" class="absolute inset-0 pointer-events-none"></div>
                </div>
                <div id="hasil-bubble" class="relative flex-1 rounded-2xl border-2 p-3 bg-emerald-50 border-emerald-300">
                    <p class="text-xs font-extrabold text-emerald-700">Arazka</p>
                    <p id="hasil-komentar" class="text-sm font-bold text-emerald-900"></p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3">
                <button type="button" id="hasil-ulang" class="bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-sm py-3 rounded-full shadow">🔁 Coba Lagi</button>
                <button type="button" id="hasil-selesai" class="bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold text-sm py-3 rounded-full shadow">🚀 Ambil & Kembali</button>
            </div>
        </div>
    </div>
</div>