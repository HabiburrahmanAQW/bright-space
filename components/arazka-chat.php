<?php
defined('BRIGHT_SPACE') or exit('Akses langsung tidak diizinkan.');
$user = current_user();
$namaUser = $user ? $user['nama'] : 'Kapten';
?>
<section id="arazka-chat" class="mb-10">
    <div class="max-w-2xl mx-auto bg-white border-4 border-cosmic-accent rounded-3xl shadow-2xl p-5 sm:p-6">

        <div class="flex items-start gap-4">
            <div class="arazka-wrap relative w-20 h-20 sm:w-24 sm:h-24 shrink-0" data-mood="halo">
                <?= file_get_contents(__DIR__ . '/arazka-svg.php') ?>
                <div id="chat-percik" class="absolute inset-0 pointer-events-none"></div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-extrabold uppercase tracking-wider text-violet-600">Arazka · Mentor AI</p>
                <p class="text-sm font-bold opacity-60">Selalu di sini untukmu ✨</p>
            </div>
        </div>

        <div class="mt-4 bg-violet-50 border-2 border-violet-200 rounded-2xl p-4">
            <p id="chat-teks" class="text-base sm:text-lg font-extrabold leading-snug text-cosmic-text">
                Halo, Kapten <?= htmlspecialchars($namaUser) ?>! 👋
            </p>
            <p id="chat-subteks" class="mt-1 text-sm font-bold opacity-70"></p>
        </div>

        <div id="chat-pilihan" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2"></div>

        <button type="button" id="chat-lanjut"
                class="hidden w-full mt-4 bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-base py-3 rounded-full shadow transition-transform hover:scale-105 active:scale-95"
                style="display:none;">
            Lanjut →
        </button>
    </div>
</section>