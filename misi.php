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

require __DIR__ . '/config/planets.php';
require_once __DIR__ . '/config/student_mission_bridge.php';
$planets = bs_student_planets($planets, $user);

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Misi - <?= e($appName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { cosmic: { bg: '#E0F2FE', card: '#EDE9FE', accent: '#FEF3C7', text: '#1E1B4B' } },
            fontFamily: { nunito: ['Nunito', 'ui-rounded', 'system-ui', 'sans-serif'] }
        } } };
    </script>
    <style>
        @keyframes planet-float { 0%,100%{transform:translateY(0) rotate(0);}50%{transform:translateY(-4px) rotate(4deg);} }
        .planet-card.is-open:hover .planet-icon { animation: planet-float 1.4s ease-in-out infinite; }
    </style>
</head>
<body class="font-nunito text-slate-800 min-h-screen antialiased" style="background-color:#F0F7FF;">

    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen flex flex-col">
        <?php include __DIR__ . '/components/header.php'; ?>

        <main class="max-w-6xl mx-auto px-4 py-6 w-full flex-1">

            <!-- Hero -->
            <section class="mb-6 text-center">
                <h1 class="text-3xl sm:text-4xl font-black">Peta Misi 🗺️</h1>
                <p class="mt-2 text-base sm:text-lg font-bold opacity-80">
                    Pilih planet untuk memulai petualanganmu, Kapten <?= e($user_name) ?>!
                </p>
            </section>

            <!-- Grid 4 Planet -->
            <section id="grid-planet" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($planets as $p): $terbuka = ($p['status'] === 'Terbuka'); ?>
                    <?php if ($terbuka): ?>
                        <!-- KARTU TERBUKA -->
                        <a href="planet-detail.php?id=<?= e($p['id']) ?>"
                           class="planet-card is-open group relative flex flex-col bg-gradient-to-br <?= e($p['gradien']) ?> border-2 <?= e($p['border']) ?> rounded-3xl p-5 shadow-md transition-all duration-300 hover:-translate-y-2 hover:shadow-xl focus:outline-none focus-visible:ring-4 focus-visible:ring-amber-300"
                           data-planet-id="<?= e($p['id']) ?>">

                            <!-- Badge Bintang -->
                            <div class="absolute top-3 right-3 flex items-center gap-1 bg-white/90 border-2 border-amber-300 rounded-full pl-1.5 pr-2.5 py-0.5 shadow-sm">
                                <span class="text-xs font-extrabold"><?= ($p['source'] ?? 'legacy') === 'database' ? '⭐ Planet Baru' : '⭐ Planet' ?></span>
                            </div>

                            <div class="planet-icon w-24 h-24 mx-auto mt-6 mb-3"><?= $p['ikon'] ?></div>

                            <h2 class="text-xl font-black text-center leading-tight"><?= e($p['nama']) ?></h2>
                            <p class="mt-1 text-sm font-bold text-center opacity-80"><?= e($p['deskripsi']) ?></p>

                            <div class="mt-3 flex justify-center">
                                <span class="inline-flex items-center gap-1 bg-white/70 text-emerald-700 text-xs font-extrabold px-3 py-1 rounded-full">
                                    ✅ <?= e($p['status']) ?>
                                </span>
                            </div>

                            <div class="mt-auto pt-5">
                                <span class="block w-full text-center text-white font-extrabold text-sm py-2.5 rounded-full shadow transition-transform duration-200 group-hover:scale-105 <?= e($p['tombol']) ?>">
                                    Jelajahi Planet 🚀
                                </span>
                            </div>
                        </a>
                    <?php else: ?>
                        <!-- KARTU TERKUNCI -->
                        <article class="planet-card opacity-90 relative flex flex-col bg-gradient-to-br <?= e($p['gradien']) ?> border-2 <?= e($p['border']) ?> rounded-3xl p-5 shadow-md"
                                 data-level="<?= (int) $p['level'] ?>">

                            <div class="absolute top-3 right-3 flex items-center gap-1 bg-white/90 border-2 border-amber-300 rounded-full pl-1.5 pr-2.5 py-0.5 shadow-sm">
                                <span class="text-xs font-extrabold"><?= ($p['source'] ?? 'legacy') === 'database' ? '⭐ Planet Baru' : '⭐ Planet' ?></span>
                            </div>

                            <div class="planet-icon w-24 h-24 mx-auto mt-6 mb-3"><?= $p['ikon'] ?></div>

                            <h2 class="text-xl font-black text-center leading-tight"><?= e($p['nama']) ?></h2>
                            <p class="mt-1 text-sm font-bold text-center opacity-80"><?= e($p['deskripsi']) ?></p>

                            
                            <div class="mt-3 flex justify-center">
                                <span class="inline-flex items-center gap-1 bg-white/70 text-slate-600 text-xs font-extrabold px-3 py-1 rounded-full">
                                    🔒 <?= e($p['status']) ?>
                                </span>
                            </div>

                            <div class="mt-auto pt-5">
                                <button type="button" class="btn-terkunci w-full bg-gray-200 text-gray-500 font-extrabold text-sm py-2.5 rounded-full cursor-not-allowed">
                                    Buka Level <?= (int) $p['level'] ?> 🔒
                                </button>
                            </div>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
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