<?php
define('BRIGHT_SPACE', true);
session_start();
require __DIR__ . '/config/database.php';
require_login();

$appName = 'BRIGHT SPACE';
$user = current_user();

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

require __DIR__ . '/config/planets.php';
require_once __DIR__ . '/config/student_mission_bridge.php';
require_once __DIR__ . '/config/question_repository.php';
// BS_STAGE6B_STUDENT_READY
require_once __DIR__ . '/config/publication_repository.php';

$planetId = isset($_GET['id']) ? trim($_GET['id']) : '';
$planet   = bs_student_get_planet($planetId, $planets, $user);

if (!$planet) { header('Location: misi.php'); exit; }
if ($planet['status'] !== 'Terbuka') { header('Location: misi.php'); exit; }

$gameModes = bs_student_game_modes($planet, $gameModes);
$pageTitle = $planet['nama'] . ' - ' . $appName;
$arazkaSvg = file_get_contents(__DIR__ . '/components/arazka-svg.php');

$topiUser     = $user['avatar_topi'] ?? null;
$kacamataUser = $user['avatar_kacamata'] ?? null;

$soalPerMode = [];
foreach ($gameModes as $mid => $m) {
    // Misi MySQL belum memiliki bank soal per-misi (akan dibuat di Tahap 4).
    $soalPerMode[$mid] = ($planet['source'] ?? 'legacy') === 'database'
        ? []
        : get_soal($planet['id'], $m['type'], $m['jumlah_soal']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { cosmic: { bg: '#E0F2FE', card: '#EDE9FE', accent: '#FEF3C7', text: '#1E1B4B' } },
            fontFamily: { nunito: ['Nunito', 'ui-rounded', 'system-ui', 'sans-serif'] }
        } } };
    </script>
    <style>
        @keyframes modal-in { from{opacity:0;transform:translateY(14px) scale(0.96);}to{opacity:1;transform:translateY(0) scale(1);} }
        @keyframes planet-float { 0%,100%{transform:translateY(0) rotate(0);}50%{transform:translateY(-6px) rotate(3deg);} }
        @keyframes arazka-lompat { 0%,100%{transform:translateY(0) rotate(0);}30%{transform:translateY(-10px) rotate(-6deg);}60%{transform:translateY(0) rotate(5deg);} }
        @keyframes arazka-miring { 0%,100%{transform:rotate(0);}50%{transform:rotate(-7deg);} }
        @keyframes percik { from{opacity:1;transform:translate(0,0) scale(0.4);}to{opacity:0;transform:translate(var(--dx),var(--dy)) scale(1.1);} }
        @keyframes coin-pop { 0%{transform:scale(1);}50%{transform:scale(1.18);}100%{transform:scale(1);} }
        @keyframes timer-pulse { 0%,100%{transform:scale(1);}50%{transform:scale(1.08);} }
        .modal-panel{animation:modal-in 0.25s ease-out;}
        .planet-hero-icon{animation:planet-float 3s ease-in-out infinite;}
        .timer-warning{animation:timer-pulse 1s ease-in-out infinite;}
        .arazka-wrap .m-senang,.arazka-wrap .m-brow,.arazka-wrap .m-terbuka{display:none;}
        .arazka-wrap[data-mood="selebrasi"] .m-normal,.arazka-wrap[data-mood="selebrasi"] .m-senyum{display:none;}
        .arazka-wrap[data-mood="selebrasi"] .m-senang,.arazka-wrap[data-mood="selebrasi"] .m-terbuka{display:inline;}
        .arazka-wrap[data-mood="semangat"] .m-brow{display:inline;}
        .arazka-wrap[data-mood="selebrasi"] .arazka-svg{animation:arazka-lompat 0.7s ease-in-out 3;}
        .arazka-wrap[data-mood="semangat"] .arazka-svg{animation:arazka-miring 0.8s ease-in-out 2;}
        .percik{position:absolute;left:50%;top:50%;width:14px;height:14px;margin:-7px 0 0 -7px;animation:percik 0.9s ease-out forwards;}
        .coin-pop{animation:coin-pop 0.4s ease-out;}
        @media (prefers-reduced-motion: reduce){*,*::before,*::after{animation:none!important;transition:none!important;}}
    </style>
</head>
<body class="font-nunito bg-cosmic-bg text-cosmic-text min-h-screen flex flex-col antialiased">

    <?php include __DIR__ . '/components/sidebar.php'; ?>
    <?php include __DIR__ . '/components/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-6 w-full flex-grow">
        <a href="misi.php" class="inline-flex items-center gap-2 bg-white border-2 border-violet-200 hover:border-violet-400 font-extrabold text-sm px-4 py-2 rounded-full shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
            Kembali
        </a>

        <section class="mt-4 relative overflow-hidden bg-gradient-to-br <?= e($planet['gradien']) ?> border-4 <?= e($planet['border']) ?> rounded-3xl p-6 sm:p-8 shadow-lg">
            <div class="flex flex-col sm:flex-row items-center gap-5 sm:gap-7">
                <div class="planet-hero-icon w-28 h-28 sm:w-36 sm:h-36 shrink-0"><?= $planet['ikon'] ?></div>
                <div class="text-center sm:text-left flex-1">
                    <span class="inline-block bg-white/70 text-xs font-extrabold uppercase tracking-wider px-3 py-1 rounded-full">🌍 Pilih Misi</span>
                    <h1 class="mt-2 text-3xl sm:text-4xl font-black"><?= e($planet['nama']) ?></h1>
                    <p class="mt-1 text-base font-bold opacity-80"><?= e($planet['deskripsi']) ?></p>
                </div>
            </div>
        </section>

        <section class="mt-8" id="section-kategori">
            <h2 class="text-2xl font-black text-center">Pilih Jenis Misi 🎯</h2>
            <p class="mt-1 text-center text-sm font-bold opacity-80">Ada 2 pilihan misi untukmu!</p>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                <button type="button" class="kategori-card group flex flex-col text-left bg-gradient-to-br from-amber-100 to-yellow-200 border-4 border-amber-300 rounded-3xl p-6 shadow-lg hover:-translate-y-2 hover:shadow-2xl transition-all"
                        data-kategori="wajib">
                    <div class="text-6xl mb-3 group-hover:scale-110 transition-transform">⭐</div>
                    <h3 class="text-2xl font-black">Misi Wajib</h3>
                    <p class="mt-2 text-sm font-bold opacity-80 flex-1">Selesaikan misi utama untuk mengumpulkan Koin Bintang & naik level!</p>
                    <span class="mt-4 inline-block bg-amber-500 text-white font-extrabold text-center py-2.5 rounded-full">Pilih Misi Wajib →</span>
                </button>

                <button type="button" class="kategori-card group flex flex-col text-left bg-gradient-to-br from-sky-100 to-cyan-200 border-4 border-sky-300 rounded-3xl p-6 shadow-lg hover:-translate-y-2 hover:shadow-2xl transition-all"
                        data-kategori="side">
                    <div class="text-6xl mb-3 group-hover:scale-110 transition-transform">💎</div>
                    <h3 class="text-2xl font-black">Side Quest</h3>
                    <p class="mt-2 text-sm font-bold opacity-80 flex-1">Tantangan bonus untuk variasi minigame & koin ekstra!</p>
                    <span class="mt-4 inline-block bg-sky-500 text-white font-extrabold text-center py-2.5 rounded-full">Pilih Side Quest →</span>
                </button>
            </div>
        </section>

        <section class="mt-8 hidden" id="section-game">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-black" id="judul-game">Pilih Game</h2>
                <button type="button" id="btn-ganti-kategori" class="text-sm font-extrabold text-violet-600 hover:underline">← Ganti Kategori</button>
            </div>

            <div id="mode-container" class="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($gameModes as $mid => $mode):
                    $badge = $kategoriBadge[$mode['kategori']] ?? $kategoriBadge['side'];
                    $labelReward = ($mode['reward'] > 0) ? '+' . $mode['reward'] . ' Koin' : 'Tanpa Koin';
                ?>
                    <article class="mode-card group relative flex flex-col bg-gradient-to-br <?= e($mode['warna']['bg']) ?> border-2 <?= e($mode['warna']['border']) ?> rounded-3xl p-5 shadow-md transition-all duration-300 hover:-translate-y-2 hover:shadow-xl"
                             data-mode-id="<?= e($mid) ?>"
                             data-kategori="<?= e($mode['kategori']) ?>"
                             style="display:none;">
                        <div class="absolute top-3 left-3 <?= e($badge['kelas']) ?> text-xs font-extrabold px-3 py-1 rounded-full"><?= e($badge['teks']) ?></div>
                        <div class="absolute top-3 right-3 bg-white/90 border-2 border-amber-300 text-xs font-extrabold px-2 py-1 rounded-full"><?= e($labelReward) ?></div>
                        <div class="text-5xl mb-2 mt-6 group-hover:scale-110 transition-transform"><?= $mode['ikon'] ?></div>
                        <h3 class="text-xl font-black"><?= e($mode['judul']) ?></h3>
                        <p class="mt-2 text-sm font-bold opacity-80 flex-1"><?= e($mode['deskripsi']) ?></p>
                        <?php $canPlay = ($planet['source'] ?? 'legacy') === 'legacy' && in_array($mode['type'], ['kuis', 'story', 'latihan'], true); ?>
                        <?php $dbPlayable = ($planet['source'] ?? 'legacy') === 'database'
                            && !empty($mode['mission_id'])
                            && bmq_ready((int)$mode['mission_id'], (string)$mode['type'], (int)$mode['jumlah_soal']); ?>
                        <?php if ($dbPlayable): ?>
                        <a href="mainkan-misi.php?mission=<?= (int)$mode['mission_id'] ?>" class="block w-full mt-4 text-center text-white font-extrabold text-base py-3 rounded-full shadow <?= e($mode['warna']['tombol']) ?> hover:scale-105">Mulai Misi →</a>
                        <?php else: ?>
                        <button type="button" <?= $canPlay ? '' : 'disabled aria-disabled="true"' ?>
                                class="btn-mode w-full mt-4 text-white font-extrabold text-base py-3 rounded-full shadow <?= e($mode['warna']['tombol']) ?> hover:scale-105 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100">
                            <?= $canPlay ? 'Mulai Game →' : '⏳ Soal belum tersedia' ?>
                        </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/components/modal-quiz.php'; ?>
    <?php include __DIR__ . '/components/modal-shop.php'; ?>

    <div id="toast" class="fixed bottom-20 left-1/2 -translate-x-1/2 z-[80] hidden bg-cosmic-text text-white font-bold text-sm px-4 py-2 rounded-full shadow-lg text-center max-w-[90%]" style="display:none;"></div>

    <footer class="bg-cosmic-card border-t-4 border-cosmic-accent">
        <div class="max-w-6xl mx-auto px-4 pt-4 pb-24 md:pb-4 text-center text-xs font-bold">BRIGHT SPACE EdTech</div>
    </footer>

    <script>
        window.BRIGHT_SPACE = {
            user: {
                id: <?= (int) $user['id'] ?>,
                nama: <?= json_encode($user['nama'], JSON_UNESCAPED_UNICODE) ?>,
                star_coins: <?= (int) $user['star_coins'] ?>,
                topi: <?= json_encode($topiUser, JSON_UNESCAPED_UNICODE) ?>,
                kacamata: <?= json_encode($kacamataUser, JSON_UNESCAPED_UNICODE) ?>
            },
            planet: {
                id: <?= json_encode($planet['id']) ?>,
                nama: <?= json_encode($planet['nama'], JSON_UNESCAPED_UNICODE) ?>,
                ikon: <?= json_encode($planet['ikon'], JSON_UNESCAPED_UNICODE) ?>
            },
            gameModes: <?= json_encode($gameModes, JSON_UNESCAPED_UNICODE) ?>,
            soalPerMode: <?= json_encode($soalPerMode, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
        };
    </script>
    <script src="assets/js/app.js" defer></script>
<script src="assets/js/student-missions.js" defer></script>
<script src="assets/js/student-mission-modal.js" defer></script>
</body>
</html>