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

// BS_STAGE6A_LEADERBOARD — berdasarkan total XP siswa di MySQL.
// Koin dapat dibelanjakan, jadi peringkat memakai XP kumulatif (bukan saldo koin).
require_once __DIR__.'/config/student_progress.php';
if(!bs_verified_student() || !($bsPdo=db())){http_response_code(503);exit('Akun siswa atau database tidak tersedia.');}
$rows=$bsPdo->query('SELECT id,display_name,total_xp,level FROM bs_students WHERE is_active=1 ORDER BY total_xp DESC,id ASC LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
$leaderboard=[];
foreach($rows as $row){
    $leaderboard[]=[
        'id'=>(int)$row['id'],'nama'=>$row['display_name'],'kelas'=>'Lv. '.(int)$row['level'],
        'bintang'=>(int)$row['total_xp'],'avatar'=>'🧑‍🚀','is_me'=>(int)$row['id']===$user_id
    ];
}
$top3=array_slice($leaderboard,0,3);
$rest=array_slice($leaderboard,3);
function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Skor - <?= e($appName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { cosmic: { bg: '#E0F2FE', card: '#EDE9FE', accent: '#FEF3C7', text: '#1E1B4B' } },
            fontFamily: { nunito: ['Nunito', 'ui-rounded', 'system-ui', 'sans-serif'] }
        } } };
    </script>
    <style>
        @keyframes crown-bounce { 0%,100%{transform:translateY(0) rotate(-3deg);}50%{transform:translateY(-6px) rotate(3deg);} }
        @keyframes frame-glow { 0%,100%{box-shadow:0 0 0 0 rgba(251,191,36,0.5);}50%{box-shadow:0 0 0 12px rgba(251,191,36,0);} }
        @keyframes float-up { 0%,100%{transform:translateY(0);}50%{transform:translateY(-4px);} }
        .crown-anim { animation: crown-bounce 2s ease-in-out infinite; }
        .podium-1 { animation: frame-glow 2s ease-in-out infinite; }
        .float-up { animation: float-up 3s ease-in-out infinite; }
    </style>
</head>
<body class="font-nunito text-slate-800 min-h-screen antialiased" style="background-color:#F0F7FF;">

    <?php include __DIR__ . '/components/sidebar.php'; ?>

    <div class="lg:ml-72 min-h-screen flex flex-col">
        <?php include __DIR__ . '/components/header.php'; ?>

        <main class="max-w-5xl mx-auto px-4 py-6 w-full flex-1">

            <!-- HEADER -->
            <section class="mb-6 text-center">
                <span class="inline-block bg-violet-100 border-2 border-violet-200 text-violet-700 text-xs font-extrabold uppercase tracking-wider px-3 py-1.5 rounded-full">
                    🏫 Komunitas BRIGHT SPACE
                </span>
                <h1 class="mt-3 text-3xl sm:text-4xl font-black">Papan Skor Kapten 🏆</h1>
                <p class="mt-2 text-base sm:text-lg font-bold opacity-80">
                    Peringkat berdasarkan total XP sepanjang waktu, bukan saldo koin.
                </p>
            </section>

            <!-- PODIUM TOP 3 -->
            <section class="mb-8">
                <div class="grid grid-cols-3 gap-2 sm:gap-4 items-end max-w-3xl mx-auto">

                    <!-- JUARA 2 (kiri) -->
                    <?php if (isset($top3[1])): $u = $top3[1]; ?>
                        <div class="flex flex-col items-center">
                            <div class="relative">
                                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-br from-slate-200 to-slate-400 border-4 border-slate-300 flex items-center justify-center text-3xl sm:text-4xl shadow-lg">
                                    <?= $u['avatar'] ?>
                                </div>
                                <span class="absolute -top-3 -right-2 text-2xl sm:text-3xl">🥈</span>
                            </div>
                            <p class="mt-2 text-xs sm:text-sm font-black truncate w-full text-center"><?= e($u['nama']) ?></p>
                            <span class="mt-0.5 inline-block bg-violet-100 text-violet-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                <?= e($u['kelas']) ?>
                            </span>
                            <p class="mt-1 text-xs sm:text-sm font-black text-amber-600"><?= (int) $u['bintang'] ?> XP</p>
                            <div class="mt-2 w-full bg-slate-300 rounded-t-2xl h-20 sm:h-24 flex items-start justify-center pt-2">
                                <span class="text-2xl font-black text-slate-700">2</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- JUARA 1 (tengah) -->
                    <?php if (isset($top3[0])): $u = $top3[0]; ?>
                        <div class="flex flex-col items-center">
                            <div class="relative float-up">
                                <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-4xl sm:text-5xl crown-anim z-10">👑</span>
                                <div class="podium-1 w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gradient-to-br from-amber-200 to-amber-400 border-4 border-amber-400 flex items-center justify-center text-4xl sm:text-5xl shadow-2xl">
                                    <?= $u['avatar'] ?>
                                </div>
                                <span class="absolute -bottom-1 -right-2 text-2xl sm:text-3xl">🥇</span>
                            </div>
                            <p class="mt-2 text-xs sm:text-sm font-black truncate w-full text-center"><?= e($u['nama']) ?></p>
                            <span class="mt-0.5 inline-block bg-amber-100 text-amber-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                <?= e($u['kelas']) ?>
                            </span>
                            <p class="mt-1 text-sm sm:text-base font-black text-amber-600"><?= (int) $u['bintang'] ?> XP</p>
                            <div class="mt-2 w-full bg-amber-400 rounded-t-2xl h-28 sm:h-32 flex items-start justify-center pt-2">
                                <span class="text-3xl font-black text-white">1</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- JUARA 3 (kanan) -->
                    <?php if (isset($top3[2])): $u = $top3[2]; ?>
                        <div class="flex flex-col items-center">
                            <div class="relative">
                                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-br from-orange-200 to-amber-600 border-4 border-amber-500 flex items-center justify-center text-3xl sm:text-4xl shadow-lg">
                                    <?= $u['avatar'] ?>
                                </div>
                                <span class="absolute -top-3 -right-2 text-2xl sm:text-3xl">🥉</span>
                            </div>
                            <p class="mt-2 text-xs sm:text-sm font-black truncate w-full text-center"><?= e($u['nama']) ?></p>
                            <span class="mt-0.5 inline-block bg-orange-100 text-orange-700 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
                                <?= e($u['kelas']) ?>
                            </span>
                            <p class="mt-1 text-xs sm:text-sm font-black text-amber-600"><?= (int) $u['bintang'] ?> XP</p>
                            <div class="mt-2 w-full bg-amber-600 rounded-t-2xl h-16 sm:h-20 flex items-start justify-center pt-2">
                                <span class="text-2xl font-black text-white">3</span>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </section>

            <!-- LIST PERINGKAT 4+ -->
            <section>
                <h2 class="text-lg sm:text-xl font-black mb-3">📋 Peringkat Lainnya</h2>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <ul class="divide-y divide-slate-100">
                        <?php foreach ($rest as $i => $u): $rank = $i + 4; $isMe = !empty($u['is_me']); ?>
                            <li class="flex items-center justify-between gap-3 px-4 py-3 <?= $isMe ? 'bg-amber-50 border-l-4 border-amber-400' : 'hover:bg-slate-50' ?>">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-8 text-center text-sm font-black text-slate-500">#<?= $rank ?></span>
                                    <div class="w-10 h-10 shrink-0 rounded-full bg-gradient-to-br from-violet-300 to-indigo-400 flex items-center justify-center text-xl shadow">
                                        <?= $u['avatar'] ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-extrabold truncate">
                                            <?= e($u['nama']) ?>
                                            <?php if ($isMe): ?>
                                                <span class="ml-1 text-[10px] bg-amber-300 text-amber-900 font-black px-2 py-0.5 rounded-full">Kamu</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs font-bold text-slate-500"><?= e($u['kelas']) ?></p>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-base font-black text-amber-600"><?= (int) $u['bintang'] ?> XP</p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <p class="mt-4 text-center text-xs font-bold text-slate-400">
                    Data peringkat berdasarkan total XP sepanjang waktu. Koin tidak menentukan peringkat.
                </p>
            </section>

        </main>

        <footer class="bg-white border-t-2 border-slate-100 mt-8">
            <div class="max-w-6xl mx-auto px-4 py-4 text-center text-xs font-bold text-slate-400">
                BRIGHT SPACE EdTech · Komunitas BRIGHT SPACE
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