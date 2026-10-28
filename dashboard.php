<?php
define('BRIGHT_SPACE', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/config/database.php';
require_login();

$appName = 'BRIGHT SPACE';
$user = current_user();
if (!$user) { header('Location: logout.php'); exit; }

$user_name  = $user['nama']        ?? 'Kapten';
$user_id    = (int) ($user['id']         ?? 0);
$star_coins = (int) ($user['star_coins'] ?? 0);
$user_level = (int) ($user['level']      ?? 1);


$appName = 'BRIGHT SPACE';
$user = current_user();
if (!$user) { header('Location: logout.php'); exit; }

$user_name  = $user['nama']        ?? 'Kapten';
$user_id    = (int) ($user['id']         ?? 0);
$star_coins = (int) ($user['star_coins'] ?? 0);
$user_level = (int) ($user['level']      ?? 1);

// BS_STAGE6A_DASHBOARD: statistik MySQL, tanpa riwayat/leaderboard fiktif.
require_once __DIR__ . '/config/student_progress.php';
if(!bs_verified_student() || !($bsPdo=db())) { http_response_code(503); exit('Akun siswa atau database tidak tersedia.'); }
$total_bintang = (int)($user['total_xp'] ?? 0); // Kartu pertama: XP, bukan koin duplikat.
$star_coins = (int)$user['star_coins'];
$bsCount=$bsPdo->prepare("SELECT COUNT(*) FROM bs_student_mission_progress p
 JOIN bs_missions m ON m.id=p.mission_id JOIN bs_planets pl ON pl.id=m.planet_id
 WHERE p.student_id=? AND m.publication_status='published' AND pl.publication_status='published'");
$bsCount->execute([$user_id]);$misi_selesai=(int)$bsCount->fetchColumn();
$total_misi=(int)$bsPdo->query("SELECT COUNT(*) FROM bs_missions m JOIN bs_planets p ON p.id=m.planet_id
 WHERE m.publication_status='published' AND p.publication_status='published'")->fetchColumn();
$daily_progress=$total_misi>0?(int)round(100*$misi_selesai/$total_misi):0;
$daily_progress=min(100,$daily_progress);
$bsDates=$bsPdo->prepare('SELECT DISTINCT DATE(completed_at) AS day FROM bs_mission_attempts WHERE student_id=? ORDER BY day DESC LIMIT 60');
$bsDates->execute([$user_id]);
$daySet=array_flip(array_column($bsDates->fetchAll(PDO::FETCH_ASSOC),'day'));
$streak_hari=0;
// Menghitung hari beruntun berdasarkan tanggal server, tidak lagi angka dummy.
$today=new DateTimeImmutable('today');
if(!isset($daySet[$today->format('Y-m-d')]))$today=$today->modify('-1 day');
while(isset($daySet[$today->format('Y-m-d')]) && $streak_hari<60){$streak_hari++;$today=$today->modify('-1 day');}
$bsHistory=$bsPdo->prepare('SELECT pl.id AS planet_id,pl.name AS planet_name,m.title AS mission_title,m.mode_type,
 a.correct_count AS soal_benar,a.question_count AS soal_total,
 ROUND(100 * a.correct_count / NULLIF(a.question_count,0)) AS akurasi,
 a.awarded_coins AS reward,a.completed_at
 FROM bs_mission_attempts a JOIN bs_missions m ON m.id=a.mission_id
 JOIN bs_planets pl ON pl.id=m.planet_id WHERE a.student_id=? ORDER BY a.completed_at DESC,a.id DESC LIMIT 3');
$bsHistory->execute([$user_id]);$riwayat=$bsHistory->fetchAll(PDO::FETCH_ASSOC);
$topRows=$bsPdo->query('SELECT id,display_name,total_xp FROM bs_students WHERE is_active=1 ORDER BY total_xp DESC,id ASC LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
$leaderboard=[];
foreach($topRows as $i=>$row){$leaderboard[]=['nama'=>$row['display_name'],'bintang'=>(int)$row['total_xp'],
 'rank'=>$i+1,'medal'=>['🥇','🥈','🥉'][$i],'is_me'=>(int)$row['id']===$user_id];}
function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
function namaPlanet($id) {
    $map = ['matematika' => 'Planet Matematika', 'sains' => 'Planet Sains', 'bahasa' => 'Planet Bahasa', 'seni-coding' => 'Planet Seni & Coding'];
    return $map[$id] ?? ucfirst($id);
}
function namaMode($id) {
    $map = ['kuis' => 'Kuis Kilat', 'story' => 'Misi Cerita', 'puzzle' => 'Teka-Teki', 'matching' => 'Cocokkan Rumus', 'latihan' => 'Latihan Bebas'];
    return $map[$id] ?? ucfirst($id);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= e($appName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { cosmic: { bg: '#E0F2FE', card: '#EDE9FE', accent: '#FEF3C7', text: '#1E1B4B' } },
            fontFamily: { nunito: ['Nunito', 'ui-rounded', 'system-ui', 'sans-serif'] }
        } } };
    </script>
    <style>
        @keyframes bounce-soft { 0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);} }
        @keyframes pulse-soft { 0%,100%{transform:scale(1);}50%{transform:scale(1.06);} }
        .arazka-bounce { animation: bounce-soft 2.4s ease-in-out infinite; }
        .arazka-pulse  { animation: pulse-soft 2s ease-in-out infinite; }
    </style>
</head>
<body class="font-nunito text-slate-800 min-h-screen antialiased" style="background-color:#F0F7FF;">

    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen flex flex-col">
        <?php include __DIR__ . '/components/header.php'; ?>

        <main class="max-w-6xl mx-auto px-4 py-6 w-full flex-1">

            <!-- SECTION 1: HERO BANNER -->
            <section class="mb-6">
                <div class="relative overflow-hidden bg-gradient-to-r from-violet-600 to-indigo-600 rounded-3xl shadow-xl p-6 sm:p-8 text-white">
                    <div class="absolute top-4 right-4 text-5xl opacity-20 select-none">⭐</div>
                    <div class="absolute bottom-2 right-20 text-3xl opacity-15 select-none">✨</div>

                    <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div class="flex-1">
                            <span class="inline-block bg-white/20 text-xs font-extrabold uppercase tracking-wider px-3 py-1.5 rounded-full">
                                🎯 Misi Hari Ini
                            </span>
                            <h1 class="mt-3 text-2xl sm:text-3xl font-black leading-tight">
                                Lanjutkan Misi dan Kumpulkan XP untuk Naik Level!
                            </h1>
                            <div class="mt-4 max-w-md">
                                <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                                    <span><?= (int) $misi_selesai ?> dari <?= (int) $total_misi ?> Misi Selesai</span>
                                    <span><?= (int) $daily_progress ?>%</span>
                                </div>
                                <div class="w-full bg-white/30 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-amber-400 h-full rounded-full transition-all duration-700" style="width: <?= (int) $daily_progress ?>%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <a href="misi.php"
                               class="inline-flex items-center gap-2 bg-amber-400 hover:bg-amber-300 text-slate-900 font-extrabold text-base px-6 py-3 rounded-2xl shadow-lg transition-transform duration-200 hover:scale-105 active:scale-95">
                                Lanjutkan Misi <span class="text-xl">➔</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 2: STATISTIK 4 CARD -->
            <section class="mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border-2 border-amber-300 p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 shrink-0 flex items-center justify-center bg-amber-100 border-2 border-amber-300 rounded-2xl text-2xl">⭐</div>
                            <div class="min-w-0">
                                <p class="text-2xl font-black leading-none"><?= (int) $total_bintang ?></p>
                                <p class="mt-1 text-xs font-bold text-slate-500">Total XP</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border-2 border-yellow-300 p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 shrink-0 flex items-center justify-center bg-yellow-100 border-2 border-yellow-300 rounded-2xl text-2xl">🪙</div>
                            <div class="min-w-0">
                                <p class="text-2xl font-black leading-none" id="stat-koin"><?= (int) $star_coins ?></p>
                                <p class="mt-1 text-xs font-bold text-slate-500">Koin Bintang</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border-2 border-emerald-300 p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 shrink-0 flex items-center justify-center bg-emerald-100 border-2 border-emerald-300 rounded-2xl text-2xl">🏆</div>
                            <div class="min-w-0">
                                <p class="text-2xl font-black leading-none"><?= (int) $misi_selesai ?> / <?= (int) $total_misi ?></p>
                                <p class="mt-1 text-xs font-bold text-slate-500">Misi Selesai</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border-2 border-rose-300 p-4 shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 shrink-0 flex items-center justify-center bg-rose-100 border-2 border-rose-300 rounded-2xl text-2xl">🔥</div>
                            <div class="min-w-0">
                                <p class="text-2xl font-black leading-none"><?= (int) $streak_hari ?> Hari</p>
                                <p class="mt-1 text-xs font-bold text-slate-500">Streak Belajar</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 3: ARAZKA MENTOR -->
            <section class="mb-6">
                <div class="flex flex-col sm:flex-row items-start gap-5 bg-white border-2 border-indigo-100 rounded-3xl shadow-md p-6">
                    <div class="shrink-0 mx-auto sm:mx-0">
                        <div class="arazka-bounce w-24 h-24 sm:w-28 sm:h-28 flex items-center justify-center bg-gradient-to-br from-indigo-100 to-violet-100 rounded-full border-4 border-indigo-200 shadow-inner">
                            <svg viewBox="0 0 100 100" class="w-20 h-20 sm:w-24 sm:h-24 arazka-pulse" aria-hidden="true">
                                <rect x="31" y="74" width="38" height="20" rx="10" fill="#A78BFA" stroke="#1E1B4B" stroke-width="2.5"/>
                                <polygon points="50,78 52,83 57,83.5 53.5,87 54.5,92 50,89.5 45.5,92 46.5,87 43,83.5 48,83" fill="#FCD34D"/>
                                <line x1="50" y1="22" x2="50" y2="13" stroke="#1E1B4B" stroke-width="3" stroke-linecap="round"/>
                                <polygon points="50,1 52,5.5 57,6 53.3,9.2 54.4,14 50,11.5 45.6,14 46.7,9.2 43,6 48,5.5" fill="#FCD34D" stroke="#B45309" stroke-width="1"/>
                                <circle cx="50" cy="48" r="29" fill="#FFFFFF" stroke="#1E1B4B" stroke-width="2.5"/>
                                <circle cx="21" cy="48" r="5" fill="#A78BFA" stroke="#1E1B4B" stroke-width="2"/>
                                <circle cx="79" cy="48" r="5" fill="#A78BFA" stroke="#1E1B4B" stroke-width="2"/>
                                <rect x="22" y="34" width="56" height="30" rx="15" fill="#312E81" stroke="#1E1B4B" stroke-width="2"/>
                                <circle cx="40" cy="47" r="4.2" fill="#7DD3FC"/>
                                <circle cx="60" cy="47" r="4.2" fill="#7DD3FC"/>
                                <path d="M44 56q6 5 12 0" fill="none" stroke="#7DD3FC" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1 w-full">
                        <p class="text-xs font-extrabold uppercase tracking-wider text-indigo-500">Arazka • Mentor AI</p>
                        <div class="mt-2 bg-indigo-50 p-4 rounded-2xl border border-indigo-100">
                            <p class="text-sm sm:text-base font-bold leading-snug text-slate-700">
                                Halo, Kapten <?= e($user_name); ?>! 🚀 Hari ini kamu makin hebat!
                                Sedikit lagi kamu bisa membeli
                                <span class="text-amber-600 font-black">Kostum Roket Emas</span>
                                di Toko Bintang!
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 4: 2 KOLOM -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Kiri: Riwayat -->
                <div>
                    <h2 class="text-lg sm:text-xl font-black mb-3">📜 Riwayat Petualangan</h2>
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
                        <ul class="divide-y divide-slate-100">
                            <?php foreach ($riwayat as $r): ?>
                                <li class="flex items-center justify-between gap-3 py-3 first:pt-1 last:pb-1">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 shrink-0 flex items-center justify-center bg-indigo-50 border-2 border-indigo-200 rounded-xl text-lg">
                                            <?php
                                                if (($r['mode_type'] ?? '') === 'kuis') echo '⚡';
                                                elseif (($r['mode_type'] ?? '') === 'story') echo '📖';
                                                elseif (($r['mode_type'] ?? '') === 'latihan') echo '🎓';
                                                else echo '🎮';
                                            ?>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-extrabold truncate">
                                                <?= e($r['planet_name'] ?? '') ?> — <?= e($r['mission_title'] ?? '') ?>
                                            </p>
                                            <p class="text-xs font-bold text-slate-500">
                                                <?= (int) ($r['soal_benar'] ?? 0) ?>/<?= (int) ($r['soal_total'] ?? 0) ?> benar • Akurasi <?= (int) ($r['akurasi'] ?? 0) ?>%
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-xs font-extrabold text-emerald-600">Selesai ✅</p>
                                        <?php if ((int) ($r['reward'] ?? 0) > 0): ?>
                                            <p class="text-xs font-black text-amber-600">+<?= (int) $r['reward'] ?> Koin</p>
                                        <?php else: ?>
                                            <p class="text-xs font-bold text-slate-400">Tanpa Koin</p>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Kanan: Leaderboard -->
                <div>
                    <h2 class="text-lg sm:text-xl font-black mb-3">🏆 Top Kapten (Total XP)</h2>
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
                        <ul class="space-y-2">
                            <?php foreach ($leaderboard as $lb): ?>
                                <li class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl <?= !empty($lb['is_me']) ? 'bg-amber-50 border-2 border-amber-200' : 'hover:bg-slate-50' ?>">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="text-2xl shrink-0"><?= $lb['medal'] ?></span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-extrabold truncate">
                                                <?= e($lb['nama']) ?>
                                                <?php if (!empty($lb['is_me'])): ?>
                                                    <span class="ml-1 text-xs bg-amber-300 text-amber-900 font-black px-2 py-0.5 rounded-full">Kamu</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-xs font-bold text-slate-500">Peringkat #<?= (int) $lb['rank'] ?></p>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-base font-black text-amber-600"><?= (int) $lb['bintang'] ?> ✨ XP</p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </section>

        </main>

        <footer class="bg-white border-t-2 border-slate-100 mt-8">
            <div class="max-w-6xl mx-auto px-4 py-4 text-center text-xs font-bold text-slate-400">
                BRIGHT SPACE EdTech · Optimized for Low-Spec School Hardware
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