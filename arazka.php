<?php
define('BRIGHT_SPACE', true);
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/config/database.php';
require_login();

$appName = 'BRIGHT SPACE';
$user = current_user();
if (!$user) { header('Location: logout.php'); exit; }

$user_name  = $user['nama']        ?? 'Kapten';
$user_id    = (int) ($user['id']         ?? 0);
$star_coins = (int) ($user['star_coins'] ?? 0);

$arazkaSvg = file_get_contents(__DIR__ . '/components/arazka-svg.php');

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanya Arazka - <?= e($appName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { cosmic: { bg: '#E0F2FE', card: '#EDE9FE', accent: '#FEF3C7', text: '#1E1B4B' } },
            fontFamily: { nunito: ['Nunito', 'ui-rounded', 'system-ui', 'sans-serif'] }
        } } };
    </script>
    <style>
        @keyframes bob { 0%,100%{transform:translateY(0);}50%{transform:translateY(-6px);} }
        .arazka-bob { animation: bob 2.5s ease-in-out infinite; }
        .chat-scroll { max-height: 60vh; overflow-y: auto; scroll-behavior: smooth; }
        .chat-scroll::-webkit-scrollbar { width: 6px; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #c4b5fd; border-radius: 3px; }
    </style>
</head>
<body class="font-nunito text-slate-800 min-h-screen antialiased" style="background-color:#F0F7FF;">

    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen flex flex-col">
        <?php include __DIR__ . '/components/header.php'; ?>

        <main class="max-w-4xl mx-auto px-4 py-6 w-full flex-1">

            <!-- Header -->
            <section class="mb-6 text-center">
                <h1 class="text-3xl sm:text-4xl font-black">🤖 Tanya Arazka</h1>
                <p class="mt-2 text-base font-bold opacity-80">
                    Mentor AI siap bantu kamu, Kapten <?= e($user_name) ?>!
                </p>
            </section>

            <!-- Quick FAQ Pills -->
            <section class="mb-4">
                <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-2">✨ Pertanyaan Cepat</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="faq-pill bg-white hover:bg-violet-100 border-2 border-violet-200 hover:border-violet-400 rounded-full px-4 py-2 text-xs sm:text-sm font-extrabold transition-all hover:scale-105"
                            data-q="Gimana cara dapet Koin Bintang?">
                        ❓ Cara dapet Koin
                    </button>
                    <button type="button" class="faq-pill bg-white hover:bg-violet-100 border-2 border-violet-200 hover:border-violet-400 rounded-full px-4 py-2 text-xs sm:text-sm font-extrabold transition-all hover:scale-105"
                            data-q="Gimana cara buka Planet yang terkunci?">
                        🚀 Buka Planet Terkunci
                    </button>
                    <button type="button" class="faq-pill bg-white hover:bg-violet-100 border-2 border-violet-200 hover:border-violet-400 rounded-full px-4 py-2 text-xs sm:text-sm font-extrabold transition-all hover:scale-105"
                            data-q="Gimana cara pake barang yang udah dibeli di Toko?">
                        🛍️ Pakai Barang Toko
                    </button>
                    <button type="button" class="faq-pill bg-white hover:bg-violet-100 border-2 border-violet-200 hover:border-violet-400 rounded-full px-4 py-2 text-xs sm:text-sm font-extrabold transition-all hover:scale-105"
                            data-q="Apa itu Star Coins?">
                        ⭐ Apa itu Star Coins?
                    </button>
                </div>
            </section>

            <!-- Chat Box -->
            <section class="bg-white border-2 border-indigo-100 rounded-3xl shadow-md overflow-hidden flex flex-col">

                <!-- Header chat -->
                <div class="flex items-center gap-3 px-5 py-3 bg-gradient-to-r from-violet-100 to-indigo-100 border-b-2 border-indigo-100">
                    <div class="arazka-bob w-12 h-12 shrink-0 arazka-wrap">
                        <?= $arazkaSvg ?>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-black">Arazka</p>
                        <p class="text-xs font-bold text-emerald-600">● Online — Siap bantu!</p>
                    </div>
                </div>

                <!-- Chat messages -->
                <div id="chat-messages" class="chat-scroll p-4 space-y-3 flex-1" style="min-height: 350px;">

                    <!-- Sapaan awal dari Arazka -->
                    <div class="flex items-start gap-2">
                        <div class="w-8 h-8 shrink-0 rounded-full bg-violet-100 border-2 border-violet-200 flex items-center justify-center text-lg">🤖</div>
                        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl rounded-tl-sm px-4 py-2.5 max-w-[80%]">
                            <p class="text-sm font-bold leading-snug">
                                Halo, Kapten <?= e($user_name) ?>! 🚀 Aku Arazka, siap bantu kamu!
                                Tanya apa aja ya, atau klik tombol pertanyaan cepat di atas! 😊
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Input area -->
                <form id="chat-form" class="flex items-center gap-2 p-3 border-t-2 border-indigo-100 bg-indigo-50/40">
                    <input type="text" id="chat-input" autocomplete="off"
                           placeholder="Ketik pertanyaanmu di sini..."
                           class="flex-1 px-4 py-3 rounded-2xl border-2 border-violet-200 focus:border-violet-500 focus:outline-none focus:ring-4 focus:ring-violet-200 font-bold text-sm bg-white">
                    <button type="submit"
                            class="shrink-0 bg-violet-500 hover:bg-violet-600 text-white font-extrabold text-sm px-5 py-3 rounded-2xl shadow transition-transform hover:scale-105 active:scale-95 flex items-center gap-1">
                        <span>Kirim</span>
                        <span>🚀</span>
                    </button>
                </form>
            </section>

        </main>

        <footer class="bg-white border-t-2 border-slate-100 mt-8">
            <div class="max-w-6xl mx-auto px-4 py-4 text-center text-xs font-bold text-slate-400">
                BRIGHT SPACE EdTech
            </div>
        </footer>
    </div>

    <div id="toast" class="fixed bottom-20 left-1/2 -translate-x-1/2 z-[80] hidden bg-cosmic-text text-white font-bold text-sm px-4 py-2 rounded-full shadow-lg text-center max-w-[90%]" style="display:none;"></div>

    <script>
        window.BRIGHT_SPACE = {
            user: {
                id: <?= (int) $user_id ?>,
                nama: <?= json_encode($user_name, JSON_UNESCAPED_UNICODE) ?>,
                star_coins: <?= (int) $star_coins ?>
            }
        };
    </script>
    <script src="assets/js/app.js" defer></script>
</body>
</html>