<?php
define('BRIGHT_SPACE',true);session_start();
require __DIR__.'/config/database.php';require_once __DIR__.'/config/student_progress.php';require_login();
$s=bs_verified_student();if(!$s){header('Location: login.php');exit;}
$pdo=mission_db();
$stmt=$pdo->prepare('SELECT p.mission_id,m.title,p.attempts_count,p.best_correct,p.best_total,p.first_reward_coins,p.first_reward_xp,p.first_completed_at
FROM bs_student_mission_progress p JOIN bs_missions m ON m.id=p.mission_id WHERE p.student_id=? ORDER BY p.last_completed_at DESC LIMIT 50');
$stmt->execute([(int)$s['id']]);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
function progress_e($v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?>
<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Progres Misi — BRIGHT SPACE</title><link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet"><script src="https://cdn.tailwindcss.com"></script><script>tailwind.config={theme:{extend:{fontFamily:{nunito:['Nunito','sans-serif']}}}};</script>
    </head>
    <body class="antialiased">
        <?php include __DIR__ . '/components/sidebar.php'; ?>

        <div class="font-nunito bg-sky-50 text-indigo-950 min-h-screen">

            <?php include __DIR__ . '/components/header.php'; ?>

            <main class="max-w-4xl mx-auto p-4 sm:p-7">
                <a href="misi.php" class="text-violet-700 font-black">← Kembali ke Peta Misi</a>
                <h1 class="text-3xl font-black mt-6">🏅 Progres Kapten <?=progress_e($s['display_name'])?></h1>
                <div class="grid sm:grid-cols-3 gap-3 mt-5">
                    <div class="bg-white rounded-2xl border-2 border-violet-200 p-5 font-black">⭐ Level <span class="text-2xl"><?= (int)$s['level'] ?></span>
                    </div>
                    <div class="bg-white rounded-2xl border-2 border-violet-200 p-5 font-black">✨ XP <span class="text-2xl"><?= (int)$s['total_xp'] ?></span>
                    </div>
                    <div class="bg-white rounded-2xl border-2 border-amber-200 p-5 font-black">🪙 Koin <span class="text-2xl"><?= (int)$s['star_coins'] ?></span>
                    </div>
                </div>
                <h2 class="text-xl font-black mt-8 mb-3">Misi yang pernah diselesaikan</h2>
                <?php if(!$rows):?><p class="bg-white p-5 rounded-xl font-bold">Belum ada misi selesai. Ayo jelajahi planet!</p>
                    <?php endif?><div class="grid sm:grid-cols-2 gap-3">
                        <?php foreach($rows as $r):?>
                            <article class="bg-white border-2 border-violet-200 rounded-2xl p-4">
                                <h3 class="font-black text-lg"><?=progress_e($r['title'])?></h3>
                                <p class="text-sm font-bold text-slate-600 mt-2">Nilai terbaik: <?= (int)$r['best_correct'] ?>/<?= (int)$r['best_total'] ?> · Percobaan: <?= (int)$r['attempts_count'] ?></p>
                                <p class="text-sm font-extrabold text-emerald-700 mt-2">Reward pertama: +<?= (int)$r['first_reward_coins'] ?> koin · +<?= (int)$r['first_reward_xp'] ?> XP</p>
                            </article>
                            <?php endforeach ?></div>
                            <p class="mt-7 text-sm text-slate-500 font-bold">Reward hanya diberikan pada penyelesaian pertama setiap misi. Bermain ulang memperbarui skor terbaik.</p>
                        </main>
                    </div>
                </body>
                </html>
